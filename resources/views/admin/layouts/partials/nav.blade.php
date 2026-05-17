<!-- Navbar -->

<nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
            <i class="icon-base ti tabler-menu-2 icon-md"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
        <!-- Search -->
        <div class="navbar-nav align-items-center">
            <div class="nav-item navbar-search-wrapper px-md-0 px-2 mb-0">
                <a class="nav-item nav-link search-toggler d-flex align-items-center px-0" href="javascript:void(0);">
                    <span class="d-inline-block text-body-secondary fw-normal" id="autocomplete"></span>
                </a>
            </div>
        </div>

        <!-- /Search -->

        <ul class="navbar-nav flex-row align-items-center ms-md-auto">

            <!-- Notifications -->
            <li class="nav-item navbar-dropdown dropdown-notifications dropdown me-2">
                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <i class="icon-base ti tabler-bell icon-md"></i>
                    @php
                        $unreadCount = auth()->user()->unreadNotifications()->count();
                    @endphp
                    @if($unreadCount > 0)
                        <span class="badge rounded-pill badge-notifications bg-danger">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                    @endif
                </a>
                <ul class="dropdown-menu dropdown-menu-end py-0">
                    <li class="dropdown-menu-header border-bottom">
                        <div class="dropdown-header d-flex align-items-center py-3">
                            <h5 class="text-body mb-0 me-auto">الإشعارات</h5>
                            @if($unreadCount > 0)
                                <form action="{{ route('admin.notifications.mark-all-read') }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-notifications-all text-body border-0 bg-transparent p-0" data-bs-toggle="tooltip" data-bs-placement="top" title="تحديد الكل كمقروء">
                                        <small class="text-muted">تحديد الكل كمقروء</small>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </li>
                    <li class="dropdown-notifications-list scrollable-container">
                        @php
                            $notifications = auth()->user()->notifications()->latest()->limit(10)->get();
                        @endphp
                        @forelse($notifications as $notification)
                            @php
                                $notificationUrl = route('admin.notifications.show', $notification->id);
                            @endphp
                            <a href="{{ $notificationUrl }}" class="dropdown-item dropdown-notifications-item {{ $notification->read_at ? '' : 'marked-as-read' }}">
                                <div class="d-flex">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar">
                                            <span class="avatar-initial rounded-circle bg-label-{{ $notification->read_at ? 'secondary' : 'primary' }}">
                                                <i class="icon-base ti tabler-{{ in_array($notification->data['type'] ?? '', ['support_ticket_created', 'support_ticket_user_reply'], true) ? 'ticket' : (($notification->data['type'] ?? '') === 'prize_request' ? 'gift' : (($notification->data['type'] ?? '') === 'wallet_topup_request' ? 'wallet' : (($notification->data['type'] ?? '') === 'wallet_withdraw_request' ? 'arrow-up-right-circle' : (($notification->data['type'] ?? '') === 'cancellation_request' ? 'x' : (($notification->data['type'] ?? '') === 'trainer_profile_update' ? 'user-check' : (($notification->data['type'] ?? '') === 'user_account_deleted' ? 'user' : 'bell')))))) }}"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">{{ $notification->data['title'] ?? $notification->data['message'] ?? 'إشعار جديد' }}</h6>
                                        <p class="mb-0">{{ Str::limit($notification->data['message'] ?? $notification->data['title'] ?? '', 50) }}</p>
                                        @if(isset($notification->data['trainer_name']))
                                            <small class="text-primary d-block">
                                                <i class="icon-base ti tabler-user me-1"></i>{{ $notification->data['trainer_name'] }}
                                            </small>
                                        @endif
                                        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                                    </div>
                                    <div class="flex-shrink-0 dropdown-notifications-actions">
                                        <a href="javascript:void(0)" class="dropdown-notifications-read">
                                            <span class="badge badge-dot {{ $notification->read_at ? 'bg-secondary' : 'bg-primary' }}"></span>
                                        </a>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="dropdown-item text-center py-4">
                                <p class="text-muted mb-0">لا توجد إشعارات</p>
                            </div>
                        @endforelse
                    </li>
                    <li class="dropdown-menu-footer border-top position-relative bg-navbar-theme" style="z-index: 2;">
                        <a href="{{ route('admin.notifications.view') }}" class="dropdown-item d-flex justify-content-center p-3 js-notifications-view-all" onclick="event.stopPropagation();">
                            عرض جميع الإشعارات
                        </a>
                    </li>
                </ul>
            </li>
            <!--/ Notifications -->

            <!-- User -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                    <div class="avatar avatar-online">
                        <img src="{{ asset('admin/assets/img/avatars/1.png') }}" alt class="rounded-circle" />
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item mt-0" href="#">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-2">
                                    <div class="avatar avatar-online">
                                        <img src="{{ asset('admin/assets/img/avatars/1.png') }}" alt class="rounded-circle" />
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ auth()->user()->name }}</h6>
                                    @php $primaryRole = auth()->user()->getRoleNames()->first(); @endphp
                                    <small class="text-body-secondary">{{ \App\Support\AccessLabels::role($primaryRole) }}</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider my-1 mx-n2"></div>
                    </li>

                    <li>
                        <div class="d-grid px-2 pt-2 pb-1">
                            <form method="POST" action="{{ route('admin.logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-danger d-flex w-100">
                                    <small class="align-middle">تسجيل الخروج</small>
                                    <i class="icon-base ti tabler-logout ms-2 icon-14px"></i>
                                </button>
                            </form>
                        </div>
                    </li>
                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>
</nav>

<!-- / Navbar -->
