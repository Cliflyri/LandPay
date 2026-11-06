<?php

namespace App\Integrations\LandInvest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExportReceiverController
{
    public function __invoke(Request $request, ExportConnection $connection, Export $export)
    {
        // Never redirect a signed upload or accept it over an insecure connection.
        if (! $request->isSecure()) {
            return response()->json(['error' => 'HTTPS required.'], 400);
        }
        if ($request->method() !== 'GET' || $request->getContent() !== '' || $request->getQueryString() !== null) {
            return response()->json(['error' => 'Use GET with no body or query string.'], 400);
        }
        $body = $request->getContent();
        if (strlen($body) > 2097152) {
            return response()->json(['error' => 'Payload too large.'], 413);
        }
        try {
            return $connection->cache()->lock('landinvest-export-connection', 30)->block(5, function () use ($request, $connection, $export, $body) {
                $state = $connection->state();
                if (! ($state['enabled'] ?? false)) {
                    return response()->json(['error' => 'Integration unavailable.'], 503);
                }
                $id = (string) $request->header('X-LandInvest-Key');
                $timestamp = (string) $request->header('X-LandInvest-Timestamp');
                $nonce = (string) $request->header('X-LandInvest-Nonce');
                $signature = (string) $request->header('X-LandInvest-Signature');
                $key = null;
                foreach (['current', 'previous'] as $slot) {
                    $candidate = $state[$slot] ?? null;
                    if ($candidate && hash_equals($candidate['id'], $id)
                        && ($slot === 'current' || ($candidate['expires'] ?? 0) > time())) {
                        $key = $candidate;
                    }
                }
                if (! $key || ! preg_match('/^\d{10}$/D', $timestamp) || abs(time() - (int) $timestamp) > 300
                    || ! preg_match('/^[a-f0-9]{32}$/D', $nonce) || ! preg_match('/^[a-f0-9]{64}$/D', $signature)
                    || ! hash_equals(ExportConnection::signature($key['secret'], $id, $timestamp, $nonce, $body), $signature)) {
                    return response()->json(['error' => 'Authentication failed.'], 401);
                }
                if (! $connection->cache()->add('landinvest-export-nonce:'.hash('sha256', $id.':'.$nonce), true, 660)) {
                    return response()->json(['error' => 'Request already used; retry with a fresh nonce.'], 409);
                }
                $data = $export->snapshot();
                $state['last_received_at'] = gmdate('c');
                $connection->save($state);
                Log::info('LandInvest CSV export served', ['key_id' => $id, 'plan_count' => $data['planCount']]);
                return response($data['csv'], 200, [
                    'Content-Type' => 'text/csv; charset=UTF-8',
                    'Content-Disposition' => 'attachment; filename="landpay-landinvest.csv"',
                    'X-Content-Type-Options' => 'nosniff',
                    'X-LandPay-Generated-At' => gmdate('Y-m-d\TH:i:s\Z'),
                    'X-LandPay-Plan-Count' => (string) $data['planCount'],
                ]);
            })->header('Cache-Control', 'no-store');
        } catch (\Throwable $e) {
            // Do not log exception text: database exceptions may contain stored credentials.
            Log::error('LandInvest export unavailable', ['exception_type' => get_class($e)]);
            return response()->json(['error' => 'Temporarily unavailable.'], 503);
        }
    }
}
