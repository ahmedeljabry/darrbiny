@extends('admin.layouts.app')
@section('title','Notifications')
@section('content')
<div class="card">
  <div class="card-header"><h5 class="mb-0">Send Notification</h5></div>
  <div class="card-body">
    <form method="post" action="#">@csrf
      <div class="row g-3">
        <div class="col-md-3">
          <label class="form-label">Audience</label>
          <select class="form-select" name="audience">
            <option value="all">All</option>
            <option value="trainers">Trainers</option>
            <option value="students">Students</option>
          </select>
        </div>
        <div class="col-md-9">
          <label class="form-label">Message</label>
          <input class="form-control" name="message" placeholder="Your message">
        </div>
        <div class="col-12"><button class="btn btn-primary">Send</button></div>
      </div>
    </form>
  </div>
</div>
@endsection

