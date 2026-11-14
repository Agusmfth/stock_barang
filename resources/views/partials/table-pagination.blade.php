@php($pageName = $pageName ?? 'page')
@php($perPageKey = $perPageKey ?? 'per_page')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 p-3 border-top">
    <div class="d-flex flex-wrap align-items-center gap-3">
        <form method="get" class="d-flex align-items-center gap-2 mb-0">
            @foreach(request()->except([$pageName, $perPageKey]) as $key => $value)
                @if(is_array($value))
                    @foreach($value as $nestedKey => $nestedValue)<input type="hidden" name="{{$key}}[{{$nestedKey}}]" value="{{$nestedValue}}">@endforeach
                @else
                    <input type="hidden" name="{{$key}}" value="{{$value}}">
                @endif
            @endforeach
            <label for="tablePageSize-{{$pageName}}" class="small text-secondary mb-0">Tampilkan</label>
            <select id="tablePageSize-{{$pageName}}" name="{{$perPageKey}}" class="form-select form-select-sm" style="width:76px" onchange="this.form.submit()">
                @foreach([10, 25, 50, 100] as $size)<option value="{{$size}}" @selected((int)$perPage===$size)>{{$size}}</option>@endforeach
            </select>
            <span class="small text-secondary">data</span>
        </form>
        <small class="text-secondary">Menampilkan {{$paginator->firstItem() ?? 0}}–{{$paginator->lastItem() ?? 0}} dari {{number_format($paginator->total(),0,',','.')}} data</small>
    </div>
    @if($paginator->hasPages())<div>{{$paginator->onEachSide(1)->links()}}</div>@endif
</div>
