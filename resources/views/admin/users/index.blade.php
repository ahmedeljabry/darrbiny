@extends('admin.layouts.app')
@section('title','المستخدمون')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item active" aria-current="page">المستخدمون</li>
  </ol>
</nav>

<div class="row g-4 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading text-muted small">إجمالي المستخدمين</span>
            <div class="d-flex align-items-center my-2">
              <h3 class="mb-0 me-2 fw-bold">{{ number_format($totalUsers) }}</h3>
            </div>
            <small class="text-muted">جميع المستخدمين في النظام</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary" style="width: 48px; height: 48px;">
              <i class="icon-base ti tabler-users" style="font-size: 24px;"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading text-muted small">المستخدمون العاديون</span>
            <div class="d-flex align-items-center my-2">
              <h3 class="mb-0 me-2 fw-bold text-info">{{ number_format($normalUsersCount ?? 0) }}</h3>
            </div>
            <small class="text-muted">الطلاب والمستخدمون العاديون</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-info" style="width: 48px; height: 48px;">
              <i class="icon-base ti tabler-user" style="font-size: 24px;"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading text-muted small">المدربون</span>
            <div class="d-flex align-items-center my-2">
              <h3 class="mb-0 me-2 fw-bold text-success">{{ number_format($trainersCount) }}</h3>
            </div>
            <small class="text-muted">المدربون والكباتن</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success" style="width: 48px; height: 48px;">
              <i class="icon-base ti tabler-school" style="font-size: 24px;"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading text-muted small">المحظورون</span>
            <div class="d-flex align-items-center my-2">
              <h3 class="mb-0 me-2 fw-bold text-danger">{{ number_format($bannedCount) }}</h3>
            </div>
            <small class="text-muted">مجمد/محظور مؤقتاً</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-danger" style="width: 48px; height: 48px;">
              <i class="icon-base ti tabler-user-exclamation" style="font-size: 24px;"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

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
  <div class="card-header border-0 d-flex justify-content-between align-items-center flex-wrap gap-3 pb-3">
    <div class="d-flex align-items-center gap-3">
      <span class="avatar-initial rounded bg-label-primary" style="width: 48px; height: 48px;">
        <i class="icon-base ti tabler-users" style="font-size: 24px;"></i>
      </span>
      <div>
        <h5 class="mb-0 fw-bold">إدارة المستخدمين</h5>
        <small class="text-muted">عرض وإدارة جميع المستخدمين والمدربين</small>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <div id="bulkActionsContainer" class="d-none d-flex align-items-center gap-2">
        <span class="badge bg-label-primary" id="selectedCount">0</span>
        <div class="dropdown">
          <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="bulkActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="icon-base ti tabler-settings me-1"></i> إجراءات جماعية
          </button>
          <ul class="dropdown-menu" aria-labelledby="bulkActionsDropdown">
            <li>
              <button class="dropdown-item" type="button" onclick="bulkAction('ban')">
                <i class="icon-base ti tabler-ban me-2"></i> حظر المحددين
              </button>
            </li>
            <li>
              <button class="dropdown-item" type="button" onclick="bulkAction('unban')">
                <i class="icon-base ti tabler-user-check me-2"></i> إلغاء حظر المحددين
              </button>
            </li>
            <li>
              <button class="dropdown-item text-success" type="button" onclick="bulkAction('approve_trainers')">
                <i class="icon-base ti tabler-check me-2"></i> قبول المدربين المحددين
              </button>
            </li>
            <li>
              <button class="dropdown-item" type="button" onclick="bulkAction('delete')">
                <i class="icon-base ti tabler-trash me-2"></i> حذف المحددين
              </button>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <button class="dropdown-item text-danger" type="button" onclick="clearSelection()">
                <i class="icon-base ti tabler-x me-2"></i> إلغاء التحديد
              </button>
            </li>
          </ul>
        </div>
      </div>
      <div class="dropdown">
        <button class="btn btn-outline-success btn-sm dropdown-toggle" type="button" id="usersDropdown" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="icon-base ti tabler-user me-1"></i> المستخدمون
        </button>
        <ul class="dropdown-menu" aria-labelledby="usersDropdown">
          <li><a class="dropdown-item" href="{{ route('admin.users.index', ['role' => 'user', 'status' => 'active']) }}">
            <i class="icon-base ti tabler-user-check me-2"></i> المستخدمون النشطون
          </a></li>
          <li><a class="dropdown-item" href="{{ route('admin.users.index', ['role' => 'user', 'status' => 'banned']) }}">
            <i class="icon-base ti tabler-user-exclamation me-2"></i> المستخدمون المحظورون
          </a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="{{ route('admin.users.index', ['role' => 'user']) }}">
            <i class="icon-base ti tabler-users me-2"></i> جميع المستخدمين
          </a></li>
        </ul>
      </div>
      <div class="dropdown">
        <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" id="trainersDropdown" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="icon-base ti tabler-school me-1"></i> المدربون
        </button>
        <ul class="dropdown-menu" aria-labelledby="trainersDropdown">
          <li><a class="dropdown-item" href="{{ route('admin.users.index', ['role' => 'trainer', 'status' => 'active']) }}">
            <i class="icon-base ti tabler-user-check me-2"></i> المدربون النشطون
          </a></li>
          <li><a class="dropdown-item" href="{{ route('admin.users.index', ['role' => 'trainer', 'status' => 'pending_trainer']) }}">
            <i class="icon-base ti tabler-alert-circle me-2"></i> مطلوب تنشيط
          </a></li>
          <li><a class="dropdown-item" href="{{ route('admin.users.index', ['role' => 'trainer', 'status' => 'banned']) }}">
            <i class="icon-base ti tabler-user-exclamation me-2"></i> المدربون المحظورون
          </a></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="{{ route('admin.users.index', ['role' => 'trainer']) }}">
            <i class="icon-base ti tabler-users me-2"></i> جميع المدربين
          </a></li>
        </ul>
      </div>
      <a href="{{ route('admin.users.create') }}" class="btn btn-primary btn-sm">
        <i class="icon-base ti tabler-plus me-1"></i> إضافة مستخدم
      </a>
    </div>
  </div>
  <div class="card-body pt-0">
    <form method="get" class="row g-3 mb-4">
      <div class="col-md-3">
        <label class="form-label fw-semibold small">
          <i class="icon-base ti tabler-shield me-1"></i> الدور
        </label>
        <select name="role" class="form-select form-select-sm">
          <option value="">الكل</option>
          <option value="trainer" {{ ($role==='trainer') ? 'selected' : '' }}>مدرب</option>
          <option value="admin" {{ ($role==='admin') ? 'selected' : '' }}>مشرف</option>
          <option value="user" {{ ($role==='user') ? 'selected' : '' }}>مستخدم</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold small">
          <i class="icon-base ti tabler-info-circle me-1"></i> الحالة
        </label>
        <select name="status" class="form-select form-select-sm">
          <option value="">الكل</option>
          <option value="active" {{ ($status==='active') ? 'selected' : '' }}>نشط</option>
          <option value="banned" {{ ($status==='banned') ? 'selected' : '' }}>محظور</option>
          <option value="pending_trainer" {{ ($status==='pending_trainer' || $status === 'activation_required') ? 'selected' : '' }}>مطلوب تنشيط</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold small">
          <i class="icon-base ti tabler-search me-1"></i> بحث
        </label>
        <input type="text" name="search" class="form-control form-control-sm" value="{{ $s ?? '' }}" placeholder="اسم، بريد، هاتف">
      </div>
      <div class="col-md-2 d-flex align-items-end">
        <button class="btn btn-primary btn-sm w-100" type="submit">
          <i class="icon-base ti tabler-filter me-1"></i> تصفية
        </button>
      </div>
    </form>
  </div>
  <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 50px;" class="text-center">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" id="selectAll" title="تحديد الكل">
              </div>
            </th>
            <th style="width: 200px;"><i class="icon-base ti tabler-user me-1"></i> المستخدم</th>
            <th style="width: 200px;"><i class="icon-base ti tabler-mail me-1"></i> البريد</th>
            <th style="width: 150px;"><i class="icon-base ti tabler-phone me-1"></i> الهاتف</th>
            <th style="width: 150px;"><i class="icon-base ti tabler-shield me-1"></i> الأدوار</th>
            <th style="width: 120px;"><i class="icon-base ti tabler-info-circle me-1"></i> الحالة</th>
            <th style="width: 150px;" class="text-center"><i class="icon-base ti tabler-settings me-1"></i> إجراءات</th>
          </tr>
        </thead>
        <tbody>
          @forelse($users as $u)
            <tr>
              <td class="text-center">
                <div class="form-check">
                  <input class="form-check-input user-checkbox" type="checkbox" value="{{ $u->id }}" data-user-id="{{ $u->id }}">
                </div>
              </td>
              <td>
              <div class="d-flex align-items-center gap-2">
                @if($u->profile_picture_url)
                  <img src="{{ $u->profile_picture_url }}" alt="{{ $u->name }}" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                @else
                  <span class="avatar-initial rounded-circle bg-label-secondary" style="width: 32px; height: 32px; font-size: 14px;">
                    {{ substr($u->name ?? 'U', 0, 1) }}
                  </span>
                @endif
                <div>
                  <div class="fw-semibold">{{ $u->name ?? '-' }}</div>
                  <small class="text-muted">#{{ substr($u->id, 0, 8) }}</small>
                </div>
              </div>
            </td>
            <td>
              @if($u->email)
                <span>{{ $u->email }}</span>
              @else
                <span class="text-muted">-</span>
              @endif
            </td>
            <td>
              <span class="text-muted">{{ $u->phone_with_cc }}</span>
            </td>
            <td>
              @foreach($u->getRoleNames() as $r)
                @php
                  $roleKey = strtoupper((string) $r);
                  $roleStyle = match($roleKey) {
                    'TRAINER' => ['badge' => 'success', 'icon' => 'school'],
                    'ADMIN' => ['badge' => 'primary', 'icon' => 'shield-check'],
                    'USER' => ['badge' => 'info', 'icon' => 'user'],
                    default => ['badge' => 'secondary', 'icon' => 'shield'],
                  };
                @endphp
                <span class="badge bg-label-{{ $roleStyle['badge'] }} me-1 mb-1">
                  <i class="icon-base ti tabler-{{ $roleStyle['icon'] }} me-1"></i>{{ \App\Support\AccessLabels::role($r) }}
                </span>
              @endforeach
            </td>
            <td>
              @php
                $requiresActivation = $u->hasRole('TRAINER') && $u->trainerProfile?->pending_approval;
              @endphp

              @if($requiresActivation)
                <span class="badge bg-label-warning">
                  <i class="icon-base ti tabler-alert-circle me-1"></i>مطلوب تنشيط
                </span>
              @elseif($u->isBanned())
                <span class="badge bg-label-danger">
                  <i class="icon-base ti tabler-user-exclamation me-1"></i>محظور
                </span>
              @else
                <span class="badge bg-label-success">
                  <i class="icon-base ti tabler-user-check me-1"></i>نشط
                </span>
              @endif
            </td>
            <td>
              <div class="d-flex gap-1 justify-content-center">
                <a href="{{ route('admin.users.show', $u->id) }}" class="btn btn-sm btn-icon btn-outline-info" title="عرض">
                  <i class="icon-base ti tabler-eye"></i>
                </a>
                <a href="{{ route('admin.users.edit', $u->id) }}" class="btn btn-sm btn-icon btn-outline-primary" title="تعديل">
                  <i class="icon-base ti tabler-edit"></i>
                </a>
                @if($requiresActivation)
                  <form method="post" action="{{ route('admin.users.trainer-profile.approve', $u->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-icon btn-outline-success" title="تنشيط المدرب">
                      <i class="icon-base ti tabler-user-check"></i>
                    </button>
                  </form>
                @elseif(!$u->isBanned())
                  <button class="btn btn-sm btn-icon btn-outline-warning" data-bs-toggle="offcanvas" data-bs-target="#offcanvasBanUser" data-user-id="{{ $u->id }}" data-user-name="{{ $u->name ?? $u->email }}" title="حظر">
                    <i class="icon-base ti tabler-ban"></i>
                  </button>
                @else
                  <form method="post" action="{{ route('admin.users.unban', $u->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-icon btn-outline-success" title="إلغاء الحظر">
                      <i class="icon-base ti tabler-user-check"></i>
                    </button>
                  </form>
                @endif
                @if(auth()->id() !== $u->id && !$u->hasRole('ADMIN'))
                  <form method="post" action="{{ route('admin.users.force-destroy', $u->id) }}" class="d-inline" onsubmit="return confirm('سيتم حذف المستخدم وتحرير رقم الجوال مع الحفاظ على الحجوزات والمدفوعات والسجلات المالية السابقة. هل تريد المتابعة؟');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-icon btn-outline-danger" title="حذف نهائي">
                      <i class="icon-base ti tabler-trash-x"></i>
                    </button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="text-center py-5">
              <div class="d-flex flex-column align-items-center">
                <span class="avatar-initial rounded bg-label-secondary mb-3" style="width: 64px; height: 64px;">
                  <i class="icon-base ti tabler-users" style="font-size: 32px;"></i>
                </span>
                <p class="text-muted mb-0">لا توجد مستخدمين</p>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <form id="bulkActionForm" method="post" action="{{ route('admin.users.bulk-action') }}" class="d-none">
    @csrf
    <div id="bulkActionUserIds"></div>
    <input type="hidden" name="action" id="bulkActionInput">
  </form>
  @if($users->hasPages())
    <div class="card-footer border-top">
      {{ $users->withQueryString()->links() }}
    </div>
  @endif
