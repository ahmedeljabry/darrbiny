@extends('admin.layouts.app')
@section('title','كل التقارير')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item active" aria-current="page">كل التقارير</li>
  </ol>
</nav>

@php
  $reports = [
    ['title' => 'المبيعات', 'desc' => 'المدفوعات الناجحة وإجمالي المبيعات', 'route' => 'admin.reports.sales', 'icon' => 'chart-line', 'color' => 'success'],
    ['title' => 'المدفوعات', 'desc' => 'جميع المدفوعات مع التصفية بالحالة والنوع', 'route' => 'admin.reports.payments', 'icon' => 'credit-card', 'color' => 'primary'],
    ['title' => 'الاشتراكات', 'desc' => 'اشتراكات المستخدمين وحالات الطلبات', 'route' => 'admin.reports.subscriptions', 'icon' => 'calendar-event', 'color' => 'info'],
    ['title' => 'مبيعات الباقات', 'desc' => 'مبيعات خطط التدريب ورسومها', 'route' => 'admin.reports.plan-sales', 'icon' => 'package', 'color' => 'info'],
    ['title' => 'رسوم التطبيق', 'desc' => 'مجموع رسوم التطبيق على المدفوعات المكتملة', 'route' => 'admin.reports.app-fees', 'icon' => 'percentage', 'color' => 'warning'],
    ['title' => 'ضريبة القيمة المضافة', 'desc' => 'ضريبة القيمة المضافة على المدفوعات المكتملة', 'route' => 'admin.reports.vat', 'icon' => 'receipt-tax', 'color' => 'danger'],
    ['title' => 'مستحقات المدربين', 'desc' => 'المبالغ المستحقة للكورسات المكتملة', 'route' => 'admin.reports.completed-payouts', 'icon' => 'wallet', 'color' => 'success'],
    ['title' => 'الكورسات النشطة', 'desc' => 'الطلبات التي ما زالت قيد التنفيذ', 'route' => 'admin.reports.active-courses', 'icon' => 'activity', 'color' => 'primary'],
    ['title' => 'بانتظار العروض', 'desc' => 'طلبات تحتاج عروض أسعار من المدربين', 'route' => 'admin.reports.awaiting-offers', 'icon' => 'clock-hour-4', 'color' => 'warning'],
    ['title' => 'رفض الإنجاز اليومي', 'desc' => 'التقدم اليومي المرفوض حسب التاريخ', 'route' => 'admin.reports.rejected-progress', 'icon' => 'x', 'color' => 'danger'],
    ['title' => 'أرصدة المحافظ', 'desc' => 'المستخدمون الذين لديهم أرصدة في المحفظة', 'route' => 'admin.reports.wallet-balances', 'icon' => 'wallet', 'color' => 'success'],
    ['title' => 'النقاط', 'desc' => 'رصيد النقاط لكل مستخدم أو مدرب', 'route' => 'admin.reports.points-balances', 'icon' => 'stars', 'color' => 'secondary'],
    ['title' => 'استبدال المكافآت', 'desc' => 'طلبات استبدال النقاط بالمكافآت', 'route' => 'admin.reports.reward-redemptions', 'icon' => 'gift', 'color' => 'info'],
    ['title' => 'الدفع عبر المحفظة', 'desc' => 'العمليات المدفوعة باستخدام المحفظة', 'route' => 'admin.reports.wallet-payments', 'icon' => 'credit-card-pay', 'color' => 'primary'],
  ];
@endphp

<div class="card mb-4 border-0 shadow-sm">
  <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h5 class="mb-0">مركز التقارير</h5>
    <small class="text-body-secondary">اختر التقرير المطلوب مباشرة من هنا</small>
  </div>
  <div class="card-body">
    <div class="row g-3">
      @foreach($reports as $report)
        <div class="col-xl-3 col-lg-4 col-md-6 col-12">
          <a href="{{ route($report['route']) }}" class="text-decoration-none">
            <div class="card h-100 border-0 shadow-sm report-card">
              <div class="card-body">
                <div class="d-flex align-items-center gap-2 mb-2">
                  <span class="avatar-initial rounded bg-label-{{ $report['color'] }}">
                    <i class="icon-base ti tabler-{{ $report['icon'] }}"></i>
                  </span>
                  <h6 class="mb-0 text-dark">{{ $report['title'] }}</h6>
                </div>
                <p class="mb-0 text-body-secondary small">{{ $report['desc'] }}</p>
              </div>
            </div>
          </a>
        </div>
      @endforeach
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h6 class="mb-0">آخر المدفوعات</h6>
    <form class="d-flex gap-2 flex-wrap" method="get">
      <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm">
      <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm">
      <button class="btn btn-sm btn-primary">تصفية</button>
    </form>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>المعرف</th>
          <th>المستخدم</th>
          <th>المبلغ</th>
          <th>النوع</th>
          <th>الحالة</th>
          <th>التاريخ</th>
        </tr>
      </thead>
      <tbody>
        @forelse($payments as $p)
          <tr>
            <td><code class="text-primary">{{ substr($p->id, 0, 8) }}</code></td>
            <td>{{ $p->user?->name ?? 'غير معروف' }}</td>
            <td>{{ number_format($p->amount_minor/100,2) }} {{ $p->currency }}</td>
            <td>{{ $p->type }}</td>
            <td>{{ $p->status }}</td>
            <td>{{ $p->created_at?->format('Y-m-d H:i') }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="text-center text-body-secondary p-4">لا توجد بيانات</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

@push('styles')
<style>
  .report-card {
    transition: transform .15s ease, box-shadow .15s ease;
  }
  .report-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 .5rem 1.25rem rgba(47,43,61,.12) !important;
  }
</style>
@endpush
@endsection
