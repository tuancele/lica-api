@php $banners = App\Modules\Banner\Models\Banner::where([['status','1'],['type','banner'],['cat_id','2']])->get() @endphp
@if($banners->count() > 0)
<aside class="banner mt-3">
    @foreach($banners as $banner)
    <a href="{{$banner->link}}">
        <img src="{{getImage($banner->image)}}" alt="{{$banner->name}}">
    </a>
    @endforeach
</aside>
@endif