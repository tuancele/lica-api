@extends('Website::layout',['image' => $detail->image])
@section('title', ($detail->seo_title)?$detail->seo_title:$detail->name)
@section('description',$detail->seo_description)
@section('content')
<section class="blogs pt-5 pb-5">
    <div class="wrapper-container">
        <div class="breadcrumb">
            <ol>
                <li><a href="/">Trang chủ</a></li>
                <li><a href="/blogs">Blogs</a></li>
            </ol>
        </div>
        <h1 class="blog-title">{{$detail->name}}</h1>
        <div class="d-flex mb-3">
            @if(isset($category) && !empty($category))
            <div class="me-2 fs-16 cat_detail">
                <a href="{{getSlug($category->slug)}}">{{$category->name}}</a>
            </div>
            @endif
            <div class="post_date">
                {{date('M',strtotime($detail->created_at))}} {{date('d',strtotime($detail->created_at))}}, {{date('Y',strtotime($detail->created_at))}}
            </div>
        </div>
    </div>
    <div class="wrapper-container2 mt-3">
        <img src="{{getImage($detail->image)}}" alt="{{$detail->name}}" class="w-100 border-radius" width="" height="">
    </div>
    <div class="wrapper-container mt-3">
        <div class="entry-content mb-2">
            {!!$toc!!}
        </div>
    </div>
	<div class="container-lg mt-5">
		<div class="row">
            <div class="col-12 col-md-12 order-1 order-md-2">
				<h3 class="title_detail">Bài viết liên quan</h3>
                <div class="list-blog">
                    <div class="row">
                    @if($recents->count() > 0)
                    @foreach($recents as $post)
                    <div class="mb-md-5 mb-3 col-12 col-md-4">
                        <div class="item-blog">
                            <a href="{{getSlug($post->slug)}}" class="box-image">
                                <img width="250" height="" class="lazy w-100" alt="{{$post->name}}" loading="lazy" src="{{getImage($post->image)}}">
                            </a>
                            <div class="ps-3 pe-3 ps-md-0 pe-md-0">
                                <span class="category">@if($post->category)<a href="">{{$post->category->name}}</a>@endif</span>
                                <h2 class="post-title"><a href="{{getSlug($post->slug)}}">{{$post->name}}</a></h2>
                                <p class="from_the_blog_excerpt ">{{$post->description}}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @endif
                    </div>
                </div>
			</div>
		</div>
	</div>
</section>
@endsection
@section('footer')
<script>
  $(".btn-toc").click(function(){
    $(".toc-content").toggle('slow');
  })
</script>
@endsection