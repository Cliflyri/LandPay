@php
    $filterId = $filterId ?? 'plan-filters';
    $searchValue = $searchValue ?? $planSearch;
    $searchLabel = $searchLabel ?? 'Search payment plans';
    $searchPlaceholder = $searchPlaceholder ?? 'Search client, APN, plan, email, or phone';
    $filterSelects = $filterSelects ?? [[
        'name' => 'status',
        'label' => 'Plan status',
        'value' => $planStatus,
        'default' => 'active',
        'options' => ['active' => 'Active', 'draft' => 'Draft', 'terminated' => 'Terminated', 'closed' => 'Closed', 'all' => 'All'],
    ]];
    $filtersChanged = collect($filterSelects)->contains(fn ($filter) => $filter['value'] !== ($filter['default'] ?? ''));
@endphp
<form id="{{ $filterId }}" method="get" action="{{ url()->current() }}#{{ $filterId }}"
      class="row g-2 align-items-center mb-3"
      role="search"
      data-list-filter-form
      data-clear-url="{{ url()->current() }}#{{ $filterId }}">

    <div class="col-12 col-md-6 col-lg-5">
        <label class="visually-hidden" for="{{ $filterId }}-search">{{ $searchLabel }}</label>
        <div class="position-relative">
            <input class="form-control pe-5"
                   id="{{ $filterId }}-search"
                   name="search"
                   type="search"
                   value="{{ $searchValue }}"
                   placeholder="{{ $searchPlaceholder }}">
            <button class="btn btn-link position-absolute top-50 end-0 translate-middle-y me-1 px-1 fs-2 lh-1 text-secondary text-decoration-none {{ $searchValue === '' ? 'd-none' : '' }}"
                    style="margin-top: -4px;"
                    type="button"
                    title="Clear search"
                    aria-label="Clear search"
                    data-list-search-clear>&times;</button>
        </div>
    </div>

    @foreach ($filterSelects as $filter)
        <div class="col-auto">
            <label class="visually-hidden" for="{{ $filterId }}-{{ $filter['name'] }}">{{ $filter['label'] }}</label>
            <select class="form-select" id="{{ $filterId }}-{{ $filter['name'] }}" name="{{ $filter['name'] }}">
                @foreach ($filter['options'] as $value => $label)
                    <option value="{{ $value }}" @selected($filter['value'] === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    @endforeach

    <div class="col-auto">
        <button class="btn btn-brand py-1" type="submit">Search</button>
    </div>

    @if ($searchValue !== '' || $filtersChanged)
        <div class="col-auto">
            <a class="btn btn-outline-brand py-1" href="{{ url()->current() }}#{{ $filterId }}">Clear</a>
        </div>
    @endif
</form>
