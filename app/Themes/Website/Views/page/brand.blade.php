@extends('Website::layout',['image' => $detail->image,'class' => 'home'])
@section('title', ($detail->seo_title)?$detail->seo_title:$detail->name)
@section('description',$detail->seo_description)
@section('header')
<link rel="stylesheet" href="public/website/owl-carousel/owl.carousel-2.0.0.css">
<script src="public/website/owl-carousel/owl.carousel-2.0.0.min.js"></script>
@endsection
@section('content')
@if($brands->count() > 0)
<section class="brand-shop mt-3">
    <div class="container-lg">
        <div class="list-brand">
        @foreach($brands as $brand)
        <div class="item-brand">
            <a class="box-icon" href="{{route('home.brand',['url' => $brand->slug])}}">
                <img class="br-5" src="{{getImage($brand->image)}}" alt="{{$brand->name}}">
            </a>
        </div>
        @endforeach
        </div>
    </div>
</section>
@endif
<section class="mt-3 mb-5">
	<div class="container-lg">
		<h1 class="fs-25 mt-4 fw-bold text-uppercase text-center">TẤT CẢ THƯƠNG HIỆU</h1>
		<div class="alphabet-row mt-4">
			@php $alphas = range('A', 'Z');@endphp
			@foreach($alphas as $char)
			<a href="{{getSlug($detail->slug)}}#tab{{$char}}">{{$char}}</a>
			@endforeach
		</div>
		<div class="brands">
			@foreach($alphas as $char)
			@php $list = App\Modules\Brand\Models\Brand::where([['name','like',$char.'%'],['status','1']])->orderBy('name','asc')->get(); @endphp
			@if($list->count() > 0)
			<div class="row_brand" id="tab{{$char}}">
				<div class="row">
					<div class="left_brand col-md-3 col-12 mb-3 mb-md-0">
						<div class="section-title">
							{{$char}}
						</div>
					</div>
					<div class="right_brand col-md-9 col-12">
						<div class="row">
							@foreach($list as $item)
							<div class="col-md-3 col-6">
								<a href="{{route('home.brand',['url' => $item->slug])}}">{{$item->name}}</a>
							</div>
							@endforeach
						</div>
					</div>
				</div>
			</div>
			@endif
			@endforeach
		</div>
	</div>
</section>

@endsection
@section('footer')
<style>
	.alphabet-row{
		display: flex;
	    -webkit-box-pack: justify;
	    justify-content: space-between;
	    flex-wrap: wrap;
	}
	.alphabet-row a {
	    color: rgb(0, 0, 0);
	    width: 30px;
	    text-align: center;
	    font-size: 18px;
	    font-weight: 600;
	}
	.row_brand{
	    border-bottom: 1px solid rgb(239, 239, 239);
	    padding-top: 40px;
	    padding-bottom: 20px;
	}
	.left_brand .section-title{
		font-weight: 700;
	    font-size: 25px;
	    line-height: 18px;
	}
	.right_brand a{
		display: block;
		margin-bottom: 20px;
	}
	@media(max-width: 568px){
		.alphabet-row a{
			margin-bottom: 10px;
		}
	}
</style>
<script>
	$('.list-brand').owlCarousel({
        navText:['<i class="fa fa-angle-left"></i>','<i class="fa fa-angle-right"></i>'],
        responsiveclass: true,
        margin: 20,
        autoplay: true,
        dots:false,
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
                items: 5,
                nav: true,
                loop: true
            }
        }
    });
</script>
@endsection