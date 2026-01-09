@extends('Website::layout',['image' => ''])
@section('title', $title)
@section('description',$title)
@section('content')
<section class="mb-5">
	<div class="container">
		<form class="form-ingredient pt-5 mb-5" action="/skindeep/search">
			<div class="form-group">
				<input type="search" placeholder="Nhập tên thành phần, thương hiệu" name="search" value="{{request()->search}}" id="searchIngredient" class="form-control">
				<button type="submit"><span role="img" class="icon"><svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.29496 18.5899C4.17485 18.5899 0 14.4151 0 9.29496C0 4.17485 4.17485 0 9.29496 0C14.4151 0 18.5899 4.17485 18.5899 9.29496C18.5899 14.4151 14.4151 18.5899 9.29496 18.5899ZM9.29496 1.10279C4.77351 1.10279 1.10279 4.77351 1.10279 9.29496C1.10279 13.8164 4.77351 17.4871 9.29496 17.4871C13.8164 17.4871 17.4871 13.8164 17.4871 9.29496C17.4871 4.77351 13.8164 1.10279 9.29496 1.10279Z" fill="black"></path><path d="M16.3409 15.2585L15.5612 16.0383L21.2202 21.6973L21.9999 20.9175L16.3409 15.2585Z" fill="black"></path></svg></span></button>
			</div>
			<div class="result-ingredient"></div>
		</form>
		{!!$html!!}
	</div>
</section>
@endsection
@section('footer')
<style>
	.form-ingredient .form-group{
		width: 100%;
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
	.browse-search-header h1 {
	    color: #000;
	    font-size: 24px;
	    letter-spacing: .04em;
	}
	.other_search_indexes{
		display: none;
	}
	.listings-pagination-wrapper {
	    margin: 0 auto;
	    max-width: 926px;
	}
	.product-page-nav {
	    align-items: center;
	    justify-content: center;
	    margin-bottom: 40px;
	    position: relative;
	}
	.product-page-nav .pages {
	        font-size: 16px;
	    align-items: center;
	}
	.product-page-nav .disabled {
	    visibility: hidden;
	}
	.product-page-nav .previous_page {
	    background-repeat: no-repeat;
	    background-size: auto;
	    min-width: 20px;
	    margin-right: 5px;
	    position: absolute;
	    left: 0;
	}
	.product-page-nav .pages .current {
	    background-color: #e3e3e3;
	    border-radius: 50%;
	    display: flex;
	    align-items: center;
	    justify-content: center;
	    font-weight: 500;
	    font-size: 1.2em;
	    height: 30px;
	    width: 30px;
	    font-style: normal;
	}
	.product-page-nav .pages a:not(.previous_page):not(.next_page) {
	    color: #000;
	    height: 30px;
	    width: 30px;
	    display: flex;
	    align-items: center;
	    justify-content: center;
	}
	.product-listings {
	    grid-template-columns: 1fr 1fr 1fr;
	    padding: 0;
	    display: grid;
	    flex: 1;
	    gap: 10px;
	    grid-auto-rows: min-content;
	    margin-bottom: 30px;
	    max-width: 926px;
	}
	.product-tile {
	    box-shadow: 0 1px 20px 0 rgba(0,0,0,.15);
	    display: flex;
	    flex-direction: column;
	}
	.product-tile .product-image-wrapper {
	    align-items: center;
	    justify-content: center;
	    margin: 0 auto;
	    padding: 0 10px;
        height: 260px;
	    padding: 0;
	    width: 240px;
	}
	.product-tile .product-image {
        max-height: 240px;
	    margin-top: 16px;
	    margin-bottom: 8px;
	    max-width: 100%;
	}
	.product-tile .text-wrapper {
	    height: 94px;
	    padding: 20px;
	}
	.product-tile .product-name {
	    font-size: 16px;
	    font-weight: 300;
	    letter-spacing: .02em;
	}
	.product-tile .product-data-availability {
	    background-color: #f8f8f8;
	    height: 58px;
	}
	.product-tile .product-score {
	    align-items: center;
	    display: flex;
	    padding: 10px 20px;
	}
	.product-tile .product-score-img {
	    margin-bottom: 6px;
	    margin-right: 10px;
        height: 32px;
	}
	.product-tile .data-level {
	    font-size: 13px;
	}
	.flex {
	    display: flex;
	}

	.browse-search-header, .form-ingredient{
		width: 926px;
    	margin: auto;
	}
	.product-page-nav .previous_page img, .product-page-nav .next_page img {
	    margin: 0 10px;
	    width: 10px;
	}
	.product-page-nav .next_page {
	    background-repeat: no-repeat;
	    background-size: auto;
	    min-width: 20px;
	    margin-left: 5px;
	    position: absolute;
	    right: 0;
	    display: flex;
	}
	@media(max-width: 568px){
		.browse-search-header, .form-ingredient{
			width: 100%;
		}
		.product-listings{
			grid-template-columns:1fr 1fr;
		}
		.product-tile .product-image-wrapper{
			align-items: center;
		    height: 106px;
		    justify-content: center;
		    margin: 0 auto;
		    padding: 0 10px;
		    width: 160px;
		}
		.product-tile .data-level {
		    font-size: 12px;
		}
		.product-tile .product-name {
		    font-size: 12px;
		    font-weight: 300;
		    letter-spacing: .02em;
		}
		.product-tile .product-score{
			padding: 10px 10px;
		}
	}
</style>
<script>
	$('.product-score-img').each(function(){
		var src = $(this).attr('src');
		$(this).attr('src','https://www.ewg.org'+src);
	});
	var next = $('.next_page img').attr('src');
	$('.next_page img').attr('src','https://www.ewg.org'+next);
	var previous = $('.previous_page img').attr('src');
	$('.previous_page img').attr('src','https://www.ewg.org'+previous);
</script>
@endsection