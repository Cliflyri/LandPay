<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $oldDefault = '<p>Hello {{ client_name }},</p><p>Thank you. We received your payment of <strong>{{ payment_amount }}</strong> on {{ payment_date }}.</p><p>Your receipt is included below and attached as a PDF. Your remaining contract balance is <strong>{{ remaining_contract_balance }}</strong>.</p>';
        $newDefault = '<p>Hello {{ client_name }},</p><p>Thank you. We received your payment of <strong>{{ payment_amount }}</strong> on {{ payment_date }}.</p><p>Your payment receipt is included below and attached as a PDF.</p>';

        DB::table('email_templates')
            ->where('slug', 'payment-receipt')
            ->where('body_html', $oldDefault)
            ->update(['body_html' => $newDefault, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Receipt templates are administrator-editable; do not overwrite later changes.
    }
};
