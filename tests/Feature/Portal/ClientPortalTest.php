<?php
namespace Tests\Feature\Portal;
use App\Models\Client;
use App\Mail\PortalInvitationMail;
use App\Models\AdminNotice;
use App\Models\ClientChangeRequest;
use App\Models\PortalInvitation;
use App\Models\Invoice;
use App\Models\PortalAccount;
use App\Models\PaymentPlan;
use App\Models\PaymentPlanClient;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
class ClientPortalTest extends TestCase
{
    use RefreshDatabase;
    public function test_admin_invites_client_and_client_sets_their_password(): void
    {
        Mail::fake();
        [$admin,$client,$plan]=$this->records('ONE');
        $this->actingAs($admin)->post(route('admin.clients.portal-invitations.store',$client))->assertSessionHas('success');
        $invitation=PortalInvitation::query()->sole();
        $this->assertTrue($invitation->expires_at->isAfter(now()->addHours(47)));
        $this->assertNotNull($invitation->encrypted_token);
        $this->actingAs($admin)->get(route('admin.clients.show',$client))
            ->assertOk()
            ->assertSee('Copy invitation link');
        $link=null;
        Mail::assertSent(PortalInvitationMail::class,function($mail)use(&$link){preg_match('/href="([^"]+)"/',$mail->renderedBody,$matches);$link=html_entity_decode($matches[1]??'');return $mail->hasTo('one@example.com')&&filled($link);});
        $token=basename(parse_url($link,PHP_URL_PATH));
        $this->get(route('portal.invitation.show',$token))->assertOk()->assertSee('Create your password');
        $this->post(route('portal.invitation.accept',$token),['password'=>'Secure-password-123!','password_confirmation'=>'Secure-password-123!'])->assertRedirect(route('portal.login'));
        $account=PortalAccount::query()->sole();
        $this->assertTrue($account->enabled);
        $notice=AdminNotice::query()->where('type','portal_invitation_accepted')->sole();
        $this->assertSame('Portal ONE - one@example.com activated portal access.',$notice->message);
        $this->get(route('portal.invitation.show',$token))->assertStatus(410);
        $this->post(route('portal.login.store'),['email'=>$client->email,'password'=>'Secure-password-123!'])->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticatedAs($account,'client');
        $plan->update(['status' => 'paused']);
        $this->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee($plan->plan_number)
            ->assertSee('Recent invoices')
            ->assertSee('Recent payments')
            ->assertDontSee('Payment schedule paused')
            ->assertDontSee('Scheduled invoices and automated reminders');
    }
    public function test_authentication_redirects_remain_within_the_correct_portal(): void
    {
        [$admin,$client]=$this->records('REDIRECT');
        \App\Models\AppSetting::putMany(['company_name'=>'Example Plan Company']);
        $this->get(route('portal.login'))->assertOk()->assertSee('Accounts are created by an authorized Example Plan Company administrator. Please contact us for information.');
        $account=PortalAccount::query()->create([
            'client_id'=>$client->id,
            'email'=>$client->email,
            'password'=>'password',
            'enabled'=>true,
        ]);

        $this->get(route('portal.dashboard'))->assertRedirect(route('portal.login'));
        $this->post(route('portal.login.store'),['email'=>$account->email,'password'=>'password'])
            ->assertRedirect(route('portal.dashboard'));
        $this->get(route('portal.login'))->assertRedirect(route('portal.dashboard'));
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->assertGuest('web');

        $this->actingAs($admin)->get(route('admin.login'))->assertRedirect(route('admin.dashboard'));
    }
    public function test_portal_documents_are_limited_to_active_memberships(): void
    {
        [$admin,$client,$ownPlan]=$this->records('OWN');
        [,,$otherPlan]=$this->records('OTHER',$admin);
        $account=PortalAccount::query()->create(['client_id'=>$client->id,'email'=>$client->email,'password'=>'password','enabled'=>true]);
        \App\Models\AppSetting::putMany(['invoice_view_admin_notice_enabled'=>'1']);
        $own=Invoice::query()->create(['payment_plan_id'=>$ownPlan->id,'invoice_number'=>'INV-OWN','issue_date'=>'2026-08-01','due_date'=>'2026-08-06','status'=>'issued','issued_at'=>now(),'created_by_user_id'=>$admin->id]);
        $other=Invoice::query()->create(['payment_plan_id'=>$otherPlan->id,'invoice_number'=>'INV-OTHER','issue_date'=>'2026-08-01','due_date'=>'2026-08-06','status'=>'issued','issued_at'=>now(),'created_by_user_id'=>$admin->id]);
        $this->actingAs($account,'client')->get(route('portal.invoices.show',$own))->assertOk()->assertSee('INV-OWN')->assertSeeText('Payment due upon receipt')->assertSeeText('Late after Aug 6, 2026');
        $firstViewedAt=$own->fresh()->first_viewed_at;
        $this->assertNotNull($firstViewedAt);
        $this->assertSame($own->id,AdminNotice::query()->where('type','invoice_first_viewed')->sole()->invoice_id);
        $this->get(route('portal.invoices.show',$own))->assertOk();
        $this->assertTrue($own->fresh()->first_viewed_at->equalTo($firstViewedAt));
        $this->assertSame(1,AdminNotice::query()->where('type','invoice_first_viewed')->count());
        $this->get(route('portal.invoices.index'))->assertOk()->assertSee('Late after')->assertDontSee('>Due<', false);
        $this->get(route('portal.invoices.download',$own))->assertOk()->assertHeader('content-type','application/pdf');
        $own->update(['status'=>'voided']);
        $this->get(route('portal.invoices.index'))->assertOk()->assertDontSee('INV-OWN');
        $this->get(route('portal.invoices.show',$own))->assertNotFound();
        $this->get(route('portal.invoices.download',$own))->assertNotFound();
        $this->get(route('portal.invoices.show',$other))->assertNotFound();
        $this->get(route('portal.invoices.download',$other))->assertNotFound();
        PaymentPlanClient::query()->where('client_id',$client->id)->where('payment_plan_id',$ownPlan->id)->update(['effective_to'=>'2026-08-01']);
        $this->get(route('portal.invoices.show',$own))->assertNotFound();
    }
    public function test_contact_changes_create_notice_and_require_admin_approval(): void
    {
        [$admin,$client]=$this->records('CONTACT');
        $account=PortalAccount::query()->create(['client_id'=>$client->id,'email'=>$client->email,'password'=>'password','enabled'=>true]);
        $this->actingAs($account,'client')->put(route('portal.account.update'),[
            'email'=>'updated@example.com','primary_phone'=>'555-0100','secondary_phone'=>'',
            'address_line_1'=>'10 New Street','address_line_2'=>'','city'=>'Phoenix','state_region'=>'AZ','postal_code'=>'85001','country_code'=>'us',
        ])->assertRedirect(route('portal.account.show'));
        $change=ClientChangeRequest::query()->sole();
        $this->assertSame('pending',$change->status);
        $notice=AdminNotice::query()->where('client_change_request_id',$change->id)->sole();
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Client contact update requested');
        $this->post(route('admin.client-change-requests.apply',$change))->assertRedirect(route('admin.clients.show',$client));
        $this->assertSame('updated@example.com',$client->fresh()->email);
        $this->assertSame('updated@example.com',$account->fresh()->email);
        $this->assertNotNull($notice->fresh()->dismissed_at);
    }

