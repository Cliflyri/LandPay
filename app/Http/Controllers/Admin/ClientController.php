<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\FinancialBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function __construct(private readonly FinancialBalanceService $balances) {}

    public function index(Request $request): View
    {
        $showAllPlans = $request->string('plans')->value() === 'all';
        $clients = Client::query()
            ->whereNull('archived_at')
            ->with([
                'portalAccount',
                'memberships' => function ($query) use ($showAllPlans): void {
                    $query->whereNull('effective_to')
                        ->with('paymentPlan')
                        ->when(! $showAllPlans, fn ($query) => $query->whereHas(
                            'paymentPlan',
                            fn ($plan) => $plan->whereNotIn('status', ['closed', 'terminated'])
                        ));
                },
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $allRows = $clients->flatMap(function (Client $client) {
            $memberships = $client->memberships->filter->paymentPlan;
            if ($memberships->isEmpty()) {
                return [['client' => $client, 'plan' => null]];
            }

            return $memberships->map(fn ($membership) => [
                'client' => $client,
                'plan' => $membership->paymentPlan,
            ])->values();
        })->sortBy(fn (array $row) => mb_strtolower(
            ($row['client']->organization_name ?: trim($row['client']->last_name.' '.$row['client']->first_name))
            .' '.($row['plan']?->apn ?: $row['plan']?->plan_number ?: '')
        ))->values();

        $perPage = 25;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $pageRows = $allRows->slice(($page - 1) * $perPage, $perPage)->map(function (array $row): array {
            $plan = $row['plan'];
            $row['contract_balance'] = $plan ? $this->balances->contractBalance($plan) : null;
            $row['paid_in_value'] = $plan ? $this->balances->administratorPaidInValue($plan) : null;

            return $row;
        })->values();

        $rows = new LengthAwarePaginator(
            $pageRows,
            $allRows->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('admin.clients.index', [
            'rows' => $rows,
            'showAllPlans' => $showAllPlans,
        ]);
    }

    public function create(): View
    {
        return view('admin.clients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedClient($request);
        $data['created_by_user_id'] = $request->user()->id;
        $data['updated_by_user_id'] = $request->user()->id;
        $client = Client::query()->create($data);

        return redirect()->route('admin.clients.show', $client)->with('success', 'Client created successfully.');
    }

    public function quickStore(Request $request): JsonResponse
    {
        $data = $this->validatedClient($request);
        $data['created_by_user_id'] = $request->user()->id;
        $data['updated_by_user_id'] = $request->user()->id;
        $client = Client::query()->create($data);

        return response()->json([
            'id' => $client->id,
            'label' => $client->organization_name ?: trim($client->first_name.' '.$client->last_name),
            'email' => $client->email,
            'phone' => $client->primary_phone,
        ], 201);
    }

    public function show(Client $client): View
    {
        $client->load(['memberships.paymentPlan', 'contacts', 'portalAccount', 'portalInvitations']);

        return view('admin.clients.show', compact('client'));
    }

    public function edit(Client $client): View
    {
        return view('admin.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $data = $this->validatedClient($request);
        $data['updated_by_user_id'] = $request->user()->id;
        $client->update($data);

        return redirect()->route('admin.clients.show', $client)->with('success', 'Client updated successfully.');
    }

    private function validatedClient(Request $request): array
    {
        $data = $request->validate([
            'client_type' => ['required', Rule::in(['individual', 'organization'])],
            'first_name' => ['nullable', 'required_if:client_type,individual', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['nullable', 'required_if:client_type,individual', 'string', 'max:100'],
            'preferred_name' => ['nullable', 'string', 'max:100'],
            'organization_name' => ['nullable', 'required_if:client_type,organization', 'string', 'max:180'],
            'email' => ['nullable', 'email', 'max:254'],
            'primary_phone' => ['nullable', 'string', 'max:32'],
            'secondary_phone' => ['nullable', 'string', 'max:32'],
            'address_line_1' => ['nullable', 'string', 'max:150'],
            'address_line_2' => ['nullable', 'string', 'max:150'],
            'city' => ['nullable', 'string', 'max:100'],
            'state_region' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:24'],
            'country_code' => ['required', 'string', 'size:2'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['email'] = filled($data['email'] ?? null) ? mb_strtolower(trim($data['email'])) : null;
        $data['country_code'] = mb_strtoupper($data['country_code']);

        return $data;
    }
}
