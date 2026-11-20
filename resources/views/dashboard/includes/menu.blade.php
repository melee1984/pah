@php
    $marketplaceOpen = Request::is(
        'data/dashboard/orders*',
        'data/dashboard/bookings*',
        'data/dashboard/booking*',
        'data/dashboard/merchant*',
        'data/dashboard/promotions*',
        'data/dashboard/coupons*',
        'data/dashboard/push-notifications*'
    );
    $peopleOpen = Request::is(
        'data/dashboard/agents*',
        'data/dashboard/users*',
        'data/dashboard/riders*',
        'data/dashboard/available-riders*'
    );
    $reportingOpen = Request::is('data/dashboard/report*');
    $billingOpen = Request::is('data/dashboard/statements*', 'data/dashboard/agent-statements*');
    $contentOpen = Request::is('data/dashboard/blog*');
    $systemOpen = Request::is(
        'data/dashboard/cities*',
        'data/dashboard/managers*',
        'data/dashboard/user-roles*',
        'data/dashboard/system-status*',
        'data/dashboard/payment-gateways*',
        'data/dashboard/settings*'
    );
@endphp

<ul class="nav nav-pills nav-sidebar flex-column nav-flat admin-sidebar-menu" data-widget="treeview" role="menu" data-accordion="true">
    <li class="nav-header">Workspace</li>
    <li class="nav-item">
        <a href="{{ route('dashboard.data') }}" class="nav-link {{ Request::is('data/dashboard') ? 'active' : '' }}"><i class="nav-icon fas fa-chart-pie"></i><p>Dashboard</p></a>
    </li>
    <li class="nav-item">
        <a href="{{ route('support.admin.index') }}" class="nav-link {{ Request::is('data/dashboard/support*') ? 'active' : '' }}"><i class="nav-icon fas fa-headset"></i><p>Support tickets <support-badge></support-badge></p></a>
    </li>

    <li class="nav-header">Manage</li>
    <li class="nav-item has-treeview {{ $marketplaceOpen ? 'menu-open' : '' }}">
        <a href="#" class="nav-link {{ $marketplaceOpen ? 'active' : '' }}"><i class="nav-icon fas fa-store"></i><p>Marketplace<i class="right fas fa-angle-left"></i></p></a>
        <ul class="nav nav-treeview">
            <li class="nav-item"><a href="{{ route('dashboard.orders') }}" class="nav-link {{ Request::is('data/dashboard/orders*') ? 'active' : '' }}"><i class="nav-icon fas fa-shopping-bag"></i><p>Orders</p></a></li>
            <li class="nav-item"><a href="{{ route('dashboard.bookings') }}" class="nav-link {{ Request::is('data/dashboard/bookings*') || Request::is('data/dashboard/booking*') ? 'active' : '' }}"><i class="nav-icon fas fa-route"></i><p>Bookings</p></a></li>
            <li class="nav-item"><a href="{{ route('dashboard.merchant') }}" class="nav-link {{ Request::is('data/dashboard/merchant*') ? 'active' : '' }}"><i class="nav-icon fas fa-store-alt"></i><p>Merchant partners</p></a></li>
            <li class="nav-item"><a href="{{ route('dashboard.promotions.index') }}" class="nav-link {{ Request::is('data/dashboard/promotions*') ? 'active' : '' }}"><i class="nav-icon fas fa-bullhorn"></i><p>Promotions</p></a></li>
            <li class="nav-item"><a href="{{ route('dashboard.coupons.index') }}" class="nav-link {{ Request::is('data/dashboard/coupons*') ? 'active' : '' }}"><i class="nav-icon fas fa-ticket-alt"></i><p>Discount coupons</p></a></li>
            <li class="nav-item"><a href="{{ route('dashboard.push-notifications.index') }}" class="nav-link {{ Request::is('data/dashboard/push-notifications*') ? 'active' : '' }}"><i class="nav-icon fas fa-bell"></i><p>Push notifications</p></a></li>
        </ul>
    </li>

    <li class="nav-item has-treeview {{ $peopleOpen ? 'menu-open' : '' }}">
        <a href="#" class="nav-link {{ $peopleOpen ? 'active' : '' }}"><i class="nav-icon fas fa-users"></i><p>People<i class="right fas fa-angle-left"></i></p></a>
        <ul class="nav nav-treeview">
            <li class="nav-item"><a href="{{ route('dashboard.agents.index') }}" class="nav-link {{ Request::is('data/dashboard/agents*') ? 'active' : '' }}"><i class="nav-icon fas fa-user-tie"></i><p>Agents</p></a></li>
            <li class="nav-item"><a href="{{ route('dashboard.user') }}" class="nav-link {{ Request::is('data/dashboard/users*') ? 'active' : '' }}"><i class="nav-icon fas fa-user-friends"></i><p>Members</p></a></li>
            <li class="nav-item"><a href="{{ route('dashboard.rider') }}" class="nav-link {{ Request::is('data/dashboard/riders*') ? 'active' : '' }}"><i class="nav-icon fas fa-motorcycle"></i><p>Riders</p></a></li>
            <li class="nav-item"><a href="{{ route('dashboard.riders.available') }}" class="nav-link {{ Request::is('data/dashboard/available-riders*') ? 'active' : '' }}"><i class="nav-icon fas fa-broadcast-tower"></i><p>Available riders</p></a></li>
        </ul>
    </li>

    <li class="nav-item has-treeview {{ $reportingOpen ? 'menu-open' : '' }}">
        <a href="#" class="nav-link {{ $reportingOpen ? 'active' : '' }}"><i class="nav-icon fas fa-chart-bar"></i><p>Reports<i class="right fas fa-angle-left"></i></p></a>
        <ul class="nav nav-treeview">
            <li class="nav-item"><a href="{{ route('dashboard.report.orders') }}" class="nav-link {{ Request::is('data/dashboard/report/orders*') ? 'active' : '' }}"><i class="nav-icon fas fa-file-invoice"></i><p>Order reports</p></a></li>
            <li class="nav-item"><a href="{{ route('dashboard.report.bookings') }}" class="nav-link {{ Request::is('data/dashboard/report/bookings*') ? 'active' : '' }}"><i class="nav-icon fas fa-clipboard-list"></i><p>Booking reports</p></a></li>
            <li class="nav-item"><a href="{{ route('dashboard.report.riders') }}" class="nav-link {{ Request::is('data/dashboard/report/riders*') ? 'active' : '' }}"><i class="nav-icon fas fa-coins"></i><p>Rider commissions</p></a></li>
            <li class="nav-item"><a href="{{ route('dashboard.report.agents') }}" class="nav-link {{ Request::is('data/dashboard/report/agent*') ? 'active' : '' }}"><i class="nav-icon fas fa-hand-holding-usd"></i><p>Agent commissions</p></a></li>
            <li class="nav-item"><a href="{{ route('dashboard.report.favorites') }}" class="nav-link {{ Request::is('data/dashboard/report/favorites*') ? 'active' : '' }}"><i class="nav-icon fas fa-heart"></i><p>Merchant favorites</p></a></li>
            <li class="nav-item"><a href="{{ route('dashboard.report.user-rewards') }}" class="nav-link {{ Request::is('data/dashboard/report/user-rewards*') ? 'active' : '' }}"><i class="nav-icon fas fa-star"></i><p>User reward points</p></a></li>
        </ul>
    </li>

    <li class="nav-item has-treeview {{ $billingOpen ? 'menu-open' : '' }}">
        <a href="#" class="nav-link {{ $billingOpen ? 'active' : '' }}"><i class="nav-icon fas fa-file-invoice-dollar"></i><p>Billing<i class="right fas fa-angle-left"></i></p></a>
        <ul class="nav nav-treeview">
            <li class="nav-item"><a href="{{ route('dashboard.statements.index') }}" class="nav-link {{ Request::is('data/dashboard/statements*') ? 'active' : '' }}"><i class="nav-icon fas fa-file-invoice-dollar"></i><p>Statements of account</p></a></li>
            <li class="nav-item"><a href="{{ route('dashboard.agent-statements.index') }}" class="nav-link {{ Request::is('data/dashboard/agent-statements*') ? 'active' : '' }}"><i class="nav-icon fas fa-receipt"></i><p>Agent statements</p></a></li>
        </ul>
    </li>

    @if (request()->attributes->get('can_manage_managers', false))
    <li class="nav-item has-treeview {{ $contentOpen ? 'menu-open' : '' }}">
        <a href="#" class="nav-link {{ $contentOpen ? 'active' : '' }}"><i class="nav-icon fas fa-pen-nib"></i><p>Content<i class="right fas fa-angle-left"></i></p></a>
        <ul class="nav nav-treeview">
            <li class="nav-item"><a href="{{ route('dashboard.blog.index') }}" class="nav-link {{ Request::is('data/dashboard/blog*') ? 'active' : '' }}"><i class="nav-icon fas fa-newspaper"></i><p>Blog posts</p></a></li>
        </ul>
    </li>
    @endif

    <li class="nav-item has-treeview {{ $systemOpen ? 'menu-open' : '' }}">
        <a href="#" class="nav-link {{ $systemOpen ? 'active' : '' }}"><i class="nav-icon fas fa-cogs"></i><p>System<i class="right fas fa-angle-left"></i></p></a>
        <ul class="nav nav-treeview">
            @if (request()->attributes->get('can_manage_managers', false))
            <li class="nav-item"><a href="{{ route('dashboard.cities.index') }}" class="nav-link {{ Request::is('data/dashboard/cities*') ? 'active' : '' }}"><i class="nav-icon fas fa-city"></i><p>Cities</p></a></li>
            <li class="nav-item"><a href="{{ route('dashboard.managers.index') }}" class="nav-link {{ Request::is('data/dashboard/managers*') ? 'active' : '' }}"><i class="nav-icon fas fa-user-shield"></i><p>Manager access</p></a></li>
            @if (request()->attributes->get('can_manage_roles', false))
            <li class="nav-item"><a href="{{ route('dashboard.user-roles.index') }}" class="nav-link {{ Request::is('data/dashboard/user-roles*') ? 'active' : '' }}"><i class="nav-icon fas fa-users-cog"></i><p>User roles</p></a></li>
            @endif
            <li class="nav-item"><a href="{{ route('dashboard.system-status.index') }}" class="nav-link {{ Request::is('data/dashboard/system-status*') ? 'active' : '' }}"><i class="nav-icon fas fa-heartbeat"></i><p>System status</p></a></li>
            <li class="nav-item"><a href="{{ route('dashboard.payment-gateways.index') }}" class="nav-link {{ Request::is('data/dashboard/payment-gateways*') ? 'active' : '' }}"><i class="nav-icon fas fa-credit-card"></i><p>Payment gateways</p></a></li>
            @endif
            <li class="nav-item"><a href="{{ route('dashboard.settings') }}" class="nav-link {{ Request::is('data/dashboard/settings*') ? 'active' : '' }}"><i class="nav-icon fas fa-cog"></i><p>Settings</p></a></li>
        </ul>
    </li>

    <li class="nav-header">Account</li>
    <li class="nav-item"><a href="{{ route('dashboard.logout') }}" class="nav-link"><i class="nav-icon fas fa-sign-out-alt"></i><p>Logout</p></a></li>
</ul>
