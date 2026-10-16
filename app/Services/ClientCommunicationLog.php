<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\PaymentPlan;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ClientCommunicationLog
{
    public function forClient(Client $client): LengthAwarePaginator
    {
        $queries = [];
        foreach ([
            ['email_deliveries', 'Email', 'template_slug', 'recipient_email', 'subject_snapshot', 'body_snapshot'],
            ['invoice_reminders', 'Email', "'payment-reminder'", 'recipient_email', 'NULL', 'NULL'],
            ['sms_deliveries', 'SMS', 'message_type', 'recipient_phone', 'NULL', 'message_snapshot'],
        ] as [$table, $channel, $type, $recipient, $subject, $body]) {
            $queries[] = DB::table($table)->where('recipient_client_id', $client->id)
                ->selectRaw("id, created_at, payment_plan_id, invoice_id, NULL as thread_uuid,
                    '{$table}' as source, '{$channel}' as channel, {$type} as message_type,
                    {$recipient} as recipient, status, {$subject} as subject, {$body} as body,
                    sent_at, failed_at, failure_message");
        }
        $queries[] = DB::table('secure_messages as m')
            ->join('secure_message_threads as t', 't.id', '=', 'm.secure_message_thread_id')
            ->leftJoin('plan_closings as c', 'c.id', '=', 't.plan_closing_id')
            ->where('t.client_id', $client->id)
            ->selectRaw("m.id, m.created_at, COALESCE(t.payment_plan_id, c.payment_plan_id) as payment_plan_id,
                NULL as invoice_id, t.uuid as thread_uuid, 'secure_messages' as source, 'Secure message' as channel,
                CASE WHEN m.sender_type = 'client' THEN 'Client to admin' ELSE 'Admin to client' END as message_type,
                NULL as recipient,
                CASE WHEN m.hidden_from_client = 1 THEN 'Internal'
                    WHEN m.sender_type = 'client' AND m.admin_viewed_at IS NOT NULL THEN 'Read'
                    WHEN m.sender_type = 'admin' AND m.client_viewed_at IS NOT NULL THEN 'Read'
                    ELSE 'Unread' END as status,
                t.subject, m.body, NULL as sent_at, NULL as failed_at, NULL as failure_message");

        // Match historical closing outcomes to their recorded recipient, not current memberships.
        $closingIds = [];
        foreach (DB::table('closing_events')->where('action', 'Client notification')->select('id', 'context')->cursor() as $event) {
            if (collect(json_decode($event->context ?? '[]', true))->contains(fn ($result) =>
                is_array($result) && (int) ($result['client_id'] ?? 0) === $client->id)) {
                $closingIds[] = $event->id;
            }
        }
        $queries[] = DB::table('closing_events as e')->join('plan_closings as c', 'c.id', '=', 'e.plan_closing_id')
            ->whereIn('e.id', $closingIds)
            ->selectRaw("e.id, e.created_at, c.payment_plan_id, NULL as invoice_id, NULL as thread_uuid,
                'closing_events' as source, 'Closing notification' as channel, e.action as message_type,
                NULL as recipient, 'Recorded outcome' as status, NULL as subject, e.context as body,
                NULL as sent_at, NULL as failed_at, NULL as failure_message");

        $combined = array_shift($queries);
        foreach ($queries as $query) $combined->unionAll($query);
        $entries = DB::query()->fromSub($combined, 'communications')
            ->orderByDesc('created_at')->orderBy('source')->orderByDesc('id')
            ->paginate(25, ['*'], 'communications_page')->appends(['tab' => 'communications']);
        $plans = PaymentPlan::whereIn('id', $entries->getCollection()->pluck('payment_plan_id')->filter())->get()->keyBy('id');
        $invoices = Invoice::whereIn('id', $entries->getCollection()->pluck('invoice_id')->filter())->get()->keyBy('id');
        foreach ($entries as $entry) {
            $entry->plan = $plans->get($entry->payment_plan_id);
            $entry->invoice = $invoices->get($entry->invoice_id);
            if ($entry->source === 'closing_events') {
                $result = collect(json_decode($entry->body ?? '[]', true))->first(fn ($result) =>
                    is_array($result) && (int) ($result['client_id'] ?? 0) === $client->id);
                $entry->body = 'Email: '.($result['email'] ?? 'not recorded')."\nSMS: ".($result['sms'] ?? 'not recorded')
                    ."\nHistorical outcome only; content and recipient address were not saved here. SMS may also appear as a separate delivery record.";
            } elseif ($entry->source === 'email_deliveries') {
                $entry->body = html_entity_decode(strip_tags(str_replace(
                    ['</p>', '<br>', '<br/>', '<br />', '</div>', '</tr>'],
                    ["\n\n", "\n", "\n", "\n", "\n", "\n"], $entry->body ?? ''
                )));
            }
        }
        return $entries;
    }
}
