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
        @if(auth()->id() !== $user->id && !$user->hasRole('ADMIN'))
          <form method="post" action="{{ route('admin.users.force-destroy', $user->id) }}" onsubmit="return confirm('سيتم حذف المستخدم نهائياً وكل بياناته المرتبطة وتحرير رقم الجوال. هل تريد المتابعة؟');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-sm btn-danger">حذف نهائي</button>
          </form>
        @endif
      </div>
    </div>
    <div class="card-body">
      <div class="row g-4 align-items-start">
        <div class="col-lg-3 col-md-4">
          <div class="border rounded-3 p-3 bg-body-tertiary text-center">
            <div class="fw-medium text-body-secondary mb-3">الصورة الشخصية</div>
            @if($user->profile_picture_url)
              <a href="{{ $user->profile_picture_url }}" target="_blank" rel="noopener noreferrer">
                <img
                  src="{{ $user->profile_picture_url }}"
                  alt="الصورة الشخصية لـ {{ $user->name ?? 'المستخدم' }}"
                  class="img-fluid rounded-3 border"
                  style="width: 100%; max-height: 320px; object-fit: contain; background: #fff;">
              </a>
              <div class="mt-2">
                <a href="{{ $user->profile_picture_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary">
                  فتح الصورة
                </a>
              </div>
            @else
              <div class="d-flex flex-column align-items-center justify-content-center rounded-3 border bg-white" style="min-height: 240px;">
                <span class="avatar-initial rounded-circle bg-label-secondary mb-2" style="width: 72px; height: 72px; font-size: 28px;">
                  {{ substr($user->name ?? 'U', 0, 1) }}
                </span>
                <span class="text-body-secondary">لا توجد صورة شخصية</span>
              </div>
            @endif
          </div>
        </div>
        <div class="col-lg-9 col-md-8">
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
          <div class="text-heading">
            {{ $user->getRoleNames()->map(fn($role) => \App\Support\AccessLabels::role($role))->implode(', ') ?: '-' }}
          </div>
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
            <div class="col-12">
          <div class="fw-medium text-body-secondary">وصف الطالبة</div>
          <div class="text-heading">
            @if(filled($userDescription ?? null))
              {!! nl2br(e($userDescription)) !!}
            @else
              -
            @endif
          </div>
        </div>
            <div class="col-md-6">
          <div class="fw-medium text-body-secondary">النقاط</div>
          <div class="text-heading">
            <span class="badge bg-label-success" style="font-size: 1rem; padding: 0.5rem 1rem;">
              <i class="icon-base ti tabler-coins me-1"></i>
              {{ number_format($user->points_balance ?? 0, 2) }} نقطة
            </span>
          </div>
        </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <h5 class="mb-0">الدعوات والإحالات</h5>
        <small class="text-muted">مراجعة كود الدعوة والمستخدمين الذين سجلوا من خلاله</small>
      </div>
      <span class="badge bg-label-primary">{{ $referredUsers->count() }} تسجيلات عبر الكود</span>
    </div>
    <div class="card-body">
      <div class="row g-4">
        <div class="col-md-4">
          <div class="border rounded-3 p-3 h-100 bg-body-tertiary">
            <div class="fw-medium text-body-secondary mb-2">كود الدعوة</div>
            <div class="text-heading d-flex align-items-center gap-2 flex-wrap">
              <code class="fs-6">{{ $user->referral_code ?? '-' }}</code>
              @if(filled($user->referral_code))
                <span class="badge bg-label-success">نشط</span>
              @endif
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="border rounded-3 p-3 h-100 bg-body-tertiary">
            <div class="fw-medium text-body-secondary mb-2">تم التسجيل عبر كود</div>
            @if($user->referrer)
              <div class="text-heading">{{ $user->referrer->name }}</div>
              <div class="text-muted small">{{ $user->referrer->phone_with_cc }}</div>
              <div class="mt-2"><code>{{ $user->referrer->referral_code }}</code></div>
            @else
              <div class="text-heading">لم يتم التسجيل عبر كود دعوة</div>
            @endif
          </div>
        </div>
        <div class="col-md-4">
          <div class="border rounded-3 p-3 h-100 bg-body-tertiary">
            <div class="fw-medium text-body-secondary mb-2">ملخص الإحالات</div>
            <div class="text-heading">{{ $referredUsers->count() }} مستخدم</div>
            <div class="text-muted small">سجلوا باستخدام كود الدعوة الخاص بهذا المستخدم</div>
          </div>
        </div>
      </div>

      <div class="mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="mb-0">المستخدمون المسجلون بكود الدعوة</h6>
          @if($referredUsers->isNotEmpty())
            <span class="badge bg-label-info">{{ $referredUsers->count() }} مستخدم</span>
          @endif
        </div>

        @if($referredUsers->isNotEmpty())
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>المستخدم</th>
                  <th>الأدوار</th>
                  <th>تاريخ التسجيل</th>
                  <th>اشتراكات مدفوعة</th>
                  <th>دفعات كاملة ناجحة</th>
                  <th>إجمالي المدفوع</th>
                </tr>
              </thead>
              <tbody>
                @foreach($referredUsers as $referredUser)
                  <tr>
                    <td>
                      <div class="d-flex flex-column">
                        <a href="{{ route('admin.users.show', $referredUser->id) }}" class="fw-semibold text-primary text-decoration-none">
                          {{ $referredUser->name ?? '-' }}
                        </a>
                        <small class="text-muted">{{ $referredUser->phone_with_cc ?? '-' }}</small>
                      </div>
                    </td>
                    <td>
                      {{ $referredUser->getRoleNames()->map(fn($role) => \App\Support\AccessLabels::role($role))->implode(', ') ?: '-' }}
                    </td>
                    <td>{{ $referredUser->created_at?->format('Y-m-d H:i') ?? '-' }}</td>
                    <td>
                      <span class="badge bg-label-primary">{{ (int) ($referredUser->paid_subscriptions_count ?? 0) }}</span>
                    </td>
                    <td>
                      <span class="badge bg-label-success">{{ (int) ($referredUser->successful_full_payments_count ?? 0) }}</span>
                    </td>
                    <td>
                      {{ number_format(((int) ($referredUser->successful_full_payments_total_minor ?? 0)) / 100, 2) }}
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @else
          <div class="border rounded-3 p-4 bg-body-tertiary text-center text-muted">
            لا يوجد مستخدمون سجلوا باستخدام كود الدعوة الخاص بهذا المستخدم حتى الآن.
          </div>
        @endif
      </div>
    </div>
  </div>
  @if($trainerProfileView)
    @php
      $profile = $user->trainerProfile;
      $profileData = $trainerProfileView['display'];
      $profileComplete = filled($profileData['bio']) && filled($profileData['car_type']) && filled($profileData['car_model_year']) && $profileData['has_driving_license'] && ($profileData['country_name'] ?? '-') !== '-' && filled($profileData['area_level_1'] ?? null);
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
            <div class="fw-medium text-body-secondary">المنطقة الأولى</div>
            <div class="text-heading">{{ $profileData['area_level_1'] ?: '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">المنطقة الثانية</div>
            <div class="text-heading">{{ $profileData['area_level_2'] ?: '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">المنطقة الثالثة</div>
            <div class="text-heading">{{ $profileData['area_level_3'] ?: '-' }}</div>
          </div>
          <div class="col-md-6">
            <div class="fw-medium text-body-secondary">الحي / المحلية</div>
            <div class="text-heading">{{ $profileData['locality'] ?: '-' }}</div>
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
                  <td><code class="text-primary">#{{ $request->formatted_order_number ?? $request->order_number ?? '—' }}</code></td>
                  <td>
                    <div class="d-flex flex-column">
                      <span class="fw-semibold">{{ $request->plan?->title ?? '-' }}</span>
                      @if($request->plan)
                        @php
                          $requestLocation = implode(' ، ', array_filter([
                            $request->locality,
                            $request->area_level_3,
                            $request->area_level_2,
                            $request->area_level_1,
                            $request->country?->name ?? $request->plan->country?->name,
                          ]));
                        @endphp
                        <small class="text-muted">{{ $requestLocation !== '' ? $requestLocation : '-' }}</small>
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
