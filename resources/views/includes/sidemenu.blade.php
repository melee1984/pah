<div class="cart-option">
    <user-current-location-display/>
</div>
<div class="cart">
    <cart-basket-summary/>
</div>
<div class="author-account">
        @if (Auth::check()) 
        <ul class="nav nav-pills">
              <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false"><i class="icofont-ui-user"></i>
 Hi {{ Auth::User()->firstname }}</a>
                <div class="dropdown-menu">
                  <a class="dropdown-item" href="{{ route('profile.dashboard') }}">Profile</a>
                  <a class="dropdown-item" href="{{ route('profile.orders') }}">My Orders</a>

                  <a class="dropdown-item" href="javascript:alert('My booking is not yet available.')">My Bookings</a>
                  <a class="dropdown-item" href="javascript:alert('Settings is not yet available.')">Settings</a>
                  <div class="dropdown-divider"></div>
                  <a class="dropdown-item" href="#"  onclick="event.preventDefault();
                    document.getElementById('logout-form').submit();">{{ __('Logout') }}</a>
                </div>
              </li>
            </ul>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
            @csrf
            </form>
        @else 
            <span style="margin-left:8px;">
                <a href="{{ route('profile.dashboard') }}">My Dashboard</a>
            </span>
        @endif
    
    <div class="author-select">
     
  </div>
  
</div>