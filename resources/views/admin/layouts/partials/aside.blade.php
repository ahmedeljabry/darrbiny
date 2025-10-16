<aside id="layout-menu" class="layout-menu menu-vertical menu">
  <!-- App Brand -->
  <div class="app-brand demo px-3 py-2">
    <a href="{{ route('admin.dashboard') }}" class="app-brand-link d-flex align-items-center">
      <span class="app-brand-logo demo">
        @if(!empty($appSettings['brand']['logo_url']))
          <img src="{{ asset($appSettings['brand']['logo_url']) }}" alt="logo" style="height:28px; width:auto;"/>
        @else
          <span class="text-primary">
            <svg width="32" height="22" viewBox="0 0 32 22" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M0.00172773 0V6.85398C0.00172773 6.85398 -0.133178 9.01207 1.98092 10.8388L13.6912 21.9964L19.7809 21.9181L18.8042 9.88248L16.4951 7.17289L9.23799 0H0.00172773Z" fill="currentColor"/>
            </svg>
          </span>
        @endif
      </span>
      <span class="app-brand-text demo menu-text fw-bold ms-3">{{ $appSettings['brand']['name'] ?? 'لوحة الإدارة' }}</span>
    </a>

    <!-- Sidebar Toggle Button -->
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
      <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
      <i class="icon-base ti tabler-x d-block d-xl-none"></i>
    </a>
  </div>

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    <!-- Dashboard Section -->
    <li class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
      <a href="{{ route('admin.dashboard') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-smart-home"></i>
        <div>لوحة التحكم</div>
      </a>
    </li>

    <!-- Menu Header Label for Management -->
    <li class="menu-header small">
      <span class="menu-header-text" data-i18n="Management">الإدارة</span>
    </li>

    <!-- User Management Section -->
    <li class="menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
      <a href="{{ route('admin.users.index') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-users"></i>
        <div>المستخدمون</div>
      </a>
    </li>

    <!-- Plans Section -->
    <li class="menu-item {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}">
      <a href="{{ route('admin.plans.index') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-package"></i>
        <div>الخطط</div>
      </a>
    </li>

    <!-- Subscriptions Section -->
    <li class="menu-item {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}">
      <a href="{{ route('admin.subscriptions.index') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-badge"></i>
        <div>الاشتراكات</div>
      </a>
    </li>

    <!-- Menu Header Label for Finance -->
    <li class="menu-header small">
      <span class="menu-header-text" data-i18n="Finance">المالية</span>
    </li>

    <!-- Payments Section -->
    <li class="menu-item {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
      <a href="{{ route('admin.payments.index') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-credit-card"></i>
        <div>المدفوعات</div>
      </a>
    </li>

    <!-- Menu Header Label for Engagement -->
    <li class="menu-header small">
      <span class="menu-header-text" data-i18n="Engagement">التفاعل</span>
    </li>

    <!-- Ratings Section -->
    <li class="menu-item {{ request()->routeIs('admin.ratings.*') ? 'active' : '' }}">
      <a href="{{ route('admin.ratings.index') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-star"></i>
        <div>التقييمات</div>
      </a>
    </li>

    <!-- Wallet Management -->
    <li class="menu-item {{ request()->routeIs('admin.wallets.*') ? 'active' : '' }}">
      <a href="{{ route('admin.wallets.index') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-wallet"></i>
        <div>المحافظ</div>
      </a>
    </li>

    <!-- Notifications Section -->
    <li class="menu-item {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
      <a href="{{ route('admin.notifications.index') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-bell"></i>
        <div>الإشعارات</div>
      </a>
    </li>

    <!-- Support Section -->
    <li class="menu-header small">
      <span class="menu-header-text" data-i18n="Support">الدعم</span>
    </li>
    <li class="menu-item {{ request()->routeIs('admin.support.*') ? 'active' : '' }}">
      <a href="{{ route('admin.support.index') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-headset"></i>
        <div>تذاكر الدعم</div>
      </a>
    </li>

    <!-- Menu Header Label for Locations -->
    <li class="menu-header small">
      <span class="menu-header-text" data-i18n="Locations">المناطق</span>
    </li>

    <!-- Geo Management Section (Countries & Cities) -->
    <li class="menu-item {{ request()->routeIs('admin.geo.*') ? 'active' : '' }}">
      <a href="{{ route('admin.geo.index') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-world"></i>
        <div>الدول والمدن</div>
      </a>
    </li>



    <!-- Menu Header Label for System -->
    <li class="menu-header small">
      <span class="menu-header-text" data-i18n="System">النظام</span>
    </li>

    <!-- Roles & Permissions Section -->
    <li class="menu-item {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
      <a href="{{ route('admin.roles.index') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-lock"></i>
        <div>الأدوار والصلاحيات</div>
      </a>
    </li>

    <!-- Settings Section -->
    <li class="menu-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
      <a href="{{ route('admin.settings.index') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-settings"></i>
        <div>الإعدادات</div>
      </a>
    </li>


  </ul>
</aside>

<div class="menu-mobile-toggler d-xl-none rounded-1">
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
    <i class="ti tabler-menu icon-base"></i>
    <i class="ti tabler-chevron-right icon-base"></i>
    </a>
</div>
