@extends('Website::layout',['image' => $detail->image])
@section('title', ($detail->seo_title)?$detail->seo_title:$detail->name)
@section('description',$detail->seo_description)
@section('content')
<section class="pt-5 pb-5">
	<div class="container-lg">
		<div class="row">
			<div class="col-12 col-md-12">
				 <div class="row">
				 		<div class="col-12 col-md-6">
				 			<h3 class="title_detail">Thông tin liên hệ</h3>
				 			<div class="entry-content">
				 					{!!$detail->content!!}
				 			</div>
				 		</div>
				 		<div class="col-12 col-md-6">
				 			 <h3 class="title_detail">Gửi tin nhắn</h3>
				 			 <form class="form-contact mb-3" method="post" action="/contact/contact">
								@csrf
								<input type="hidden" name="recaptcha" id="recaptcha">
								<div class="form-group mb-2">
									<label>Họ và tên</label>
									<input type="text" name="name" tabindex="1" class="form-control w-100" required>
								</div>
								<div class="form-group mb-2">
									<label>Địa chỉ email</label>
									<input type="email" name="email" tabindex="2" class="form-control w-100">
								</div>
								<div class="form-group mb-2">
									<label>Điện thoại</label>
									<input type="tel" autocomplete="off" tabindex="3" name="phone" pattern="(\+84|0){1}(9|8|7|5|3){1}[0-9]{8}" class="w-100 form-control" required>
								</div>
								<div class="form-group mb-2">
									<label>Nội dung</label>
									<textarea class="form-control" tabindex="4" rows="5" name="content"></textarea>
								</div>
								<button class="btn btn-default w-200" tabindex="5" type="submit">GỬI</button>
									@if (count($errors) >0)
				          <div class="alert alert-danger">
				              <ul>
				                  @foreach ($errors->all() as $error)
				                      <li>{{ $error }}</li>
				                  @endforeach
				              </ul>
				          </div>
				        @endif
				        @if (session('error'))
				          <div class="alert alert-danger">
				              <ul><li> {{ session('error') }}</li></ul>
				          </div>
				        @endif
				        @if (session('success'))
				          <div class="alert alert-success">
				             {!! session('success') !!}
				          </div>
				        @endif
							</form>
				 		</div>
				 </div>
				 <div class="map mb-5 mt-3">
					{!!getConfig('company_map')!!}
				</div>
			</div>
		</div>
	</div>
</section>
@if(getConfig('recaptcha_status'))
<script src="https://www.google.com/recaptcha/api.js?render={{getConfig('recaptcha_site_key')}}"></script>
<script type="text/javascript">
  grecaptcha.ready(function () {
      grecaptcha.execute("{{getConfig('recaptcha_site_key')}}", { action: "submit" }).then(function (token) {
          document.getElementById("recaptcha").value = token;
      });
  });
</script>
@endif
<script>
	 $('.map iframe').attr('width','100%').attr('height','300px');
</script>
@endsection