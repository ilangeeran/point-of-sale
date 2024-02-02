@extends('emails.layouts.default')

@section('content')

Dear
<?php echo $user_name ?>,
<br />
<br />

<p>Your password has been changed.</p>

@endsection