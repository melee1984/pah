@component('mail::message')
# Contact Us Message.
<p>
Name: <br>{{ $name }} <br><br>
Email: <br> {{ $email }}<br><br>
Subject:<br> {{ $subject }}<br><br>
Message: <br>{{ $message }}
</p>
@endcomponent
