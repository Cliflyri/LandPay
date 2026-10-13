<?php

namespace App\Http\Controllers\Admin;

use App\Services\DatabaseSqlExport;
use Illuminate\Validation\ValidationException;

class DatabaseBackupController
{
    public function __invoke(DatabaseSqlExport $export)
    {
        try {
            $stream = $export->create();
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['database_backup'=>$exception->getMessage()]);
        }
        $size = fstat($stream)['size'];
        return response()->streamDownload(function () use ($stream): void {
            try { fpassthru($stream); } finally { fclose($stream); }
        }, 'landpay-database-'.now()->format('Y-m-d-His').'.sql', [
            'Content-Type'=>'application/sql',
            'Content-Length'=>(string) $size,
            'Cache-Control'=>'private, no-store',
            'X-Content-Type-Options'=>'nosniff',
        ]);
    }
}
