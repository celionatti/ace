@extends('layouts/main')

@section('title')
Blog - MVC Framework
@endsection

@section('content')
<div class="container" style="max-width: 800px;">
    <div style="margin-bottom: 2.5rem;">
        <h1 style="margin-bottom: 0.25rem;"><span class="gradient-text">Latest Posts</span></h1>
        <p style="color: var(--text-muted); font-size: 0.95rem; margin-bottom: 0;">Insights, tutorials & updates from the MiniMVC community</p>
    </div>
    
    @empty($posts)
        <div class="card" style="text-align: center; padding: 4rem 2rem;">
            <p style="font-size: 1.2rem; color: var(--text-secondary); margin-bottom: 0;">📝 No posts available yet.</p>
        </div>
    @else
        @foreach($posts as $post)
            <div class="card" style="margin-bottom: 1.5rem; padding: 2rem;">
                <div style="margin-bottom: 1rem;">
                    <h2 style="font-size: 1.35rem; font-weight: 600; margin-bottom: 0.5rem; line-height: 1.4;">
                        <a href="{{ route('blog/' . $post->id) }}" style="color: var(--text-primary); text-decoration: none; transition: var(--transition-fast);">{{ $post->title }}</a>
                    </h2>
                    <div style="display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;">
                        <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: var(--text-muted); font-size: 0.85rem;">
                            👤 {{ $post->author()->name ?? 'Unknown' }}
                        </span>
                        <span style="color: var(--text-muted); font-size: 0.7rem;">•</span>
                        <span style="display: inline-flex; align-items: center; gap: 0.35rem; color: var(--text-muted); font-size: 0.85rem;">
                            📅 {{ date('M j, Y', strtotime($post->created_at)) }}
                        </span>
                    </div>
                </div>
                <p style="color: var(--text-secondary); line-height: 1.65; margin-bottom: 1.25rem;">{{ substr($post->content, 0, 180) }}...</p>
                <a href="{{ route('blog/' . $post->id) }}" class="btn btn-outline" style="padding: 0.5rem 1.25rem; font-size: 0.85rem;">Read More →</a>
            </div>
        @endforeach
    @endempty
    
    <!-- Pagination Controls -->
    @if($totalPages > 1)
    <nav style="margin-top: 2.5rem; margin-bottom: 2rem;">
        <div style="display: flex; align-items: center; justify-content: center; gap: 0.4rem; flex-wrap: wrap;">
            
            <!-- Previous Button -->
            @if($page > 1)
                <a href="?page={{ $page - 1 }}" style="
                    display: inline-flex; align-items: center; justify-content: center;
                    padding: 0.55rem 1rem; border-radius: 0.6rem; font-size: 0.85rem; font-weight: 500;
                    background: rgba(255,255,255,0.04); color: var(--text-secondary);
                    border: 1px solid var(--border-glass); text-decoration: none;
                    transition: var(--transition-fast);
                " onmouseover="this.style.background='rgba(99,102,241,0.15)';this.style.color='var(--text-primary)';this.style.borderColor='rgba(99,102,241,0.4)'"
                   onmouseout="this.style.background='rgba(255,255,255,0.04)';this.style.color='var(--text-secondary)';this.style.borderColor='var(--border-glass)'">
                    &laquo; Prev
                </a>
            @else
                <span style="
                    display: inline-flex; align-items: center; justify-content: center;
                    padding: 0.55rem 1rem; border-radius: 0.6rem; font-size: 0.85rem; font-weight: 500;
                    background: rgba(255,255,255,0.02); color: var(--text-muted);
                    border: 1px solid rgba(255,255,255,0.03); cursor: not-allowed; opacity: 0.5;
                ">&laquo; Prev</span>
            @endif

            <!-- Page Number Buttons -->
            <?php
            // Calculate the window of page numbers to show
            $windowSize = 5;
            $halfWindow = floor($windowSize / 2);
            $startPage = max(1, $page - $halfWindow);
            $endPage = min($totalPages, $startPage + $windowSize - 1);
            // Adjust start if we're near the end
            if ($endPage - $startPage + 1 < $windowSize) {
                $startPage = max(1, $endPage - $windowSize + 1);
            }
            ?>

            @if($startPage > 1)
                <a href="?page=1" style="
                    display: inline-flex; align-items: center; justify-content: center;
                    width: 2.4rem; height: 2.4rem; border-radius: 0.6rem; font-size: 0.85rem; font-weight: 600;
                    background: rgba(255,255,255,0.04); color: var(--text-secondary);
                    border: 1px solid var(--border-glass); text-decoration: none;
                    transition: var(--transition-fast);
                " onmouseover="this.style.background='rgba(99,102,241,0.15)';this.style.color='var(--text-primary)'"
                   onmouseout="this.style.background='rgba(255,255,255,0.04)';this.style.color='var(--text-secondary)'">1</a>
                @if($startPage > 2)
                    <span style="color: var(--text-muted); font-size: 0.85rem; padding: 0 0.15rem;">…</span>
                @endif
            @endif

            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                @if($i === $page)
                    <span style="
                        display: inline-flex; align-items: center; justify-content: center;
                        width: 2.4rem; height: 2.4rem; border-radius: 0.6rem; font-size: 0.85rem; font-weight: 700;
                        background: var(--primary-gradient); color: white;
                        border: 1px solid transparent;
                        box-shadow: 0 4px 14px rgba(99, 102, 241, 0.35);
                    ">{{ $i }}</span>
                @else
                    <a href="?page={{ $i }}" style="
                        display: inline-flex; align-items: center; justify-content: center;
                        width: 2.4rem; height: 2.4rem; border-radius: 0.6rem; font-size: 0.85rem; font-weight: 600;
                        background: rgba(255,255,255,0.04); color: var(--text-secondary);
                        border: 1px solid var(--border-glass); text-decoration: none;
                        transition: var(--transition-fast);
                    " onmouseover="this.style.background='rgba(99,102,241,0.15)';this.style.color='var(--text-primary)'"
                       onmouseout="this.style.background='rgba(255,255,255,0.04)';this.style.color='var(--text-secondary)'">{{ $i }}</a>
                @endif
            <?php endfor; ?>

            @if($endPage < $totalPages)
                @if($endPage < $totalPages - 1)
                    <span style="color: var(--text-muted); font-size: 0.85rem; padding: 0 0.15rem;">…</span>
                @endif
                <a href="?page={{ $totalPages }}" style="
                    display: inline-flex; align-items: center; justify-content: center;
                    width: 2.4rem; height: 2.4rem; border-radius: 0.6rem; font-size: 0.85rem; font-weight: 600;
                    background: rgba(255,255,255,0.04); color: var(--text-secondary);
                    border: 1px solid var(--border-glass); text-decoration: none;
                    transition: var(--transition-fast);
                " onmouseover="this.style.background='rgba(99,102,241,0.15)';this.style.color='var(--text-primary)'"
                   onmouseout="this.style.background='rgba(255,255,255,0.04)';this.style.color='var(--text-secondary)'">{{ $totalPages }}</a>
            @endif

            <!-- Next Button -->
            @if($page < $totalPages)
                <a href="?page={{ $page + 1 }}" style="
                    display: inline-flex; align-items: center; justify-content: center;
                    padding: 0.55rem 1rem; border-radius: 0.6rem; font-size: 0.85rem; font-weight: 500;
                    background: rgba(255,255,255,0.04); color: var(--text-secondary);
                    border: 1px solid var(--border-glass); text-decoration: none;
                    transition: var(--transition-fast);
                " onmouseover="this.style.background='rgba(99,102,241,0.15)';this.style.color='var(--text-primary)';this.style.borderColor='rgba(99,102,241,0.4)'"
                   onmouseout="this.style.background='rgba(255,255,255,0.04)';this.style.color='var(--text-secondary)';this.style.borderColor='var(--border-glass)'">
                    Next &raquo;
                </a>
            @else
                <span style="
                    display: inline-flex; align-items: center; justify-content: center;
                    padding: 0.55rem 1rem; border-radius: 0.6rem; font-size: 0.85rem; font-weight: 500;
                    background: rgba(255,255,255,0.02); color: var(--text-muted);
                    border: 1px solid rgba(255,255,255,0.03); cursor: not-allowed; opacity: 0.5;
                ">Next &raquo;</span>
            @endif
        </div>

        <!-- Page info text -->
        <div style="text-align: center; margin-top: 1rem;">
            <span style="color: var(--text-muted); font-size: 0.8rem;">Page {{ $page }} of {{ $totalPages }}</span>
        </div>
    </nav>
    @endif
</div>
@endsection

