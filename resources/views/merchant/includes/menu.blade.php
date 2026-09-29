<ul class="nav nav-pills nav-sidebar flex-column nav-flat nav-compact merchant-sidebar-menu" role="menu">
  <li class="nav-header merchant-nav-section">Overview</li>
  <li class="nav-item">
    <a href="{{ route('merchant.dashboard.index') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.index') ? 'active' : '' }}">
      <i class="nav-icon fas fa-home"></i><p>Dashboard</p>
    </a>
  </li>

  <li class="nav-header merchant-nav-section">Manage store</li>
  <li class="nav-item">
    <a href="{{ route('merchant.dashboard.orders') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.orders') || request()->routeIs('merchant.orders.*') || request()->routeIs('merchant.dashboard.previous-order') ? 'active' : '' }}">
      <i class="nav-icon fas fa-shopping-bag"></i><p>Orders</p>
    </a>
  </li>
  <li class="nav-item">
    <a href="{{ route('merchant.dashboard.product') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.product*') ? 'active' : '' }}">
      <i class="nav-icon fas fa-utensils"></i><p>Products</p>
    </a>
  </li>
  <li class="nav-item">
    <a href="{{ route('merchant.dashboard.category') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.category') ? 'active' : '' }}">
      <i class="nav-icon fas fa-layer-group"></i><p>Categories</p>
    </a>
  </li>
  <li class="nav-item">
    <a href="{{ route('merchant.dashboard.promotions.index') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.promotions.*') || request()->routeIs('merchant.dashboard.coupons.*') ? 'active' : '' }}">
      <i class="nav-icon fas fa-bullhorn"></i><p>Promotions</p>
    </a>
  </li>

  <li class="nav-header merchant-nav-section">Locations</li>
  <li class="nav-item">
    <a href="{{ route('merchant.dashboard.location') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.location') ? 'active' : '' }}">
      <i class="nav-icon fas fa-map-marker-alt"></i><p>Branches</p>
    </a>
  </li>
  <li class="nav-item">
    <a href="{{ route('merchant.dashboard.tables') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.tables') ? 'active' : '' }}">
      <i class="nav-icon fas fa-chair"></i><p>Dining tables</p>
    </a>
  </li>

  <li class="nav-header merchant-nav-section">Insights</li>
  <li class="nav-item">
    <a href="{{ route('merchant.dashboard.report.salestoday') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.report.*') ? 'active' : '' }}">
      <i class="nav-icon fas fa-chart-line"></i><p>Sales report</p>
    </a>
  </li>

  <li class="nav-header merchant-nav-section">Business account</li>
  <li class="nav-item">
    <a href="{{ route('merchant.dashboard.settings') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.settings') ? 'active' : '' }}">
      <i class="nav-icon fas fa-store"></i><p>Business profile</p>
    </a>
  </li>
  @if (Auth::User()->merchant?->agent_id)
    <li class="nav-item">
      <a href="{{ route('merchant.application.show') }}" class="nav-link {{ request()->routeIs('merchant.application.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-file-alt"></i><p>Application documents</p>
      </a>
    </li>
  @endif
</ul>

<div class="merchant-sidebar-footer">
  <p class="merchant-sidebar-section-label">Support</p>
  <a href="{{ route('merchant.help') }}" class="nav-link {{ request()->routeIs('merchant.help') ? 'active' : '' }}">
    <i class="nav-icon fas fa-life-ring"></i><p>Help center</p>
  </a>
  <a href="{{ route('merchant.logout') }}" class="nav-link merchant-logout-link">
    <i class="nav-icon fas fa-sign-out-alt"></i><p>Log out</p>
  </a>
</div>
