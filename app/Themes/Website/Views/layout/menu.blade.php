@php $menus = App\Modules\Menu\Models\Menu::where([['group_id',$menu],['parent','0']])->orderBy('sort','asc')->get(); @endphp
@if($menus->count() > 0)
<nav class="d-none d-lg-block">
    <ul class="menu-top" id="menutop">
        @foreach($menus as $key => $menu)
        <li class="li-item">
            @php $childrens = $menu->children;@endphp
            <a class="item-menu" href="{{getSlug($menu->url)}}">{{$menu->name}} @if($childrens->count() > 0)<i class="fa fa-angle-down" aria-hidden="true"></i>@endif</a>
            @if($childrens->count() > 0)
            <div class="submenu">
                <div class="container-lg">
                    <div class="d-flex">
                        <ul class="box-submenu">
                            @foreach($childrens as $children)
                            <li class="li-item-2">
                                <a class="item-menu-2" href="{{getSlug($children->url)}}">{{$children->name}}</a>
                                @php $children2s = $children->children;@endphp
                                @if($children2s->count() > 0)
                                <ul class="submenu2">
                                    @foreach($children2s as $children2)
                                    <li><a class="item-menu-3" href="{{getSlug($children2->url)}}">{{$children2->name}}</a></li>
                                    @endforeach
                                </ul>
                                @endif
                            </li>
                            @endforeach
                        </ul>
                        @if($menu->image != "")
                        <div class="image-menu">
                            <img class="active" src="{{getImage($menu->image)}}"  alt="{{$menu->name}}">
                        </div>
                    @endif
                    </div>
                </div>
            </div>
            @endif
        </li>
        @endforeach
    </ul>
</nav>
@endif