@extends('layouts/main')

@section('title')
{{ $post->title }} - MVC Framework
@endsection

@section('content')
<div class="container" style="max-width: 800px;">
    <a href="{{ route('blog') }}" style="color: var(--primary-color); text-decoration: none;">&larr; Back to Blog</a>
    
    <h1 style="margin-top: 2rem;">{{ $post->title }}</h1>
    <small style="color: var(--text-secondary);">By {{ $post->author()->name ?? 'Unknown' }} on {{ date('M j, Y', strtotime($post->created_at)) }}</small>
    
    <hr style="margin: 2rem 0; border: 0; border-top: 1px solid var(--border-color);">
    
    <div style="font-size: 1.1rem; line-height: 1.8;">
        {!! nl2br(htmlspecialchars($post->content)) !!}
    </div>
    
    <div style="margin-top: 4rem;">
        <h3>Comments ({{ count($post->comments()) }})</h3>
        <hr style="margin: 1rem 0; border: 0; border-top: 1px solid var(--border-color);">
        
        @foreach($post->comments() as $comment)
            <div style="background: var(--surface-2); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                <strong>{{ $comment->author()->name ?? 'Unknown' }}</strong> <small style="color: var(--text-secondary);">{{ date('M j, Y H:i', strtotime($comment->created_at)) }}</small>
                <p style="margin-top: 0.5rem; margin-bottom: 0;">{{ $comment->content }}</p>
            </div>
        @endforeach
        
        <h4 style="margin-top: 2rem;">Add a Comment</h4>
        @auth
            <form action="{{ route('blog/' . $post->id) }}" method="POST">
                {!! csrf_field() !!}
                <div class="form-group" style="margin-bottom: 1rem;">
                    <textarea name="content" class="form-control" rows="4" required placeholder="Write your comment here..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Submit Comment</button>
            </form>
        @else
            <p>You must <a href="{{ route('login') }}" style="color: var(--primary-color);">log in</a> to post a comment.</p>
        @endauth
    </div>
</div>
@endsection

