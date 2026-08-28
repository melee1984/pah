<ul class="nav nav-pills nav-sidebar flex-column nav-flat" data-widget="treeview" role="menu" data-accordion="false">
    <li class="nav-header">Overview</li>
    <li class="nav-item">
        <a href="{{ route('dashboard.data') }}" class="nav-link {{ Request::is('data/dashboard') ? 'active' : '' }}"><i class="nav-icon fas fa-chart-pie"></i><p>Dashboard</p></a>
    </li>

    <li class="nav-header">Marketplace</li>
    <li class="nav-item">
        <a href="{{ route('dashboard.orders') }}" class="nav-link {{ Request::is('data/dashboard/orders') ? 'active' : '' }}"><i class="nav-icon fas fa-shopping-bag"></i><p>Orders</p></a>
    </li>
    <li class="nav-item">
        <a href="{{ route('dashboard.bookings') }}" class="nav-link {{ Request::is('data/dashboard/bookings') ? 'active' : '' }}"><i class="nav-icon fas fa-route"></i><p>Bookings</p></a>
    </li>
    <li class="nav-item">
        <a href="{{ route('dashboard.merchant') }}" class="nav-link {{ Request::is('data/dashboard/merchant') ? 'active' : '' }}"><i class="nav-icon fas fa-store"></i><p>Merchant partners</p></a>
    </li>

    <li class="nav-header">People</li>
    <li class="nav-item">
        <a href="{{ route('dashboard.agents.index') }}" class="nav-link {{ Request::is('data/dashboard/agents*') ? 'active' : '' }}"><i class="nav-icon fas fa-user-tie"></i><p>Agents</p></a>
    </li>
    <li class="nav-item">
        <a href="{{ route('dashboard.user') }}" class="nav-link {{ Request::is('data/dashboard/users') ? 'active' : '' }}"><i class="nav-icon fas fa-users"></i><p>Members</p></a>
    </li>
    <li class="nav-item">
        <a href="{{ route('dashboard.rider') }}" class="nav-link {{ Request::is('data/dashboard/riders') ? 'active' : '' }}"><i class="nav-icon fas fa-motorcycle"></i><p>Riders</p></a>
    </li>

    <li class="nav-header">Reporting</li>
    <li class="nav-item">
        <a href="{{ route('dashboard.report.orders') }}" class="nav-link {{ Request::is('data/dashboard/report/orders') ? 'active' : '' }}"><i class="nav-icon fas fa-file-invoice"></i><p>Order reports</p></a>
    </li>
    <li class="nav-item">
        <a href="{{ route('dashboard.report.bookings') }}" class="nav-link {{ Request::is('data/dashboard/report/bookings') ? 'active' : '' }}"><i class="nav-icon fas fa-clipboard-list"></i><p>Booking reports</p></a>
    </li>
    <li class="nav-item">
        <a href="{{ route('dashboard.report.riders') }}" class="nav-link {{ Request::is('data/dashboard/report/riders') ? 'active' : '' }}"><i class="nav-icon fas fa-chart-line"></i><p>Rider reports</p></a>
    </li>

    <li class="nav-header">System</li>
    <li class="nav-item">
        <a href="{{ route('dashboard.settings') }}" class="nav-link {{ Request::is('data/dashboard/settings') ? 'active' : '' }}"><i class="nav-icon fas fa-cog"></i><p>Settings</p></a>
    </li>
    <li class="nav-item">
        <a href="{{ route('dashboard.logout') }}" class="nav-link"><i class="nav-icon fas fa-sign-out-alt"></i><p>Logout</p></a>
    </li>
</ul>
