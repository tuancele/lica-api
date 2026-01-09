@extends('Website::layout',['image' => ''])
@section('title', ($detail->seo_title)?$detail->seo_title:$detail->name)
@section('description',$detail->seo_description)
@section('content')
<section class="blogs pt-5 pb-5 ingredients">
    <div class="wrapper-container3">
        <div class="breadcrumb">
            <ol>
                <li><a href="/">Trang chủ</a></li>
                <li><a href="/ingredient-dictionary">Ingredient Dictionary</a></li>
            </ol>
        </div>
        <h1 class="blog-title mt-4">{{$detail->name}}</h1>
        <p class="in_rating">Rating: <span style="color:{{$detail->rate->color??''}}">{{$detail->rate->name??'NOT RATED'}}</span></p>
        @if(isset($benefits) && !empty($benefits))
        <p class="in_benefit">Benefits: 
        	@foreach($benefits as $valben)
        	@php $benefit = App\Modules\Dictionary\Models\IngredientBenefit::find($valben) @endphp
        	@if(isset($benefit) && !empty($benefit)) <a href="/ingredient-dictionary?benefit={{$benefit->id}}">{{$benefit->name}}</a>,@endif
        	@endforeach
        </p>
        @endif
        @if(isset($categories) && !empty($categories))
        <p class="in_category">Categories: 
        	@foreach($categories as $valcat)
        	@php $category = App\Modules\Dictionary\Models\IngredientCategory::find($valcat) @endphp
        	@if(isset($category) && !empty($category)) <a href="/ingredient-dictionary?cat={{$category->id}}">{{$category->name}}</a>,@endif
        	@endforeach
        </p>
        @endif
    </div>
    <div class="wrapper-container3 mt-4">
        <div class="entry-content mb-2">
            @if($detail->glance !="")
            <h2>{{$detail->name}} at a Glance</h2>
            {!!$detail->glance!!}
            @endif
        	<h2 class="mt-5">{{$detail->name}} Description</h2>
            {!!$toc!!}
            @if($detail->shortcode != "")
            @php 
                $pattern = '#[[title] (.*?)]#';
                $matches = null;
                $returnval = preg_match_all($pattern, $detail->shortcode, $matches);
            @endphp
            @if(isset($matches[1][0]))<h2 class="mt-5">{{$matches[1][0]}} {{$detail->name}}</h2>@endif
            {!!$shortcode!!}
            @endif
            @if($detail->reference !="")
            <h2 class="mt-5">{{$detail->name}} References</h2>
            <div class="reference"> {!!$detail->reference!!}</div>
            @endif
            <div class="disclaimer"> 
            {!!$detail->disclaimer!!}
            </div>
        </div>
    </div>
</section>
@endsection
@section('footer')
<style>
	.wrapper-container3{
	    max-width: 680px;
	    margin:auto;
	    padding-right: var(--bs-gutter-x,.75rem);
    	padding-left: var(--bs-gutter-x,.75rem);
	}
    .entry-content ul li{
        margin-bottom: 5px;
    }
    .entry-content .reference p{
        margin-bottom: 5px;
        margin-top: 0px;
    }
	.in_rating,.in_benefit,.in_category{
		font-size: 16px;
	}
	.in_rating span{
		font-weight: 600;
		color:rgb(137, 138, 141);
		text-transform: uppercase;
	}
	.in_benefit a,.in_category a{
		color:rgb(93, 126, 149);
	}
    .disclaimer{
        font-size: 12px;
        margin-top: 30px;
    }
    .ingredients.blogs .list-product{
        --columns: 3;
    }
    .ingredients .item-product img{
        height: 100% !important;
    }
    @media(max-width: 568px){
        .ingredients .card-cover{
            height: initial;
        }
        .ingredients.blogs .list-product{
            --columns: 2;
        }
    }
</style>
@endsection