<ul class="nav nav-pills nav-sidebar flex-column nav-flat nav-compact" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
         <li class="nav-item">
            <a href="{{ URL::to('merchant/dashboard') }}" class="nav-link {{ Request::is('merchant/dashboard') ? 'active' : '' }}">
              <i class="nav-icon fas fa-chalkboard-teacher"></i>
              <p>Dashboard </p>
            </a>
          </li>

            <li class="nav-header">PRODUCTS</li>
          <li class="nav-item">
            <a href="{{ URL::to('merchant/products') }}" class="nav-link {{ Request::is('merchant/products') ? 'active' : '' }}">
              <i class="nav-icon fas fa-hamburger"></i>
              <p>
                Manage
              </p>
            </a>
          </li>

         <li class="nav-header">ORDERS</li>
          <li class="nav-item">
            <a href="{{ URL::to('merchant/orders') }}" class="nav-link {{ Request::is('merchant/orders') ? 'active' : '' }}">
              <i class="nav-icon fas fa-shopping-cart"></i>
              <p>
                Order
              </p>
            </a>
          </li>
        <!--   <li class="nav-item">
            <a href="{{ URL::to('merchant/previous-orders') }}" class="nav-link {{ Request::is('merchant/previous-orders') ? 'active' : '' }}">
              <i class="nav-icon fas fa-shopping-cart"></i>
              <p>
                Previous Order
              </p>
            </a> -->
          </li>

          <li class="nav-header">SETTINGS</li>
          <!-- <li class="nav-item">
            <a href="{{ URL::to('merchant/voucher') }}" class="nav-link {{ Request::is('merchant/voucher') ? 'active' : '' }}">
              <i class="nav-icon fas fa-check-double"></i>
              <p>
                Voucher
              </p>
            </a>
          </li> -->
          <li class="nav-item">
            <a href="{{ URL::to('merchant/location') }}" class="nav-link {{ Request::is('merchant/location') ? 'active' : '' }}">
              <i class="nav-icon fas fa-map"></i>
              <p>Location </p>
            </a>
          </li>
           <li class="nav-item">
            <a href="{{ URL::to('merchant/category') }}" class="nav-link {{ Request::is('merchant/category') ? 'active' : '' }}">
              <i class="nav-icon fas fa-layer-group"></i>
              <p>Category </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ URL::to('merchant/settings') }}" class="nav-link {{ Request::is('merchant/settings') ? 'active' : '' }}">
              <i class="nav-icon fas fa-users-cog"></i>
              <p>Profile </p>
            </a>
          </li>
          <li class="nav-header">REPORTS</li>
          <li class="nav-item">
            <a href="{{ URL::to('merchant/report-sales-for-today') }}" class="nav-link {{ Request::is('merchant/report-sales-for-today') ? 'active' : '' }}">
              <i class="fas fa-circle nav-icon"></i>
              <p>Sales</p>
            </a>
          </li>
        <!--   <li class="nav-item">
            <a href="{{ URL::to('merchant/report-sales') }}" class="nav-link {{ Request::is('merchant/report-sales') ? 'active' : '' }}">
              <i class="fas fa-circle nav-icon"></i>
              <p>Sales Report</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="{{ URL::to('merchant/soa') }}" class="nav-link {{ Request::is('merchant/soa') ? 'active' : '' }}">
              <i class="fas fa-circle nav-icon"></i>
              <p>SOA</p>
            </a>
          </li> -->
          <li class="nav-header"><hr></li>
           <li class="nav-item">
            <a href="{{ route('merchant.logout') }}" class="nav-link">
              <i class="nav-icon far fa-circle text-info"></i>
              <p>Logout</p>
            </a>
          </li>
        </ul>