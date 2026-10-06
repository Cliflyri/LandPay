@foreach($closingNotices as $reviewClosing)
<div class="alert alert-warning d-flex flex-wrap justify-content-between align-items-center gap-2" role="status">
    <div><strong>Ready to close:</strong> Plan {{ $reviewClosing->paymentPlan->plan_number }} may be fulfilled and is ready for closing review.
        <a href="{{ route('admin.plans.show', $reviewClosing->paymentPlan) }}#closing">Review closing</a>
    </div>
    <form method="post" action="{{ route('admin.closing.dismiss', $reviewClosing->paymentPlan) }}">@csrf
        <button class="btn btn-sm btn-outline-dark" aria-label="Dismiss closing review for plan {{ $reviewClosing->paymentPlan->plan_number }}">Dismiss</button>
    </form>
</div>
@endforeach
