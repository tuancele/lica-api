<div class="div-info">
	<div class="box-account">
		<div class="icon-user">
			<svg width="29" height="29" viewBox="0 0 29 29" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M14.5 0C6.50896 0 0 6.50896 0 14.5C0 22.491 6.50896 29 14.5 29C22.491 29 29 22.491 29 14.5C29 6.50896 22.5063 0 14.5 0ZM14.5 1.06955C21.9104 1.06955 27.9305 7.08957 27.9305 14.5C27.9305 17.7392 26.7845 20.7034 24.8746 23.0258C24.3093 21.1006 23.148 19.3588 21.4979 18.0448C20.2908 17.0669 18.8699 16.3641 17.3419 15.9821C19.2366 14.9737 20.52 12.9721 20.52 10.6802C20.52 7.36459 17.8156 4.66017 14.5 4.66017C11.1844 4.66017 8.47998 7.33404 8.47998 10.6649C8.47998 12.9568 9.76344 14.9584 11.6581 15.9668C10.1301 16.3488 8.70917 17.0516 7.50211 18.0295C5.86723 19.3435 4.69073 21.0854 4.12539 23.0105C2.21549 20.6881 1.06955 17.7239 1.06955 14.4847C1.08483 7.08957 7.10485 1.06955 14.5 1.06955ZM14.5 15.6154C11.765 15.6154 9.54952 13.3999 9.54952 10.6649C9.54952 7.92993 11.765 5.71444 14.5 5.71444C17.235 5.71444 19.4505 7.92993 19.4505 10.6649C19.4505 13.3999 17.235 15.6154 14.5 15.6154ZM14.5 27.9152C10.7871 27.9152 7.42571 26.4025 4.99631 23.9578C5.40885 21.9868 6.52423 20.1839 8.17439 18.8546C9.9315 17.4489 12.1776 16.6697 14.5 16.6697C16.8224 16.6697 19.0685 17.4489 20.8256 18.8546C22.4758 20.1839 23.5911 21.9868 24.0037 23.9578C21.5743 26.4025 18.2129 27.9152 14.5 27.9152Z" fill="black"></path></svg>
		</div>
		<div class="name-user">
			@php $member = auth()->guard('member')->user(); @endphp
			{{$member['first_name']}} {{$member['last_name']}}
		</div>
		
	</div>
	<a href="#" class="quyentloi">Xem tất cả quyền lợi <span>></span></a>
</div>
<ul class="list-action mt-5 mb-5 d-none d-md-block">
	<li class="mt-3"><a href="{{route('account.profile')}}" @if($active == 'profile')class="active"@endif>Tài khoản</a></li>
	<li class="mt-3"><a href="{{route('account.orders')}}" @if($active == 'orders')class="active"@endif>Đơn hàng</a></li>
	<li class="mt-3"><a href="{{route('account.address')}}" @if($active == 'address')class="active"@endif>Địa chỉ giao nhận</a></li>
	<li class="mt-3"><a href="{{route('account.promotion')}}" @if($active == 'promotion')class="active"@endif>Ưu đãi của tôi</a></li>
	<li class="mt-3"><a href="{{route('account.password')}}" @if($active == 'password')class="active"@endif>Đổi mật khẩu</a></li>
	<li class="mt-3"><a href="{{route('account.logout')}}" @if($active == 'logout')class="active"@endif>Đăng xuất</a></li>
</ul>