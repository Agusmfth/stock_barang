@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $start = max(1, $current - 2);
        $end = min($last, $current + 2);
    @endphp
    <nav aria-label="Navigasi halaman data">
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                @if ($paginator->onFirstPage())<span class="page-link" aria-hidden="true">‹</span>
                @else<a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">‹</a>@endif
            </li>
            @if ($start > 1)
                <li class="page-item"><a class="page-link" href="{{ $paginator->url(1) }}">1</a></li>
                @if ($start > 2)<li class="page-item disabled"><span class="page-link">…</span></li>@endif
            @endif
            @for ($page = $start; $page <= $end; $page++)
                <li class="page-item {{ $page === $current ? 'active' : '' }}">
                    @if ($page === $current)<span class="page-link" aria-current="page">{{ $page }}</span>
                    @else<a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>@endif
                </li>
            @endfor
            @if ($end < $last)
                @if ($end < $last - 1)<li class="page-item disabled"><span class="page-link">…</span></li>@endif
                <li class="page-item"><a class="page-link" href="{{ $paginator->url($last) }}">{{ $last }}</a></li>
            @endif
            <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                @if ($paginator->hasMorePages())<a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">›</a>
                @else<span class="page-link" aria-hidden="true">›</span>@endif
            </li>
        </ul>
    </nav>
@endif
