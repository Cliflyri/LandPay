<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_notices', function (Blueprint $table): void {
            $table->string('provider_event_id', 120)->nullable()->unique();
            $table->string('provider_event_type', 80)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('admin_notices', function (Blueprint $table): void {
            $table->dropUnique(['provider_event_id']);
            $table->dropColumn(['provider_event_id', 'provider_event_type']);
        });
    }
};
