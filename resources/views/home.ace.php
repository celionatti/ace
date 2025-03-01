@extends('layouts/default')

@section('title')
    Home Page
@endsection

@section('content')
    @if($isLoggedIn)
        <p>Welcome back, {{ $username }}!</p>
    @endif

    <h2>Welcome to Ace Framework</h2>
    <p>This is the content of the homepage.</p>

    <h3>Recent Articles</h3>
    <ul>
        @foreach($articles as $article)
            <li>{{ $article }}</li>
        @endforeach
    </ul>
@endsection
