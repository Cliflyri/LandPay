@if($client)<a href="{{route('admin.clients.show',$client)}}">{{$client->organization_name ?: trim($client->first_name.' '.$client->last_name)}}</a>@else Not assigned @endif
@if($plan)<small class="d-block text-muted"><a href="{{route('admin.plans.show',$plan)}}">{{$plan->plan_number}}</a></small>@endif
