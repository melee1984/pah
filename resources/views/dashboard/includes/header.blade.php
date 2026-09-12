<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav align-items-center">
        <li class="nav-item"><a class="nav-link" data-widget="pushmenu" href="#" role="button" aria-label="Toggle navigation"><i class="fas fa-bars"></i></a></li>
        <li class="nav-item d-none d-sm-inline-block"><span class="admin-top-title">Operations dashboard</span></li>
    </ul>
    <ul class="navbar-nav ml-auto align-items-center">
        <li class="nav-item d-none d-sm-block"><a class="nav-link" href="{{ route('home') }}" target="_blank"><i class="fas fa-external-link-alt mr-1"></i> View website</a></li>
        <li class="nav-item"><span class="nav-link admin-top-profile"><span>{{ mb_strtoupper(mb_substr(Auth::User()->fullname, 0, 1)) }}</span><strong>{{ Auth::User()->fullname }}</strong></span></li>
    </ul>
</nav>
