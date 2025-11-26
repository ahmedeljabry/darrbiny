@extends('admin.layouts.app')
@section('title','الاشتراكات')
@section('content')
@php
  $statusLabels = [
    'pending_payment' => 'بانتظار الدفع',
    'awaiting_offers' => 'بانتظار العروض',
    'offer_selected' => 'تم اختيار عرض',
    'paid' => 'مدفوع',
    'in_training' => 'جار التدريب',
    'completed' => 'مكتمل',
    'cancelled' => 'ملغى',
  ];
@endphp
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <h5 class="mb-0">اشتراكات المستخدمين</h5>
      <small class="text-body-secondary">عرض النشطة، المكتملة أو بانتظار العروض</small>
    </div>
    <form class="d-flex flex-wrap gap-2" method="get">
      <select name="scope" class="form-select">
        <option value="">جميع الحالات</option>
        <option value="active" @selected(($scope ?? request('scope'))==='active')>نشطة</option>
        <option value="completed" @selected(($scope ?? request('scope'))==='completed')>مكتملة</option>
        <option value="awaiting_offers" @selected(($scope ?? request('scope'))==='awaiting_offers')>بانتظار العروض</option>
      </select>
      <select name="status" class="form-select">
        <option value="">حالة محددة</option>
        @foreach($statusLabels as $key => $label)
          <option value="{{ $key }}" @selected(($status ?? request('status'))===$key)>{{ $label }}</option>
        @endforeach
      </select>
      <button class="btn btn-primary">تصفية</button>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table">
      <thead><tr><th>المعرف</th><th>المستخدم</th><th>الخطة</th><th>الحالة</th><th>تاريخ البدء</th></tr></thead>
      <tbody>
        @foreach($subs as $r)
          <tr>
            <td>{{ $r->id }}</td>
            <td>{{ optional($r->user)->name ?? $r->user_id }}</td>
            <td>{{ optional($r->plan)->title ?? $r->plan_id }}</td>
            <td><span class="badge bg-label-secondary">{{ $statusLabels[$r->status] ?? $r->status }}</span></td>
            <td>{{ optional($r->start_date)->toDateString() }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $subs->withQueryString()->links() }}</div>
</div>
@endsection
