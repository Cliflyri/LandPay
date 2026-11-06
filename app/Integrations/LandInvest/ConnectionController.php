<?php

namespace App\Integrations\LandInvest;

use App\Models\AppSetting;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConnectionController
{
    public function update(Request $request, Connection $connection)
    {
        return $this->manage($request, $connection, 'admin.landinvest.import', 'landinvest.');
    }

    public function updateExport(Request $request, ExportConnection $connection)
    {
        return $this->manage($request, $connection, 'admin.landinvest.export', 'landinvest.export.');
    }

    private function manage(Request $request, Connection $connection, string $returnRoute, string $eventPrefix)
    {
        abort_unless($request->isSecure() || app()->environment('local'), 400, 'HTTPS required.');
        $request->validate([
            'password' => ['required', 'current_password:web'],
            'operation' => ['required', 'in:generate,rotate,revoke,enable,disable'],
            'overlap' => ['nullable', 'boolean'],
        ]);
        $issued = $connection->cache()->lock($connection->lockName(), 30)->block(5, function () use ($request, $connection, $eventPrefix) {
            return DB::transaction(function () use ($request, $connection, $eventPrefix) {
                $state = $connection->state();
                $operation = $request->string('operation')->toString();
                $secret = null;
                if (in_array($operation, ['generate', 'rotate'], true)) {
                    if ($operation === 'generate' && isset($state['current'])) {
                        abort(409, 'A key already exists. Use Rotate.');
                    }
                    if ($operation === 'rotate' && $request->boolean('overlap') && isset($state['current'])) {
                        $state['previous'] = $state['current'] + ['expires' => time() + 86400];
                    } else {
                        unset($state['previous']);
                    }
                    $secret = bin2hex(random_bytes(32));
                    $state['current'] = ['id' => bin2hex(random_bytes(8)), 'secret' => $secret, 'created_at' => gmdate('c')];
                } elseif ($operation === 'revoke') {
                    $state = ['enabled' => false];
                } elseif ($operation === 'enable') {
                    abort_unless(isset($state['current']), 422, 'Generate a key first.');
                    $state['enabled'] = true;
                } else {
                    $state['enabled'] = false;
                }
                $connection->save($state);
                AuditLog::create([
                    'actor_type' => 'admin', 'actor_user_id' => $request->user('web')->id,
                    'event' => $eventPrefix.$operation, 'auditable_type' => AppSetting::class,
                    'auditable_id' => AppSetting::query()->where('key', $connection::SETTING)->value('id'),
                    'after_values' => ['enabled' => $state['enabled'] ?? false, 'key_id' => $state['current']['id'] ?? null],
                    'ip_address' => $request->ip(), 'created_at' => now(),
                ]);
                return $secret === null ? null : ['secret' => $secret, 'keyId' => $state['current']['id']];
            });
        });
        if ($issued !== null) {
            // Direct response only: never flash a secret into a session or old input.
            return response()->view('admin.actions.landinvest-key', [
                'secret' => $issued['secret'], 'keyId' => $issued['keyId'], 'returnRoute' => $returnRoute,
            ])->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
        }
        return redirect()->route($returnRoute)->with('import_summary', 'Automatic update settings saved.');
    }
}
