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
    $opsOpen = request()->routeIs('admin.bookings.*','admin.course.details','admin.plans.*','admin.withdrawal-requests.*');
    $usersOpen = request()->routeIs('admin.users.*','admin.ratings.*');
    $financeOpen = request()->routeIs('admin.payments.*','admin.app-wallet-account.*','admin.app-expenses.*','admin.wallets.*','admin.gateway-wallets.*','admin.wallet-transactions.*','admin.withdrawal-requests.*');
    $rewardsOpen = request()->routeIs('admin.prizes.*','admin.prize-redemptions.*','admin.rewards.*');
    $commsOpen = request()->routeIs('admin.notifications.*','admin.messages.*','admin.support.*');
    $reportsOpen = request()->routeIs('admin.reports.*','admin.cancellation-requests.*');
    $geoOpen = request()->routeIs('admin.geo.*');
    $systemOpen = request()->routeIs('admin.roles.*','admin.permissions.*','admin.settings.*');

    $canViewAdmin = auth()->user()?->can('view_admin') ?? false;
    $canManagePlans = auth()->user()?->can('manage_plans') ?? false;
    $canManageUsers = auth()->user()?->can('manage_users') ?? false;
    $canManageRatings = auth()->user()?->can('manage_ratings') ?? false;
    $canManagePayments = auth()->user()?->can('manage_payments') ?? false;
    $canManageWallets = auth()->user()?->can('manage_wallets') ?? false;
    $canManageRewards = auth()->user()?->can('manage_rewards') ?? false;
    $canManageNotifications = auth()->user()?->can('manage_notifications') ?? false;
    $canManageReports = auth()->user()?->can('manage_reports') ?? false;
    $canManagePayouts = auth()->user()?->can('manage_payouts') ?? false;
    $canManageGeo = auth()->user()?->can('manage_geo') ?? false;

    $showOperations = $canManagePlans || $canManageWallets;
    $showUsers = $canManageUsers || $canManageRatings;
    $showFinance = $canManagePayments || $canManageWallets;
    $showRewards = $canManageRewards;
    $showComms = $canManageNotifications;
    $showReports = $canManageReports || $canManagePayouts || $canManagePlans;
    $showGeo = $canManageGeo;
  @endphp

  <ul class="menu-inner py-1">
    @if($canViewAdmin)
      <li class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
        <a href="{{ route('admin.dashboard') }}" class="menu-link">
          <i class="menu-icon icon-base ti tabler-smart-home"></i>
          <div>لوحة التحكم</div>
        </a>
      </li>
    @endif

    @if($showOperations)
      <li class="menu-item {{ $opsOpen ? 'open' : '' }}">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon icon-base ti tabler-settings-automation"></i>
          <div>العمليات</div>
        </a>
        <ul class="menu-sub">
          @if($canManagePlans)
            <li class="menu-item {{ request()->routeIs('admin.bookings.*') ? 'active' : '' }}">
              <a href="{{ route('admin.bookings.index') }}" class="menu-link">الحجوزات</a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.course.details') ? 'active' : '' }}">
              <a href="{{ route('admin.course.details') }}" class="menu-link">تفاصيل الدورات</a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}">
              <a href="{{ route('admin.plans.index') }}" class="menu-link">الخطط</a>
            </li>
          @endif
          @if($canManageWallets)
            <li class="menu-item {{ request()->routeIs('admin.withdrawal-requests.*') ? 'active' : '' }}">
              <a href="{{ route('admin.withdrawal-requests.index') }}" class="menu-link">طلبات السحب</a>
            </li>
          @endif
        </ul>
      </li>
    @endif

    @if($showUsers)
      <li class="menu-item {{ $usersOpen ? 'open' : '' }}">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon icon-base ti tabler-users"></i>
          <div>المستخدمون</div>
        </a>
        <ul class="menu-sub">
          @if($canManageUsers)
            <li class="menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
              <a href="{{ route('admin.users.index') }}" class="menu-link">إدارة المستخدمين</a>
            </li>
          @endif
          @if($canManageRatings)
            <li class="menu-item {{ request()->routeIs('admin.ratings.*') ? 'active' : '' }}">
              <a href="{{ route('admin.ratings.index') }}" class="menu-link">التقييمات</a>
            </li>
          @endif
        </ul>
      </li>
    @endif

    @if($showFinance)
      <li class="menu-item {{ $financeOpen ? 'open' : '' }}">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon icon-base ti tabler-credit-card"></i>
          <div>المالية</div>
        </a>
        <ul class="menu-sub">
          @if($canManagePayments)
            <li class="menu-item {{ request()->routeIs('admin.app-wallet-account.*') ? 'active' : '' }}">
              <a href="{{ route('admin.app-wallet-account.index') }}" class="menu-link">حساب محفظة التطبيق</a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.app-expenses.*') ? 'active' : '' }}">
              <a href="{{ route('admin.app-expenses.index') }}" class="menu-link">مصروفات التطبيق</a>
            </li>
          @endif
          @if($canManageWallets)
            <li class="menu-item {{ request()->routeIs('admin.wallets.*') ? 'active' : '' }}">
              <a href="{{ route('admin.wallets.index') }}" class="menu-link">محافظ العملاء</a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.wallet-transactions.*') ? 'active' : '' }}">
              <a href="{{ route('admin.wallet-transactions.index') }}" class="menu-link">محافظ العملاء</a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.gateway-wallets.*') && request()->route('gateway') === 'tap' ? 'active' : '' }}">
              <a href="{{ route('admin.gateway-wallets.show', 'tap') }}" class="menu-link">محفظة تاب</a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.gateway-wallets.*') && request()->route('gateway') === 'tabby' ? 'active' : '' }}">
              <a href="{{ route('admin.gateway-wallets.show', 'tabby') }}" class="menu-link">محفظة تابي</a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.gateway-wallets.*') && request()->route('gateway') === 'tamara' ? 'active' : '' }}">
              <a href="{{ route('admin.gateway-wallets.show', 'tamara') }}" class="menu-link">محفظة تمارا</a>
            </li>
          @endif
        </ul>
      </li>
    @endif

    @if($showRewards)
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
          <li class="menu-item {{ request()->routeIs('admin.rewards.points') ? 'active' : '' }}">
            <a href="{{ route('admin.rewards.points') }}" class="menu-link">النقاط</a>
          </li>
          <li class="menu-item {{ request()->routeIs('admin.rewards.redemptions-report') ? 'active' : '' }}">
            <a href="{{ route('admin.rewards.redemptions-report') }}" class="menu-link">استبدال المكافآت</a>
          </li>
        </ul>
      </li>
    @endif

    @if($showComms)
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
    @endif

    @if($showReports)
      <li class="menu-item {{ $reportsOpen ? 'open' : '' }}">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon icon-base ti tabler-chart-donut-3"></i>
          <div>التقارير</div>
        </a>
        <ul class="menu-sub">
          @if($canManageReports)
            <li class="menu-item {{ request()->routeIs('admin.reports.index') ? 'active' : '' }}">
              <a href="{{ route('admin.reports.index') }}" class="menu-link">كل التقارير</a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.reports.sales') ? 'active' : '' }}">
              <a href="{{ route('admin.reports.sales') }}" class="menu-link">الإيرادات</a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.reports.payments') ? 'active' : '' }}">
              <a href="{{ route('admin.reports.payments') }}" class="menu-link">المدفوعات</a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.reports.subscriptions') ? 'active' : '' }}">
              <a href="{{ route('admin.reports.subscriptions') }}" class="menu-link">الاشتراكات</a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.reports.vat') ? 'active' : '' }}">
              <a href="{{ route('admin.reports.vat') }}" class="menu-link">ضريبة القيمة المضافة</a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.reports.app-profits') ? 'active' : '' }}">
              <a href="{{ route('admin.reports.app-profits') }}" class="menu-link">أرباح التطبيق</a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.reports.rejected-progress') ? 'active' : '' }}">
              <a href="{{ route('admin.reports.rejected-progress') }}" class="menu-link">رفض الإنجاز اليومي</a>
            </li>
            <li class="menu-item {{ request()->routeIs('admin.reports.wallet-balances') ? 'active' : '' }}">
              <a href="{{ route('admin.reports.wallet-balances') }}" class="menu-link">أرصدة المحافظ</a>
            </li>
          @endif
          @if($canManagePlans)
            <li class="menu-item {{ request()->routeIs('admin.cancellation-requests.*') ? 'active' : '' }}">
              <a href="{{ route('admin.cancellation-requests.index') }}" class="menu-link">طلبات الإلغاء</a>
            </li>
          @endif
          @if($canManagePayouts)
            <li class="menu-item {{ request()->routeIs('admin.reports.completed-payouts') ? 'active' : '' }}">
              <a href="{{ route('admin.reports.completed-payouts') }}" class="menu-link">مستحقات المدربين</a>
            </li>
          @endif
        </ul>
      </li>
    @endif

    @if($showGeo)
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
    @endif

    @canany(['manage_roles', 'manage_permissions', 'manage_settings'])
      <li class="menu-item {{ $systemOpen ? 'open' : '' }}">
        <a href="javascript:void(0);" class="menu-link menu-toggle">
          <i class="menu-icon icon-base ti tabler-settings"></i>
          <div>النظام</div>
        </a>
        <ul class="menu-sub">
          @can('manage_roles')
            <li class="menu-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
              <a href="{{ route('admin.roles.index') }}" class="menu-link">الأدوار</a>
            </li>
          @endcan
          @can('manage_permissions')
            <li class="menu-item {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
              <a href="{{ route('admin.permissions.index') }}" class="menu-link">الصلاحيات</a>
            </li>
          @endcan
          @can('manage_settings')
            <li class="menu-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
              <a href="{{ route('admin.settings.index') }}" class="menu-link">الإعدادات</a>
            </li>
          @endcan
        </ul>
      </li>
    @endcanany
  </ul>
</aside>

<div class="menu-mobile-toggler d-xl-none rounded-1">
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
    <i class="ti tabler-menu icon-base"></i>
    <i class="ti tabler-chevron-right icon-base"></i>
    </a>
</div>
