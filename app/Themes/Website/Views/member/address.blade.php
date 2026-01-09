@extends('Website::layout',['image' => ''])
@section('title', 'Địa chỉ giao nhận')
@section('description','Địa chỉ giao nhận')
@section('content')
<section class="mt-4">
	<div class="wrapper-container2 mb-5">
		<div class="row">
			<div class="col-12 col-md-4">
        <div class="breadcrumb d-block d-md-none">
              <ol>
                  <li><a href="/">Trang chủ</a></li>
                  <li><a href="{{route('account.profile')}}">Địa chỉ giao nhận</a></li>
              </ol>
          </div>
				@include('Website::member.sidebar',['active' => 'address'])
			</div>
			<div class="col-12 col-md-8 mt-4 mt-md-0">
				    <div class="breadcrumb d-none d-md-block">
		            <ol>
		                <li><a href="/">Trang chủ</a></li>
		                <li><a href="{{route('account.profile')}}">Địa chỉ giao nhận</a></li>
		            </ol>
		        </div>
		        <div class="d-block overflow-hidden mb-2 pt-2 pe-1">
		        	<h1 class="title_account float-start">Địa chỉ giao nhận</h1>
		        	<button type="button" class="btn btn-dark float-end btn-address " data-bs-toggle="modal" data-bs-target="#addAddress">+ Thêm địa chỉ</button>
		        </div>
		        @if($addresses->count() > 0)
		        <div class="box-alert"></div>
		        <div class="row">
		        	@foreach($addresses as $address)
		        	<div class="col-12 mb-3 mb-md-0 col-md-6 item-address-{{$address->id}}">
		        		<div class="border br-10">
			        		<div class="header-address border-bottom pd-15 overflow-hidden">
			        			<div class="float-start">
			        				<strong>{{$address->first_name}} {{$address->last_name}}</strong>
			        				@if($address->is_default == 1)
			        				<p class="mb-0 mt-0 color-blue">Mặc định</p>
			        				@endif
			        			</div>
			        			<div class="group-action float-end">
			        				<button type="button" data-id="{{$address->id}}" class="btn delAddress flex-center float-end pt-0 pb-0"><span role="img" class="icon"><svg width="13" height="15" viewBox="0 0 13 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3.36332 14.9748C3.20248 14.9495 3.04165 14.9243 2.88081 14.8865C1.62094 14.5963 0.736351 13.5492 0.736351 12.3255C0.722948 9.82759 0.736351 7.32969 0.736351 4.83179C0.736351 4.41548 1.04462 4.10008 1.46011 4.10008C1.8756 4.10008 2.18386 4.40286 2.18386 4.84441C2.18386 7.31707 2.18386 9.77712 2.18386 12.2498C2.18386 13.0698 2.77359 13.6249 3.63138 13.6249C5.54799 13.6249 7.47801 13.6249 9.39462 13.6249C10.239 13.6249 10.8287 13.0698 10.8287 12.275C10.8287 9.80236 10.8287 7.32969 10.8287 4.85702C10.8287 4.47855 11.0432 4.21363 11.3917 4.13793C11.8071 4.04962 12.1958 4.28932 12.2628 4.69302C12.2762 4.76871 12.2762 4.84441 12.2762 4.9201C12.2762 7.38015 12.2762 9.8402 12.2762 12.2876C12.2762 13.7006 11.2174 14.8108 9.72969 14.9748C9.68948 14.9748 9.64928 14.9874 9.60907 15C7.53162 14.9748 5.44077 14.9748 3.36332 14.9748Z" fill="black"></path><path d="M13 2.86375C12.8526 3.26745 12.5443 3.40622 12.102 3.40622C8.3358 3.39361 4.56958 3.40622 0.803367 3.40622C0.414683 3.40622 0.146625 3.24222 0.0394023 2.93944C-0.121432 2.48528 0.227043 2.04373 0.776561 2.04373C1.64775 2.04373 2.51894 2.04373 3.40353 2.04373C3.47054 2.04373 3.53756 2.04373 3.61797 2.04373C3.61797 1.75357 3.61797 1.47603 3.61797 1.19849C3.61797 1.0471 3.61797 0.895711 3.61797 0.731707C3.61797 0.277544 3.91284 0 4.39534 0C5.45417 0 6.513 0 7.57183 0C7.9069 0 8.24197 0 8.56364 0C9.08636 0 9.36782 0.264928 9.36782 0.756939C9.36782 1.17325 9.36782 1.58957 9.36782 2.03112C9.44823 2.03112 9.51525 2.04373 9.56886 2.04373C10.3998 2.04373 11.2308 2.05635 12.0484 2.04373C12.4907 2.03112 12.8124 2.16989 12.9732 2.58621C13 2.67452 13 2.77544 13 2.86375ZM5.06549 2.04373C6.0305 2.04373 6.9821 2.04373 7.93371 2.04373C7.93371 1.81665 7.93371 1.60219 7.93371 1.38772C6.9687 1.38772 6.01709 1.38772 5.06549 1.38772C5.06549 1.60219 5.06549 1.81665 5.06549 2.04373Z" fill="black"></path><path d="M4.34173 8.49033C4.34173 7.69554 4.34173 6.91337 4.34173 6.11859C4.34173 5.79058 4.58298 5.51304 4.93146 5.44996C5.23972 5.38688 5.5882 5.55088 5.70883 5.82843C5.76244 5.92935 5.77584 6.05551 5.78924 6.16905C5.78924 7.72077 5.78924 9.2725 5.78924 10.8368C5.78924 11.2658 5.48098 11.5559 5.06549 11.5559C4.65 11.5559 4.34173 11.2532 4.34173 10.8242C4.34173 10.0547 4.34173 9.2725 4.34173 8.49033Z" fill="black"></path><path d="M8.67087 8.50294C8.67087 9.29773 8.67087 10.0799 8.67087 10.8747C8.67087 11.2027 8.45642 11.455 8.13475 11.5307C7.82648 11.6064 7.49141 11.4929 7.33058 11.2279C7.26356 11.1144 7.22335 10.963 7.22335 10.8368C7.20995 9.2725 7.22335 7.72077 7.22335 6.15643C7.22335 5.74012 7.54502 5.43734 7.96051 5.43734C8.376 5.43734 8.67087 5.74012 8.67087 6.15643C8.67087 6.95122 8.67087 7.72077 8.67087 8.50294Z" fill="black"></path></svg></span></button>
			        				<button type="button" data-id="{{$address->id}}" class="btn editAddress float-end flex-center pt-0 pb-0"><span role="img" class="icon"><svg width="13" height="15" viewBox="0 0 13 15" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.0291 0C10.2889 0.0631845 10.5358 0.139006 10.7956 0.20219C10.8606 0.214827 10.9255 0.252738 10.9905 0.290649C11.8479 0.707666 12.7702 1.83235 12.9391 2.67902C13.121 3.58888 12.9002 4.41028 12.2116 5.08003C9.97716 7.24094 7.74269 9.41449 5.50822 11.5754C5.20943 11.866 4.83269 12.0051 4.41697 12.0809C3.57255 12.2452 2.72813 12.3968 1.88371 12.5737C1.06527 12.7506 0.259822 12.2578 0.0649555 11.4743C0.0389733 11.3858 0.0259822 11.31 0 11.2216C0 11.1331 0 11.0447 0 10.9562C0.0129911 10.9436 0.0129911 10.9183 0.0259822 10.9056C0.220849 9.99579 0.415715 9.08593 0.610581 8.17607C0.688528 7.83488 0.83143 7.54423 1.09125 7.29149C3.31273 5.10531 5.5342 2.93176 7.76867 0.758214C8.09345 0.442291 8.49617 0.214827 8.93787 0.101095C9.09376 0.0631845 9.23667 0.0379107 9.39256 0C9.60042 0 9.80827 0 10.0291 0ZM1.54594 11.1205C1.5979 11.1205 1.62389 11.1205 1.64987 11.1205C2.52027 10.9815 3.37768 10.8425 4.24809 10.6908C4.32603 10.6782 4.42996 10.6403 4.48193 10.5897C6.69041 8.44145 8.8989 6.28054 11.0944 4.13227C11.64 3.60152 11.627 2.93176 11.0944 2.38837C10.9125 2.21146 10.7306 2.0219 10.5358 1.84499C10.0551 1.37742 9.28863 1.37742 8.80796 1.84499C6.59947 3.99326 4.378 6.15417 2.16951 8.31508C2.13054 8.35299 2.09157 8.41617 2.07857 8.47936C2.01362 8.74473 1.96165 9.02275 1.90969 9.30076C1.79277 9.89469 1.66286 10.5013 1.54594 11.1205Z" fill="black"></path><path d="M6.44358 13.5594C8.34028 13.5594 10.224 13.5594 12.1207 13.5594C12.6014 13.5594 12.9521 13.9385 12.8872 14.3934C12.8482 14.722 12.5364 14.9747 12.1597 15C12.1077 15 12.0557 15 12.0038 15C8.28832 15 4.55987 15 0.844421 15C0.454688 15 0.168884 14.8104 0.0649555 14.5072C-0.0909376 14.0143 0.23384 13.572 0.779465 13.572C2.66317 13.5594 4.55987 13.5594 6.44358 13.5594Z" fill="black"></path></svg></span></button>
			        			</div>
			        		</div>
			        		<div class="content-address pd-15">
			        			<p><strong>{{$address->phone}}</strong></p>
			        			<p><strong>{{$address->email}}</strong></p>
			        			<p>{{$address->address}}@if($address->ward), {{$address->ward->name}}@endif @if($address->district), {{$address->district->name}}@endif @if($address->province), {{$address->province->name}}@endif</p>
			        		</div>
		        		</div>
		        	</div>
		        	@endforeach
		        </div>
		        @endif
			</div>
		</div>
	</div>
