@extends('admin.layouts.app')
@section('title', 'جدول المتابعة - ' . $plan->title)
@section('content')

@if (session('status'))
  <div class="alert alert-success alert-dismissible" role="alert">
    {{ session('status') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
  </div>
@endif

<div class="row g-6">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">جدول المتابعة - {{ $plan->title }}</h5>
                <a href="{{ route('admin.plans.edit', $plan->id) }}" class="btn btn-outline-secondary">
                    <i class="icon-base ti tabler-arrow-right me-1"></i> العودة للخطة
                </a>
            </div>
            <div class="card-body">
                <p class="text-muted mb-4">
                    عدد أيام الخطة: <strong>{{ $plan->duration_days }}</strong>
                    @if(count($scheduleItems) < (int) $plan->duration_days)
                        <span class="badge bg-label-warning ms-2">يجب إنشاء {{ (int) $plan->duration_days - count($scheduleItems) }} عناصر إضافية</span>
                    @endif
                </p>

                <form method="POST" action="{{ route('admin.plans.schedule.store', $plan->id) }}">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th style="width: 100px;">رقم اليوم</th>
                                    <th>عنوان اليوم</th>
                                    <th style="width: 150px;">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @for($day = 1; $day <= (int) $plan->duration_days; $day++)
                                    @php
                                        $item = $scheduleItems->where('day_number', $day)->first();
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>اليوم {{ $day }}</strong>
                                        </td>
                                        <td>
                                            <input type="hidden" name="items[{{ $day }}][day_number]" value="{{ $day }}">
                                            <input type="text" 
                                                   name="items[{{ $day }}][title]" 
                                                   class="form-control" 
                                                   value="{{ $item->title ?? '' }}" 
                                                   placeholder="أدخل عنوان اليوم {{ $day }}">
                                        </td>
                                        <td>
                                            @if($item)
                                                <form method="POST" action="{{ route('admin.plans.schedule.destroy', $item->id) }}" class="d-inline" data-confirm="delete">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="icon-base ti tabler-trash"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </td>
                                    </tr>
                                @endfor
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="icon-base ti tabler-check me-1"></i> حفظ جدول المتابعة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

