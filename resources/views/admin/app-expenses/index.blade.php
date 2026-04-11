@extends('admin.layouts.app')
@section('title', 'مصروفات التطبيق')

@php
  $typeBadgeClasses = [
    \App\Models\AppExpense::TYPE_OPERATING_EXPENSE => 'warning',
    \App\Models\AppExpense::TYPE_TRAINER_DUES => 'primary',
    \App\Models\AppExpense::TYPE_PACKAGE_REFUND => 'danger',
    \App\Models\AppExpense::TYPE_PROFIT_WITHDRAWAL => 'secondary',
  ];
@endphp

@section('content')
  <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
      <li class="breadcrumb-item active" aria-current="page">مصروفات التطبيق</li>
    </ol>
  </nav>

  @if (session('status'))
    <div class="alert alert-success alert-dismissible" role="alert">
      {{ session('status') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
    </div>
  @endif

  @if ($errors->any())
    <div class="alert alert-danger" role="alert">
      <ul class="mb-0">
        @foreach ($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-start gap-2">
            <span class="avatar-initial rounded bg-label-success">
              <i class="icon-base ti tabler-chart-line"></i>
            </span>
            <div>
              <div class="text-muted small">إجمالي الأرباح الخام</div>
              <h4 class="mb-1">{{ number_format($grossProfitMinor / 100, 2) }}</h4>
              <small class="text-muted">رسوم الحجز + رسوم الجدية + رسوم التطبيق على الدفع الكلي</small>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-start gap-2">
            <span class="avatar-initial rounded bg-label-danger">
              <i class="icon-base ti tabler-credit-card-off"></i>
            </span>
            <div>
              <div class="text-muted small">إجمالي المصروفات</div>
              <h4 class="mb-1">{{ number_format($totalExpensesMinor / 100, 2) }}</h4>
              <small class="text-muted">كل المصروفات المسجلة في هذه الصفحة</small>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-xl-4 col-md-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-start gap-2">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base ti tabler-wallet"></i>
            </span>
            <div>
              <div class="text-muted small">صافي الأرباح بعد المصروفات</div>
              <h4 class="mb-1">{{ number_format($netProfitMinor / 100, 2) }}</h4>
              <small class="text-muted">يتم تحديثه تلقائيًا بعد إضافة أي مصروف</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header border-0">
          <h5 class="mb-1">إضافة مصروف جديد</h5>
          <small class="text-muted">اختر النوع ثم أدخل المبلغ والملاحظات إن وجدت</small>
        </div>
        <div class="card-body">
          <form method="post" action="{{ route('admin.app-expenses.store') }}">
            @csrf
            <div class="mb-3">
              <label class="form-label">نوع المصروف</label>
              <select name="type" class="form-select" required>
                <option value="">اختر النوع</option>
                @foreach($typeOptions as $value => $label)
                  <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">المبلغ</label>
              <input type="number" name="amount" class="form-control" min="0.01" step="0.01" required placeholder="مثال: 150.00">
            </div>
            <div class="mb-3">
              <label class="form-label">ملاحظات</label>
              <textarea name="notes" class="form-control" rows="4" placeholder="سبب المصروف أو تفاصيل إضافية"></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100">
              <i class="icon-base ti tabler-plus me-1"></i>
              حفظ المصروف
            </button>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-8">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap gap-3">
          <div>
            <h5 class="mb-1">سجل المصروفات</h5>
            <small class="text-muted">يمكن للإدارة تعديل أو حذف المصروفات المسجلة</small>
          </div>
          <form method="get" class="d-flex gap-2 flex-wrap">
            <select name="type" class="form-select form-select-sm" style="min-width: 220px;">
              <option value="">كل الأنواع</option>
              @foreach($typeOptions as $value => $label)
                <option value="{{ $value }}" @selected(($filters['type'] ?? null) === $value)>{{ $label }}</option>
              @endforeach
            </select>
            <button class="btn btn-sm btn-primary" type="submit">
              <i class="icon-base ti tabler-filter me-1"></i> تصفية
            </button>
            <a href="{{ route('admin.app-expenses.index') }}" class="btn btn-sm btn-outline-secondary">
              <i class="icon-base ti tabler-rotate-2 me-1"></i> إعادة
            </a>
          </form>
        </div>
        <div class="card-body border-top pt-3">
          <div class="row g-2 mb-3">
            @foreach($typeOptions as $value => $label)
              <div class="col-md-6 col-xl-3">
                <div class="border rounded-3 p-3 bg-body-tertiary h-100">
                  <div class="small text-muted mb-1">{{ $label }}</div>
                  <div class="fw-semibold">{{ number_format(((int) ($categoryTotalsMinor[$value] ?? 0)) / 100, 2) }}</div>
                </div>
              </div>
            @endforeach
          </div>

          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>النوع</th>
                  <th>المبلغ</th>
                  <th>الملاحظات</th>
                  <th>أضيف بواسطة</th>
                  <th>التاريخ</th>
                  <th class="text-center">إجراءات</th>
                </tr>
              </thead>
              <tbody>
                @forelse($expenses as $expense)
                  <tr>
                    <td>
                      <span class="badge bg-label-{{ $typeBadgeClasses[$expense->type] ?? 'secondary' }}">
                        {{ $expense->typeLabel() }}
                      </span>
                    </td>
                    <td class="fw-semibold">{{ number_format($expense->amount_minor / 100, 2) }}</td>
                    <td>{{ $expense->notes ?: '—' }}</td>
                    <td>{{ $expense->creator?->name ?? '—' }}</td>
                    <td>{{ $expense->created_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    <td class="text-center">
                      <div class="d-flex justify-content-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editExpenseModal{{ $expense->id }}">
                          <i class="icon-base ti tabler-edit"></i>
                        </button>
                        <form method="post" action="{{ route('admin.app-expenses.destroy', $expense->id) }}" onsubmit="return confirm('هل أنت متأكد من حذف هذا المصروف؟');">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="icon-base ti tabler-trash"></i>
                          </button>
                        </form>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-center py-5 text-muted">لا توجد مصروفات مسجلة حتى الآن</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
        @if($expenses->hasPages())
          <div class="card-footer border-0">{{ $expenses->links() }}</div>
        @endif
      </div>
    </div>
  </div>

  @foreach($expenses as $expense)
    <div class="modal fade" id="editExpenseModal{{ $expense->id }}" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">تعديل المصروف</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
          </div>
          <form method="post" action="{{ route('admin.app-expenses.update', $expense->id) }}">
            @csrf
            @method('PUT')
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">نوع المصروف</label>
                <select name="type" class="form-select" required>
                  @foreach($typeOptions as $value => $label)
                    <option value="{{ $value }}" @selected($expense->type === $value)>{{ $label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">المبلغ</label>
                <input type="number" name="amount" class="form-control" min="0.01" step="0.01" value="{{ number_format($expense->amount_minor / 100, 2, '.', '') }}" required>
              </div>
              <div class="mb-3">
                <label class="form-label">ملاحظات</label>
                <textarea name="notes" class="form-control" rows="4">{{ $expense->notes }}</textarea>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
              <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endforeach
@endsection
