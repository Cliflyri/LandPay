<?php
namespace Tests\Feature\Admin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ReportsTest extends TestCase{
 use RefreshDatabase;
 public function test_reports_and_exports_are_available_to_admin():void{
  $this->actingAs(User::factory()->create());
  foreach(['payments','receivables','contracts','fees'] as $report){
   $this->get(route('admin.reports.show',['report'=>$report]))->assertOk()->assertSee('Export CSV')->assertSee('Print report');
   $this->get(route('admin.reports.export',['report'=>$report]))->assertOk()->assertHeader('content-type','text/csv; charset=UTF-8');
  }
 }
 public function test_reports_require_authentication():void{
  $this->get(route('admin.reports.show'))->assertRedirect(route('admin.login'));
 }
}