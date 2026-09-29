<div class="dashboard-table-card"><div class="table-responsive"><table class="table dashboard-table align-middle mb-0"><thead><tr><th class="dashboard-actions-menu">Actions</th><th>Client</th><th>Document</th><th>Plan</th><th>Category</th><th>Shared</th><th>Client activity</th><th>Uploaded</th></tr></thead><tbody>
@forelse($documents as $document)
<tr>
<td class="dashboard-actions-menu"><div class="dropdown"><button class="btn btn-sm btn-light dashboard-menu-button" type="button" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false" aria-label="Actions for {{$document->name}}"><span aria-hidden="true">&#8942;</span></button><ul class="dropdown-menu dropdown-menu-end">@if(in_array($document->mime,['application/pdf','image/jpeg','image/png'],true))<li><button class="dropdown-item" type="button" data-document-preview="{{route('admin.documents.preview',$document)}}" data-document-name="{{$document->name}}">Preview</button></li>@endif<li><a class="dropdown-item" href="{{route('admin.documents.download',$document)}}">Download</a></li><li><form method="post" action="{{route('admin.documents.visibility',$document)}}">@csrf<button class="dropdown-item">{{$document->visible_to_client ? 'Hide from client' : 'Share with client'}}</button></form></li><li><form method="post" action="{{route('admin.documents.archive',$document)}}">@csrf<button class="dropdown-item">{{$document->archived_at ? 'Restore' : 'Archive'}}</button></form></li><li><form method="post" action="{{route('admin.documents.destroy',$document)}}" onsubmit="return confirm('Permanently delete this document? This cannot be undone.')">@csrf @method('DELETE')<button class="dropdown-item text-danger">Delete permanently</button></form></li></ul></div></td>
<td><a class="dashboard-plan-link" href="{{route('admin.clients.show',$document->client)}}">{{$document->client->organization_name ?: trim($document->client->first_name.' '.$document->client->last_name)}}</a></td>
<td>@if(in_array($document->mime,['application/pdf','image/jpeg','image/png'],true))<button class="btn btn-link p-0 align-baseline" type="button" data-document-preview="{{route('admin.documents.preview',$document)}}" data-document-name="{{$document->name}}">{{$document->name}}</button>@else<a href="{{route('admin.documents.download',$document)}}">{{$document->name}}</a>@endif<div class="small text-muted">{{number_format($document->size/1024)}} KB @if($document->archived_at) &middot; Archived @endif</div></td>
<td>@if($document->paymentPlan)<a href="{{route('admin.plans.show',$document->paymentPlan)}}">{{$document->paymentPlan->plan_number}} &mdash; {{$document->paymentPlan->title}}</a>@else &mdash; @endif</td>
<td>{{str($document->category)->replace('_',' ')->title()}}</td>
<td>
    @if($document->visible_to_client)
        <span class="dashboard-status status-current fs-6 status-tight">
            <span aria-hidden="true">&#10003;</span> Yes
        </span>
    @else
        <span class="dashboard-status status-due fs-6">
            <span aria-hidden="true">&times;</span> No
        </span>
    @endif
</td>
<td><div class="small"><strong>Viewed:</strong> {{$document->client_viewed_at?->format('M j, Y g:i A') ?? 'Not yet'}}</div><div class="small"><strong>Downloaded:</strong> {{$document->client_downloaded_at?->format('M j, Y g:i A') ?? 'Not yet'}}</div></td>
<td>{{$document->created_at->format('M j, Y')}}<div class="small text-muted">{{$document->uploadedByClient ? 'Client' : 'Administrator'}}</div></td>
</tr>
@empty
<tr><td colspan="8" class="dashboard-empty">No documents found.</td></tr>
@endforelse
</tbody></table></div></div><div class="dashboard-pagination">{{$documents->links()}}</div>
