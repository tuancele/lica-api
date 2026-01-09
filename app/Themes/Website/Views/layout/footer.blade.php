@php $menus = App\Modules\Menu\Models\Menu::where([['group_id',$menu],['parent','0']])->orderBy('sort','asc')->get(); @endphp
@if($menus->count() > 0)
@foreach($menus as $key =>  $menu)
<div class="col-12 col-md-4 mt-3 mt-md-0">
   <div class="menu_footer">
      <div class="item-nav">
          <p class="title_nav">{{$menu->name}}</p>
          @php $submenus = $menu->children; @endphp 
          @if($submenus->count() > 0)
           <ul>
              @foreach($submenus as $submenu)
              <li><a href="{{getSlug($submenu->url)}}">{{$submenu->name}}</a></li>
              @endforeach
           </ul>
           @endif
      </div>
   </div>
</div>
@endforeach
@endif