</section>
@endsection
@section('footer')
<div class="modal" tabindex="-1" id="addAddress">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body pd-30">
        <button  data-bs-dismiss="modal" aria-label="Close" class="btn btnClose" type="button">
            <span class="icon">
                <svg width="1em" height="1em" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.2453 9L17.5302 2.71516C17.8285 2.41741 17.9962 2.01336 17.9966 1.59191C17.997 1.17045 17.8299 0.76611 17.5322 0.467833C17.2344 0.169555 16.8304 0.00177586 16.4089 0.00140366C15.9875 0.00103146 15.5831 0.168097 15.2848 0.465848L9 6.75069L2.71516 0.465848C2.41688 0.167571 2.01233 0 1.5905 0C1.16868 0 0.764125 0.167571 0.465848 0.465848C0.167571 0.764125 0 1.16868 0 1.5905C0 2.01233 0.167571 2.41688 0.465848 2.71516L6.75069 9L0.465848 15.2848C0.167571 15.5831 0 15.9877 0 16.4095C0 16.8313 0.167571 17.2359 0.465848 17.5342C0.764125 17.8324 1.16868 18 1.5905 18C2.01233 18 2.41688 17.8324 2.71516 17.5342L9 11.2493L15.2848 17.5342C15.5831 17.8324 15.9877 18 16.4095 18C16.8313 18 17.2359 17.8324 17.5342 17.5342C17.8324 17.2359 18 16.8313 18 16.4095C18 15.9877 17.8324 15.5831 17.5342 15.2848L11.2453 9Z" fill="currentColor"></path></svg>
            </span>
        </button>
        <div class="fw-bold fs-24 text-center mb-3">Thêm địa chỉ</div>
        <form class="formAddAddress mt-3" method="post">
            @csrf
            <div class="row">
            	<div class="col-6 mb-3">
            		<input type="text" class="form-control" name="first_name" placeholder="Họ *" autocomplete="false">
            	</div>
            	<div class="col-6 mb-3">
            		 <input type="text" class="form-control" name="last_name" placeholder="Tên *" autocomplete="false">
            	</div>
            </div>
            <div class="mb-3">
                <input type="email" class="form-control" name="email" placeholder="Email " autocomplete="false">
                <input type="hidden" name="recaptcha" id="recaptcha">
            </div>
            <div class="mb-3">
                <input type="text" class="form-control" name="phone" placeholder="Số điện thoại *" autocomplete="false">
            </div>
            <div class="mb-3">
            	<select class="form-control" name="provinceid" id="Province">
            		<option value="">Tỉnh/Thành phố *</option>
            		{!!$province!!}
            	</select>
            </div>
            <div class="row">
            	<div class="col-6 mb-3">
            		<select class="form-control" name="districtid" id="District">
	            		<option value="">Quận/Huyện *</option>
	            	</select>
            	</div>
            	<div class="col-6 mb-3">
            		<select class="form-control" name="wardid" id="Ward">
	            		<option value="">Phường/Xã *</option>
	            	</select>
            	</div>
            </div>
            <div class="mb-3">
                <input type="text" class="form-control" name="address" placeholder="Địa chỉ *" autocomplete="false">
            </div>
            <div class="mb-3">
			    <label class="fw-normal">
			        <input type="checkbox" name="default" value="1">
			        Đặt làm địa chỉ mặc định
			    </label>
			</div>
            <div class="text-center">
                <button class="btn btn-default w-100" type="submit">LƯU</button>
            </div>
            <div class="box-alert text-center"></div>
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
        </form>
      </div>
    </div>
  </div>
