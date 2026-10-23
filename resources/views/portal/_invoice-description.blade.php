@php
$descriptionItems = $descriptionInvoice->items->sortBy('display_order');
$nonServiceItems = $descriptionItems->reject(fn ($item) => $item->item_type === \App\Enums\InvoiceItemType::MonthlyServiceFee);
$firstDescriptionItem = $nonServiceItems->first() ?? $descriptionItems->first();
$additionalItemCount = max(0, $nonServiceItems->count() - 1);
@endphp
@if($firstDescriptionItem)
<small class="d-block text-muted">{{ $firstDescriptionItem->description }}@if($additionalItemCount > 0) (+{{ $additionalItemCount }} more {{ $additionalItemCount === 1 ? 'item' : 'items' }})@endif</small>
@endif