</div>

<!-- Offcanvas: Ban User -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasBanUser" aria-labelledby="offcanvasBanUserLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasBanUserLabel" class="offcanvas-title">حظر المستخدم</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="إغلاق"></button>
  </div>
  <div class="offcanvas-body mx-0 flex-grow-0 p-6 h-100">
    <form id="banUserForm" class="pt-0" method="post">
      @csrf
      <div class="mb-6 form-control-validation">
        <label class="form-label">حتى تاريخ</label>
        <input type="datetime-local" name="until" class="form-control" required>
      </div>
      <div class="mb-6 form-control-validation">
        <label class="form-label">السبب (اختياري)</label>
        <input type="text" name="reason" class="form-control" maxlength="255">
      </div>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-danger">تأكيد الحظر</button>
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="offcanvas">إلغاء</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const offcanvas = document.getElementById('offcanvasBanUser');
  offcanvas.addEventListener('show.bs.offcanvas', function (event) {
    const button = event.relatedTarget;
    const userId = button.getAttribute('data-user-id');
    const form = document.getElementById('banUserForm');
    form.action = '{{ url('admin/users') }}/' + userId + '/ban';
  });

  // Bulk selection
  const selectAll = document.getElementById('selectAll');
  const checkboxes = document.querySelectorAll('.user-checkbox');
  const bulkActionsContainer = document.getElementById('bulkActionsContainer');
  const selectedCount = document.getElementById('selectedCount');

  function updateBulkActions() {
    const checked = document.querySelectorAll('.user-checkbox:checked');
    const count = checked.length;
    
    if (count > 0) {
      bulkActionsContainer.classList.remove('d-none');
      selectedCount.textContent = count + ' محدد';
    } else {
      bulkActionsContainer.classList.add('d-none');
    }
    
    // Update select all checkbox state
    if (checkboxes.length > 0) {
      selectAll.indeterminate = count > 0 && count < checkboxes.length;
      selectAll.checked = count === checkboxes.length;
    }
  }

  selectAll.addEventListener('change', function() {
    checkboxes.forEach(checkbox => {
      checkbox.checked = this.checked;
    });
    updateBulkActions();
  });

  checkboxes.forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkActions);
  });

  // Initialize
  updateBulkActions();
});

