@if($admin || $propertyPlan->hasPropertyDetails())
<div class="admin-next-card mt-4" id="property-details-{{$propertyPlan->id}}">
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2"><div><h2 class="mb-1">Your Property</h2><small class="text-muted">{{$propertyPlan->plan_number}} &middot; {{$propertyPlan->title}}</small></div>
@if($admin)<a class="btn btn-sm btn-outline-brand" href="{{route('admin.property.edit',$propertyPlan)}}">Edit property details</a>@endif</div>
@php($photos=$propertyPlan->property_photos??[])
@php($hasMap=$propertyPlan->property_latitude!==null && $propertyPlan->property_longitude!==null)
<div class="row g-3">
@if($photos)
<div class="{{$hasMap?'col-md-6':'col-12'}}">
@php($galleryId='property-gallery-'.$propertyPlan->id)
<button type="button" class="border-0 p-0 bg-transparent w-100" data-bs-toggle="modal" data-bs-target="#{{$galleryId}}" aria-label="View property photos">
<img class="rounded w-100" style="display:block;width:auto!important;height:auto!important;max-width:100%;max-height:340px;aspect-ratio:auto!important;margin:0 auto;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.15);" src="{{route($admin?'admin.property.photo':'portal.property.photo',[$propertyPlan,$photos[0]['id']])}}" alt="Property photo: {{$propertyPlan->title}}" loading="lazy"></button>
@if(count($photos)>1)<div class="d-flex gap-2 overflow-auto mt-2 pb-1">
@foreach($photos as $photo)<button type="button" class="border-0 p-0 bg-transparent flex-shrink-0" data-bs-toggle="modal" data-bs-target="#{{$galleryId}}" data-property-slide="{{$loop->index}}" aria-label="View property photo {{$loop->iteration}}">
<img class="rounded" style="width:64px;height:48px;object-fit:cover" src="{{route($admin?'admin.property.photo':'portal.property.photo',[$propertyPlan,$photo['id']])}}" alt="Photo {{$loop->iteration}}" loading="lazy"></button>@endforeach
</div>@endif
<div class="modal fade" id="{{$galleryId}}" tabindex="-1" aria-labelledby="{{$galleryId}}-title" aria-hidden="true" data-property-gallery>
<div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
<div class="modal-header"><h2 class="modal-title fs-5" id="{{$galleryId}}-title">{{$propertyPlan->title}}</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
<div class="modal-body p-2"><div id="{{$galleryId}}-slides" class="carousel slide" data-bs-interval="false">
<div class="carousel-inner">@foreach($photos as $photo)<div class="carousel-item {{$loop->first?'active':''}}"><img class="d-block w-100" style="height:65vh;object-fit:contain" src="{{route($admin?'admin.property.photo':'portal.property.photo',[$propertyPlan,$photo['id']])}}" alt="Property photo {{$loop->iteration}} of {{count($photos)}}" loading="lazy"><p class="text-center small text-muted mb-0">{{$loop->iteration}} / {{count($photos)}}</p></div>@endforeach</div>
@if(count($photos)>1)<button class="carousel-control-prev" type="button" data-bs-target="#{{$galleryId}}-slides" data-bs-slide="prev"><span class="carousel-control-prev-icon bg-dark rounded" aria-hidden="true"></span><span class="visually-hidden">Previous photo</span></button><button class="carousel-control-next" type="button" data-bs-target="#{{$galleryId}}-slides" data-bs-slide="next"><span class="carousel-control-next-icon bg-dark rounded" aria-hidden="true"></span><span class="visually-hidden">Next photo</span></button>@endif
</div></div></div></div></div>
</div>
@endif
@if($hasMap)
@php($coordinates=$propertyPlan->property_latitude.','.$propertyPlan->property_longitude)
<div class="{{$photos?'col-md-6':'col-12'}}">
<iframe class="rounded w-100 border-0" height="305" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Map of {{$propertyPlan->title}}" src="https://maps.google.com/maps?q={{urlencode($coordinates)}}&amp;z=15&amp;output=embed"></iframe>
<a class="small d-inline-block mt-2" href="https://www.google.com/maps/search/?api=1&amp;query={{urlencode($coordinates)}}" target="_blank" rel="noopener noreferrer">Open in Google Maps &nearr;</a>
</div>
@endif
</div>
@if(filled($propertyPlan->property_notes))<div class="mt-3" style="white-space:pre-wrap;overflow-wrap:anywhere">{!! \App\Support\FormattedText::linkedPlainText($propertyPlan->property_notes) !!}</div>@endif
@if(!$propertyPlan->hasPropertyDetails())<p class="text-muted small mb-0">Add a map pin, photos, or directions to show this card in the client portal.</p>@endif
</div>
@once
@push('scripts')
<script>
document.querySelectorAll('[data-property-gallery]').forEach(modal => {
 modal.addEventListener('show.bs.modal', event => {
  const carousel = modal.querySelector('.carousel');
  bootstrap.Carousel.getOrCreateInstance(carousel, {interval:false}).to(Number(event.relatedTarget?.dataset.propertySlide || 0));
 });
});
</script>
@endpush
@endonce
@endif