</div>
<div class="modal" tabindex="-1" id="editAddress">
  	<div class="modal-dialog modal-dialog-centered">
	    <div class="modal-content pd-30">
	    	<button  data-bs-dismiss="modal" aria-label="Close" class="btn btnClose" type="button">
			    <span class="icon">
			        <svg width="1em" height="1em" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.2453 9L17.5302 2.71516C17.8285 2.41741 17.9962 2.01336 17.9966 1.59191C17.997 1.17045 17.8299 0.76611 17.5322 0.467833C17.2344 0.169555 16.8304 0.00177586 16.4089 0.00140366C15.9875 0.00103146 15.5831 0.168097 15.2848 0.465848L9 6.75069L2.71516 0.465848C2.41688 0.167571 2.01233 0 1.5905 0C1.16868 0 0.764125 0.167571 0.465848 0.465848C0.167571 0.764125 0 1.16868 0 1.5905C0 2.01233 0.167571 2.41688 0.465848 2.71516L6.75069 9L0.465848 15.2848C0.167571 15.5831 0 15.9877 0 16.4095C0 16.8313 0.167571 17.2359 0.465848 17.5342C0.764125 17.8324 1.16868 18 1.5905 18C2.01233 18 2.41688 17.8324 2.71516 17.5342L9 11.2493L15.2848 17.5342C15.5831 17.8324 15.9877 18 16.4095 18C16.8313 18 17.2359 17.8324 17.5342 17.5342C17.8324 17.2359 18 16.8313 18 16.4095C18 15.9877 17.8324 15.5831 17.5342 15.2848L11.2453 9Z" fill="currentColor"></path></svg>
			    </span>
			</button>
			<div class="fw-bold fs-24 text-center mb-3">Chỉnh sửa địa chỉ</div>
			<form class="formEditAddress mt-3" method="post">
			</form>
		</div>
	</div>
