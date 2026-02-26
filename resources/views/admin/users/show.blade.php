@extends('admin.layouts.app')
@section('title','بيانات المستخدم')
@section('content')

<!-- Breadcrumbs -->
<nav aria-label="breadcrumb" class="mb-4">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">لوحة التحكم</a></li>
    <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">المستخدمون</a></li>
    <li class="breadcrumb-item active" aria-current="page">بيانات المستخدم</li>
  </ol>
</nav>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h5 class="mb-0">بيانات المستخدم</h5>
      <div class="d-flex gap-2">
        <a class="btn btn-sm btn-primary" href="{{ route('admin.users.edit',$user->id) }}">تعديل</a>
      </div>
    </div>
    <div class="card-body">
      <div class="row g-4">
        <div class="col-md-6">
          <div class="fw-medium text-body-secondary">الاسم</div>
          <div class="text-heading">{{ $user->name ?? '-' }}</div>
        </div>
        <div class="col-md-6">
          <div class="fw-medium text-body-secondary">البريد الإلكتروني</div>
          <div class="text-heading">{{ $user->email ?? '-' }}</div>
        </div>
        <div class="col-md-6">
          <div class="fw-medium text-body-secondary">الهاتف</div>
          <div class="text-heading">{{ $user->phone_with_cc }}</div>
        </div>
        <div class="col-md-6">
          <div class="fw-medium text-body-secondary">الأدوار</div>
          <div class="text-heading">{{ $user->getRoleNames()->implode(', ') ?: '-' }}</div>
        </div>
        <div class="col-md-6">
          <div class="fw-medium text-body-secondary">الحالة</div>
          <div class="text-heading">{{ $user->isBanned() ? 'محظور' : 'نشط' }}</div>
        </div>
        <div class="col-md-6">
          <div class="fw-medium text-body-secondary">محظور حتى</div>
          <div class="text-heading">{{ $user->banned_until?->format('Y-m-d H:i') ?: '-' }}</div>
        </div>
        <div class="col-md-6">
          <div class="fw-medium text-body-secondary">سبب الحظر</div>
          <div class="text-heading">{{ $user->banned_reason ?: '-' }}</div>
        </div>
        <div class="col-md-6">
          <div class="fw-medium text-body-secondary">النقاط</div>
          <div class="text-heading">
            <span class="badge bg-label-success" style="font-size: 1rem; padding: 0.5rem 1rem;">
              <i class="icon-base ti tabler-coins me-1"></i>
              {{ number_format($user->points_balance ?? 0) }} نقطة
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
  @if($trainerProfileView)
    @php
      $profile = $user->trainerProfile;
      $profileData = $trainerProfileView['display'];
      $profileComplete = filled($profileData['bio']) && filled($profileData['car_type']) && filled($profileData['car_model_year']) && $profileData['has_driving_license'] && ($profileData['country_name'] ?? '-') !== '-' && ($profileData['city_name'] ?? '-') !== '-';
    @endphp
    <div class="card mt-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          <h5 class="mb-0">بيانات الكابتن</h5>
          @if($trainerProfileView['has_pending_changes'])
            <small class="text-warning d-block mt-1">
              <i class="icon-base ti tabler-alert-circle me-1"></i>
              في انتظار موافقة الإدارة على آخر تحديث
            </small>
          @endif
        </div>
        <div class="d-flex gap-2 align-items-center">
          <span class="badge {{ $profileComplete ? 'bg-success' : 'bg-label-warning' }}">
            {{ $profileComplete ? 'مكتمل' : 'غير مكتمل' }}
          </span>
          @if($trainerProfileView['has_pending_changes'])
            @php $hasChangeDetails = count($trainerProfileView['changes']) > 0; @endphp
            <form method="post" action="{{ route('admin.users.trainer-profile.approve', $user->id) }}" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('هل أنت متأكد من الموافقة على تعديلات المدرب؟')">
                <i class="icon-base ti tabler-check me-1"></i> {{ $hasChangeDetails ? 'الموافقة على التعديلات' : 'تفعيل المدرب' }}
              </button>
            </form>
            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectTrainerModal">
              <i class="icon-base ti tabler-x me-1"></i> {{ $hasChangeDetails ? 'رفض التعديلات' : 'رفض التنشيط' }}
            </button>
          @endif
        </div>
      </div>
      <div class="card-body">
        <div class="row g-4">
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">نوع السيارة</div>
            <div class="text-heading">{{ $profileData['car_type'] ?: '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">موديل السيارة</div>
            <div class="text-heading">{{ $profileData['car_model'] ?: '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">سنة الموديل</div>
            <div class="text-heading">{{ $profileData['car_model_year'] ?: '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">سنة الصنع</div>
            <div class="text-heading">{{ $profileData['car_year'] ?: '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">رخصة القيادة</div>
            <div class="text-heading">{{ $profileData['has_driving_license'] ? 'نعم' : 'لا' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">رقم الرخصة</div>
            <div class="text-heading">{{ $profileData['license_number'] ?: '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">انتهاء الرخصة</div>
            <div class="text-heading">{{ $profileData['license_expiry_date'] ?: '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">رقم اللوحة</div>
            <div class="text-heading">{{ $profileData['car_plate_number'] ?: '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">توفر سيارة للتدريب</div>
            <div class="text-heading">{{ $profileData['car_available'] ? 'نعم' : 'لا' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">خدمة التوصيل</div>
            <div class="text-heading">{{ $profileData['pickup_available'] ? 'نعم' : 'لا' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">الدولة</div>
            <div class="text-heading">{{ $profileData['country_name'] ?? '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">المدينة / المحافظة</div>
            <div class="text-heading">{{ $profileData['city_name'] ?? '-' }}</div>
          </div>
        </div>
        <div class="mt-4">
          <div class="fw-medium text-body-secondary mb-2">النبذة التعريفية</div>
          <div class="border rounded p-3 bg-body-tertiary">
            @if($profileData['bio'])
              {!! nl2br(e($profileData['bio'])) !!}
            @else
              <span class="text-body-secondary">لا توجد نبذة</span>
            @endif
          </div>
        </div>
      </div>
      @if($trainerProfileView['has_pending_changes'] && count($trainerProfileView['changes']) > 0)
        <div class="card-footer bg-label-warning">
          <div class="d-flex align-items-start gap-2">
            <i class="icon-base ti tabler-info-circle mt-1"></i>
            <div>
              <strong class="d-block mb-2">تفاصيل التعديلات المعلقة:</strong>
              <ul class="mb-0 small">
                @foreach($trainerProfileView['changes'] as $change)
                  <li>
                    <strong>{{ $change['label'] }}:</strong>
                    <span class="text-decoration-line-through text-muted">{{ $change['old'] }}</span>
                    <i class="icon-base ti tabler-arrow-left-right mx-1"></i>
                    <span class="fw-semibold text-success">{{ $change['new'] }}</span>
                  </li>
                @endforeach
              </ul>
            </div>
          </div>
        </div>
      @endif
    </div>
  @endif

  <!-- Modal: Reject Trainer Profile -->
  @if($trainerProfileView && $trainerProfileView['has_pending_changes'])
    <div class="modal fade" id="rejectTrainerModal" tabindex="-1" aria-labelledby="rejectTrainerModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="rejectTrainerModalLabel">رفض تعديلات المدرب</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
          </div>
          <form method="post" action="{{ route('admin.users.trainer-profile.reject', $user->id) }}">
            @csrf
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label fw-semibold">سبب الرفض <span class="text-danger">*</span></label>
                <textarea name="rejection_reason" class="form-control" rows="4" placeholder="اكتب سبب رفض التعديلات..." required></textarea>
                <small class="text-muted">يجب إدخال سبب الرفض لإعلام المدرب</small>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
              <button type="submit" class="btn btn-danger">رفض التعديلات</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endif

  @if($user->isUser() && $userRequests->count() > 0)
    <div class="card mt-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">حجوزات الطالب</h5>
        <span class="badge bg-label-primary">{{ $userRequests->count() }} حجز</span>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th><i class="icon-base ti tabler-hash me-1"></i> رقم الحجز</th>
                <th><i class="icon-base ti tabler-file-check me-1"></i> الخطة</th>
                <th><i class="icon-base ti tabler-user me-1"></i> المدرب</th>
                <th><i class="icon-base ti tabler-calendar me-1"></i> تاريخ البدء</th>
                <th><i class="icon-base ti tabler-info-circle me-1"></i> الحالة</th>
                <th class="text-center"><i class="icon-base ti tabler-settings me-1"></i> إجراءات</th>
              </tr>
            </thead>
            <tbody>
              @foreach($userRequests as $request)
                <tr>
                  <td><code class="text-primary">#{{ substr($request->id, 0, 8) }}</code></td>
                  <td>
                    <div class="d-flex flex-column">
                      <span class="fw-semibold">{{ $request->plan?->title ?? '-' }}</span>
                      @if($request->plan)
                        <small class="text-muted">{{ $request->plan->city?->name ?? '' }}, {{ $request->plan->country?->name ?? '' }}</small>
                      @endif
                    </div>
                  </td>
                  <td>
                    @if($request->trainer)
                      <span class="fw-semibold">{{ $request->trainer->name }}</span>
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    @if($request->start_date)
                      {{ $request->start_date->format('Y-m-d') }}
                    @else
                      <span class="text-muted">-</span>
                    @endif
                  </td>
                  <td>
                    @php
                      $statusColors = [
                        'pending_payment' => 'warning',
                        'awaiting_offers' => 'info',
                        'offer_selected' => 'primary',
                        'paid' => 'success',
                        'in_training' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                      ];
                      $statusLabels = [
                        'pending_payment' => 'قيد الدفع',
                        'awaiting_offers' => 'في انتظار العروض',
                        'offer_selected' => 'تم اختيار العرض',
                        'paid' => 'مدفوع',
                        'in_training' => 'قيد التدريب',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغي',
                      ];
                      $color = $statusColors[$request->status] ?? 'secondary';
                    @endphp
                    <span class="badge bg-label-{{ $color }}">{{ $statusLabels[$request->status] ?? $request->status }}</span>
                  </td>
                  <td class="text-center">
                    <a href="{{ route('admin.bookings.show', $request->id) }}" class="btn btn-sm btn-outline-primary" title="عرض التفاصيل">
                      <i class="icon-base ti tabler-eye"></i>
                    </a>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endif
@endsection
