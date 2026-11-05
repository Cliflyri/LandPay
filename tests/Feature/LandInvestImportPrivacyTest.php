<?php

namespace Tests\Feature;

use App\Models\PortalAccount;
use App\Models\User;
use App\Integrations\LandInvest\RepaymentSnapshot;
use Tests\TestCase;

class LandInvestImportPrivacyTest extends TestCase
{
    public function test_guest_and_client_cannot_access_import(): void
    {
        $this->get('/admin/actions/import-landinvest')->assertRedirect();
        $this->post('/admin/actions/import-landinvest', ['snapshot' => '{}'])->assertRedirect();
        $this->actingAs(new PortalAccount, 'client');
        $this->get('/admin/actions/import-landinvest')->assertRedirect();
        $this->post('/admin/actions/import-landinvest', ['snapshot' => '{}'])->assertRedirect();
    }

    public function test_indicator_is_private_and_missing_status_has_slash(): void
    {
        $this->app->instance(RepaymentSnapshot::class, new RepaymentSnapshot(sys_get_temp_dir().'/nonexistent-landpay-'.bin2hex(random_bytes(8)).'.json'));
        $data = ['repaymentPlan' => (object) ['plan_number' => '123']];
        $this->assertStringNotContainsString('<svg', view('admin.plans.partials.repayment-status', $data)->render());
        $this->actingAs(new PortalAccount, 'client');
        $this->assertStringNotContainsString('<svg', view('admin.plans.partials.repayment-status', $data)->render());
        $this->actingAs(new User, 'web');
        $html = view('admin.plans.partials.repayment-status', $data)->render();
        $this->assertStringContainsString('No repayment status available from LandInvest.', $html);
        $this->assertStringContainsString('M4 12L12 4', $html);
    }
}