</div>
<script>
	$('#Province').change(function(){
        var province = $(this).val();
        $("#District").load("/district/"+province);
    });
    $('#District').change(function(){
        var district = $(this).val();
        $("#Ward").load("/ward/"+district);
    });
	$('.formAddAddress').validate({
      rules: {
          first_name: {
             required: true,
             maxlength:60,
          },
          last_name: {
             required: true,
             maxlength:60,
          },
          phone: {
             required: true,
             number: true,
          },
          address:{
              required: true,
          },
          provinceid:{
          	required: true,
          },
          districtid:{
          	required: true,
          },
          wardid:{
          	required: true,
          }
      },
    	messages: {
        first_name: {
           required: "Họ là bắt buộc",
           maxlength:"Số ký tự không vượt quá 60"
        },
        last_name: {
           required: "Tên là bắt buộc",
           maxlength:"Số ký tự không vượt quá 60"
        },
        phone: {
             required: "Số điện thoại là bắt buộc",
             number: "Số điện thoại không đúng",
        },
        address:{
          required: "Địa chỉ là bắt buộc",
        },
        provinceid:{
	      	required: "Tỉnh/Thành phố là bắt buộc",
	      },
	      districtid:{
	      	required: "Quận/Huyện là bắt buộc",
	      },
	      wardid:{
	      	required: "Phường/Xã là bắt buộc",
	      }
    },
      submitHandler: function (form) {
          $.ajax({
              type: 'post',
              url:  '{{route("account.address.store")}}',
              data: $(form).serialize(),
              beforeSend: function () {
                  $('.box_img_load_ajax').removeClass('hidden');
              },
              success: function (res) {
                if(res.status == 'success'){
                  $('.formAddAddress .box-alert').html('<div class="alert alert-success mt-3" role="alert"><i class="fa fa-check" aria-hidden="true"></i> '+res.message+'</div>');
                      setTimeout(function () {
                        $('.formAddAddress .box-alert').hide();
                    }, 2000);
                    $('.formAddAddress')[0].reset();
                    window.location = res.url;
                }else{
                    var errTxt = '';
                    if(res.errors !== undefined) {
                        Object.keys(res.errors).forEach(key => {
                            errTxt += '<li>'+res.errors[key][0]+'</li>';
                        });
                    } else {
                        errTxt = res.message;
                    }
                    $('.formAddAddress .box-alert').html('<div class="alert alert-danger mt-3" role="alert"><ul>'+errTxt+'</ul></div>');
                }
              }
          });
          return false;
      }
    });

    $('body').on('click','.editAddress',function(){
    	var id = $(this).attr('data-id');
    	$.ajax({
              type: 'post',
              url:  '{{route("account.address.edit")}}',
              data: {id:id},
              headers:
	            {
	                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
	            },
              beforeSend: function () {
                  $('.box_img_load_ajax').removeClass('hidden');
              },
              success: function (res) {
              	$('.box_img_load_ajax').addClass('hidden');
              	var editAddress = new bootstrap.Modal(document.getElementById('editAddress'))
        		editAddress.show();
              	$('#editAddress .formEditAddress').html(res);
              }
          });
    });
    $('.formEditAddress').validate({
      rules: {
          first_name: {
             required: true,
             maxlength:60,
          },
          last_name: {
             required: true,
             maxlength:60,
          },
          phone: {
             required: true,
             number: true,
          },
          address:{
              required: true,
          },
          provinceid:{
          	required: true,
          },
          districtid:{
          	required: true,
          },
          wardid:{
          	required: true,
          }
      },
    	messages: {
        first_name: {
           required: "Họ là bắt buộc",
           maxlength:"Số ký tự không vượt quá 60"
        },
        last_name: {
           required: "Tên là bắt buộc",
           maxlength:"Số ký tự không vượt quá 60"
        },
        phone: {
             required: "Số điện thoại là bắt buộc",
             number: "Số điện thoại không đúng",
        },
        address:{
          required: "Địa chỉ là bắt buộc",
        },
        provinceid:{
	      	required: "Tỉnh/Thành phố là bắt buộc",
	      },
	      districtid:{
	      	required: "Quận/Huyện là bắt buộc",
	      },
	      wardid:{
	      	required: "Phường/Xã là bắt buộc",
	      }
    },
      submitHandler: function (form) {
          $.ajax({
              type: 'post',
              url:  '{{route("account.address.update")}}',
              data: $(form).serialize(),
              beforeSend: function () {
                  $('.box_img_load_ajax').removeClass('hidden');
              },
              success: function (res) {
                if(res.status == 'success'){
                  $('.formEditAddress .box-alert').html('<div class="alert alert-success mt-3" role="alert"><i class="fa fa-check" aria-hidden="true"></i> '+res.message+'</div>');
                      setTimeout(function () {
                        $('.formEditAddress .box-alert').hide();
                    }, 2000);
                    $('.formEditAddress')[0].reset();
                    window.location = res.url;
                }else{
                    var errTxt = '';
                    if(res.errors !== undefined) {
                        Object.keys(res.errors).forEach(key => {
                            errTxt += '<li>'+res.errors[key][0]+'</li>';
                        });
                    } else {
                        errTxt = res.message;
                    }
                    $('.formEditAddress .box-alert').html('<div class="alert alert-danger mt-3" role="alert"><ul>'+errTxt+'</ul></div>');
                }
              }
          });
          return false;
      }
    });
    $('body').on('click','.delAddress',function(){
    	if (confirm('Bạn có chắc muốn xóa địa chỉ này?')) {
    		var id = $(this).attr('data-id');
    		$.ajax({
              type: 'post',
              url:  '{{route("account.address.delete")}}',
              data: {id:id},
              headers:
	            {
	                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
	            },
              beforeSend: function () {
                  $('.box_img_load_ajax').removeClass('hidden');
              },
              success: function (res) {
                if(res.status == 'success'){
                	$('.item-address-'+id+'').remove();
                }else{
                    var errTxt = '';
                    if(res.errors !== undefined) {
                        Object.keys(res.errors).forEach(key => {
                            errTxt += '<li>'+res.errors[key][0]+'</li>';
                        });
                    } else {
                        errTxt = res.message;
                    }
                    $('.box-alert').html('<div class="alert alert-danger mt-3" role="alert"><ul>'+errTxt+'</ul></div>');
                }
              },error: function(xhr, status, error){
                $('.box-alert').html('<div class="alert alert-danger mt-3" role="alert"><ul>Có lỗi xảy ra, xin vui lòng thử lại</ul></div>');
            }
          });
    	}else{
	        return false;
	    }
    })
</script>
@endsection