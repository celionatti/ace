@extends('layouts/main')

@section('title')
Upload - Mini MVC
@endsection

@section('content')
<div style="margin-bottom: 2rem;">
    <h1 class="gradient-text">File Upload & Image Resizer</h1>
    <p>Upload files safely, check extension structures, and resize images dynamically using PHP's native GD library.</p>
</div>

@if(!empty($error))
    <div class="alert alert-danger">
        <span>❌</span> {{ $error }}
    </div>
@endif

<div class="profile-grid" style="grid-template-columns: 1fr;">
    
    <div class="auth-grid" style="margin: 0 auto; max-width: 650px; width: 100%;">
        <div class="card">
            <h2 style="margin-bottom: 0.5rem; font-size: 1.8rem;" class="gradient-text-alt">Upload Image</h2>
            <p style="margin-bottom: 2rem; font-size: 0.95rem; color: var(--text-secondary);">Supports JPEG, PNG, GIF, and WEBP formats up to 5MB.</p>
            
            <form action="{{ route('upload') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="image" class="form-label">Select Image File</label>
                    <input type="file" name="image" id="image" class="form-control" accept="image/*" required 
                           style="padding-top: 0.6rem;">
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin-bottom: 2rem;">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="width" class="form-label">Target Width (Pixels)</label>
                        <input type="number" name="width" id="width" class="form-control" 
                               value="{{ $width ?? 300 }}" required min="10" max="2000">
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label for="height" class="form-label">Target Height (Pixels)</label>
                        <input type="number" name="height" id="height" class="form-control" 
                               value="{{ $height ?? 300 }}" required min="10" max="2000">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.9rem;">
                    <span>📤</span> Upload & Resize Image
                </button>
            </form>
        </div>
    </div>

    @if(!empty($originalUrl) && !empty($thumbnailUrl))
        <div class="card" style="margin-top: 3rem; animation: slideIn 0.4s ease-out;">
            <h3 style="margin-bottom: 1.5rem; font-size: 1.4rem; text-align: center;" class="gradient-text">Processing Results</h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 2rem; justify-items: center;">
                
                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.75rem; width: 100%;">
                    <span style="font-weight: 600; color: var(--text-secondary);">Original File</span>
                    <div style="border: 1px solid var(--border-glass); border-radius: 0.75rem; overflow: hidden; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.1); width: 100%; max-width: 300px; aspect-ratio: 1/1;">
                        <img src="{!! $originalUrl !!}" alt="Original File" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                    </div>
                    <a href="{!! $originalUrl !!}" target="_blank" class="btn btn-secondary" style="padding: 0.4rem 1rem; font-size: 0.8rem;">Open Full Resolution</a>
                </div>
                
                <div style="display: flex; flex-direction: column; align-items: center; gap: 0.75rem; width: 100%;">
                    <span style="font-weight: 600; color: var(--text-secondary);">Resized Thumbnail ({{ $width }}x{{ $height }})</span>
                    <div style="border: 1.5px dashed var(--primary); border-radius: 0.75rem; overflow: hidden; display: flex; align-items: center; justify-content: center; background: rgba(99,102,241,0.05); width: 100%; max-width: 300px; aspect-ratio: 1/1;">
                        <img src="{!! $thumbnailUrl !!}" alt="Resized File" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                    </div>
                    <a href="{!! $thumbnailUrl !!}" target="_blank" class="btn btn-primary" style="padding: 0.4rem 1rem; font-size: 0.8rem;">View Resized Image</a>
                </div>

            </div>
        </div>
    @endif

</div>
@endsection

