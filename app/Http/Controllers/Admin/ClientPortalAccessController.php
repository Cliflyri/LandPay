<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ClientPortalAccessController extends Controller
{
    public function store(Request $request, Client $client): RedirectResponse
    {
        $client->load('portalAccount');
        $account = $client->portalAccount;
        if ($account === null || ! $account->enabled) {
            throw ValidationException::withMessages([
                'portal_account' => 'This client does not have active portal access.',
            ]);
        }

        Auth::guard('client')->logout();
        Auth::guard('client')->login($account);

        $startedAt = now();
        $audit = AuditLog::query()->create([
            'actor_type' => 'administrator',
            'actor_user_id' => $request->user()->id,
            'actor_client_id' => $client->id,
            'event' => 'client_portal.admin_access_started',
            'auditable_type' => Client::class,
            'auditable_id' => $client->id,
            'before_values' => null,
            'after_values' => ['mode' => 'read_only', 'started_at' => $startedAt->toIso8601String()],
            'ip_address' => $request->ip(),
            'user_agent' => str($request->userAgent())->limit(500),
        ]);

        $request->session()->put('portal_impersonation', [
            'admin_user_id' => $request->user()->id,
            'client_id' => $client->id,
            'client_name' => $account->load('client')->displayName(),
            'started_at' => $startedAt->toIso8601String(),
            'start_audit_id' => $audit->id,
        ]);

        return redirect()->route('portal.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $access = $request->session()->get('portal_impersonation');
        if (is_array($access)) {
            AuditLog::query()->create([
                'actor_type' => 'administrator',
                'actor_user_id' => $request->user()->id,
                'actor_client_id' => $access['client_id'],
                'event' => 'client_portal.admin_access_ended',
                'auditable_type' => Client::class,
                'auditable_id' => $access['client_id'],
                'before_values' => ['started_at' => $access['started_at'], 'start_audit_id' => $access['start_audit_id']],
                'after_values' => ['ended_at' => now()->toIso8601String()],
                'ip_address' => $request->ip(),
                'user_agent' => str($request->userAgent())->limit(500),
            ]);
        }

        Auth::guard('client')->logout();
        $request->session()->forget('portal_impersonation');

        return redirect()->route('admin.dashboard')->with('success', 'Returned to administration.');
    }
}
