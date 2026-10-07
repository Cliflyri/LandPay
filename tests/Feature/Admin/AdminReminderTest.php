<?php
namespace Tests\Feature\Admin;
use App\Mail\AdminNoticeMail;
use App\Models\{AdminReminder,AdminReminderOccurrence,AppSetting,User};
use App\Services\AdminReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
class AdminReminderTest extends TestCase{
 use RefreshDatabase;
 protected function tearDown():void{Carbon::setTestNow();parent::tearDown();}
 public function test_admin_can_create_monthly_reminder_from_admin_actions():void{
  $admin=User::factory()->create();
  $this->actingAs($admin)->post(route('admin.reminders.store'),[
   'title'=>'Download monthly contracts report','message'=>'Download and archive the report.','day_of_month'=>31,
   'display_time'=>'08:00','destination'=>'contracts_report','send_email'=>'1','active'=>'1',
  ])->assertRedirect(route('admin.reminders.index'));
  $this->assertDatabaseHas('admin_reminders',['title'=>'Download monthly contracts report','day_of_month'=>31,'send_email'=>1,'active'=>1]);
  $this->get(route('admin.actions.index'))->assertOk()->assertSee('Admin Reminders');
 }
 public function test_due_occurrence_uses_last_day_is_idempotent_and_requires_both_email_switches():void{
  Mail::fake();Carbon::setTestNow('2026-02-28 08:05:00');
  $admin=User::factory()->create();AppSetting::putMany(['admin_notice_email_address'=>'admin@example.com','admin_notice_email_scheduled_reminders'=>'0']);
  $reminder=AdminReminder::query()->create(['title'=>'Download contracts','day_of_month'=>31,'display_time'=>'08:00','destination'=>'contracts_report','send_email'=>true,'active'=>true]);
  $service=app(AdminReminderService::class);
  $this->assertSame(1,$service->processDue());$this->assertSame(0,$service->processDue());
  $this->assertDatabaseCount('admin_reminder_occurrences',1);Mail::assertNothingSent();
  $reminder->update(['title'=>'Next reminder']);AppSetting::putMany(['admin_notice_email_scheduled_reminders'=>'1']);
  Carbon::setTestNow('2026-03-31 08:05:00');$this->assertSame(1,$service->processDue());
  Mail::assertSent(AdminNoticeMail::class);
  $mail=Mail::sent(AdminNoticeMail::class)->sole();
  $this->assertSame('Next reminder',$mail->noticeSubject);
  $this->assertSame(route('admin.reports.show',['report'=>'contracts']),$mail->adminUrl);
  $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk()->assertSee('Next reminder')->assertSee(route('admin.reports.show',['report'=>'contracts']),false)->assertSee('1 admin reminder due');
 }
 public function test_dismissal_is_per_admin_and_only_for_current_month():void{
  Carbon::setTestNow('2026-09-16 09:00:00');$one=User::factory()->create();$two=User::factory()->create();
  $reminder=AdminReminder::query()->create(['title'=>'Monthly task','day_of_month'=>1,'display_time'=>'08:00','destination'=>'contracts_report','active'=>true]);
  $occurrence=AdminReminderOccurrence::query()->create(['admin_reminder_id'=>$reminder->id,'period'=>'2026-09','due_at'=>now()]);
  $this->actingAs($one)->post(route('admin.reminders.dismiss',$occurrence))->assertSessionHas('success');
  $this->get(route('admin.dashboard'))->assertDontSee('Monthly task');
  $this->actingAs($two)->get(route('admin.dashboard'))->assertSee('Monthly task');
 }

 public function test_weekly_notification_only_repeats_after_dismissal():void{
  Mail::fake();Carbon::setTestNow('2026-10-07 07:59:00');
  $admin=User::factory()->create();
  AppSetting::putMany(['admin_notice_email_address'=>'admin@example.com','admin_notice_email_scheduled_reminders'=>'1']);
  $this->actingAs($admin)->post(route('admin.reminders.store'),[
   'title'=>'Weekly review','message'=>'Review tasks','recurrence_type'=>'weekly','day_of_week'=>3,
   'display_time'=>'08:00','send_email'=>1,'active'=>1,
  ])->assertSessionHasNoErrors();
  $reminder=AdminReminder::query()->where('title','Weekly review')->firstOrFail();
  $this->assertSame('notification_only',$reminder->destination);
  $service=app(AdminReminderService::class);
  $this->assertSame(0,$service->processDue());
  Carbon::setTestNow('2026-10-07 08:00:00');
  $this->assertSame(1,$service->processDue());$this->assertSame(0,$service->processDue());
  $mail=Mail::sent(AdminNoticeMail::class)->sole();
  $this->assertNull($mail->adminUrl);
  $this->get(route('admin.dashboard'))->assertOk()->assertSee('Weekly review')->assertDontSee('Open Notification only');
  $this->post(route('admin.reminders.dismiss',$service->dueFor($admin)->sole()))->assertSessionHas('success');
  $this->assertCount(0,$service->dueFor($admin));
  Carbon::setTestNow('2026-10-14 08:00:00');
  $this->assertSame(1,$service->processDue());$this->assertCount(1,$service->dueFor($admin));
 }
 public function test_annual_schedule_clamps_leap_day_and_remains_visible_in_later_months():void{
  Mail::fake();$admin=User::factory()->create();
  $this->actingAs($admin)->post(route('admin.reminders.store'),[
   'title'=>'Annual review','recurrence_type'=>'annually','month_of_year'=>2,'day_of_month'=>29,
   'display_time'=>'08:00','destination'=>'notification_only','active'=>1,
  ])->assertSessionHasNoErrors();
  $service=app(AdminReminderService::class);
  Carbon::setTestNow('2027-02-28 07:59:00');$this->assertSame(0,$service->processDue());
  Carbon::setTestNow('2027-02-28 08:00:00');$this->assertSame(1,$service->processDue());
  Carbon::setTestNow('2027-03-01 08:00:00');$this->assertSame(0,$service->processDue());$this->assertCount(1,$service->dueFor($admin));
  Carbon::setTestNow('2028-02-28 08:00:00');$this->assertSame(0,$service->processDue());$this->assertCount(0,$service->dueFor($admin));
  Carbon::setTestNow('2028-02-29 08:00:00');$this->assertSame(1,$service->processDue());$this->assertCount(1,$service->dueFor($admin));
 }
 public function test_recurrence_fields_are_required_and_form_renders():void{
  $this->actingAs(User::factory()->create())->get(route('admin.reminders.create'))->assertOk()->assertSee('Notification only')->assertSee('Annually')->assertSee('Weekly');
  $base=['title'=>'Task','display_time'=>'08:00','destination'=>'notification_only'];
  $this->post(route('admin.reminders.store'),$base+['recurrence_type'=>'weekly'])->assertSessionHasErrors('day_of_week');
  $this->post(route('admin.reminders.store'),$base+['recurrence_type'=>'annually','day_of_month'=>1])->assertSessionHasErrors('month_of_year');
 }
}
