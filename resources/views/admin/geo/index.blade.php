@extends('admin.layouts.app')
@section('title','Geo: Countries & Cities')
@section('content')
<div class="row">
  <div class="col-lg-6">
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0">Countries</h5></div>
      <div class="table-responsive"><table class="table"><thead><tr><th>ISO</th><th>Name</th><th>Currency</th></tr></thead><tbody>@foreach($countries as $c)<tr><td>{{ $c->iso2 }}</td><td>{{ $c->name }}</td><td>{{ $c->currency }}</td></tr>@endforeach</tbody></table></div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card mb-4">
      <div class="card-header"><h5 class="mb-0">Cities</h5></div>
      <div class="table-responsive"><table class="table"><thead><tr><th>City</th><th>Country</th></tr></thead><tbody>@foreach($cities as $city)<tr><td>{{ $city->name }}</td><td>{{ optional($city->country)->name }}</td></tr>@endforeach</tbody></table></div>
      <div class="card-footer">{{ $cities->links() }}</div>
    </div>
  </div>
</div>
@endsection

