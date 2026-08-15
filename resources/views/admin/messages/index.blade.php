@extends('layouts.admin')

@section('title', 'Secure messages | LandPay')
@section('body_class', 'admin-page')

@section('content')
<section class="admin-section">
    <div class="container-fluid dashboard-container">

        <div class="admin-heading d-flex flex-wrap justify-content-between align-items-end gap-3">
            <div>
                <span class="eyebrow eyebrow-dark">Administration</span>

                <h1>Secure messages</h1>

                <p class="mb-0">
                    Private client conversations and documents.
                </p>
            </div>

            <a class="btn btn-sun" href="{{ route('admin.messages.create') }}">
                New secure message
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success mt-4">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-warning mt-4">
                {{ session('error') }}
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mt-4 mb-3">
            <div class="d-flex gap-2">
                @foreach(['all' => 'All', 'unread' => 'Unread', 'starred' => 'Starred'] as $value => $label)
                    <a
                        class="btn btn-sm {{ $filter === $value ? 'btn-brand' : 'btn-outline-brand' }}"
                        style="padding-top:.15rem; padding-bottom:.15rem;"
                        href="{{ route('admin.messages.index', ['filter' => $value]) }}"
                    >
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <form
                method="post"
                action="{{ route('admin.messages.email-notifications') }}"
                class="form-check form-switch mb-0"
            >
                @csrf

                <input type="hidden" name="enabled" value="0">

                <input
                    class="form-check-input"
                    type="checkbox"
                    role="switch"
                    id="admin-message-email"
                    name="enabled"
                    value="1"
                    @checked($adminEmailEnabled)
                    @disabled(!$adminEmailAvailable)
                    onchange="this.form.submit()"
                >

                <label class="form-check-label" for="admin-message-email">
                    @if($adminEmailAvailable)
                        Email notifications
                        <small class="text-muted">
                            ({{ $adminEmail }})
                        </small>
                    @else
                        Email notifications &mdash; no reply-to email set
                        <a href="{{ route('admin.settings.index') }}#company-settings">
                            Set email
                        </a>
                    @endif
                </label>
            </form>
        </div>

        <div class="dashboard-table-card">
            <div class="table-responsive">
                <table class="table dashboard-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="dashboard-actions-menu">Actions</th>
                            <th>Client</th>
                            <th>Subject</th>
                            <th>Reference</th>
                            <th>Category</th>
                            <th>Latest activity</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($threads as $thread)
                            <tr class="dashboard-plan-row">
                                <td class="dashboard-actions-menu">
                                    <div class="d-inline-flex align-items-center gap-2">
                                        <div class="dropdown">
                                            <button
                                                class="btn btn-sm btn-light dashboard-menu-button"
                                                type="button"
                                                data-bs-toggle="dropdown"
                                                data-bs-boundary="viewport"
                                                aria-expanded="false"
                                                aria-label="Actions for {{ $thread->subject }}"
                                            >
                                                <span aria-hidden="true">&#8942;</span>
                                            </button>

                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li>
                                                    <a
                                                        class="dropdown-item"
                                                        href="{{ route('admin.messages.show', $thread) }}"
                                                    >
                                                        Open message
                                                    </a>
                                                </li>

                                                <li>
                                                    <form
                                                        method="post"
                                                        action="{{ route('admin.messages.star', $thread) }}"
                                                    >
                                                        @csrf

                                                        <button class="dropdown-item" type="submit">
                                                            {{ $thread->starred_at ? 'Remove follow-up star' : 'Star for follow-up' }}
                                                        </button>
                                                    </form>
                                                </li>

                                                <li><hr class="dropdown-divider"></li>

                                                <li>
                                                    <form
                                                        method="post"
                                                        action="{{ route('admin.messages.destroy', $thread) }}"
                                                        onsubmit="return confirm('Permanently delete this conversation and all attachments?') && confirm('Final confirmation: this cannot be undone. Delete permanently?');"
                                                    >
                                                        @csrf
                                                        @method('DELETE')

                                                        <button class="dropdown-item text-danger" type="submit">
                                                            Delete conversation
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>

                                        <span
                                            class="secure-message-star {{ $thread->starred_at ? 'is-starred' : '' }}"
                                            role="img"
                                            aria-label="{{ $thread->starred_at ? 'Starred for follow-up' : 'Not starred for follow-up' }}"
                                            title="{{ $thread->starred_at ? 'Starred for follow-up' : 'Not starred for follow-up' }}"
                                        >
                                            @if($thread->starred_at)
                                                &#9733;
                                            @else
                                                &#9734;
                                            @endif
                                        </span>
                                    </div>
                                </td>

                                <td style="font-size: 1.05rem;">
                                    {{ $thread->client->organization_name ?: trim($thread->client->first_name.' '.$thread->client->last_name) }}
                                </td>

                                <td>
                                    <a
                                        class="dashboard-plan-link"
                                        href="{{ route('admin.messages.show', $thread) }}"
                                    >
                                        {{ $thread->subject }}
                                    </a>

                                    @if($thread->unread_count)
                                        <span class="dashboard-status status-due ms-2">
                                            Unread
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    @if($thread->paymentPlan)
                                        <a href="{{ route('admin.plans.show', $thread->paymentPlan) }}">
                                            {{ $thread->paymentPlan->plan_number }}
                                        </a>
                                    @else
                                        &mdash;
                                    @endif
                                </td>

                                <td>
                                    {{ str($thread->category)->replace('_', ' ')->title() }}
                                </td>

                                <td>
                                    {{ $thread->latest_message_at->format('M j, Y g:i A') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="dashboard-empty">
                                    <strong>No secure messages.</strong>

                                    <span>
                                        Send a message when a client needs private instructions or documents.
                                    </span>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-pagination">
            {{ $threads->links() }}
        </div>

    </div>
</section>
@endsection