@extends('layouts.app')
@section('title','Payment plans | LandPay')
@section('body_class','admin-page')
@section('content')
<section class="admin-section"><div class="container"><div class="d-flex justify-content-between"><h1>Payment plans</h1><a class="btn btn-sun" href="{{route('admin.plans.create')}}">New plan</a></div><table class="table bg-white mt-4"><tr><th>Plan</th><th>Status</th><th>Monthly</th><th></th></tr>@forelse($plans as $plan)<tr><td>{{$plan->title}}<br><small>{{$plan->plan_number}}</small></td><td>{{$plan->status}}</td><td>{{\App\Support\Money::format($plan->customary_monthly_payment)}}</td><td><a href="{{route('admin.plans.show',$plan)}}">View</a></td></tr>@empty<tr><td colspan="4">No plans yet.</td></tr>@endforelse</table>{{$plans->links()}}</div></section>
@endsection
