@auth
<details class="customer-account-menu">
    <summary><span class="customer-account-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(auth()->user()->firstname, 0, 1)) }}</span><span class="customer-account-name">{{ trim(auth()->user()->full_name) ?: auth()->user()->email }}</span><i class="icofont-rounded-down" aria-hidden="true"></i></summary>
    <nav class="customer-account-dropdown" aria-label="Account navigation">
        <a href="{{ route('profile.dashboard') }}">Dashboard</a>
        <a href="{{ route('profile.orders') }}">My Orders</a>
        <a href="{{ route('profile.edit') }}">My Profile</a>
        <a href="{{ route('profile.support') }}">My Support Requests</a>
        <form action="{{ route('logout') }}" method="POST">@csrf<button type="submit">Log Out</button></form>
    </nav>
</details>
@endauth
