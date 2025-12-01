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

<div class="row g-6 mb-6">
  <div class="col-sm-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">إجمالي المستخدمين</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $totalUsers }}</h4>
            </div>
            <small class="mb-0">كل المستخدمين</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-primary">
              <i class="icon-base ti tabler-users icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">المدربون</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $trainersCount }}</h4>
            </div>
            <small class="mb-0">Role: TRAINER</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-success">
              <i class="icon-base ti tabler-school icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">محظورون</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $bannedCount }}</h4>
            </div>
            <small class="mb-0">مجمد/محظور مؤقتاً</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-danger">
              <i class="icon-base ti tabler-user-exclamation icon-26px"></i>
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-start justify-content-between">
          <div class="content-left">
            <span class="text-heading">نشطون</span>
            <div class="d-flex align-items-center my-1">
              <h4 class="mb-0 me-2">{{ $activeCount }}</h4>
            </div>
            <small class="mb-0">غير محظورين</small>
          </div>
          <div class="avatar">
            <span class="avatar-initial rounded bg-label-info">
              <i class="icon-base ti tabler-user-check icon-26px"></i>
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
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
  <div class="card-header border-0 d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div class="d-flex align-items-center gap-2">
      <span class="avatar-initial rounded bg-label-primary">
        <i class="icon-base ti tabler-users"></i>
      </span>
      <div>
        <h5 class="mb-0">المستخدمون</h5>
        <small class="text-muted">إدارة جميع المستخدمين والمدربين</small>
      </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <div class="dropdown">
        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="trainersDropdown" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="icon-base ti tabler-school me-1"></i> المدربون
        </button>
        <ul class="dropdown-menu" aria-labelledby="trainersDropdown">
          <li><a class="dropdown-item" href="{{ route('admin.users.index', ['role' => 'trainer', 'status' => 'active']) }}">
            <i class="icon-base ti tabler-user-check me-2"></i> المدربون النشطون
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
      <div class="dropdown">
        <button class="btn btn-outline-success dropdown-toggle" type="button" id="usersDropdown" data-bs-toggle="dropdown" aria-expanded="false">
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
      <a href="{{ route('admin.users.create') }}" class="btn btn-primary">
        <i class="icon-base ti tabler-plus me-1"></i> إضافة مستخدم
      </a>
    </div>
  </div>
  <div class="card-body">
    <form method="get" class="row g-3 mb-4">
      <div class="col-md-3">
        <label class="form-label fw-semibold">
          <i class="icon-base ti tabler-shield me-1"></i> الدور
        </label>
        <div class="input-group input-group-merge">
          <span class="input-group-text"><i class="icon-base ti tabler-shield"></i></span>
          <select name="role" class="form-select select2">
            <option value="">الكل</option>
            <option value="trainer" {{ ($role==='trainer') ? 'selected' : '' }}>مدرب</option>
            <option value="admin" {{ ($role==='admin') ? 'selected' : '' }}>مشرف</option>
            <option value="user" {{ ($role==='user') ? 'selected' : '' }}>مستخدم</option>
          </select>
        </div>
      </div>
      <div class="col-md-3">
        <label class="form-label fw-semibold">
          <i class="icon-base ti tabler-info-circle me-1"></i> الحالة
        </label>
        <div class="input-group input-group-merge">
          <span class="input-group-text"><i class="icon-base ti tabler-info-circle"></i></span>
          <select name="status" class="form-select select2">
            <option value="">الكل</option>
            <option value="active" {{ ($status==='active') ? 'selected' : '' }}>نشط</option>
            <option value="banned" {{ ($status==='banned') ? 'selected' : '' }}>محظور</option>
          </select>
        </div>
      </div>
      <div class="col-md-4">
        <label class="form-label fw-semibold">
          <i class="icon-base ti tabler-search me-1"></i> بحث
        </label>
        <div class="input-group input-group-merge">
          <span class="input-group-text"><i class="icon-base ti tabler-search"></i></span>
          <input type="text" name="search" class="form-control" value="{{ $s ?? '' }}" placeholder="اسم، بريد، هاتف">
        </div>
      </div>
      <div class="col-md-2 d-grid">
        <label class="form-label d-none d-md-block">&nbsp;</label>
        <button class="btn btn-primary" type="submit">
          <i class="icon-base ti tabler-filter me-1"></i> تطبيق
        </button>
      </div>
    </form>
  </div>
  <div class="card-datatable table-responsive p-3">
    <table class="table table-hover align-middle" id="usersTable">
      <thead class="table-light border-top">
        <tr>
          <th><i class="icon-base ti tabler-user me-1"></i> المستخدم</th>
          <th><i class="icon-base ti tabler-mail me-1"></i> البريد</th>
          <th><i class="icon-base ti tabler-phone me-1"></i> الهاتف</th>
          <th><i class="icon-base ti tabler-shield me-1"></i> الأدوار</th>
          <th><i class="icon-base ti tabler-info-circle me-1"></i> الحالة</th>
          <th class="text-center"><i class="icon-base ti tabler-settings me-1"></i> إجراءات</th>
        </tr>
      </thead>
      <tbody>
        @foreach($users as $u)
          <tr>
            <td>{{ $u->name ?? '-' }}</td>
            <td>{{ $u->email ?? '-' }}</td>
            <td>{{ $u->phone_with_cc }}</td>
            <td>
              @foreach($u->getRoleNames() as $r)
                @if($r === 'TRAINER')
                  <span class="badge bg-label-success me-1">
                    <i class="icon-base ti tabler-school me-1"></i>{{ $r }}
                  </span>
                @elseif($r === 'ADMIN')
                  <span class="badge bg-label-primary me-1">
                    <i class="icon-base ti tabler-shield-check me-1"></i>{{ $r }}
                  </span>
                @else
                  <span class="badge bg-label-info me-1">
                    <i class="icon-base ti tabler-user me-1"></i>{{ $r }}
                  </span>
                @endif
              @endforeach
            </td>
            <td>
              @if($u->isBanned())
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
              <div class="d-flex gap-2 flex-wrap justify-content-center">
                <a href="{{ route('admin.users.show', $u->id) }}" class="btn btn-sm btn-outline-info" title="عرض">
                  <i class="icon-base ti tabler-eye"></i>
                </a>
                <a href="{{ route('admin.users.edit', $u->id) }}" class="btn btn-sm btn-outline-primary" title="تعديل">
                  <i class="icon-base ti tabler-edit"></i>
                </a>
                @if(!$u->isBanned())
                  <button class="btn btn-sm btn-outline-warning" data-bs-toggle="offcanvas" data-bs-target="#offcanvasBanUser" data-user-id="{{ $u->id }}" data-user-name="{{ $u->name ?? $u->email }}" title="حظر">
                    <i class="icon-base ti tabler-ban"></i>
                  </button>
                @else
                  <form method="post" action="{{ route('admin.users.unban', $u->id) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-success" title="إلغاء الحظر">
                      <i class="icon-base ti tabler-user-check"></i>
                    </button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <div class="mt-3">{{ $users->links() }}</div>
  </div>
</div>

<!-- Offcanvas: Ban User -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasBanUser" aria-labelledby="offcanvasBanUserLabel">
  <div class="offcanvas-header border-bottom">
    <h5 id="offcanvasBanUserLabel" class="offcanvas-title">حظر المستخدم</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
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

  if (window.jQuery && jQuery.fn.DataTable) {
    jQuery('#usersTable').DataTable({
      order: [],
      pageLength: 10,
      language: {
        url: '',
      }
    });
  }
});
</script>
@endsection
