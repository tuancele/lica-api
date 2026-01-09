@extends('Website::layout',['image' => $detail->image])
@section('title', ($detail->seo_title)?$detail->seo_title:$detail->name)
@section('description',$detail->seo_description)
@section('content')
<section class="sec_tracuu pt-5 mb-5">
	<div class="container-lg">
		<h1>{{$detail->name}}</h1>
		<p>{{$detail->description}}</p>
		<form class="form-ingredient mt-3" action="/skindeep/search">
			<div class="form-group">
				<input type="search" placeholder="Nhập tên thành phần, thương hiệu" name="search" id="searchIngredient" class="form-control">
				<button type="submit"><span role="img" class="icon"><svg width="22" height="22" viewBox="0 0 22 22" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.29496 18.5899C4.17485 18.5899 0 14.4151 0 9.29496C0 4.17485 4.17485 0 9.29496 0C14.4151 0 18.5899 4.17485 18.5899 9.29496C18.5899 14.4151 14.4151 18.5899 9.29496 18.5899ZM9.29496 1.10279C4.77351 1.10279 1.10279 4.77351 1.10279 9.29496C1.10279 13.8164 4.77351 17.4871 9.29496 17.4871C13.8164 17.4871 17.4871 13.8164 17.4871 9.29496C17.4871 4.77351 13.8164 1.10279 9.29496 1.10279Z" fill="black"></path><path d="M16.3409 15.2585L15.5612 16.0383L21.2202 21.6973L21.9999 20.9175L16.3409 15.2585Z" fill="black"></path></svg></span></button>
			</div>
			<div class="result-ingredient"></div>
		</form>
		<div class="list-number">
			<div class="number-wrapper">
				<div class="number products">
					{{number_format($products)}}
				</div>
				<span>Sản phẩm</span>
			</div>
			<div class="number-wrapper">
				<div class="number products">
					{{number_format($brands)}}
				</div>
				<span>Thương hiệu</span>
			</div>
		</div>
		<div class="entry-content">
			{!!$detail->content!!}
		</div>
	</div>
</section>
@endsection
@section('footer')
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
</script>
<style>
	.sec_tracuu{
		background: linear-gradient(180deg,#FBE4D1 0%,rgba(251,228,209,0) 100%);
    	background-image: url(https://static.ewg.org/skindeep/img/hero-bg-home.png),linear-gradient(180deg,#FBE4D1 0%,rgba(251,228,209,0) 100%);
	    background-repeat: no-repeat;
        background-position-y: 240px,0;
        background-position-x: initial;
	    background-size: cover;
        padding-bottom: 300px;
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
	@media(max-width: 568px){
		.form-ingredient .form-group,.result-ingredient{
			width: 100%;
		}
	}
</style>
@endsection