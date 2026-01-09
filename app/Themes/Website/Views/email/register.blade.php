<div style="font:15px/1.35 'Helvetica Neue',Arial,sans-serif;color:#333333">
	<div style="background-color:#f0f0f0;padding:10px">
		<div style="border:1px solid #d8dfe6;background-color:#ffffff;padding:20px 10px 30px 10px">
			<div style="margin-bottom:10px">
				<a href="{{asset('')}}" title="{{getConfig('company_name')}}" target="_blank">
					<img style="width:auto;height:30px;border:0" alt="{{getConfig('company_name')}}" src="{{getImage(getConfig('logo'))}}" class="CToWUd">
				</a>
			</div>
			<div>
				<h1 style="color:#3f74b8;font-size:18px;border-bottom:solid 1px #d8dfe6;margin-top:0;padding-top:0;padding-bottom:5px;margin-bottom:20px">
					Kích hoạt tài khoản
					<span style="float:right;color:#333;font-size:13px;font-weight: normal;">Ngày: {{date('H:i:s d-m-Y')}}</span>
				</h1>
				<div style="width:100%;overflow: hidden;font-size:13px">
					<p>Chào mừng {{$data['body']['name']}} đã đăng ký thành viên tại {{asset('')}}. Bạn hãy click vào đường link sau đây để hoàn tất việc đăng ký.</p>
			        <p><a href="{{$data['body']['url']}}">{{$data['body']['url']}}</a></p>
				</div>
			</div>
		</div>
		<div style="padding:10px 0;color:#333333;font-size:13px">
			<div style="margin-bottom:5px">
				<a href="{{asset('')}}" style="color:#333333;text-decoration:none;font-size:16px;font-weight:600;" target="_blank">{{getConfig('company_name')}}</a>
			</div>
			<div style="margin-bottom:20px">
				Email:
				<a href="mailto:{{getConfig('company_email')}}" style="color:#333333;text-decoration:none" target="_blank">{{getConfig('company_email')}}</a>
				<br>
				Điện thoại:
				<a href="tel:{{getConfig('company_phone')}}" style="color:#333333;text-decoration:none" target="_blank">{{getConfig('company_phone')}}</a>
				<p style="color:#333333;margin-top:0px">Địa chỉ:{{getConfig('company_address')}}</p>
			</div>
			<div style="font-size:12px">
				{{getConfig('company_name')}}
			</div>
		</div>
	</div>
</div>