@extends('admin.layouts.app')
@section('title','المستخدمون')
@section('content')
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

<div class="card">
  <div class="card-header border-bottom d-flex justify-content-between align-items-center">
    <h5 class="card-title mb-0">التصفية</h5>
    <a href="{{ route('admin.users.create') }}" class="btn btn-primary">إضافة مستخدم</a>
    <form method="get" class="row pt-4 g-3 align-items-end">
      <div class="col-md-3">
        <label class="form-label">الدور</label>
        <select name="role" class="form-select">
          <option value="">الكل</option>
          <option value="trainer" {{ ($role==='trainer') ? 'selected' : '' }}>مدرب</option>
          <option value="admin" {{ ($role==='admin') ? 'selected' : '' }}>مشرف</option>
          <option value="user" {{ ($role==='user') ? 'selected' : '' }}>مستخدم</option>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">الحالة</label>
        <select name="status" class="form-select">
          <option value="">الكل</option>
          <option value="active" {{ ($status==='active') ? 'selected' : '' }}>نشط</option>
          <option value="banned" {{ ($status==='banned') ? 'selected' : '' }}>محظور</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">بحث</label>
        <input type="text" name="search" class="form-control" value="{{ $s ?? '' }}" placeholder="اسم، بريد، هاتف">
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-primary" type="submit">تطبيق</button>
      </div>
    </form>
  </div>
  <div class="card-datatable table-responsive p-3">
    <table class="table table-striped" id="usersTable">
      <thead class="border-top">
        <tr>
          <th>المستخدم</th>
          <th>البريد</th>
          <th>الهاتف</th>
          <th>الأدوار</th>
          <th>الحالة</th>
          <th>إجراءات</th>
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
                <span class="badge bg-label-dark me-1">{{ $r }}</span>
              @endforeach
            </td>
            <td>
              @if($u->isBanned())
                <span class="badge bg-label-danger">محظور</span>
              @else
                <span class="badge bg-label-success">نشط</span>
              @endif
            </td>
            <td class="d-flex gap-2 flex-wrap">
              <a href="{{ route('admin.users.show', $u->id) }}" class="btn btn-sm btn-outline-secondary">عرض</a>
              <a href="{{ route('admin.users.edit', $u->id) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
              @if(!$u->isBanned())
                <button class="btn btn-sm btn-outline-warning" data-bs-toggle="offcanvas" data-bs-target="#offcanvasBanUser" data-user-id="{{ $u->id }}" data-user-name="{{ $u->name ?? $u->email }}">
                  حظر
                </button>
              @else
                <form method="post" action="{{ route('admin.users.unban', $u->id) }}">
                  @csrf
                  <button type="submit" class="btn btn-sm btn-outline-success">إلغاء الحظر</button>
                </form>
              @endif
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
