@if(session()->has('newslleter_status'))
<br/>
<div class="alert alert-success alert-dismissible">
    {{ session()->get('message') }}
</div>
@endif

@if(session()->has('display'))

	 <div class="alert {{ session()->get('display') }} alert-dismissible">
	    <button type="button" class="close" data-dismiss="alert">×</button>
	   {{ session()->get('message') }}
	</div>

@endif


@if ($errors->any())
    <div class="alert alert-danger alert-dismissible">
    	<button type="button" class="close" data-dismiss="alert">×</button>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif