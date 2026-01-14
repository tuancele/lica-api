@extends('Website::layout',['image' => $detail->image,'canonical'=> getSlug($detail->slug)])
@section('title', ($detail->seo_title)?$detail->seo_title:$detail->name)
@section('description',$detail->seo_description)
@section('content')
<section class="blogs pt-3 pb-3">
	<div class="container-lg">
        <div class="row">
            <div class="col-12 col-md-12 order-1 order-md-2">
                <div class="title-news mb-3">Danh mục bài viết</div>
                <ul class="list-category mb-3">
                    <li><a href="{{getSlug($detail->slug)}}" class="active">Tất cả</a></li>
                    @if($catgories->count() > 0)
                    @foreach($catgories as $category)
                    <li><a href="{{getSlug($category->slug)}}">{{$category->name}}</a></li>
                    @endforeach
                    @endif
                </ul>
                <div class="list-blog">
                    <div class="row">
                    @if($posts->count() > 0)
                    @foreach($posts as $post)
                    <div class="mb-3 col-12 col-md-4">
                        <div class="item-blog">
                            <a href="{{getSlug($post->slug)}}" class="box-image">
                                <div class="skeleton--img-square js-skeleton">
                                    <img width="250" height="" class="lazy w-100 js-skeleton-img" alt="{{$post->name}}" loading="lazy" src="{{getImage($post->image)}}">
                                </div>
                            </a>
                            <div class="ps-3 pe-3 ps-md-0 pe-md-0">
                                <span class="category">@if($post->category)<a href="{{getSlug($post->category->slug)}}">{{$post->category->name}}</a>@endif</span>
                                <h2 class="post-title"><a href="{{getSlug($post->slug)}}">{{$post->name}}</a></h2>
                                <p class="from_the_blog_excerpt ">{{$post->description}}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @endif
                    </div>
                </div>
                {{$posts->links()}}
                <div class="entry-content">
                    {!!$detail->content!!}
                </div>
            </div>
        </div>
	</div>
</section>
@endsection