@extends('admin.layouts.app')
@section('title', 'الخطط')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item active" aria-current="page">الخطط</li>
  </ol>
</nav>

<div class="row g-6">
    <div class="col-12">
    <div class="card border-0 shadow-sm h-100">
            <div class="card-header border-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="avatar-initial rounded bg-label-primary">
                    <i class="icon-base ti tabler-file-check"></i>
                  </span>
                  <div>
                    <h5 class="mb-0">قائمة الخطط</h5>
                    <small class="text-body-secondary">إدارة جميع الخطط والدورات</small>
                  </div>
                </div>
                <div class="d-flex align-items-end gap-2 flex-wrap">
                    <form method="get" class="d-flex align-items-end gap-2 flex-wrap">
                        <div>
                            <label class="form-label">بحث</label>
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="ابحث بالعنوان أو الوصف">
                        </div>
                        <div>
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select select2">
                                <option value="">الكل</option>
                                <option value="active" @selected(request('status')==='active')>نشطة</option>
                                <option value="inactive" @selected(request('status')==='inactive')>غير نشطة</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">الدولة</label>
                            <select name="country_id" class="form-select select2" style="min-width:180px">
                                <option value="">الكل</option>
                                @foreach($countries as $c)
                                  <option value="{{ $c->id }}" @selected(request('country_id')===$c->id)>{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label">المدينة</label>
                            <select name="city_id" class="form-select select2" style="min-width:180px">
                                <option value="">الكل</option>
                                @foreach($cities as $city)
                                  <option value="{{ $city->id }}" @selected(request('city_id')===$city->id)>{{ $city->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex gap-2 align-items-end">
                            <button class="btn btn-outline-secondary">تصفية</button>
                            <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-dark">إعادة تعيين</a>
                        </div>
                    </form>
                    <a class="btn btn-primary" href="{{ route('admin.plans.create') }}">إضافة خطة</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th><i class="icon-base ti tabler-file-text me-1"></i> العنوان</th>
                            <th><i class="icon-base ti tabler-clock me-1"></i> الساعات</th>
                            <th><i class="icon-base ti tabler-calendar me-1"></i> الجلسات</th>
                            <th><i class="icon-base ti tabler-currency-dollar me-1"></i> السعر الأدنى</th>
                            <th><i class="icon-base ti tabler-wallet me-1"></i> الدفعة</th>
                            <th><i class="icon-base ti tabler-info-circle me-1"></i> الحالة</th>
                            <th class="text-center"><i class="icon-base ti tabler-settings me-1"></i> إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($plans as $plan)
                            <tr>
                                <td>{{ $plan->title }}</td>
                                <td>{{ $plan->hours_count }}</td>
                                <td>{{ $plan->session_count }}</td>
                                <td>{{ number_format((float) $plan->price_min, 2) }}</td>
                                <td>{{ $plan->deposit_amount ? number_format((float) $plan->deposit_amount, 2) : '-' }}</td>
                                <td>
                                    <span class="badge bg-label-{{ $plan->is_active ? 'success' : 'secondary' }}">{{ $plan->is_active ? 'نشطة' : 'غير نشطة' }}</span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn p-0 dropdown-toggle hide-arrow"
                                            data-bs-toggle="dropdown">
                                            <i class="icon-base ti tabler-dots-vertical"></i>
                                        </button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item" href="{{ route('admin.plans.edit', $plan->id) }}"><i class="icon-base ti tabler-pencil me-1"></i> تعديل</a>
                                            <a class="dropdown-item" href="{{ route('admin.plans.schedule.index', $plan->id) }}"><i class="icon-base ti tabler-calendar me-1"></i> جدول المتابعة</a>
                                            <form method="post" action="{{ route('admin.plans.destroy', $plan->id) }}" data-confirm="delete">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="icon-base ti tabler-trash me-1"></i> حذف
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">{{ $plans->links() }}</div>
        </div>
    </div>
</div>
@endsection
