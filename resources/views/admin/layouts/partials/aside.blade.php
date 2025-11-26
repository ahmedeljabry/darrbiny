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

  @php
    $opsOpen = request()->routeIs('admin.bookings.*','admin.cancellation-requests.*','admin.course.details','admin.plans.*','admin.subscriptions.*');
    $usersOpen = request()->routeIs('admin.users.*','admin.ratings.*');
    $financeOpen = request()->routeIs('admin.payments.*','admin.wallets.*','admin.wallet-transactions.*');
    $rewardsOpen = request()->routeIs('admin.prizes.*','admin.prize-redemptions.*');
    $commsOpen = request()->routeIs('admin.notifications.*','admin.messages.*','admin.support.*');
    $reportsOpen = request()->routeIs('admin.reports.*');
    $geoOpen = request()->routeIs('admin.geo.*');
    $systemOpen = request()->routeIs('admin.roles.*','admin.permissions.*','admin.settings.*');
  @endphp

  <ul class="menu-inner py-1">
    <li class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
      <a href="{{ route('admin.dashboard') }}" class="menu-link">
        <i class="menu-icon icon-base ti tabler-smart-home"></i>
        <div>لوحة التحكم</div>
      </a>
    </li>

    <li class="menu-item {{ $opsOpen ? 'open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ti tabler-settings-automation"></i>
        <div>العمليات</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
          <a href="{{ route('admin.bookings.index') }}" class="menu-link">الحجوزات</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.cancellation-requests.*') ? 'active' : '' }}">
          <a href="{{ route('admin.cancellation-requests.index') }}" class="menu-link">طلبات الإلغاء</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.course.details') ? 'active' : '' }}">
          <a href="{{ route('admin.course.details') }}" class="menu-link">تفاصيل الدورات</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}">
          <a href="{{ route('admin.plans.index') }}" class="menu-link">الخطط</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.subscriptions.*') ? 'active' : '' }}">
          <a href="{{ route('admin.subscriptions.index') }}" class="menu-link">الاشتراكات</a>
        </li>
      </ul>
    </li>

    <li class="menu-item {{ $usersOpen ? 'open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ti tabler-users"></i>
        <div>المستخدمون</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
          <a href="{{ route('admin.users.index') }}" class="menu-link">إدارة المستخدمين</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.ratings.*') ? 'active' : '' }}">
          <a href="{{ route('admin.ratings.index') }}" class="menu-link">التقييمات</a>
        </li>
      </ul>
    </li>

    <li class="menu-item {{ $financeOpen ? 'open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ti tabler-credit-card"></i>
        <div>المالية</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ request()->routeIs('admin.payments.*') ? 'active' : '' }}">
          <a href="{{ route('admin.payments.index') }}" class="menu-link">المدفوعات</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.wallets.*') ? 'active' : '' }}">
          <a href="{{ route('admin.wallets.index') }}" class="menu-link">المحافظ</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.wallet-transactions.*') ? 'active' : '' }}">
          <a href="{{ route('admin.wallet-transactions.index') }}" class="menu-link">طلبات المحفظة</a>
        </li>
      </ul>
    </li>

    <li class="menu-item {{ $rewardsOpen ? 'open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ti tabler-gift"></i>
        <div>المكافآت</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ request()->routeIs('admin.prizes.*') ? 'active' : '' }}">
          <a href="{{ route('admin.prizes.index') }}" class="menu-link">الجوائز</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.prize-redemptions.*') ? 'active' : '' }}">
          <a href="{{ route('admin.prize-redemptions.index') }}" class="menu-link">طلبات الجوائز</a>
        </li>
      </ul>
    </li>

    <li class="menu-item {{ $commsOpen ? 'open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ti tabler-headset"></i>
        <div>التواصل والدعم</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
          <a href="{{ route('admin.notifications.index') }}" class="menu-link">الإشعارات</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.messages.*') ? 'active' : '' }}">
          <a href="{{ route('admin.messages.index') }}" class="menu-link">الرسائل</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.support.*') ? 'active' : '' }}">
          <a href="{{ route('admin.support.index') }}" class="menu-link">تذاكر الدعم</a>
        </li>
      </ul>
    </li>

    <li class="menu-item {{ $reportsOpen ? 'open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ti tabler-chart-donut-3"></i>
        <div>التقارير</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ request()->routeIs('admin.reports.sales') ? 'active' : '' }}">
          <a href="{{ route('admin.reports.sales') }}" class="menu-link">مبيعات</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.reports.plan-sales') ? 'active' : '' }}">
          <a href="{{ route('admin.reports.plan-sales') }}" class="menu-link">مبيعات الباقات</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.reports.app-fees') ? 'active' : '' }}">
          <a href="{{ route('admin.reports.app-fees') }}" class="menu-link">رسوم التطبيق</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.reports.vat') ? 'active' : '' }}">
          <a href="{{ route('admin.reports.vat') }}" class="menu-link">ضريبة القيمة المضافة</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.reports.completed-payouts') ? 'active' : '' }}">
          <a href="{{ route('admin.reports.completed-payouts') }}" class="menu-link">مستحقات المدربين</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.reports.active-courses') ? 'active' : '' }}">
          <a href="{{ route('admin.reports.active-courses') }}" class="menu-link">الكورسات النشطة</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.reports.awaiting-offers') ? 'active' : '' }}">
          <a href="{{ route('admin.reports.awaiting-offers') }}" class="menu-link">بانتظار العروض</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.reports.rejected-progress') ? 'active' : '' }}">
          <a href="{{ route('admin.reports.rejected-progress') }}" class="menu-link">رفض الإنجاز اليومي</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.reports.wallet-balances') ? 'active' : '' }}">
          <a href="{{ route('admin.reports.wallet-balances') }}" class="menu-link">أرصدة المحافظ</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.reports.points-balances') ? 'active' : '' }}">
          <a href="{{ route('admin.reports.points-balances') }}" class="menu-link">النقاط</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.reports.reward-redemptions') ? 'active' : '' }}">
          <a href="{{ route('admin.reports.reward-redemptions') }}" class="menu-link">استبدال المكافآت</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.reports.wallet-payments') ? 'active' : '' }}">
          <a href="{{ route('admin.reports.wallet-payments') }}" class="menu-link">دفع عبر المحفظة</a>
        </li>
      </ul>
    </li>

    <li class="menu-item {{ $geoOpen ? 'open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ti tabler-world"></i>
        <div>النطاق الجغرافي</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ request()->routeIs('admin.geo.*') ? 'active' : '' }}">
          <a href="{{ route('admin.geo.index') }}" class="menu-link">الدول والمدن</a>
        </li>
      </ul>
    </li>

    <li class="menu-item {{ $systemOpen ? 'open' : '' }}">
      <a href="javascript:void(0);" class="menu-link menu-toggle">
        <i class="menu-icon icon-base ti tabler-settings"></i>
        <div>النظام</div>
      </a>
      <ul class="menu-sub">
        <li class="menu-item {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
          <a href="{{ route('admin.roles.index') }}" class="menu-link">الأدوار والصلاحيات</a>
        </li>
        <li class="menu-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
          <a href="{{ route('admin.settings.index') }}" class="menu-link">الإعدادات</a>
        </li>
      </ul>
    </li>
  </ul>
</aside>

<div class="menu-mobile-toggler d-xl-none rounded-1">
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
    <i class="ti tabler-menu icon-base"></i>
    <i class="ti tabler-chevron-right icon-base"></i>
    </a>
</div>
