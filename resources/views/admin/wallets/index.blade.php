@extends('admin.layouts.app')
@section('title','المحافظ')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item active" aria-current="page">المحافظ</li>
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

<div class="card border-0 shadow-sm">
  <div class="card-header border-0 d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-2">
      <span class="avatar-initial rounded bg-label-primary">
        <i class="icon-base ti tabler-wallet"></i>
      </span>
      <div>
        <h5 class="mb-0">أرصدة المحافظ</h5>
        <small class="text-body-secondary">إدارة أرصدة المستخدمين</small>
      </div>
    </div>
  </div>
  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th><i class="icon-base ti tabler-user me-1"></i> المستخدم</th>
          <th><i class="icon-base ti tabler-phone me-1"></i> رقم الجوال</th>
          <th><i class="icon-base ti tabler-coins me-1"></i> الرصيد</th>
          <th class="text-center"><i class="icon-base ti tabler-settings me-1"></i> إجراءات</th>
        </tr>
      </thead>
      <tbody>
        @foreach($users as $u)
          <tr>
            <td>{{ $u->name ?? $u->id }}</td>
            <td>{{ $u->phone_with_cc }}</td>
            <td><strong>{{ number_format($u->points_balance, 2) }}</strong></td>
            <td>
              <div class="d-flex gap-2 justify-content-center">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editWalletModal{{ $u->id }}" title="تعديل الرصيد">
                  <i class="icon-base ti tabler-edit"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#addBalanceModal{{ $u->id }}" title="إضافة رصيد">
                  <i class="icon-base ti tabler-plus"></i>
                </button>
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="card-footer">{{ $users->links() }}</div>
</div>

