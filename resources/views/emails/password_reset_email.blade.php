@extends('emails.layouts.default')

@section('content')

Dear {{$name}},
<br /> 
<br /> 
You recently requested to reset your password for your {{ env('APP_NAME') }} account. 

<br />


<table width="200" style="border: 2px solid #3060fd; margin:0 auto;" bgcolor="#ffbde8">
	<tr>
		<td style="padding:20px; font-size: 24px; font-weight:bold; text-align:center;">
			{{ $token }}
		</td>
	</tr>
</table>

<br /><br />
If you did not request this information, contact
<a href="mailto:support@example.com">support@example.com</a> to report an account breach.

@endsection