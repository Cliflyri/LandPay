<form id="plan-filters" method="get" action="{{ url()->current() }}#plan-filters"
      class="row g-2 align-items-center mb-3"
      role="search"
      data-plan-filter-form
      data-clear-url="{{ url()->current() }}#plan-filters">

    <div class="col-12 col-md-6 col-lg-5">
        <label class="visually-hidden" for="plan-search">Search payment plans</label>
        <div class="position-relative">
            <input class="form-control pe-5"
                   id="plan-search"
                   name="search"
                   type="search"
                   value="{{ $planSearch }}"
                   placeholder="Search client, APN, plan, email, or phone">
            <button class="btn btn-link position-absolute top-50 end-0 translate-middle-y me-1 px-1 fs-2 lh-1 text-secondary text-decoration-none {{ $planSearch === '' ? 'd-none' : '' }}"
                    style="margin-top: -4px;"        
                    type="button"
                    title="Clear search"
                    aria-label="Clear search"
                    data-plan-search-clear>&times;</button>
        </div>
    </div>

    <div class="col-auto">
        <label class="visually-hidden" for="plan-status-filter">Plan status</label>
        <select class="form-select" id="plan-status-filter" name="status">
            @foreach (['active' => 'Active', 'draft' => 'Draft', 'terminated' => 'Terminated', 'closed' => 'Closed', 'all' => 'All'] as $value => $label)
                <option value="{{ $value }}" @selected($planStatus === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-auto">
        <button class="btn btn-brand py-1" type="submit">Search</button>
    </div>

    @if ($planSearch !== '' || $planStatus !== 'active')
        <div class="col-auto">
            <a class="btn btn-outline-brand py-1" href="{{ url()->current() }}#plan-filters">Clear</a>
        </div>
    @endif
</form>