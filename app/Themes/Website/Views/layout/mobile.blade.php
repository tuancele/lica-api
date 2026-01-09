@php $menus = App\Modules\Menu\Models\Menu::where([['group_id',$menu],['parent','0']])->orderBy('sort','asc')->get(); @endphp
@if($menus->count() > 0)
<ul class="mb-menu">
    <li class="active">
        <a href="/">
            Trang chủ
        </a>
    </li>
    @foreach($menus as $key => $menu)
    <li class="">
        @php $childrens = $menu->children;@endphp
        <a href="{{getSlug($menu->url)}}">
            {{$menu->name}} @if($childrens->count() > 0)<i class="fa fa-angle-down" aria-hidden="true"></i>@endif
        </a>
        @if($childrens->count() > 0)<button class="plus-menu lv-1-open cl-open">Open</button>
        <ul class="menu-mb-lv2 menu-childrent">
            @foreach($childrens as $children)
            <li class="">
                @php $children2s = $children->children;@endphp
                <a href="{{getSlug($children->url)}}" title="{{$children->name}}">
                    {{$children->name}} @if($children2s->count() > 0)<i class="fa fa-angle-down" aria-hidden="true"></i>@endif
                </a>
                @if($children2s->count() > 0)
                <button class="plus-menu lv-2-open cl-open">Open</button>
                <ul class="menu-mb-lv3 menu-childrent">
                    @foreach($children2s as $children2)
                    <li>
                        <a href="{{getSlug($children2->url)}}">{{$children2->name}}</a>
                    </li>
                    @endforeach
                </ul>
                @endif
            </li>
            @endforeach
        </ul>
        @endif
    </li>
    @endforeach
</ul>
@endif