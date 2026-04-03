@extends('admin.layouts.app')
@section('title', $title)
@section('content')
@php
  $supportsExcel = $supportsExcel ?? false;
  $filters = $filters ?? [];
@endphp

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.reports.index') }}">التقارير</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
  </ol>
</nav>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h5 class="mb-0">{{ $title }}</h5>
    <div class="d-flex gap-2 align-items-center flex-wrap">
      @if($supportsExcel)
        <a class="btn btn-success" href="{{ request()->fullUrlWithQuery(['export' => 'excel']) }}">
          <i class="icon-base ti tabler-file-excel me-1"></i> تصدير Excel
        </a>
      @endif
      <a class="btn btn-sm btn-outline-primary" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">تصدير CSV</a>
    </div>
  </div>
  @if(!empty($filters))
    <div class="card-body border-top pb-0">
      <form class="row g-3 align-items-end" method="get">
        @if(array_key_exists('name', $filters))
          <div class="col-md-3">
            <label class="form-label mb-1">الاسم</label>
            <input type="text" name="name" value="{{ $filters['name'] }}" class="form-control" placeholder="ابحث بالاسم">
          </div>
        @endif
        @if(array_key_exists('phone', $filters))
          <div class="col-md-3">
            <label class="form-label mb-1">رقم الجوال</label>
            <input type="text" name="phone" value="{{ $filters['phone'] }}" class="form-control" placeholder="ابحث برقم الجوال">
          </div>
        @endif
        @if(array_key_exists('date_from', $filters))
          <div class="col-md-2">
            <label class="form-label mb-1">من</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control">
          </div>
        @endif
        @if(array_key_exists('date_to', $filters))
          <div class="col-md-2">
            <label class="form-label mb-1">إلى</label>
            <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control">
          </div>
        @endif
        <div class="col-md-2 d-flex gap-2">
          <button class="btn btn-primary flex-fill">تصفية</button>
          <a class="btn btn-outline-secondary flex-fill" href="{{ url()->current() }}">إعادة</a>
        </div>
      </form>
    </div>
  @endif
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table mb-0">
        <thead>
          <tr>
            @foreach($headers as $h)
              <th>{{ $h }}</th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $row)
            <tr>
              @foreach($row as $cell)
                <td>{{ $cell }}</td>
              @endforeach
            </tr>
          @empty
            <tr><td colspan="{{ count($headers) }}" class="text-center text-body-secondary p-4">لا توجد بيانات</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
