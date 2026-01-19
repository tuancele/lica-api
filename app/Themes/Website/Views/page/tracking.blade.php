@extends('Website::layout',['image' => $detail->image])
@section('title', ($detail->seo_title)?$detail->seo_title:$detail->name)
@section('description',$detail->seo_description)
@section('header')
<link rel="stylesheet" href="/public/website/owl-carousel/owl.carousel-2.0.0.css">
<script src="/public/website/owl-carousel/owl.carousel-2.0.0.min.js"></script>
@endsection
@section('content')
<section class="sec_tracuu pt-5 mb-5">
	<div class="container-lg">
		<h1>Theo dõi đơn hàng</h1>
		<form class="form-ingredient mt-3" action="#" id="submitTracking" method="post">
			@csrf
			<div class="form-group">
				<input type="text" placeholder="Nhập mã đơn hàng" name="order" id="searchOrder" class="form-control">
				<button type="submit"><span role="img" class="icon"><svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.29496 18.5899C4.17485 18.5899 0 14.4151 0 9.29496C0 4.17485 4.17485 0 9.29496 0C14.4151 0 18.5899 4.17485 18.5899 9.29496C18.5899 14.4151 14.4151 18.5899 9.29496 18.5899ZM9.29496 1.10279C4.77351 1.10279 1.10279 4.77351 1.10279 9.29496C1.10279 13.8164 4.77351 17.4871 9.29496 17.4871C13.8164 17.4871 17.4871 13.8164 17.4871 9.29496C17.4871 4.77351 13.8164 1.10279 9.29496 1.10279Z" fill="black"></path><path d="M16.3409 15.2585L15.5612 16.0383L21.2202 21.6973L21.9999 20.9175L16.3409 15.2585Z" fill="black"></path></svg></span></button>
			</div>
		</form>
		<div class="result_tracking"></div>
		@if($detail->banner != "")
		<a class="banner" href="{{$detail->link}}">
			<img src="{{getImage($detail->banner)}}" alt="{{$detail->name}}">
		</a>
		@endif
		@if($categories->count() > 0)
		<div class="div_category">
			<h2 class="fs-25 fw-bold text-uppercase text-center">Danh mục nổi bật</h2>
	        <div class="list-taxonomy mt-3">
	            @foreach($categories as $category)
	                <div class="col8 pt-2">
	                    <a href="{{getSlug($category->slug)}}">
	                    <div class="taxonomy-item">
	                        <div class="taxonomy-cover">
	                            <img src="{{getImage($category->image)}}" alt="{{$category->name}}">
	                        </div>
	                        <div class="taxonomy-title">{{$category->name}}</div>
	                    </div>
	                    </a>
	                </div>
	            @endforeach
	        </div>
	    </div>
	    @endif
		<div class="entry-content">
			{!!$detail->content!!}
		</div>
	</div>
