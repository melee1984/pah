@component('mail::message')
# New inquiry for Merchant Partner.
<p>
	Restaurant: <br>{{ $_partner->restaurant_name }} <br><br>
Email: <br> {{ $_partner->email }}<br><br>
Contact no: <br> {{ $_partner->contact_no }}<br><br>
Address:<br> {{ $_partner->address }}<br><br>
City: <br>{{ $_partner->city }}
</p>


@endcomponent
