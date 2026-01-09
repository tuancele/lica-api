@extends('Website::layout',['image' => $detail->image])
@section('title', ($detail->seo_title)?$detail->seo_title:$detail->name)
@section('description',$detail->seo_description)
@section('content')
@php $member = auth()->guard('member')->user(); @endphp
<section class="pt-5 pb-5">
	<div class="container-lg">
        <div class="breadcrumb">
	        <ol class="text-center">
	            <li><a href="/">Trang chủ</a></li>
	            <li><a href="{{getSlug($detail->slug)}}">{{$detail->name}}</a></li>
	        </ol>
	    </div>
        <h1 class="fw-700 size-24 text-center">{{$detail->name}}</h1>
	</div>
	<div class="detail-wrapper-container mt-5">
		<div class="row">
		@if($list->count() > 0)
		@foreach($list as $item)
			<div class="col-md-4">
				<div class="item-promotion item-promotion-{{$item->id}}">
					<div class="box-header align-center space-between">
						<div>Mã ưu đãi</div>
						<button class="btn_copy" type="button" data-id="{{$item->id}}"><span class="me-1">{{$item->code}}</span><span role="img" aria-label="copy" class="anticon anticon-copy"><svg viewBox="64 64 896 896" focusable="false" data-icon="copy" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M832 64H296c-4.4 0-8 3.6-8 8v56c0 4.4 3.6 8 8 8h496v688c0 4.4 3.6 8 8 8h56c4.4 0 8-3.6 8-8V96c0-17.7-14.3-32-32-32zM704 192H192c-17.7 0-32 14.3-32 32v530.7c0 8.5 3.4 16.6 9.4 22.6l173.3 173.3c2.2 2.2 4.7 4 7.4 5.5v1.9h4.2c3.5 1.3 7.2 2 11 2H704c17.7 0 32-14.3 32-32V224c0-17.7-14.3-32-32-32zM350 856.2L263.9 770H350v86.2zM664 888H414V746c0-22.1-17.9-40-40-40H232V264h432v624z"></path></svg></span></button>
					</div>
					<div class="divider-promotion"></div>
					<div class="box-content">
						<h3>{{$item->name}}</h3>
						<div class="d-flex fs-12 align-center space-between">
							<p><strong>Hiệu lực từ:</strong>{{date('d/m/Y',strtotime($item->start))}}</p>
							<p><strong>Hết hạn:</strong> {{date('d/m/Y',strtotime($item->end))}}</p>
						</div>
						<p class="fs-12">{{$item->content}}</p>
						<a href="javascript:;" class="xemchitiet" data-id="{{$item->id}}">Xem chi tiết</a>
					</div>
					<div class="divider-promotion"></div>
					<div class="box-footer align-center space-between">
						<div class="fs-10">Ưu đãi không giới hạn</div>
						@if(isset($member) && !empty($member))
							@if(checkPromotion($item->id))
								<button class="btn btn-save btn-remove-promotion btn_saved" data-id="{{$item->id}}" disabled="true" type="button">Đã lưu</button>
							@else
								<button class="btn btn-save btn-save-promotion" data-id="{{$item->id}}" type="button">Lưu</button>
							@endif
						@else
						<button class="btn btn-save" type="button" data-bs-target="#myLogin" data-bs-toggle="modal">Lưu</button>
						@endif
					</div>
					<input type="text" value="{{$item->code}}" id="myInput{{$item->id}}" style="height:1px;width: 1px;    display: none;">
				</div>
			</div>
		@endforeach
		@endif
		</div>
	</div>
