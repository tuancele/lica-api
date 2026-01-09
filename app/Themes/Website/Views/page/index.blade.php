@extends('Website::layout',['image' => $detail->image])
@section('title', ($detail->seo_title)?$detail->seo_title:$detail->name)
@section('description',$detail->seo_description)
@section('content')
<section class="pt-5 pb-5">
	<div class="container-lg">
		<div class="row">
			<div class="col-12 col-md-3 order-2 order-md-1">
				@include('Website::sidebar.taxonomy')
				@include('Website::sidebar.support')
				@include('Website::sidebar.fanpage')
			</div>
			<div class="col-12 col-md-9 order-1 order-md-2">
				<div class="entry-content">
					<h1 class="title_detail">{{$detail->name}}</h1>
					{!!$detail->content!!}
				</div>
				<div class="social-icons mb-5 mt-2">
                    <a href="//www.facebook.com/sharer.php?u={{getSlug($detail->slug)}}" data-label="Facebook" onclick="window.open(this.href,this.title,'width=500,height=500,top=300px,left=300px');  return false;" rel="noopener noreferrer nofollow" target="_blank" class="icon button circle is-outline facebook tooltipstered"><i class="fa fa-facebook"></i></a>
                    <a href="//twitter.com/share?url={{getSlug($detail->slug)}}" onclick="window.open(this.href,this.title,'width=500,height=500,top=300px,left=300px');  return false;" rel="noopener noreferrer nofollow" target="_blank" class="icon button circle is-outline twitter tooltipstered"><i class="fa fa-twitter"></i></a>
                    <a href="//pinterest.com/pin/create/button/?url={{getSlug($detail->slug)}}&media={{getImage($detail->image)}}&description={{$detail->description}}" onclick="window.open(this.href,this.title,'width=500,height=500,top=300px,left=300px');  return false;" rel="noopener noreferrer nofollow" target="_blank" class="icon button circle is-outline pinterest tooltipstered"><i class="fa fa-pinterest"></i></a>
                    <a href="//www.linkedin.com/shareArticle?mini=true&url={{getSlug($detail->slug)}}&title={{$detail->name}}" onclick="window.open(this.href,this.title,'width=500,height=500,top=300px,left=300px');  return false;" rel="noopener noreferrer nofollow" target="_blank" class="icon button circle is-outline linkedin tooltipstered"><i class="fa fa-linkedin"></i></a>
                </div>
			</div>
		</div>
	</div>
</section>
@endsection