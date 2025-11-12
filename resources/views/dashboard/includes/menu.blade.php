<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
          <li class="nav-item has-treeview menu-open">
            <a href="{{ URL::to('data/dashboard') }}" class="nav-link {{ Request::is('data/dashboard') ? 'active' : '' }}">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>
          
          <li class="nav-item">
            <a href="{{ URL::to('data/dashboard/orders') }}" class="nav-link {{ Request::is('data/dashboard/orders') ? 'active' : '' }}">
              <i class="nav-icon fas fa-th"></i>
              <p>
                Orders
<!--                 <span class="right badge badge-danger">New</span>
 -->              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ URL::to('data/dashboard/bookings') }}" class="nav-link {{ Request::is('data/dashboard/bookings') ? 'active' : '' }}">
              <i class="nav-icon fas fa-th"></i>
              <p>Bookings</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ URL::to('data/dashboard/users') }}" class="nav-link {{ Request::is('data/dashboard/users') ? 'active' : '' }}">
              <i class="nav-icon fas fa-th"></i>
              <p>
                Member
<!--                 <span class="right badge badge-danger">New</span>
 -->              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ URL::to('data/dashboard/merchant') }}" class="nav-link {{ Request::is('data/dashboard/merchant') ? 'active' : '' }}">
              <i class="nav-icon fas fa-th"></i>
              <p>
                Merchant Partner</p>
            </a>
          </li>

           <li class="nav-item">
            <a href="{{ URL::to('data/dashboard/settings') }}" class="nav-link {{ Request::is('data/dashboard/settings') ? 'active' : '' }}">
              <i class="nav-icon fas fa-th"></i>
              <p>Settings </p>
            </a>
          </li>

          <li class="nav-header">RIDERS</li>
          <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="fas fa-circle nav-icon"></i>
              <p>Remittances</p>
            </a>
          </li>

          <li class="nav-header">REPORTS</li>
          <li class="nav-item">
            <a href="{{ URL::to('data/dashboard/report/orders') }}" class="nav-link {{ Request::is('data/dashboard/report/orders') ? 'active' : '' }}" >
              <i class="fas fa-circle nav-icon"></i>
              <p>Orders</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ URL::to('data/dashboard/report/bookings') }}" class="nav-link {{ Request::is('data/dashboard/report/bookings') ? 'active' : '' }}" >
              <i class="fas fa-circle nav-icon"></i>
              <p>Bookings</p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ URL::to('data/dashboard/report/riders') }}" class="nav-link {{ Request::is('data/dashboard/report/riders') ? 'active' : '' }}">
              <i class="fas fa-circle nav-icon"></i>
              <p>Riders</p>
            </a>
          </li>
           <li class="nav-item">
            <a href="#" class="nav-link">
              <i class="nav-icon far fa-circle text-info"></i>
              <p>Logout</p>
            </a>
          </li>
        </ul>