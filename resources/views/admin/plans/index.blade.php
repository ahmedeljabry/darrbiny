@extends('admin.layouts.app')
@section('title', 'الخطط')
@section('content')
<div class="row g-6">
    <div class="col-12">
    <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h5 class="mb-0">قائمة الخطط</h5>
                <div class="d-flex align-items-end gap-2 flex-wrap">
                    <form method="get" class="d-flex align-items-end gap-2 flex-wrap">
                        <div>
                            <label class="form-label">بحث</label>
                            <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="ابحث بالعنوان أو الوصف">
                        </div>
                        <div>
                            <label class="form-label">الحالة</label>
                            <select name="status" class="form-select">
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
                    <thead>
                        <tr>
                            <th>العنوان</th>
                            <th>الساعات</th>
                            <th>الجلسات</th>
                            <th>السعر الأدنى</th>
                            <th>الدفعة</th>
                            <th>الحالة</th>
                            <th>إجراءات</th>
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
