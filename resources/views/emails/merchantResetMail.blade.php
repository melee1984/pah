@component('mail::message')
# Welcome to <br>Pahatud Food Merchant Dashboard

Hello {{ $company_name }}, 

We are happy to share with you your Pahatud Food Merchant access you may click the link below to gain your password.

Email: {{ $email }} <br>
Password: <strong>******</strong> <br>

You may set your password using the link below <br>
<a href="{{$resetURL}}">Set Password</a> <br>

Thanks,<br>
<strong>Pahatud Food Delivery Services</strong>
@endcomponent