function clearSelection() {
  document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = false);
  document.getElementById('selectAll').checked = false;
  document.getElementById('bulkActionsContainer').classList.add('d-none');
}

function bulkAction(action) {
  const form = document.getElementById('bulkActionForm');
  const userIdsContainer = document.getElementById('bulkActionUserIds');
  const checked = document.querySelectorAll('.user-checkbox:checked');
  
  if (checked.length === 0) {
    alert('يرجى تحديد مستخدم واحد على الأقل');
    return;
  }

  let confirmMessage = '';
  let actionText = '';
  
  switch(action) {
    case 'ban':
      confirmMessage = `هل أنت متأكد من حظر ${checked.length} مستخدم؟`;
      actionText = 'حظر';
      break;
    case 'unban':
      confirmMessage = `هل أنت متأكد من إلغاء حظر ${checked.length} مستخدم؟`;
      actionText = 'إلغاء حظر';
      break;
    case 'approve_trainers':
      confirmMessage = `هل أنت متأكد من قبول ${checked.length} مدرب؟ سيتم تفعيل حساباتهم وتطبيق التعديلات.`;
      actionText = 'قبول المدربين';
      break;
    case 'delete':
      confirmMessage = `هل أنت متأكد من حذف ${checked.length} مستخدم؟ سيتم تحرير أرقام الجوال مع الحفاظ على السجلات المالية.`;
      actionText = 'حذف';
      break;
  }

  if (!confirm(confirmMessage)) {
    return;
  }

  userIdsContainer.innerHTML = '';
  checked.forEach((checkbox) => {
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'user_ids[]';
    input.value = checkbox.value;
    userIdsContainer.appendChild(input);
  });

  document.getElementById('bulkActionInput').value = action;
  form.submit();
}
</script>
@endsection