<!-- Edit Wallet Modals -->
@foreach($users as $u)
  <!-- Edit Wallet Modal -->
  <div class="modal fade" id="editWalletModal{{ $u->id }}" tabindex="-1" aria-labelledby="editWalletModalLabel{{ $u->id }}" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editWalletModalLabel{{ $u->id }}">تعديل محفظة {{ $u->name ?? $u->id }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
        </div>
        <form method="post" action="{{ route('admin.wallets.update', $u->id) }}">
          @csrf
          @method('PUT')
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">الرصيد الحالي</label>
              <input type="text" class="form-control" value="{{ number_format($u->points_balance, 2) }}" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label">الرصيد الجديد</label>
              <input type="number" name="balance" class="form-control" value="{{ number_format($u->points_balance, 2, '.', '') }}" min="0" step="0.01" required>
            </div>
            <div class="mb-3">
              <label class="form-label">ملاحظة (اختياري)</label>
              <textarea name="notes" class="form-control" rows="3" placeholder="سبب التعديل"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
            <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Add Balance Modal -->
  <div class="modal fade" id="addBalanceModal{{ $u->id }}" tabindex="-1" aria-labelledby="addBalanceModalLabel{{ $u->id }}" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addBalanceModalLabel{{ $u->id }}">إضافة رصيد إلى {{ $u->name ?? $u->id }}</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
        </div>
        <form method="post" action="{{ route('admin.wallets.store') }}">
          @csrf
          <input type="hidden" name="user_id" value="{{ $u->id }}">
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">الرصيد الحالي</label>
              <input type="text" class="form-control" value="{{ number_format($u->points_balance, 2) }}" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label">إجمالي المبلغ المراد إضافته</label>
              <input
                type="number"
                name="amount"
                class="form-control js-wallet-amount"
                min="0.01"
                step="0.01"
                required
                placeholder="أدخل إجمالي المبلغ"
                data-current-balance="{{ number_format($u->points_balance, 2, '.', '') }}"
                data-app-fee-percent="{{ $appFeePercent }}"
                data-preview-target="walletPreview{{ $u->id }}"
              >
              <small class="text-muted d-block mt-1">لإضافة رصيد يدوي. إذا أدخلت رقم كورس فسيتم اعتماد صافي المدرب من تقرير المستحقات بدلًا من هذا الرقم.</small>
            </div>
            <div class="mb-3">
              <div class="form-check form-switch">
                <input
                  class="form-check-input js-apply-app-fee"
                  type="checkbox"
                  role="switch"
                  id="applyAppFee{{ $u->id }}"
                  name="apply_app_fee"
                  value="1"
                  checked
                  data-preview-target="walletPreview{{ $u->id }}"
                >
                <label class="form-check-label" for="applyAppFee{{ $u->id }}">
                  خصم رسوم التطبيق تلقائيًا ({{ rtrim(rtrim(number_format($appFeePercent, 2, '.', ''), '0'), '.') }}%)
                </label>
              </div>
              <small class="text-muted d-block mt-1">هذا الخيار للرصيد اليدوي فقط. عند إدخال رقم الكورس سيتم استخدام صافي المدرب المحسوب مسبقًا.</small>
            </div>
            <div class="mb-3">
              <label class="form-label">رقم الكورس (اختياري)</label>
              <input
                type="text"
                name="course_reference"
                class="form-control js-course-reference"
                maxlength="100"
                placeholder="مثال: #00005001"
                data-preview-target="walletPreview{{ $u->id }}"
              >
              <small class="text-muted d-block mt-1">استخدم رقم الطلب الظاهر في صفحة الحجوزات. عند إدخاله سيتم سحب صافي المدرب من الكورس المكتمل كما يظهر في تقرير مستحقات المدربين، وسيتم حفظ الملاحظة تلقائيًا.</small>
            </div>
            <div class="alert alert-info d-none" id="walletPreview{{ $u->id }}">
              <div class="d-flex justify-content-between">
                <span>رسوم التطبيق ({{ rtrim(rtrim(number_format($appFeePercent, 2, '.', ''), '0'), '.') }}%)</span>
                <strong class="js-preview-fee">0</strong>
              </div>
              <div class="d-flex justify-content-between mt-2">
                <span>الصافي المضاف للمحفظة</span>
                <strong class="js-preview-net">0</strong>
              </div>
              <div class="d-flex justify-content-between mt-2">
                <span>الرصيد بعد الإضافة</span>
                <strong class="js-preview-balance">{{ number_format($u->points_balance, 2) }}</strong>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">ملاحظة (اختياري)</label>
              <textarea name="notes" class="form-control" rows="3" placeholder="سبب الإضافة"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
            <button type="submit" class="btn btn-success">إضافة الرصيد</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endforeach

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const amountInputs = document.querySelectorAll('.js-wallet-amount');

    amountInputs.forEach(function (amountInput) {
      const previewId = amountInput.dataset.previewTarget;
      const preview = document.getElementById(previewId);

      if (!preview) {
        return;
      }

      const modalBody = amountInput.closest('.modal-body');
      const courseReferenceInput = modalBody?.querySelector('.js-course-reference');
      const applyAppFeeInput = modalBody?.querySelector('.js-apply-app-fee');
      const feeNode = preview.querySelector('.js-preview-fee');
      const netNode = preview.querySelector('.js-preview-net');
      const balanceNode = preview.querySelector('.js-preview-balance');
      const currentBalanceMinor = Math.round((parseFloat(amountInput.dataset.currentBalance || '0') || 0) * 100);
      const appFeePercent = parseFloat(amountInput.dataset.appFeePercent || '0') || 0;
      const formatAmount = function (amountMinor) {
        return (amountMinor / 100).toLocaleString(undefined, {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        });
      };

      const updatePreview = function () {
        const grossAmountMinor = Math.round((parseFloat(amountInput.value || '0') || 0) * 100);
        const hasCourseReference = (courseReferenceInput?.value || '').trim() !== '';
        const shouldApplyAppFee = hasCourseReference || Boolean(applyAppFeeInput?.checked);
        const feeAmountMinor = shouldApplyAppFee ? Math.min(Math.round(grossAmountMinor * (appFeePercent / 100)), grossAmountMinor) : 0;
        const netAmountMinor = Math.max(0, grossAmountMinor - feeAmountMinor);
        const nextBalanceMinor = currentBalanceMinor + netAmountMinor;

        if (grossAmountMinor <= 0) {
          preview.classList.add('d-none');
          return;
        }

        feeNode.textContent = formatAmount(feeAmountMinor);
        netNode.textContent = formatAmount(netAmountMinor);
        balanceNode.textContent = formatAmount(nextBalanceMinor);
        preview.classList.remove('d-none');
      };

      amountInput.addEventListener('input', updatePreview);
      courseReferenceInput?.addEventListener('input', updatePreview);
      applyAppFeeInput?.addEventListener('change', updatePreview);
      updatePreview();
    });
  });
</script>

@endsection
