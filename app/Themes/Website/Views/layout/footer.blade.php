@php $menus = App\Modules\Menu\Models\Menu::where([['group_id',$menu],['parent','0']])->orderBy('sort','asc')->get(); @endphp
@if($menus->count() > 0)
@foreach($menus as $key =>  $menu)
<div class="col-12 col-md-4 mt-3 mt-md-0">
   <div class="menu_footer {{$key === 0 ? 'active' : ''}}">
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
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile collapse/expand cho footer menu
    if (window.innerWidth <= 768) {
        const menuFooters = document.querySelectorAll('.menu_footer');
        menuFooters.forEach(function(menu) {
            const title = menu.querySelector('.title_nav');
            if (title) {
                title.addEventListener('click', function() {
                    menu.classList.toggle('active');
                });
            }
        });
    }
    
    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const menuFooters = document.querySelectorAll('.menu_footer');
            if (window.innerWidth > 768) {
                // Desktop: expand all
                menuFooters.forEach(function(menu) {
                    menu.classList.add('active');
                });
            } else {
                // Mobile: only first expanded
                menuFooters.forEach(function(menu, index) {
                    if (index === 0) {
                        menu.classList.add('active');
                    } else {
                        menu.classList.remove('active');
                    }
                });
            }
        }, 250);
    });
});
</script>