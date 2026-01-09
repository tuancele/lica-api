@extends('Website::layout',['image' => ''])
@section('title', 'Đổi mật khẩu')
@section('description','Đổi mật khẩu')
@section('content')
<section class="mt-4">
	<div class="wrapper-container2 mb-5">
		<div class="row">
			<div class="col-12 col-md-4">
				<div class="breadcrumb d-block d-md-none">
		            <ol>
		                <li><a href="/">Trang chủ</a></li>
		                <li><a href="{{route('account.profile')}}">Tài khoản</a></li>
                        <li><a href="{{route('account.password')}}">Đổi mật khẩu</a></li>
		            </ol>
		        </div>
				@include('Website::member.sidebar',['active' => 'password'])
			</div>
			<div class="col-12 col-md-8 mt-4 mt-md-0">
				<div class="breadcrumb d-none d-md-block">
		            <ol>
		                <li><a href="/">Trang chủ</a></li>
		                <li><a href="{{route('account.profile')}}">Tài khoản</a></li>
                        <li><a href="{{route('account.password')}}">Đổi mật khẩu</a></li>
		            </ol>
		        </div>
		        <h1 class="title_account">Đổi mật khẩu</h1>
		        <form method="post" action="{{route('account.password.update')}}">
		        	@csrf
                    <input type="hidden" name="recaptcha" id="recaptchaAccount">
		        	@if (session('error'))
			          <div class="alert alert-danger mb-3">
			              <ul><li> {{ session('error') }}</li></ul>
			          </div>
			        @endif
			        @if (session('success'))
			          <div class="alert alert-success mb-3">
			             {!! session('success') !!}
			          </div>
			        @endif
		        	<div class="row">
                        <div class="col-12 col-md-8">
                            <div class="row">
                            <div class="col-12 mb-3">
                                <label>Mật khẩu hiện tại <span>*</span></label>
                                <input type="password" name="password_current" value="{{old('password_current')}}" class="form-control" required>
                                @error('password_current')<label class="error">{{ $message }}</label>@enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label>Mật khẩu mới <span>*</span></label>
                                <input type="password" name="password_new" value="{{old('password_new')}}" class="form-control" required min="6">
                                @error('password_new')<label class="error">{{ $message }}</label>@enderror
                            </div>
                            <div class="col-12 mb-3">
                                <label>Nhập lại mật khẩu mới <span>*</span></label>
                                <input type="password" name="confirm" value="{{old('confirm')}}" class="form-control" required>
                                @error('confirm')<label class="error">{{ $message }}</label>@enderror
                            </div>
                            </div>
                        </div>
		        		
		        	</div>
		        	<div class="text-start">
		        		<button type="submit" class="btn_save bg_gradient">Lưu</button>
		        	</div>
		        </form>
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