@php
  $staff = Auth::user()->isMerchantStaff();
  $staffMerchant = request()->attributes->get('merchantAccount') ?? Auth::user()->merchant;
  $canBill = ! $staff || Auth::user()->hasPermissionTo('branch.bills.view', 'web');
  $staffPage = request()->routeIs('merchant.staff.index');
  $operationalPage = request()->routeIs('merchant.staff.*');
  $staffView = $bootstrap['view'] ?? request('view');
  $staffBranch = request('branch_id') ? ['branch_id' => request('branch_id')] : [];
  $canOrders = ! $staff || Auth::user()->hasPermissionTo('branch.orders.view', 'web');
  $catalogOpen = request()->routeIs(
      'merchant.dashboard.product*',
      'merchant.dashboard.category',
      'merchant.dashboard.promotions.*',
      'merchant.dashboard.coupons.*'
  );
  $storeOpen = request()->routeIs(
      'merchant.dashboard.settings',
      'merchant.dashboard.location',
      'merchant.dashboard.tables',
      'merchant.dashboard.users.*',
      'merchant.application.*'
  );
  $diningOpen = request()->routeIs('merchant.staff.*');
@endphp

<ul class="nav nav-pills nav-sidebar flex-column nav-flat nav-compact merchant-sidebar-menu" data-widget="treeview" data-accordion="true" role="menu">
  @if ($operationalPage)
    <li class="nav-header merchant-nav-section">Dining workspace</li>
    @unless ($staff)
    <li class="nav-item"><a href="{{ route('merchant.dashboard.index') }}" class="nav-link merchant-back-link"><i class="nav-icon fas fa-arrow-left"></i><p>Back to main menu</p></a></li>
    @endunless
    @if ($staffMerchant?->qr_ordering_enabled && $canOrders)
      @if($canBill)
      <li class="nav-item"><a href="{{ route('merchant.staff.index', $staffBranch) }}" aria-label="Cashier workspace" @if($staffPage) :class="{ active: !['kitchen', 'history'].includes(workspace.view) }" @endif class="nav-link {{ ! $staffPage && ! in_array($staffView, ['kitchen', 'history'], true) ? 'active' : '' }}"><i class="nav-icon fas fa-cash-register"></i><p>Cashier workspace</p></a></li>
      @endif
      <li class="nav-item"><a href="{{ route('merchant.staff.index', ['view' => 'kitchen'] + $staffBranch) }}" aria-label="Kitchen display" @if($staffPage) :class="{ active: workspace.view === 'kitchen' }" @endif class="nav-link {{ ! $staffPage && $staffView === 'kitchen' ? 'active' : '' }}"><i class="nav-icon fas fa-utensils"></i><p>Kitchen display</p></a></li>
    @endif
  @elseif ($staff)
    <li class="nav-header merchant-nav-section">Workspace</li>
    <li class="nav-item"><a href="{{ route('merchant.staff.index', $staffBranch) }}" class="nav-link"><i class="nav-icon fas fa-concierge-bell"></i><p>Dining service</p></a></li>
  @else
    <li class="nav-header merchant-nav-section">Workspace</li>
    <li class="nav-item">
      <a href="{{ route('merchant.dashboard.index') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.index') ? 'active' : '' }}"><i class="nav-icon fas fa-home"></i><p>Dashboard</p></a>
    </li>
    <li class="nav-item">
      <a href="{{ route('merchant.dashboard.orders') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.orders') || request()->routeIs('merchant.orders.*') || request()->routeIs('merchant.dashboard.previous-order') ? 'active' : '' }}"><i class="nav-icon fas fa-shopping-bag"></i><p>Orders</p></a>
    </li>

    <li class="nav-header merchant-nav-section">Manage</li>
    @if ($staffMerchant?->qr_ordering_enabled && $canOrders)
    <li class="nav-item has-treeview {{ $diningOpen ? 'menu-open' : '' }}">
      <a href="#" class="nav-link merchant-group-link {{ $diningOpen ? 'active' : '' }}"><i class="nav-icon fas fa-concierge-bell"></i><p><span>Dining service</span><i class="merchant-group-chevron fas fa-chevron-down" aria-hidden="true"></i></p></a>
      <ul class="nav nav-treeview">
        @if($canBill)
        <li class="nav-item"><a href="{{ route('merchant.staff.index', $staffBranch) }}" class="nav-link {{ $diningOpen && ! in_array($staffView, ['kitchen', 'history'], true) ? 'active' : '' }}"><i class="nav-icon fas fa-cash-register"></i><p>Cashier workspace</p></a></li>
        @endif
        <li class="nav-item"><a href="{{ route('merchant.staff.index', ['view' => 'kitchen'] + $staffBranch) }}" class="nav-link {{ $diningOpen && $staffView === 'kitchen' ? 'active' : '' }}"><i class="nav-icon fas fa-utensils"></i><p>Kitchen display</p></a></li>
      </ul>
    </li>
    @endif

    <li class="nav-item has-treeview {{ $catalogOpen ? 'menu-open' : '' }}">
      <a href="#" class="nav-link merchant-group-link {{ $catalogOpen ? 'active' : '' }}"><i class="nav-icon fas fa-box-open"></i><p><span>Catalog &amp; marketing</span><i class="merchant-group-chevron fas fa-chevron-down" aria-hidden="true"></i></p></a>
      <ul class="nav nav-treeview">
        <li class="nav-item"><a href="{{ route('merchant.dashboard.product') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.product*') ? 'active' : '' }}"><i class="nav-icon fas fa-utensils"></i><p>Products</p></a></li>
        <li class="nav-item"><a href="{{ route('merchant.dashboard.category') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.category') ? 'active' : '' }}"><i class="nav-icon fas fa-layer-group"></i><p>Categories</p></a></li>
        <li class="nav-item"><a href="{{ route('merchant.dashboard.promotions.index') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.promotions.*') || request()->routeIs('merchant.dashboard.coupons.*') ? 'active' : '' }}"><i class="nav-icon fas fa-bullhorn"></i><p>Promotions</p></a></li>
      </ul>
    </li>

    <li class="nav-item has-treeview {{ $storeOpen ? 'menu-open' : '' }}">
      <a href="#" class="nav-link merchant-group-link {{ $storeOpen ? 'active' : '' }}"><i class="nav-icon fas fa-store-alt"></i><p><span>Store &amp; team</span><i class="merchant-group-chevron fas fa-chevron-down" aria-hidden="true"></i></p></a>
      <ul class="nav nav-treeview">
        <li class="nav-item"><a href="{{ route('merchant.dashboard.settings') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.settings') ? 'active' : '' }}"><i class="nav-icon fas fa-cog"></i><p>Store settings</p></a></li>
        <li class="nav-item"><a href="{{ route('merchant.dashboard.location') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.location') ? 'active' : '' }}"><i class="nav-icon fas fa-map-marker-alt"></i><p>Branches</p></a></li>
        <li class="nav-item"><a href="{{ route('merchant.dashboard.tables') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.tables') ? 'active' : '' }}"><i class="nav-icon fas fa-chair"></i><p>Dining tables</p></a></li>
        @if ((int) Auth::user()->merchant?->user_id === (int) Auth::id())
        <li class="nav-item"><a href="{{ route('merchant.dashboard.users.index') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.users.*') ? 'active' : '' }}"><i class="nav-icon fas fa-users-cog"></i><p>Merchant staff</p></a></li>
        @endif
        @if (Auth::user()->merchant?->requiresEnrollmentReview())
        <li class="nav-item"><a href="{{ route('merchant.application.show') }}" class="nav-link {{ request()->routeIs('merchant.application.*') ? 'active' : '' }}"><i class="nav-icon fas fa-file-alt"></i><p>Application documents</p></a></li>
        @endif
      </ul>
    </li>

    <li class="nav-item">
      <a href="{{ route('merchant.dashboard.report.salestoday') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.report.salestoday') || request()->routeIs('merchant.dashboard.report.report') ? 'active' : '' }}"><i class="nav-icon fas fa-chart-line"></i><p>Sales report</p></a>
    </li>
  @endif
</ul>

<div class="merchant-sidebar-footer">
  @if(! $staff && ! $operationalPage)
  <p class="merchant-sidebar-section-label">Support</p>
  <a href="{{ route('merchant.help') }}" class="nav-link {{ request()->routeIs('merchant.help') ? 'active' : '' }}"><i class="nav-icon fas fa-life-ring"></i><p>Help center</p></a>
  @endif
  <a href="{{ route('merchant.logout') }}" class="nav-link merchant-logout-link"><i class="nav-icon fas fa-sign-out-alt"></i><p>Log out</p></a>
</div>