</section>
@if($taxonomies->count() > 0)
@foreach($taxonomies as $taxonomy)
<section class="taxonomy-product mt-5 mb-5">
     <div class="container-lg">
        <h2 class="fs-25 fw-bold text-uppercase text-center">{{$taxonomy->name}}</h2>
        @php $parents =  App\Modules\Product\Models\Product::select('id','name','slug')->where([['status','1'],['type','taxonomy'],['cat_id',$taxonomy->id]])->orderBy('sort','asc')->get();@endphp
        @if($parents->count() > 0)
        <ul class="nav nav-pills taxonomy-home mb-3 text-center" id="pills-tab" role="tablist">
          @foreach($parents as $p => $parent)
          <li class="nav-item" role="presentation">
            <button class="nav-link @if($p == 0) active @endif" data-slug="{{$parent->slug}}" data-id="{{$parent->id}}" id="taxonomy-tab-{{$parent->id}}" data-bs-toggle="pill" data-bs-target="#taxonomy-{{$parent->id}}" type="button" role="tab" aria-controls="taxonomy-{{$parent->id}}" aria-selected="true">{{$parent->name}}</button>
          </li>
          @endforeach
        </ul>
        <div class="tab-content" id="pills-tabTaxonomy">
            @foreach($parents as $p => $parent)
            @if($p == 0)
            <div class="tab-pane fade show active " id="taxonomy-{{$parent->id}}" role="tabpanel" aria-labelledby="taxonomy-tab-{{$parent->id}}" tabindex="0">
                @php $products = App\Modules\Product\Models\Product::join('variants','variants.product_id','=','posts.id')->select('posts.id','posts.stock','posts.name','posts.slug','posts.image','posts.brand_id','variants.price as price','variants.size_id as size_id','variants.color_id as color_id')->where([['status','1'],['type','product'],['stock','1']])->where('cat_id','like','%'.$parent->id.'%')->orderBy('posts.created_at','desc')->limit('20')->get(); @endphp
                @if($products->count() > 0)
                <div class="list-watch mt-3">
                @foreach($products as $product)
                @include('Website::product.item',['product' => $product])
                @endforeach
                </div>
                @endif
                <div class="text-center mt-3">
                    <a href="{{getSlug($parent->slug)}}" class="btn-view-all">Xem tất cả</a>
                </div>
            </div>
            @else
            <div class="tab-pane fade" id="taxonomy-{{$parent->id}}" role="tabpanel" aria-labelledby="taxonomy-tab-{{$parent->id}}" tabindex="{{$p}}">
                
            </div>
            @endif
            @endforeach
        </div>
        @else
        @php $products = App\Modules\Product\Models\Product::join('variants','variants.product_id','=','posts.id')->select('posts.id','posts.stock','posts.name','posts.slug','posts.image','posts.brand_id','variants.price as price','variants.size_id as size_id','variants.color_id as color_id')->where([['status','1'],['type','product'],['stock','1']])->where('cat_id','like','%'.$taxonomy->id.'%')->orderBy('posts.created_at','desc')->limit('20')->get(); @endphp
        @if($products->count() > 0)
        <div class="list-watch mt-3">
        @foreach($products as $product)
        @include('Website::product.item',['product' => $product])
        @endforeach
        </div>
        @endif
        <div class="text-center mt-3">
            <a href="{{getSlug($taxonomy->slug)}}" class="btn-view-all">Xem tất cả</a>
        </div>
        @endif
    </div>
</section>
@endforeach
@endif
@endsection
@section('footer')
<script>
	$(document).ready(function () {
		$('#submitTracking').submit(function(event){
			$.ajax({
	            type: 'post',
	            url: '{{route("postTracking")}}',
	            data: $('#submitTracking').serialize(),
	            beforeSend: function () {
		            $('#submitTracking button').prop('disabled',true);
		            $('#submitTracking button').html('<span class="spinner-border text-light"></span>');
		        },
	            success: function (res) {
	            	$('#submitTracking button').prop('disabled',false);
		            $('#submitTracking button').html('<span role="img" class="icon"><svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.29496 18.5899C4.17485 18.5899 0 14.4151 0 9.29496C0 4.17485 4.17485 0 9.29496 0C14.4151 0 18.5899 4.17485 18.5899 9.29496C18.5899 14.4151 14.4151 18.5899 9.29496 18.5899ZM9.29496 1.10279C4.77351 1.10279 1.10279 4.77351 1.10279 9.29496C1.10279 13.8164 4.77351 17.4871 9.29496 17.4871C13.8164 17.4871 17.4871 13.8164 17.4871 9.29496C17.4871 4.77351 13.8164 1.10279 9.29496 1.10279Z" fill="black"></path><path d="M16.3409 15.2585L15.5612 16.0383L21.2202 21.6973L21.9999 20.9175L16.3409 15.2585Z" fill="black"></path></svg></span>');
	                $('.result_tracking').show();
	                $('.result_tracking').html(res);
	            },
	            error: function(xhr, status, error){
	                alert('Có lỗi xảy ra, xin vui lòng thử lại');
	                //window.location = window.location.href;
	            }
	        })
			event.preventDefault();
		})
	})
	$('.list-watch').owlCarousel({
        navText:['<i class="fa fa-angle-left"></i>','<i class="fa fa-angle-right"></i>'],
        responsiveclass: true,
        autoplay: true,
        dots:false,
        loop: true,
        autoWidth:true,
        responsive: {
            0: {
                items: 2,
                nav: true
            },
            768: {
                items: 3,
                nav: true
            },
            1000: {
                items: 4,
                nav: true,

            }
        }
    });
