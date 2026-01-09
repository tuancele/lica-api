@extends('Website::layout',['image' => ''])
@section('title', $title)
@section('description', $title)
@section('content')
<section class="mb-5 radial-bg-peach">
	<div class="container">
		<form class="form-ingredient pt-5 mb-5" action="/skindeep/search">
			<div class="form-group">
				<input type="search" placeholder="Nhập tên thành phần, thương hiệu" name="search" id="searchIngredient" class="form-control">
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
	.form-ingredient{
		padding-left: 90px;
    	padding-right: 70px;
	}
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
	.radial-bg-peach{
		background: radial-gradient(50% 50% at 100% 40%,#FBE4D1 0%,rgba(251,228,209,0) 100%);
    	background-repeat: no-repeat;
	}
	.product-info-wrapper {
	    column-gap: 80px;
	    display: grid;
	    grid-template-columns: 612px 340px;
	    grid-template-rows: auto 1fr;
	    justify-content: center;
	}
	.product-score-name-wrapper {
	    display: flex;
	    margin: 0 auto;
	    max-width: 732px;
	    padding: 0 20px;
	}
	.product-score-name-wrapper .product-score {
	    max-width: 106px;
        text-align: center;
	}
	.product-score-name-wrapper .squircle {
	    margin-bottom: 8px;
	    width: 100%;
	}
	.product-wrapper {
	    align-self: flex-start;
	    display: block;
	    flex-direction: column;
	    grid-column: 2;
	    grid-row: 1/span 2;
	    margin: 0 auto 100px;
	    width: 340px;
        max-width: 732px;
        background-color: #fff;
	    box-shadow: 0 1px 20px 0 rgba(0,0,0,.15);
	    margin: 0 auto;
	    max-width: 340px;
	    padding: 30px 28px 32px;
        border: 0;
	    font: inherit;
	    font-size: 100%;
	    vertical-align: baseline;
	}
	.product-concerns-and-info {
	    padding: 44px 20px;
	}
	.product-wrapper .title {
	    color: #2b2b2b;
	    font-family: niveau-grotesk,sans-serif;
	    font-size: 11px;
	    letter-spacing: 1.5px;
	    text-transform: uppercase;
	}
	.product-wrapper .product-lower {
	    line-height: 120%;
	}
	.ingredient-concerns-inner-wrapper {
	    display: flex;
	}
	.ingredient-concerns-nav {
	    padding: 44px 66px 0 20px;
	}
	.concerns-sources-wrapper {
	    border-left: 1px solid rgba(43,43,43,.15);
	    border-top: none;
	    flex: 1;
	    padding: 24px 0;
	    padding-left: 66px;
	    padding-top: 24px;
	}
	.collapsable-block {
	    margin-bottom: 14px;
	}
	.collapsable-header {
	    align-items: center;
	    border-bottom: 1px solid rgba(43,43,43,.15);
	    cursor: pointer;
	    display: flex;
	    justify-content: space-between;
	    padding: 20px 0;
	}
	.collapsable-header h3 {
	    font-size: 20px;
	    color: #2b2b2b;
	    font-weight: 400;
	    margin-bottom: 0;
	}
	.collapsable-header .plus, .collapsable-header .minus {
	    width: 14px;
	}
	.collapsable-block-content {
	    display: none;
	    margin-top: 22px;
	}
	.collapsable-block-content .chemical-concern-table {
	    table-layout: fixed;
	    margin-bottom: 50px;
	    width: 100%;
	    word-break: break-word;
	}
	.collapsable-block-content .chemical-concern-table tr {
	    border-bottom: 1px solid rgba(43,43,43,.15);
	}
	.plus {
	    width: 10px;
	    margin-right: 15px;
	}
	.minus {
	    width: 10px;
	    margin-right: 15px;
	    display: none;
	}
	.text-block {
	    margin: 0 24px;
	}
	.product-score-name-wrapper .product-name {
	    font-size: 38px;
	    font-weight: 400;
	    letter-spacing: -1px;
	    line-height: 120%;
	    margin-bottom: 20px;
	    max-width: 418px;
	}
	.ingredient-concerns-nav li {
	    cursor: pointer;
	    font-size: 12px;
	    letter-spacing: 1.5px;
	    margin-bottom: 20px;
	    position: relative;
	}
	.ingredient-concerns-nav li.active {
	    font-weight: 600;
	}
	.ingredient-concerns-nav li.active::before {
	    content: '';
	    border-bottom: 1px solid #000;
	    left: -22px;
	    position: absolute;
	    top: 5px;
	    width: 10px;
	}
	.dn {
	    display: none!important;
	}
	.product-wrapper .btn {
	    align-items: center;
	    display: flex;
	    font-size: 11px;
	    height: 50px;
	    justify-content: center;
	    letter-spacing: 1.5px;
	    max-width: 276px;
	    margin-bottom: 12px;
	    text-align: center;
	    text-transform: uppercase;
        background-color: #2b2b2b;
    	color: #fff;
	}
	.product-wrapper .product-upper .source {
	    color: rgba(43,43,43,.7);
	    font-family: niveau-grotesk,sans-serif;
	    font-size: 10px;
	    letter-spacing: 1px;
	    margin-bottom: 46px;
	    margin-left: 20px;
	    margin-top: -30px;
	}
	@media(max-width: 568px){
		.product-info-wrapper{
			display: block;
		}
		.ingredient-concerns-inner-wrapper{
			display: block;
		}
		.ingredient-concerns-nav{
			width: 100%;
		}
		.concerns-sources-wrapper{
			width: 100%;
		    padding-left: 0px;
		    border-left: none;
	        border-top: 1px solid rgba(43,43,43,.15);
		}
		.product-score-name-wrapper .product-score {
		    margin-bottom: 20px;
		    max-width: 70px;
		    text-align: center;
		}
		.product-score-name-wrapper .product-name{
			font-size: 26px;
		}
		.form-ingredient{
			padding: 0;
		}
	}
</style>
<script>
	$('#searchIngredient').keyup(function(){
		var keyword = $(this).val();
		$.ajax({
            type: 'post',
            url: '{{route("loadIngredient")}}',
            data: {s:keyword},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                $('.result-ingredient').show();
                $('.result-ingredient').html(res);
            },
            error: function(xhr, status, error){
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
                window.location = window.location.href;
            }
        })
	});
	$('#ingredient-concerns').click(function(){
		$('#ingredient-sources').removeClass('active');
		$('.ingredient-data-sources').addClass('dn');
		$('#ingredient-concerns').addClass('active');
		$('.ingredient-concerns-blocks').removeClass('dn');
	})
	$('#ingredient-sources').click(function(){
		$('#ingredient-sources').addClass('active');
		$('.ingredient-data-sources').removeClass('dn');
		$('#ingredient-concerns').removeClass('active');
		$('.ingredient-concerns-blocks').addClass('dn');
	})
	$('body').on('click','.collapsable-header',function(){
		if($(this).attr('data-check') == 'true'){
			$(this).find('.plus').show();
			$(this).find('.minus').hide();
			$(this).attr('data-check','false');
			$(this).parent().find('.collapsable-block-content').hide();
		}else{
			$(this).find('.plus').hide();
			$(this).find('.minus').show();
			$(this).attr('data-check','true');
			$(this).parent().find('.collapsable-block-content').show();
		}
	});
	$('.ingredient-concerns-blocks a').each(function(){
		$(this).attr('href','javascript:;');
	});
	var src = $('.product-score img').attr('src');
	$('.product-score img').attr('src','https://www.ewg.org'+src);
	$('.product-upper ul li a').attr('href','javascript:;');
</script>
@endsection