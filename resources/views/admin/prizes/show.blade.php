@extends('admin.layouts.app')
@section('title', 'تفاصيل الجائزة')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.prizes.index') }}">الجوائز</a></li>
    <li class="breadcrumb-item active" aria-current="page">تفاصيل الجائزة</li>
  </ol>
</nav>

<div class="row g-6">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">تفاصيل الجائزة</h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.prizes.edit', $prize->id) }}" class="btn btn-warning">
                        <i class="icon-base ti tabler-edit me-1"></i> تعديل
                    </a>
                    <a href="{{ route('admin.prizes.index') }}" class="btn btn-outline-secondary">
                        <i class="icon-base ti tabler-arrow-right me-1"></i> العودة
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6>معلومات الجائزة</h6>
                        <p class="mb-0">
                            <strong>العنوان:</strong> {{ $prize->title }}<br>
                            <strong>النقاط المطلوبة:</strong> {{ number_format($prize->required_points) }}<br>
                            <strong>الترتيب:</strong> {{ $prize->order }}<br>
                            <strong>الحالة:</strong> 
                            @if($prize->active)
                                <span class="badge bg-label-success">نشط</span>
                            @else
                                <span class="badge bg-label-secondary">غير نشط</span>
                            @endif
                        </p>
                    </div>
                    <div class="col-md-6">
                        @if($prize->image)
                            <h6>الصورة</h6>
                            <img src="{{ \App\Support\StorageUrl::make($prize->image) }}" alt="{{ $prize->title }}" style="max-width: 300px; max-height: 300px; border-radius: 4px;">
                        @endif
                    </div>
                </div>

                <h6 class="mb-3">طلبات الاسترداد ({{ $prize->redemptions->count() }})</h6>
                @if($prize->redemptions->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>المستخدم</th>
                                    <th>النقاط</th>
                                    <th>الحالة</th>
                                    <th>التاريخ</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($prize->redemptions as $redemption)
                                    <tr>
                                        <td>{{ $redemption->user->name ?? 'N/A' }}</td>
                                        <td>{{ number_format($redemption->points_spent) }}</td>
                                        <td>
                                            @if($redemption->status === 'pending')
                                                <span class="badge bg-label-warning">معلق</span>
                                            @elseif($redemption->status === 'approved')
                                                <span class="badge bg-label-success">موافق عليه</span>
                                            @else
                                                <span class="badge bg-label-danger">مرفوض</span>
                                            @endif
                                        </td>
                                        <td>{{ $redemption->created_at->format('Y-m-d H:i') }}</td>
                                        <td>
                                            <a href="{{ route('admin.prize-redemptions.show', $redemption->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="icon-base ti tabler-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-muted">لا توجد طلبات استرداد</p>
                @endif
            </div>
        </div>
    </div>
</div>

@endsection
