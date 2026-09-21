<p class="merchant-nav-label">Workspace</p>

<ul class="nav nav-pills nav-sidebar flex-column nav-flat nav-compact merchant-sidebar-menu" role="menu">
  <li class="nav-item">
    <a href="{{ route('merchant.dashboard.index') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.index') ? 'active' : '' }}">
      <i class="nav-icon fas fa-home"></i><p>Dashboard</p>
    </a>
  </li>
  <li class="nav-item">
    <a href="{{ route('merchant.dashboard.product') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.product*') ? 'active' : '' }}">
      <i class="nav-icon fas fa-hamburger"></i><p>Products</p>
    </a>
  </li>
  <li class="nav-item">
    <a href="{{ route('merchant.dashboard.orders') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.orders') || request()->routeIs('merchant.orders.*') ? 'active' : '' }}">
      <i class="nav-icon fas fa-shopping-cart"></i><p>Orders</p>
    </a>
  </li>
  @if (Auth::User()->merchant?->agent_id)
    <li class="nav-item">
      <a href="{{ route('merchant.application.show') }}" class="nav-link {{ request()->routeIs('merchant.application.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-file-alt"></i><p>Application &amp; documents</p>
      </a>
    </li>
  @endif
  <li class="nav-item">
    <a href="{{ route('merchant.dashboard.location') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.location') ? 'active' : '' }}">
      <i class="nav-icon fas fa-map-marked-alt"></i><p>Branches</p>
    </a>
  </li>
  <li class="nav-item">
    <a href="{{ route('merchant.dashboard.category') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.category') ? 'active' : '' }}">
      <i class="nav-icon fas fa-layer-group"></i><p>Category</p>
    </a>
  </li>
  <li class="nav-item">
    <a href="{{ route('merchant.dashboard.settings') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.settings') ? 'active' : '' }}">
      <i class="nav-icon fas fa-user-cog"></i><p>Profile</p>
    </a>
  </li>
  <li class="nav-item">
    <a href="{{ route('merchant.dashboard.report.salestoday') }}" class="nav-link {{ request()->routeIs('merchant.dashboard.report.*') ? 'active' : '' }}">
      <i class="nav-icon fas fa-chart-line"></i><p>Sales</p>
    </a>
  </li>
</ul>

<div class="merchant-sidebar-footer">
  <a href="{{ route('merchant.help') }}" class="nav-link {{ request()->routeIs('merchant.help') ? 'active' : '' }}">
    <i class="nav-icon fas fa-question-circle"></i><p>Help &amp; documentation</p>
  </a>
  <a href="{{ route('merchant.logout') }}" class="nav-link merchant-logout-link">
    <i class="nav-icon fas fa-sign-out-alt"></i><p>Logout</p>
  </a>
</div>
