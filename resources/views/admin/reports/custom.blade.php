@extends('admin.layouts.app')
@section('title', $title)
@section('content')
@php
  $supportsExcel = $supportsExcel ?? false;
  $dateFilter = $dateFilter ?? null;
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
  @if($dateFilter !== null)
    <div class="card-body border-top pb-0">
      <form class="d-flex gap-2 align-items-end flex-wrap" method="get">
        <div>
          <label class="form-label mb-1">التاريخ</label>
          <input type="date" name="date" value="{{ request('date', $dateFilter) }}" class="form-control">
        </div>
        <button class="btn btn-primary">تصفية</button>
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
