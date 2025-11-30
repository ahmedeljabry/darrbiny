@extends('admin.layouts.app')
@section('title', $title)
@section('content')

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
    <div class="d-flex gap-2">
      <a class="btn btn-sm btn-outline-primary" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}">تصدير Excel (CSV)</a>
      <small class="text-body-secondary">استخدم تصدير CSV ثم افتحه في Excel/Sheets أو اطبع PDF من المتصفح.</small>
    </div>
  </div>
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
