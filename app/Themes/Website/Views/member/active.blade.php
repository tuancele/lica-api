@extends('Website::layout',['image' => ''])
@section('title', 'Xác thực tài khoản thành công')
@section('description','Xác thực tài khoản thành công')
@section('header')
<meta name="robots" content="noindex" />
@endsection
@section('content')
<section class="pt-5 pb-5">
	<div class="container-cs text-center">
		<div class="icon-check"><i class="fa fa-check" aria-hidden="true"></i></div>
		<p class="mw-60">Chúc mừng bạn đã kích hoạt tài khoản thành công. Bạn có thể đăng nhập vào trang quản trị thành viên.</p>
	</div>
</section>
@endsection
@section('footer')
<style>
	.icon-check{
		display: inline-block;
		width: 50px;
		height: 50px;
		background-color: #62b35d;
		color: #fff;
		font-size: 20px;
		text-align: center;
		line-height: 50px;
		border-radius: 25px;
		margin-bottom: 20px;
	}
	.mw-60{
		max-width: 45%;
		margin: auto;
		margin-bottom: 20px;
		font-size: 16px;
	}
	@media(max-width: 568px){
		.mw-60{
			width: 100%;
		}
	}
</style>
@endsection