</section>
@endsection
@section('footer')
<div class="modal" tabindex="-1" id="myPromotion">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
    	<button  data-bs-dismiss="modal" aria-label="Close" class="btn btnClose btnClosePromotion" type="button">
	        <span class="icon">
	            <svg width="1em" height="1em" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M11.2453 9L17.5302 2.71516C17.8285 2.41741 17.9962 2.01336 17.9966 1.59191C17.997 1.17045 17.8299 0.76611 17.5322 0.467833C17.2344 0.169555 16.8304 0.00177586 16.4089 0.00140366C15.9875 0.00103146 15.5831 0.168097 15.2848 0.465848L9 6.75069L2.71516 0.465848C2.41688 0.167571 2.01233 0 1.5905 0C1.16868 0 0.764125 0.167571 0.465848 0.465848C0.167571 0.764125 0 1.16868 0 1.5905C0 2.01233 0.167571 2.41688 0.465848 2.71516L6.75069 9L0.465848 15.2848C0.167571 15.5831 0 15.9877 0 16.4095C0 16.8313 0.167571 17.2359 0.465848 17.5342C0.764125 17.8324 1.16868 18 1.5905 18C2.01233 18 2.41688 17.8324 2.71516 17.5342L9 11.2493L15.2848 17.5342C15.5831 17.8324 15.9877 18 16.4095 18C16.8313 18 17.2359 17.8324 17.5342 17.5342C17.8324 17.2359 18 16.8313 18 16.4095C18 15.9877 17.8324 15.5831 17.5342 15.2848L11.2453 9Z" fill="currentColor"></path></svg>
	        </span>
	    </button>
      	<div class="modal-body">
        	
      	</div>
    </div>
  </div>
