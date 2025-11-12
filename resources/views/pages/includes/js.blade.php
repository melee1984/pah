
<script type="text/javascript">
	var page_url = "{{ Request::path() }}";
	var isLogged = "{{ Auth::check() }}";
	var MAINURL = "{{ URL::to('/') }}";

	@if (Auth::check()) 
		var api_token = "{{ Auth::User()->api_token }}";	
	@else 
		var api_token = "";
	@endif
</script>

<script src="{{ asset('assets/js/jquery.js') }}"></script>
<script src="{{ asset('assets/js/waypoints.min.js') }}"></script>
<script src="{{ asset('assets/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('assets/js/isotope.pkgd.min.js') }}"></script>
<script src="{{ asset('assets/js/wow.min.js') }}"></script>
<script src="{{ asset('assets/js/swiper.min.js') }}"></script>
<script src="{{ asset('assets/js/lightcase.js') }}"></script>
<script src="{{ asset('assets/js/jquery.counterup.min.js')}}"></script>
<script src="{{ asset('assets/js/functions.js') }}"></script>