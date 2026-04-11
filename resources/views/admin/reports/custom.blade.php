@extends('admin.layouts.app')
@section('title', $title)

@php
  $supportsExcel = $supportsExcel ?? false;
  $filters = $filters ?? [];
  $filterFields = [];

  if (array_key_exists('name', $filters)) {
    $filterFields[] = ['name' => 'name', 'label' => 'الاسم', 'placeholder' => 'ابحث بالاسم', 'col' => 'col-xl-3 col-md-4'];
  }

  if (array_key_exists('phone', $filters)) {
    $filterFields[] = ['name' => 'phone', 'label' => 'رقم الجوال', 'placeholder' => 'ابحث برقم الجوال', 'col' => 'col-xl-3 col-md-4'];
  }

  if (array_key_exists('date_from', $filters)) {
    $filterFields[] = ['name' => 'date_from', 'label' => 'من تاريخ', 'type' => 'date', 'col' => 'col-xl-2 col-md-4'];
  }

  if (array_key_exists('date_to', $filters)) {
    $filterFields[] = ['name' => 'date_to', 'label' => 'إلى تاريخ', 'type' => 'date', 'col' => 'col-xl-2 col-md-4'];
  }

  $subtitle = match (true) {
    str_contains($title, 'المكتملة') => 'تقرير تشغيلي يومي يركز على المبالغ المستحقة وحركة إغلاق الدورات المكتملة.',
    str_contains($title, 'النشطة') => 'متابعة سريعة للطلبات الجارية وقيمها وتواريخ بدايتها.',
    str_contains($title, 'عروض الأسعار') => 'قائمة الطلبات التي تحتاج متابعة من جهة العروض والتخصيص.',
    str_contains($title, 'الإنجاز اليومي') => 'مراجعة الحالات المرفوضة مع إمكان البحث بالاسم أو الجوال أو النطاق الزمني.',
    str_contains($title, 'المحافظ') => 'رؤية مباشرة للأرصدة الحالية في المحافظ مع دعم التصدير.',
    str_contains($title, 'نقاط الإحالة') => 'التقرير يعرض النقاط المكتسبة من التسجيلات الفعلية وليس رصيد المحفظة.',
    str_contains($title, 'المحفظة') => 'استعراض عمليات الدفع الناجحة التي تمت عبر المحفظة.',
    default => 'تقرير إداري مُعاد تصميمه بواجهة أوضح وجدول أسهل في المراجعة والتصدير.',
  };

  $tone = match (true) {
    str_contains($title, 'رفض') => 'danger',
    str_contains($title, 'النشطة') => 'primary',
    str_contains($title, 'المحافظ') => 'success',
    str_contains($title, 'نقاط') => 'secondary',
    str_contains($title, 'المكافآت') => 'info',
    default => 'primary',
  };

  $icon = match (true) {
    str_contains($title, 'المكتملة') => 'wallet',
    str_contains($title, 'النشطة') => 'activity',
    str_contains($title, 'عروض الأسعار') => 'clock-hour-4',
    str_contains($title, 'الإنجاز اليومي') => 'alert-circle',
    str_contains($title, 'المحافظ') => 'wallet',
    str_contains($title, 'نقاط') => 'stars',
    str_contains($title, 'المكافآت') => 'gift',
    default => 'table',
  };

  $actions = [];

  if ($supportsExcel) {
    $actions[] = ['label' => 'تصدير Excel', 'url' => request()->fullUrlWithQuery(['export' => 'excel']), 'class' => 'btn btn-success', 'icon' => 'file-excel'];
  }

  $actions[] = ['label' => 'تصدير CSV', 'url' => request()->fullUrlWithQuery(['export' => 'csv']), 'class' => 'btn btn-outline-primary', 'icon' => 'file-text'];
@endphp

@section('content')
  @include('admin.reports.partials.page-header', [
    'title' => $title,
    'subtitle' => $subtitle,
    'icon' => $icon,
    'tone' => $tone,
    'actions' => $actions,
    'stats' => [
      ['label' => 'عدد النتائج', 'value' => number_format(count($rows)), 'icon' => 'list-details'],
      ['label' => 'عدد الأعمدة', 'value' => number_format(count($headers)), 'icon' => 'table', 'tone' => 'info'],
      ['label' => 'الفلاتر النشطة', 'value' => number_format(collect($filters)->filter(fn ($value) => filled($value))->count()), 'icon' => 'adjustments-horizontal', 'tone' => 'warning'],
      ['label' => 'التصدير المتاح', 'value' => $supportsExcel ? 'Excel + CSV' : 'CSV', 'icon' => 'download', 'tone' => 'secondary'],
    ],
  ])

  <div class="card report-panel">
    <div class="card-body">
      @if(!empty($filterFields))
        @include('admin.reports.partials.filter-fields', [
          'fields' => $filterFields,
          'values' => $filters,
          'resetUrl' => url()->current(),
          'title' => 'فلترة التقرير',
          'subtitle' => 'يمكن الدمج بين الاسم والجوال والنطاق الزمني للوصول إلى النتائج الدقيقة.',
        ])
      @endif

      <div class="table-responsive">
        <table class="table table-hover report-table">
          <thead>
            <tr>
              @foreach($headers as $header)
                <th>{{ $header }}</th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @forelse($rows as $row)
              <tr>
                @foreach($row as $cell)
                  @php
                    $displayCell = $cell instanceof \DateTimeInterface
                      ? $cell->format('Y-m-d H:i')
                      : $cell;
                  @endphp
                  <td>{{ $displayCell }}</td>
                @endforeach
              </tr>
            @empty
              @include('admin.reports.partials.empty-state', ['colspan' => count($headers), 'icon' => 'database-off', 'message' => 'لا توجد بيانات مطابقة للتقرير الحالي'])
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection
