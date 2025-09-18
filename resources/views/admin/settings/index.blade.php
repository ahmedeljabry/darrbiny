@extends('admin.layouts.app')
@section('title','Settings')
@section('content')
<div class="row">
  <div class="col-lg-6">
    <div class="card mb-4">
      <div class="card-header"><h6 class="mb-0">Brand</h6></div>
      <div class="card-body">
        <form method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">@csrf
          <div class="mb-3">
            <label class="form-label">Brand Name</label>
            <input class="form-control" name="brand_name" value="{{ $settings['brand.name'] ?? '' }}">
          </div>
          <div class="mb-3">
            <label class="form-label">Logo</label>
            <input type="file" name="logo" class="form-control">
            @if(!empty($settings['brand.logo_path']))
              <div class="mt-2"><img src="{{ Storage::disk(config('filesystems.default','public'))->url($settings['brand.logo_path']) }}" alt="logo" height="48"></div>
            @endif
          </div>
          <button class="btn btn-primary">Save</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card mb-4">
      <div class="card-header"><h6 class="mb-0">Payments: TAP</h6></div>
      <div class="card-body">
        <form method="post" action="{{ route('admin.settings.update') }}">@csrf
          <div class="mb-3">
            <label class="form-label">Public Key</label>
            <input class="form-control" name="tap_public_key" value="{{ $settings['payment.tap.public_key'] ?? '' }}">
          </div>
          <div class="mb-3">
            <label class="form-label">Secret Key</label>
            <input class="form-control" name="tap_secret_key" value="{{ $settings['payment.tap.secret_key'] ?? '' }}">
          </div>
          <div class="mb-3">
            <label class="form-label">Webhook Secret</label>
            <input class="form-control" name="tap_webhook_secret" value="{{ $settings['payment.tap.webhook_secret'] ?? '' }}">
          </div>
          <button class="btn btn-primary">Save</button>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

