<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\PortalAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
class ClientPortalAccountController extends Controller
{
    public function update(Request $request, Client $client): RedirectResponse
    {
        $account = $client->portalAccount;
        $data = $request->validate([
            'enabled' => ['nullable','boolean'],
            'portal_password' => [$account ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ]);
        if (blank($client->email)) throw ValidationException::withMessages(['portal_account'=>'Add a client email before enabling portal access.']);
        $duplicate = PortalAccount::query()->where('email',mb_strtolower($client->email))->when($account,fn($q)=>$q->whereKeyNot($account->id))->exists();
        if ($duplicate) throw ValidationException::withMessages(['portal_account'=>'That email is already assigned to another portal account.']);
        $values=['email'=>mb_strtolower($client->email),'enabled'=>$request->boolean('enabled')];
        if (filled($data['portal_password'] ?? null)) $values['password']=$data['portal_password'];
        $client->portalAccount()->updateOrCreate([], $values);
        return back()->with('success','Client portal access updated.');
    }
}
