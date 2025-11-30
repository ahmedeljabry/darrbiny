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

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h5 class="mb-0">أرصدة المحافظ</h5>
  </div>
  <div class="table-responsive">
    <table class="table table-striped table-hover align-middle">
      <thead>
        <tr>
          <th>المستخدم</th>
          <th>رقم الجوال</th>
          <th>الرصيد</th>
          <th>إجراءات</th>
        </tr>
      </thead>
      <tbody>
        @foreach($users as $u)
          <tr>
            <td>{{ $u->name ?? $u->id }}</td>
            <td>{{ $u->phone_with_cc }}</td>
            <td><strong>{{ number_format($u->points_balance) }}</strong></td>
            <td>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editWalletModal{{ $u->id }}">
                  <i class="icon-base ti tabler-edit me-1"></i> تعديل
                </button>
                <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#addBalanceModal{{ $u->id }}">
                  <i class="icon-base ti tabler-plus me-1"></i> إضافة رصيد
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
              <input type="text" class="form-control" value="{{ number_format($u->points_balance) }}" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label">الرصيد الجديد</label>
              <input type="number" name="balance" class="form-control" value="{{ $u->points_balance }}" min="0" step="1" required>
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
              <input type="text" class="form-control" value="{{ number_format($u->points_balance) }}" readonly>
            </div>
            <div class="mb-3">
              <label class="form-label">المبلغ المراد إضافته</label>
              <input type="number" name="amount" class="form-control" min="1" step="1" required placeholder="أدخل المبلغ">
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

@endsection