    public function test_account_prompts_for_missing_contact_information_until_update_is_pending(): void
    {
        [, $client] = $this->records('MISSINGCONTACT');
        $account=PortalAccount::query()->create(['client_id'=>$client->id,'email'=>$client->email,'password'=>'password','enabled'=>true]);
        $this->actingAs($account,'client')->get(route('portal.account.show'))->assertOk()->assertSee('Please complete your contact information.')->assertSee('your phone number and mailing address');
        $this->get(route('portal.dashboard'))->assertOk()->assertSee('Please complete your contact information.')->assertSee(route('portal.account.edit'));
        $this->put(route('portal.account.update'),['email'=>$client->email,'primary_phone'=>'555-0100','secondary_phone'=>'','address_line_1'=>'10 Main Street','address_line_2'=>'','city'=>'Phoenix','state_region'=>'AZ','postal_code'=>'85001','country_code'=>'US'])->assertRedirect(route('portal.account.show'));
        $this->get(route('portal.account.show'))->assertOk()->assertSee('Contact update pending.')->assertDontSee('Please complete your contact information.');
        $this->get(route('portal.dashboard'))->assertOk()->assertSee('Contact update pending.')->assertDontSee('Please complete your contact information.');
    }

    public function test_account_displays_current_payoff_without_changing_contract_balance(): void
    {
        [$admin, $client, $plan] = $this->records('PAYOFF');
        $plan->update(['monthly_service_fee' => 2_500, 'status' => 'draft']);
        app(\App\Services\ContractOpeningService::class)->open($plan, $admin, 100_000, 0, 0, '2026-08-01');
        app(\App\Services\OpeningPrincipalCreditService::class)->post($plan, $admin, 12_345, '2026-08-01');
        $plan->update(['status' => 'active']);
        $account = PortalAccount::query()->create(['client_id'=>$client->id,'email'=>$client->email,'password'=>'password','enabled'=>true]);
        $contractBalance = app(\App\Services\FinancialBalanceService::class)->contractBalance($plan);

        $this->actingAs($account, 'client')->get(route('portal.account.show'))
            ->assertOk()
            ->assertSee('Principal Paid')
            ->assertDontSee('Paid to date')
            ->assertSee(\App\Support\Money::format(12_345))
            ->assertSee('Current Payoff')
            ->assertSee(\App\Support\Money::format($contractBalance + 2_500));

        $this->assertSame($contractBalance, app(\App\Services\FinancialBalanceService::class)->contractBalance($plan));
    }

