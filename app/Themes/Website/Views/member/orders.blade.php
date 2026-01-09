@extends('Website::layout',['image' => ''])
@section('title', 'Đơn hàng')
@section('description','Đơn hàng')
@section('content')
<section class="mt-4">
	<div class="wrapper-container2 mb-5">
		<div class="row">
			<div class="col-12 col-md-4">
				<div class="breadcrumb d-block d-md-none">
		            <ol>
		                <li><a href="/">Trang chủ</a></li>
		                <li><a href="{{route('account.profile')}}">Tài khoản</a></li>
                        <li><a href="{{route('account.orders')}}">Đơn hàng</a></li>
		            </ol>
		        </div>
				@include('Website::member.sidebar',['active' => 'orders'])
			</div>
			<div class="col-12 col-md-8 mt-4 mt-md-0">
				<div class="breadcrumb d-none d-md-block">
		            <ol>
		                <li><a href="/">Trang chủ</a></li>
		                <li><a href="{{route('account.profile')}}">Tài khoản</a></li>
                        <li><a href="{{route('account.orders')}}">Đơn hàng</a></li>
		            </ol>
		        </div>
		        <h1 class="title_account">Đơn hàng</h1>
                <div class="table-responsive-md">
			        <table class="table">
		        		<thead>
		        			<tr>
						      <th scope="col">Đơn hàng</th>
						      <th scope="col">Ngày</th>
						      <th scope="col">Địa chỉ</th>
						      <th scope="col">Giá trị đơn hàng</th>
						      <th scope="col">TT thanh toán</th>
						      <th scope="col">TT vận chuyển</th>
						    </tr>
		        		</thead>
		        		<body>
		        			@if($orders->count() > 0)
		        			@foreach($orders as $order)
		        			<tr>
		        				<td><a href="{{route('account.order',['code' => $order->code])}}" class="text-decoration-underline">{{$order->code}}</a></td>
		        				<td>{{formatDate($order->created_at)}}</td>
		        				<td>{{$order->address}}</td>
		        				<td>{{formatPrice($order->total)}}</td>
		        				<td>
		        					@if($order->payment == 2) Hoàn trả @elseif($order->payment == 1) Đã thanh toán @else Chưa thanh toán @endif
								</td>
		        				<td>@if($order->ship == 3) Hoàn trả @elseif($order->ship == 2) Đã nhận @elseif($order->ship) Đã giao hàng @else Chưa giao hàng @endif</td>
		        			</tr>
		        			@endforeach
		        			@else
		        			<tr>
		        				<td class="text-center" colspan="6">Không có đơn hàng nào</td>
		        			</tr>
		        			@endif
		        		</body>
		        	</table>
		        </div>
			</div>
		</div>
	</div>	
</section>
@endsection
@section('footer')
@if(getConfig('recaptcha_status'))
<script src="https://www.google.com/recaptcha/api.js?render={{getConfig('recaptcha_site_key')}}"></script>
<script type="text/javascript">
  grecaptcha.ready(function () {
      grecaptcha.execute("{{getConfig('recaptcha_site_key')}}", { action: "submit" }).then(function (token) {
          document.getElementById("recaptchaAccount").value = token;
      });
  });
</script>
@endif
@endsection