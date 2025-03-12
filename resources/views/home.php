@section('styles')
<style>
    .hero-section {
        background-color: red;
        padding: 3rem 0;
        margin-bottom: 2rem;
    }

    .featured-posts {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
</style>
@endsection

@section('content')

@include('header')

<div class="hero-section">
    <h2>Welcome, {{ $user }}</h2>
    <p>Thank you for visiting our website!</p>
</div>

<section class="featured-posts">
    <h3>Latest Posts</h3>

    @if(!empty($articles))
        @foreach($articles as $post)
            <div class="post-card">
                <h4>{{ $post }}</h4>
                <a href="/posts/{{ $post }}">Read more</a>
            </div>
        @endforeach
    @else
        <p>No posts found.</p>
    @endif
</section>

@endsection