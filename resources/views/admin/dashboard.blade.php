@extends('admin.layouts.app')
@section('title', 'الرئيسيه')
@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="mb-0">لوحة التحكم</h5>
        <form method="get" class="d-flex align-items-center gap-2">
            <label for="period" class="form-label mb-0">الفترة:</label>
            <select id="period" name="period" class="form-select" style="min-width: 180px" onchange="this.form.submit()">
                <option value="all" {{ $period->value === 'all' ? 'selected' : '' }}>الكل</option>
                <option value="today" {{ $period->value === 'today' ? 'selected' : '' }}>اليوم</option>
                <option value="week" {{ $period->value === 'week' ? 'selected' : '' }}>هذا الأسبوع</option>
                <option value="month" {{ $period->value === 'month' ? 'selected' : '' }}>هذا الشهر</option>
            </select>
        </form>
    </div>

    <div class="row g-6">
        <div class="col-lg-3 col-sm-6">
            <div class="card card-border-shadow-primary h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-4">
                            <span class="avatar-initial rounded bg-label-primary"><i class="icon-base ti tabler-users icon-28px"></i></span>
                        </div>
                        <h4 class="mb-0">{{ $metrics->usersTotal }}</h4>
                    </div>
                    <p class="mb-1">المستخدمون</p>
                    <p class="mb-0">
                        <small class="text-body-secondary">إجمالي عدد المستخدمين</small>
                        @if($period->value !== 'all')
                            <small class="text-success ms-2">جديد: {{ $metrics->usersNew }}</small>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="card card-border-shadow-success h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-4">
                            <span class="avatar-initial rounded bg-label-success"><i class="icon-base ti tabler-package icon-28px"></i></span>
                        </div>
                        <h4 class="mb-0">{{ $metrics->plansTotal }}</h4>
                    </div>
                    <p class="mb-1">الخطط</p>
                    <p class="mb-0">
                        <small class="text-body-secondary">إجمالي عدد الخطط</small>
                        @if($period->value !== 'all')
                            <small class="text-success ms-2">جديد: {{ $metrics->plansNew }}</small>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="card card-border-shadow-info h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-4">
                            <span class="avatar-initial rounded bg-label-info"><i class="icon-base ti tabler-building-skyscraper icon-28px"></i></span>
                        </div>
                        <h4 class="mb-0">{{ $metrics->citiesTotal }}</h4>
                    </div>
                    <p class="mb-1">المدن</p>
                    <p class="mb-0">
                        <small class="text-body-secondary">إجمالي عدد المدن</small>
                        @if($period->value !== 'all')
                            <small class="text-success ms-2">جديد: {{ $metrics->citiesNew }}</small>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-sm-6">
            <div class="card card-border-shadow-warning h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="avatar me-4">
                            <span class="avatar-initial rounded bg-label-warning"><i class="icon-base ti tabler-world icon-28px"></i></span>
                        </div>
                        <h4 class="mb-0">{{ $metrics->countriesTotal }}</h4>
                    </div>
                    <p class="mb-1">الدول</p>
                    <p class="mb-0">
                        <small class="text-body-secondary">إجمالي عدد الدول</small>
                        @if($period->value !== 'all')
                            <small class="text-success ms-2">جديد: {{ $metrics->countriesNew }}</small>
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
@endsection
