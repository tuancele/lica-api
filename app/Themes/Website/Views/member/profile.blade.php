@extends('Website::layout',['image' => ''])
@section('title', 'Tài khoản')
@section('description','Tài khoản')
@section('content')
<section class="mt-4">
	<div class="wrapper-container2 mb-5">
		<div class="row">
			<div class="col-12 col-md-4">
				<div class="breadcrumb d-block d-md-none">
		            <ol>
		                <li><a href="/">Trang chủ</a></li>
		                <li><a href="{{route('account.profile')}}">Tài khoản</a></li>
		            </ol>
		        </div>
				@include('Website::member.sidebar',['active' => 'profile'])
			</div>
			<div class="col-12 col-md-8 mt-4 mt-md-0">
				<div class="breadcrumb d-none d-md-block">
		            <ol>
		                <li><a href="/">Trang chủ</a></li>
		                <li><a href="{{route('account.profile')}}">Tài khoản</a></li>
		            </ol>
		        </div>
		        <h1 class="title_account">Tài khoản</h1>
		        <form method="post" action="{{route('account.profile.update')}}">
		        	@csrf
		        	@php $member = auth()->guard('member')->user(); @endphp
		        	<div class="row">
		        		<div class="col-6 mb-3">
		        			<label>Họ <span>*</span></label>
		        			<input type="text" name="first_name" value="{{$member['first_name']}}" class="form-control" required>
		        		</div>
		        		<div class="col-6 mb-3">
		        			<label>Tên <span>*</span></label>
		        			<input type="text" name="last_name" value="{{$member['last_name']}}" class="form-control" required>
		        		</div>
		        		<div class="col-6 mb-3">
		        			<label>Email <span>*</span></label>
		        			<input type="text" name="email" value="{{$member['email']}}" class="form-control" required>
		        		</div>
		        		<div class="col-6 mb-3">
		        			<label>Số điện thoại <span>*</span></label>
		        			<input type="text" name="phone" value="{{$member['phone']}}" class="form-control" required>
		        		</div>
		        	</div>
		        	<div class="text-end">
		        		<button type="submit" class="btn_save bg_gradient">Lưu</button>
		        	</div>
		        </form>
			</div>
		</div>
	</div>	
</section>
@endsection