<?php
namespace Tests\Feature\Admin;
use App\Models\User;
use App\Services\DatabaseSqlExport;
use Tests\TestCase;
class DatabaseBackupTest extends TestCase
{
    public function test_guests_cannot_download(): void
    {
        $this->mock(DatabaseSqlExport::class)->shouldNotReceive('create');
        $this->post(route('admin.database-backup.download'))->assertRedirect(route('admin.login'));
    }
    public function test_authenticated_download_streams_sql_and_closes_temporary_file(): void
    {
        $file=tmpfile();fwrite($file,"-- test SQL\nCREATE TABLE example (id INT);\n");rewind($file);
        $this->mock(DatabaseSqlExport::class)->shouldReceive('create')->once()->andReturn($file);
        $user=new User;$user->forceFill(['id'=>1,'name'=>'Test','email'=>'test@example.test']);
        $response=$this->actingAs($user)->post(route('admin.database-backup.download'));
        $response->assertOk()->assertHeader('Content-Type','application/sql');
        $this->assertStringContainsString('CREATE TABLE example',$response->streamedContent());
        $this->assertFalse(is_resource($file));
    }
    public function test_failed_export_redirects_without_partial_download(): void
    {
        $this->mock(DatabaseSqlExport::class)->shouldReceive('create')->once()->andThrow(new \RuntimeException('Database export failed.'));
        $user=new User;$user->forceFill(['id'=>1]);
        $this->actingAs($user)->from(route('admin.actions.index'))->post(route('admin.database-backup.download'))->assertRedirect(route('admin.actions.index'))->assertSessionHasErrors('database_backup');
    }
}