    public function test_administrator_portal_access_is_read_only_audited_and_does_not_change_last_login(): void
    {
        [$admin, $client] = $this->records('ADMINVIEW');
        $lastLogin = now()->subDays(3)->startOfSecond();
        $account = PortalAccount::query()->create([
            'client_id' => $client->id,
            'email' => $client->email,
            'password' => 'password',
            'enabled' => true,
            'last_login_at' => $lastLogin,
        ]);

        $this->actingAs($admin)->post(route('admin.portal-access.store', $client))
            ->assertRedirect(route('portal.dashboard'))
            ->assertSessionHas('portal_impersonation.client_id', $client->id);
        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertAuthenticatedAs($account, 'client');
        $this->get(route('portal.dashboard'))
            ->assertOk()
            ->assertSee('Administrator view:')
            ->assertSee('read-only mode')
            ->assertSee('Return to administration');
        $this->put(route('portal.account.update'), [
            'email' => 'forbidden@example.com',
            'country_code' => 'US',
        ])->assertForbidden();
        $this->assertTrue($account->fresh()->last_login_at->equalTo($lastLogin));
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'actor_client_id' => $client->id,
            'event' => 'client_portal.admin_access_started',
        ]);

        $this->delete(route('admin.portal-access.destroy'))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionMissing('portal_impersonation');
        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertGuest('client');
        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $admin->id,
            'actor_client_id' => $client->id,
            'event' => 'client_portal.admin_access_ended',
        ]);
    }
    public function test_client_authentication_cannot_access_administrator_routes(): void
    {
        [, $client] = $this->records('CLIENTONLY');
        $account = PortalAccount::query()->create([
            'client_id' => $client->id,
            'email' => $client->email,
            'password' => 'password',
            'enabled' => true,
        ]);

        $this->actingAs($account, 'client')
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));

        $this->post(route('admin.portal-access.store', $client))
            ->assertRedirect(route('admin.login'));

        $this->assertDatabaseMissing('audit_logs', [
            'actor_client_id' => $client->id,
            'event' => 'client_portal.admin_access_started',
        ]);
    }

    public function test_client_can_request_a_password_reset_without_account_disclosure(): void
    {
        Notification::fake();
        [, $client]=$this->records('RESET');
        $account=PortalAccount::query()->create(['client_id'=>$client->id,'email'=>$client->email,'password'=>'password','enabled'=>true]);
        $this->post(route('portal.password.email'),['email'=>$account->email])->assertSessionHas('status');
        Notification::assertSentTo($account,ResetPassword::class);
        $this->post(route('portal.password.email'),['email'=>'missing@example.com'])->assertSessionHas('status');
    }
    private function records(string $suffix, ?User $admin=null): array
    {
        $admin ??= User::factory()->create();
        $client=Client::query()->create(['client_type'=>'individual','first_name'=>'Portal','last_name'=>$suffix,'email'=>strtolower($suffix).'@example.com','country_code'=>'US','created_by_user_id'=>$admin->id,'updated_by_user_id'=>$admin->id]);
        $plan=PaymentPlan::query()->create(['plan_number'=>'LP-'.$suffix,'title'=>'Portal plan '.$suffix,'purchase_price'=>100000,'documentation_fee_standard'=>0,'documentation_fee_waived'=>0,'original_purchase_balance'=>100000,'customary_monthly_payment'=>10000,'monthly_service_fee'=>0,'monthly_due_day'=>1,'first_due_date'=>'2026-08-06','plan_start_date'=>'2026-08-01','status'=>'active','activated_at'=>now(),'created_by_user_id'=>$admin->id,'updated_by_user_id'=>$admin->id]);
        PaymentPlanClient::query()->create(['payment_plan_id'=>$plan->id,'client_id'=>$client->id,'role'=>'primary','responsibility'=>'joint','receives_invoices'=>true,'effective_from'=>'2026-08-01','contact_risk_acknowledged_at'=>now(),'contact_risk_acknowledgment_method'=>'admin_contract_acceptance','created_by_user_id'=>$admin->id]);
        return [$admin,$client,$plan];
    }
}