</div>
<script>
	$('body').on('click','.btn_copy',function(){
		var id = $(this).attr('data-id');
		var copyText = document.getElementById("myInput"+id+"");
		  copyText.select();
		  copyText.setSelectionRange(0, 99999);
		  navigator.clipboard.writeText(copyText.value);
		  alert("Đã lưu vào bộ nhớ đệm")
	});
	$('body').on('click','.xemchitiet',function(){
		var id = $(this).attr('data-id');
        $.ajax({
          type: "post",
          url: "{{route('promotion')}}",
          data: { id: id},
          headers:
          {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          success: function (res) {
          	$('#myPromotion .modal-body').html(res);
            var myPromotion = new bootstrap.Modal(document.getElementById('myPromotion'))
    		myPromotion.show();
          },
          error: function(xhr, status, error){
            alert('Có lỗi xảy ra, xin vui lòng thử lại');
            window.location = window.location.href;
           }
      });
	});
	$('body').on('click','.btn-save-promotion',function(){
		var id = $(this).attr('data-id');
		$.ajax({
          type: "post",
          url: "{{route('account.addpromotion')}}",
          data: { id: id},
          headers:
          {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
          },
          beforeSend: function () {
              $('.item-promotion-'+id+' .btn-save-promotion').html('<span class="spinner-border text-light"></span>');
              $('.item-promotion-'+id+' .btn-save-promotion').prop('disabled','true');
          },
          success: function (res) {
          	 if(res.status == "success"){
          	 	$('.item-promotion-'+id+' .btn-save-promotion').html('Đã lưu');
          	 	$('.item-promotion-'+id+' .btn-save-promotion').addClass('btn_saved');
          	 }else{
          	 	alert('Có lỗi xảy ra, xin vui lòng thử lại');
          	 }
          },
          // error: function(xhr, status, error){
          //   alert('Có lỗi xảy ra, xin vui lòng thử lại');
          //   window.location = window.location.href;
          //  }
      });
	})
</script>
<style>
	@media (min-width: 776px){
		#myPromotion .modal-dialog {
		    max-width: 700px;
		    margin: 1.75rem auto;
		}
	}
	.divider-horizontal{
	    display: flex;
	    clear: both;
	    width: 100%;
	    min-width: 100%;
	    margin: 15px 0px;
        border-top: 1px solid rgba(0,0,0,.06);
        color:#000;
	}
	#myPromotion .modal-body{
		padding:0;
	}
	.ticket-code{
	    background: rgb(246, 246, 246);
	    padding: 5px 20px;
	    border-radius: 18px;
	    margin-top: 10px;
	    white-space: nowrap;
	    text-align: center;
	    color: #000;
	    font-weight: 600;
	    width: initial;
	    display: inline-block;
	    margin-bottom: 20px;
	}
	.fw-700{
		font-weight: 700;
	}
	.fs-14{
		font-size: 14px;
	}
	.promotion-content{
		padding:0px 20px;
		margin-top: 30px;
	}
	.title_modal{
	    font-weight: 700;
	    font-size: 20px;
	    margin-bottom: 10px;
	    color: #000;
	    line-height: 1.2;
	}
	.btnClosePromotion{
	    background: rgb(255, 255, 255);
	    border-radius: 20px;
	    top: -20px;
	    right: -20px;
	    width: 40px;
	    height: 40px;
	    padding: 0;
	    z-index: 9;
	}
	.ticket-container{
	    padding: 20px;
	    border-top-right-radius: 5px;
	    border-top-left-radius: 5px;
	}
	.header-detail{
	    border-radius: 5px;
	    position: relative;
	    box-sizing: border-box;
	    height: fit-content;
	    box-shadow: rgba(0, 0, 0, 0.25) 0px 0px 4px;
	    background-color: #fff;
	    padding:15px;
	}
	.header-detail:before{
		content: "";
	    position: absolute;
	    height: calc(100% - 4px);
	    width: 20px;
	    top: 2px;
	    left: -5px;
	    background: radial-gradient(circle, transparent, transparent 50%, rgb(255, 255, 255) 50%, rgb(255, 255, 255) 100%) -8px -8px / 15px 18px repeat-y;
	}
	.breadcrumb ol{
		width: 100%;
	}
	.size-24{
		font-size:24px;
	}
	.detail-wrapper-container {
	    box-sizing: content-box;
	    margin-left: auto;
	    margin-right: auto;
	    max-width: 994px;
	    position: relative;
	    width: 90%;
	}
	.item-promotion{
		border: 1px solid rgb(239, 239, 239);
	    border-radius: 10px;
	    padding: 15px 20px;
	    width: 100%;
	    height: 100%;
	}
	.box-header{
		display: flex;
	    -webkit-box-align: center;
    	align-items: center;
	}
	.item-promotion h3{
		font-size: 16px;
		font-weight: 700;
	}
	.btn_copy {
		line-height: 1.5715;
	    position: relative;
	    display: inline-block;
	    white-space: nowrap;
	    text-align: center;
	    background-image: none;
	    box-shadow: 0 2px 0 rgba(0,0,0,.015);
	    cursor: pointer;
	    transition: all .3s cubic-bezier(.645,.045,.355,1);
	    -webkit-user-select: none;
	    -moz-user-select: none;
	    -ms-user-select: none;
	    user-select: none;
	    touch-action: manipulation;
	    height: 40px;
	    padding: 8px 15px;
	    font-size: 14px;
	    border-radius: 2px;
	    color: var(--text-primary);
	    border: 1px solid #d9d9d9;
	    background: #fff;
	    border: none;
	    background: rgb(246, 246, 246);
	    border-radius: 38px;
	    font-weight: 600;
	    color: rgb(0, 0, 0);
	}
	.btn_copy:hover span{
		font-weight: 700;
	}
	.fs-12{
		font-size: 12px;
	}
	.divider-promotion{
	    margin: 15px 0px;
        background: none;
	    border: dashed rgba(0,0,0,.06);
	    border-width: 1px 0 0;
        display: flex;
	    clear: both;
	    width: 100%;
	    min-width: 100%;
	}
	.fs-10{
		font-size: 10px;
	}
	.btn-save{
		line-height: 1.5715;
	    position: relative;
	    display: inline-block;
	    font-weight: 400;
	    white-space: nowrap;
	    text-align: center;
	    background-image: none;
	    box-shadow: 0 2px 0 rgba(0,0,0,.015);
	    cursor: pointer;
	    transition: all .3s cubic-bezier(.645,.045,.355,1);
	    -webkit-user-select: none;
	    -moz-user-select: none;
	    -ms-user-select: none;
	    user-select: none;
	    touch-action: manipulation;
	    height: 40px;
	    padding: 8px 15px;
	    font-size: 14px;
	    border-radius: 2px;
	    color: var(--text-primary);
	    border: 1px solid #d9d9d9;
	    background: #fff;
		color: rgb(255, 255, 255);
	    background: #000 !important;
	    border: 1px solid #000 !important;
	    border-radius: 10px;
    	padding: 7px 30px;
	}
	.btn-save.btn_saved{
	    border: 1px solid #000 !important;
	    background-color: #fff !important;
	    color: #000 !important;
	    opacity: 1;
	}
	.btn-save:hover{
		color:#fff;
		opacity: 0.7;
	}
	.xemchitiet{
		text-decoration: underline;
		margin-top: 10px;
	}
</style>
@endsection