</script>
<style>
	.banner{
		border-radius: 10px;
		overflow: hidden;
		margin-top: 30px;
		display: block;
	}
	.banner img{
		width: 100%;
	}
	#submitTracking button{
	    color: #212529;
	    background-color: inherit;
	    line-height: 27px;
	}
	#submitTracking button span{
		color:#212529 !important;
	}
	.sec_tracuu{
		background: linear-gradient(180deg,#FBE4D1 0%,rgba(251,228,209,0) 100%);
    	background-image: url(/public/website/images/hero-bg-home.webp),linear-gradient(180deg,#FBE4D1 0%,rgba(251,228,209,0) 100%);
	    background-repeat: no-repeat;
        background-position-y: 240px,0;
        background-position-x: initial;
	    background-size: cover;
        padding-bottom: 50px;
    	padding-top: 0;
	}
	.form-ingredient .form-group{
		width: 590px;
		position: relative;
	}
	.form-ingredient .form-group input{
		width: 100%;
		height: 50px;
		border-radius: 25px;
	}
	.form-ingredient .form-group button{
		position: absolute;
		top: 13px;
		right: 15px;
	}
	.form-ingredient .form-group button svg{
		background-color: #fff;
	}
	.number-wrapper{
		display: inline-block;
		border-right: 1px solid #d1d1d1;
		text-align: center;
		padding: 0px 20px;
		margin-top: 50px;
	}
	.number-wrapper:last-child{
		border-right: none;
	}
	.number-wrapper .number{
		font-size: 40px;
		font-weight: 600;
	}
	.number-wrapper span{
		display: block;
	}
	.result-ingredient{
		width: 590px;
		background-color: #fff;
		border:1px solid #eee;
		display: none;
	    position: absolute;
    	z-index: 9;
	}
	.result-ingredient ul li a{
		display: block;
		width: 100%;
		padding: 3px 10px;
		font-size: 13px;
	}
	.result-ingredient ul li a:hover{
		background-color: #ddd;
	}
	.order-status {
	    width: 738px;
	}
	.order-status .order-process-detail-list {
    font-size: 14px;
    line-height: 20px;
    color: #959ba4;
    margin-bottom: 8px;
}
.order-status .detail-list-item {
    display: flex;
    position: relative;
    margin-bottom: 20px;
}
.order-status .detail-list-item:nth-child(1) {
    color: #303844;
}
.order-status .detail-list-item .item-date {
    margin-right: 16px;
    width: 135px;
}
.order-status .detail-list-item .item-desc {
    position: relative;
    margin-left: 16px;
}
.order-status .detail-list-item .item-desc:before {
    content: "";
    position: absolute;
    width: 8px;
    height: 8px;
    border: 2px solid #B5BBC6;
    top: 6px;
    left: -16px;
    border-radius: 50%;
    background: #FFF;
}
.order-status .detail-list-item:nth-child(1) .item-desc:before {
    background: #1CC461;
    box-shadow: #1bc4614d 0 0 0 2px;
    border: 0 none;
    left: -16px;
}
.order-status .detail-list-item .item-desc .item-text-box {
    width: 550px;
    word-wrap: break-word;
    margin-left: 8px;
}
.order-status .detail-list-item .item-desc:after {
    content: "";
    position: absolute;
    width: 1px;
    height: 100%;
    top: 20px;
    left: -13px;
    border-radius: 2px;
    background: #B5BBC6;
}
.jHoqBg {
    font-size: 22px;
    line-height: 24px;
    color: rgb(48, 56, 68);
    margin-bottom: 24px;
    font-weight: 700;
    display: block;
    overflow: hidden;
}
.jHoqBg .ssc-ui-tag {
    height: 24px;
    line-height: 16px;
    font-size: 14px;
    padding: 4px 8px;
}
.ssc-ui-tag.blue {
    color: #3274f7;
    background-color: #f0f7ff;
}
.result_tracking{
    background-color: #fff;
    padding: 30px;
    border-radius: 10px;
    margin-top: 20px;
    box-shadow: 0px 0px 5px #d1d1d1;
    display: none;
}
.div_category{
	background-color: #fff;
	padding:20px;
	margin-top: 30px;
	border-radius: 5px;
}
	@media(max-width: 568px){
		.form-ingredient .form-group,.result-ingredient{
			width: 100%;
		}
		.result_tracking{
			padding:10px;
		}
		.order-status{
			width: 100%;
		}
		.order-status .detail-list-item .item-desc,.order-status .detail-list-item .item-desc .item-text-box{
			width: 100%;
		}
		.status_order{
			margin-top: 10px;
			margin-left: 0px !important;
		}
	}
</style>
@endsection