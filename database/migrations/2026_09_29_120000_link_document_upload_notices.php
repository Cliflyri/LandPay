<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('admin_notices', function (Blueprint $table): void {
            $table->foreignId('shared_document_id')->nullable()->constrained()->nullOnDelete();
        });

        // Only link historical notices when one upload matches the client and filename.
        DB::table('admin_notices')->where('type', 'shared_document_uploaded')->orderBy('id')
            ->chunkById(100, function ($notices): void {
                foreach ($notices as $notice) {
                    $matches = DB::table('shared_documents')
                        ->where('client_id', $notice->client_id)
                        ->where('uploaded_by_client_id', $notice->client_id)
                        ->where('created_at', '<=', $notice->created_at)
                        ->get(['id', 'name'])
                        ->filter(fn ($document) => str_ends_with($notice->message, ' uploaded '.$document->name.'.'));
                    if ($matches->count() === 1) {
                        DB::table('admin_notices')->where('id', $notice->id)
                            ->update(['shared_document_id' => $matches->first()->id]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('admin_notices', fn (Blueprint $table) => $table->dropConstrainedForeignId('shared_document_id'));
    }
};
