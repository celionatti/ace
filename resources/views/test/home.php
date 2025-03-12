<?php

?>

@section('content')

@include('header')

<div class="">
    @include('alert', ['flashMessage' => $flashMessage])
    <h2>Welcome, {{ $user }}</h2>
    <p>Thank you for visiting our website!</p>
</div>

@include('footer')

@endsection