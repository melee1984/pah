  <aside class="main-sidebar elevation-4 sidebar-light-danger">
    <!-- Brand Logo -->
    <a href="{{ Auth::user()->isMerchantStaff() ? route('merchant.staff.index') : route('merchant.dashboard.index') }}" class="brand-link">
      <img src="{{ asset('images/logo.jpg') }}" alt="Pahatud" class="brand-image">
      <span class="brand-copy"><strong>{{ (request()->attributes->get('merchantAccount') ?? Auth::user()->merchant)->restaurant_name }}</strong><small>Merchant partner</small></span>
    </a>
    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar Menu -->
      <nav class="merchant-sidebar-navigation" aria-label="Merchant navigation">
          @include('merchant.includes.menu')
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>
