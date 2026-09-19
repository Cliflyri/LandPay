<?php
namespace Tests\Feature\Admin;
use App\Models\AppSetting;
use App\Models\User;
use App\Services\ReportSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;
class ReportSnapshotTest extends TestCase{
 use RefreshDatabase;
 private string $path;
 protected function setUp():void{parent::setUp();$this->path=storage_path('framework/testing/report-snapshots');config()->set('landpay.report_snapshots_path',$this->path);File::deleteDirectory($this->path);}
 protected function tearDown():void{File::deleteDirectory($this->path);parent::tearDown();}
 public function test_admin_can_view_and_save_snapshot_schedule():void{
  $this->actingAs(User::factory()->create())->get(route('admin.report-snapshots.index'))->assertOk()->assertSee('Report Snapshots')->assertSee('Create snapshot now');
  $this->put(route('admin.report-snapshots.update'),['enabled'=>'1','frequency'=>'weekly','time'=>'03:30','weekday'=>2,'month_day'=>1,'keep'=>4])->assertRedirect();
  $this->assertSame('1',AppSetting::valueFor('report_snapshots_enabled'));$this->assertSame('4',AppSetting::valueFor('report_snapshots_keep'));
 }
 public function test_snapshot_contains_every_report_and_prunes_old_generations():void{
  AppSetting::putMany(['report_snapshots_keep'=>'1']);$service=app(ReportSnapshotService::class);
  $first=$service->create('manual');$this->travel(1)->second();$second=$service->create('scheduled');
  $this->assertDirectoryDoesNotExist($this->path.DIRECTORY_SEPARATOR.$first['id']);$this->assertDirectoryExists($this->path.DIRECTORY_SEPARATOR.$second['id']);
  foreach(['payments','receivables','contracts','fees','client-portals','client-sms'] as $report)$this->assertFileExists($this->path.DIRECTORY_SEPARATOR.$second['id'].DIRECTORY_SEPARATOR.$report.'.csv');
 }
}
