@php $categories = App\Modules\Taxonomy\Models\Category::select('id','name','slug')->where([['status','1'],['type','taxonomy'],['cat_id','0']])->orderBy('sort','asc')->get() @endphp
@if($categories->count() > 0)
<aside class="category">
    <div class="title_side">
        Danh mục
    </div>
    <div class="box_category">
        <ul>
        	@foreach($categories as $category)
            <li><a href="{{getSlug($category->slug)}}"> {{$category->name}}</a></li>
            @endforeach
        </ul>
    </div>
</aside>
@endif