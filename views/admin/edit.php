@extends('layouts/admin')

@section('title')
Edit Post - Admin
@endsection

@section('content')
<div style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 1.5rem;">
        <a href="{{ route('admin/dashboard') }}" style="color: var(--primary); text-decoration: none; font-weight: 500; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 0.5rem;">
            <span>&larr;</span> Back to Dashboard
        </a>
    </div>

    <div class="card">
        <h2 style="font-size: 1.8rem; margin-bottom: 2rem;" class="gradient-text">Edit Post</h2>
        
        <form action="{{ route('admin/edit/' . $post->id) }}" method="POST" novalidate>
            {!! csrf_field() !!}
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label" style="display: block; margin-bottom: 0.5rem; color: var(--text-primary); font-weight: 500;">Post Title</label>
                <input type="text" name="title" class="form-control {{ $post->hasError('title') ? 'is-invalid' : '' }}" 
                       style="width: 100%; padding: 0.75rem 1rem; border-radius: 0.5rem; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border-glass); color: var(--text-primary); outline: none; transition: var(--transition-fast);"
                       value="{{ $post->title ?? '' }}" required>
                @if($post->hasError('title'))
                    <div class="invalid-feedback" style="font-size: 0.8rem; color: var(--error); margin-top: 0.25rem;">{{ $post->getFirstError('title') }}</div>
                @endif
            </div>
            
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label class="form-label" style="display: block; margin-bottom: 0.5rem; color: var(--text-primary); font-weight: 500;">Body Content</label>
                <textarea name="content" class="form-control {{ $post->hasError('content') ? 'is-invalid' : '' }}" 
                          style="width: 100%; padding: 0.75rem 1rem; border-radius: 0.5rem; background: rgba(255, 255, 255, 0.05); border: 1px solid var(--border-glass); color: var(--text-primary); outline: none; transition: var(--transition-fast); resize: vertical;"
                          rows="10" required>{{ $post->content ?? '' }}</textarea>
                @if($post->hasError('content'))
                    <div class="invalid-feedback" style="font-size: 0.8rem; color: var(--error); margin-top: 0.25rem;">{{ $post->getFirstError('content') }}</div>
                @endif
            </div>
            
            <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-weight: 600; cursor: pointer;">Update Post</button>
        </form>
    </div>
</div>
@endsection

