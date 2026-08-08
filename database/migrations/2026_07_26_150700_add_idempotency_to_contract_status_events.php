<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_status_events', function (Blueprint $table) {
            $table->string('idempotency_key', 100)->nullable()->unique()->after('related_prior_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('contract_status_events', function (Blueprint $table) {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn('idempotency_key');
        });
    }
};