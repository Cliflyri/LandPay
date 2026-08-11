@extends('layouts.admin')

@section('title', 'Payment plans | LandPay')
@section('body_class', 'admin-page')

@section('content')
<section class="admin-section">
    <div class="container-fluid dashboard-container px-2">

        <div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <h1>Payment plans</h1>
                <p class="mb-0">
                    Manage client plans, billing schedules, and payment status.
                </p>
            </div>

            <a class="btn btn-sun" href="{{ route('admin.plans.create') }}">
                New plan
            </a>
        </div>

        <div class="mt-4">@include('admin.plans.partials.filters')</div>

        <div class="dashboard-table-card">
            <div class="table-responsive">
                <table class="table dashboard-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">
                                <span class="visually-hidden">Actions</span>
                            </th>
                            <th scope="col">Client</th>
                            <th scope="col">APN / Plan #</th>
                            <th scope="col">Property</th>
                            <th scope="col">Status</th>
                            <th scope="col" class="text-center">
                                Monthly
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($plans as $plan)
                            @php
                                $primaryMembership = $plan->memberships->firstWhere('role', 'primary');
                                $primaryClient = $primaryMembership?->client;

                                $clientName = $primaryClient
                                    ? (
                                        $primaryClient->organization_name
                                        ?: trim(
                                            ($primaryClient->first_name ?? '')
                                            .' '.
                                            ($primaryClient->last_name ?? '')
                                        )
                                    )
                                    : 'No primary client';

                                $coClientCount = $plan->memberships
                                    ->where('role', 'co_client')
                                    ->count();

                                $terms = $plan->currentBillingTerms;

                                $monthlyPrincipal = (int) (
                                    $terms?->scheduled_payment_amount
                                    ?? $plan->customary_monthly_payment
                                );

                                $monthlyFee = (int) (
                                    $terms?->monthly_service_fee
                                    ?? $plan->monthly_service_fee
                                );

                                $monthlyTotal = $monthlyPrincipal + $monthlyFee;

                                $statusClass = match ($plan->status) {
                                    'active' => 'status-current',
                                    'paused' => 'status-current',
                                    'draft' => 'status-draft',
                                    'closed', 'terminated' => 'status-closed',
                                    default => 'status-draft',
                                };

                                $statusLabel = match ($plan->status) {
                                    'paused' => '❚❚ Paused',
                                    default => str($plan->status)
                                        ->replace('_', ' ')
                                        ->title(),
                                };
                            @endphp

                            <tr class="dashboard-plan-row {{ $plan->ready_to_close ? 'ready-to-close' : '' }}">

                                <td class="dashboard-actions-menu">
                                    <div class="dropdown">
                                        <button
                                            class="btn btn-sm btn-light dashboard-menu-button"
                                            type="button"
                                            data-bs-toggle="dropdown"
                                            data-bs-boundary="viewport"
                                            aria-expanded="false"
                                            aria-label="Actions for {{ $clientName }}"
                                        >
                                            <span aria-hidden="true">&#8942;</span>
                                        </button>

                                        <ul class="dropdown-menu dropdown-menu-start">
                                            <li>
                                                <a
                                                    class="dropdown-item"
                                                    href="{{ route('admin.plans.show', $plan) }}"
                                                >
                                                    View plan
                                                </a>
                                            </li>

                                            <li>
                                                <a
                                                    class="dropdown-item"
                                                    href="{{ route('admin.plans.edit', $plan) }}"
                                                >
                                                    Edit plan
                                                </a>
                                            </li>
                                            <li>
                                                <a
                                                    class="dropdown-item"
                                                    href="{{ route('admin.plans.edit', $plan) }}#plan-status"
                                                >
                                                    Change status
                                                </a>
                                            </li>

                                            <li>
                                                <a
                                                    class="dropdown-item"
                                                    href="{{ route('admin.plans.invoices.manual.create', $plan) }}"
                                                >
                                                    Create invoice
                                                </a>
                                            </li>

                                            <li>
                                                <a
                                                    class="dropdown-item"
                                                    href="{{ route('admin.plans.payments.create', $plan) }}"
                                                >
                                                    Record payment
                                                </a>
                                            </li>
@if ($primaryClient)
    <li>
        <form method="post" action="{{ route('admin.portal-access.store', $primaryClient) }}">
            @csrf
            <button class="dropdown-item" type="submit">
                Open client portal
            </button>
        </form>
    </li>
@endif
                                        </ul>
                                    </div>
                                </td>

                                <td class="text-nowrap">
                                    @if ($primaryClient)
                                        <a
                                            class="dashboard-client-link"
                                            href="{{ route('admin.clients.show', $primaryClient) }}"
                                        >
                                            {{ $clientName }}
                                        </a>
                                    @else
                                        <span class="muted-value">
                                            {{ $clientName }}
                                        </span>
                                    @endif

                                    @if ($coClientCount > 0)
                                        <span
                                            class="co-client-count"
                                            title="{{ $coClientCount }} co-client(s)"
                                        >
                                            +{{ $coClientCount }}
                                        </span>
                                    @endif
                                </td>

                                <td class="text-nowrap">
                                    <a
                                        class="dashboard-plan-link"
                                        href="{{ route('admin.plans.show', $plan) }}"
                                    >
                                        {{ $plan->plan_number }}
                                    </a>
                                </td>

                                <td>
                                    {{ $plan->title }}
                                </td>

                                <td>
                                    <span class="dashboard-status {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                    @if ($plan->ready_to_close)
<div class="mt-1">
    <a
        href="{{ route('admin.plans.edit', $plan) }}#plan-status"
        class="text-decoration-none"
        title="Change plan status"
    >
        <span class="dashboard-status status-ready-to-close">
            &#10003; Ready to close
        </span>
    </a>
</div>
                                    @endif

                                    @if ($plan->accelerated_testing_mode)
                                        <div class="mt-1">
                                            <a
                                                href="{{ route('admin.plans.edit', $plan) }}#accelerated-testing-mode"
                                                class="text-decoration-none"
                                                title="Testing mode enabled — click to edit"
                                            >
                                                <span
                                                    class="dashboard-status status-due"
                                                    style="font-size:.78rem; letter-spacing:normal; padding:.3rem .65rem;"
                                                >
                                                    TEST: Daily Billing
                                                </span>
                                            </a>
                                        </div>
                                    @endif
                                </td>

                                <td class="money-cell text-center">
                                    <div class="fw-bold fs-6">
                                        {{ \App\Support\Money::format($monthlyTotal) }}
                                    </div>

                                    <div class="text-muted small">
                                        ({{ \App\Support\Money::format($monthlyPrincipal) }} principal)
                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="dashboard-empty">
                                    <strong>No plans match these filters.</strong>
                                    <span>Change or clear the filters to see other plans.</span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-pagination">
            {{ $plans->links() }}
        </div>

    </div>
</section>
@endsection