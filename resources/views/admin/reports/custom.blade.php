@extends('admin.layouts.app')
@section('title', $title)

@php
  $supportsExcel = $supportsExcel ?? false;
  $filters = $filters ?? [];
  $stats = $stats ?? [];
  $filterFields = [];
  $rejectionReasonColumnIndex = array_search('سبب الرفض', $headers, true);

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

  if (array_key_exists('country_id', $filters)) {
    $filterFields[] = [
      'name' => 'country_id',
      'label' => 'الدولة',
      'type' => 'select',
      'options' => $filters['country_options'] ?? [],
      'placeholder' => 'كل الدول',
      'col' => 'col-xl-2 col-md-4',
    ];
  }

  if (array_key_exists('status', $filters)) {
    $filterFields[] = [
      'name' => 'status',
      'label' => 'الحالة',
      'type' => 'select',
      'options' => $filters['status_options'] ?? [],
      'placeholder' => 'كل الحالات',
      'col' => 'col-xl-2 col-md-4',
    ];
  }

  $subtitle = match (true) {
    str_contains($title, 'المكتملة') => 'تقرير تشغيلي يركز على المبالغ المستحقة وحركة إغلاق الدورات المكتملة.',
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
    'stats' => $stats ?: [
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
                @foreach($row as $cellIndex => $cell)
                  @php
                    $displayCell = $cell instanceof \DateTimeInterface
                      ? $cell->format('Y-m-d H:i')
                      : $cell;
                  @endphp
                  <td>
                    @if($rejectionReasonColumnIndex !== false && $cellIndex === $rejectionReasonColumnIndex && filled($displayCell) && $displayCell !== '-')
                      <div class="d-flex flex-column gap-2">
                        <div class="text-truncate" style="max-width: 320px;" title="{{ $displayCell }}">{{ $displayCell }}</div>
                        <button
                          type="button"
                          class="btn btn-sm btn-outline-danger align-self-start js-show-rejection-reason"
                          data-rejection-reason="{{ $displayCell }}"
                        >
                          عرض السبب كاملًا
                        </button>
                      </div>
                    @else
                      {{ $displayCell }}
                    @endif
                  </td>
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

  @if($rejectionReasonColumnIndex !== false)
    <div class="modal fade" id="rejectionReasonModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">سبب الرفض</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
          </div>
          <div class="modal-body">
            <textarea id="rejectionReasonModalText" class="form-control" rows="10" readonly></textarea>
          </div>
        </div>
      </div>
    </div>

    @push('scripts')
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          const modalElement = document.getElementById('rejectionReasonModal');
          const modalText = document.getElementById('rejectionReasonModalText');

          if (!modalElement || !modalText || typeof bootstrap === 'undefined') {
            return;
          }

          const modal = new bootstrap.Modal(modalElement);

          document.querySelectorAll('.js-show-rejection-reason').forEach(function (button) {
            button.addEventListener('click', function () {
              modalText.value = button.getAttribute('data-rejection-reason') || '';
              modal.show();
            });
          });
        });
      </script>
    @endpush
  @endif
@endsection
