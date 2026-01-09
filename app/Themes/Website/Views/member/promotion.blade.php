@extends('Website::layout',['image' => ''])
@section('title', 'Ưu đãi của tôi')
@section('description','Ưu đãi của tôi')
@section('content')
<section class="mt-4">
	<div class="wrapper-container2 mb-5">
		<div class="row">
			<div class="col-12 col-md-4">
				<div class="breadcrumb d-block d-md-none">
		            <ol>
		                <li><a href="/">Trang chủ</a></li>
		                <li><a href="{{route('account.profile')}}">Tài khoản</a></li>
                        <li><a href="{{route('account.promotion')}}">Ưu đãi của tôi</a></li>
		            </ol>
		        </div>
				@include('Website::member.sidebar',['active' => 'promotion'])
			</div>
			<div class="col-12 col-md-8 mt-4 mt-md-0">
				<div class="breadcrumb d-none d-md-block">
		            <ol>
		                <li><a href="/">Trang chủ</a></li>
		                <li><a href="{{route('account.profile')}}">Tài khoản</a></li>
                        <li><a href="{{route('account.promotion')}}">Ưu đãi của tôi</a></li>
		            </ol>
		        </div>
		        <h1 class="title_account">Ưu đãi của tôi</h1>
		        @if($list->count() > 0)
		        <div class="list-promotion row">
		        	@foreach($list as $item)
		        	@php $promotion = App\Modules\Promotion\Models\Promotion::where([['id',$item->promotion_id],['status','1']])->first();@endphp 
		        	@if(isset($promotion) && !empty($promotion))
		        	<div class="col-12 col-md-6">
		        		<div class="item-promotion">
		        			<div class="code-promotion">
		        				<span role="img" class="me-1"><svg width="24" height="17" viewBox="0 0 24 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21.042 16.32H0V0H16.605V1.35436H1.34454V14.9656H19.6975V9.99967H21.042V16.32Z" fill="#060404"></path><path d="M21.042 0H19.6975V7.31353H21.042V0Z" fill="#060404"></path><path d="M24 2.97958H16.7395V4.33394H24V2.97958Z" fill="#060404"></path><path d="M14.1176 9.18705H6.92437V0H14.1176V9.18705ZM8.26891 7.8327H12.7731V1.35436H8.26891V7.8327Z" fill="#060404"></path></svg></span>
		        				<span>{{$promotion->code}}</span>
		        			</div>
		        			<div class="date-time"><span role="img"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.267 3.63376H11.7337V3.14891C11.7337 2.88224 11.4937 2.66406 11.2003 2.66406C10.907 2.66406 10.667 2.88224 10.667 3.14891V3.63376H5.33366V3.14891C5.33366 2.88224 5.09366 2.66406 4.80033 2.66406C4.50699 2.66406 4.26699 2.88224 4.26699 3.14891V3.63376H3.73366C3.14699 3.63376 2.66699 4.07012 2.66699 4.60346V12.361C2.66699 12.8944 3.14699 13.3307 3.73366 13.3307H12.267C12.8537 13.3307 13.3337 12.8944 13.3337 12.361V4.60346C13.3337 4.07012 12.8537 3.63376 12.267 3.63376ZM11.7337 12.361H4.26699C3.97366 12.361 3.73366 12.1429 3.73366 11.8762V6.058H12.267V11.8762C12.267 12.1429 12.027 12.361 11.7337 12.361Z" fill="#868686"></path></svg></span>
		        				{{date('d/m/Y',strtotime($promotion->start))}}
		        			</div>
		        			<div class="name-promotion"><span role="img"><svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4.5 2.5H5.5V3.5H4.5V2.5ZM4.5 4.5H5.5V7.5H4.5V4.5ZM5 0C2.24 0 0 2.24 0 5C0 7.76 2.24 10 5 10C7.76 10 10 7.76 10 5C10 2.24 7.76 0 5 0ZM5 9C2.795 9 1 7.205 1 5C1 2.795 2.795 1 5 1C7.205 1 9 2.795 9 5C9 7.205 7.205 9 5 9Z" fill="black"></path></svg></span> {{$promotion->name}}</div>
		        			<button type="button" class="btn-dieukien" data-id="{{$promotion->id}}">Điều kiện</button>
		        		</div> 
		        	</div>
		        	@endif
		        	@endforeach
		        </div>
		        @endif
		    </div>
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
	$('body').on('click','.btn-dieukien',function(){
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
</script>
<style>
	.code-promotion{
		font-weight: 700;
		margin-bottom: 5px;
	}
	.code-promotion span{
		font-weight: 700;
	}
	.date-time{
		color: rgb(134, 134, 134);
		margin-bottom: 5px;
	}
	.item-promotion{
	    border: 1px solid rgb(236, 236, 236);
	    position: relative;
	    border-radius: 10px;
	    height: 100%;
	    position: relative;
	    padding:20px;
	}
	.item-promotion:before{
		content: "";
		position: absolute;
		left: 0;
		top: 0;
		height: 100%;
	    width: 5px;
	    border-top-left-radius: 10px;
	    border-bottom-left-radius: 10px;
        background: linear-gradient(90deg, rgb(255, 212, 0) 0%, rgb(199, 49, 48) 50.52%, rgb(102, 54, 149) 99.61%);
	}
	.name-promotion{
		overflow: hidden;
	    display: -webkit-box;
	    -webkit-line-clamp: 1;
	    -webkit-box-orient: vertical;
	    opacity: 0.3;
	    font-size: 12px;
	    margin-bottom: 5px;
	}
	.btn-dieukien{
	    padding: 0px;
	    background-color: initial;
	    height: auto;
	    box-shadow: none;
	    color: rgb(0, 0, 0) !important;
	    border: none !important;
	    text-decoration: underline !important;
	    font-weight: 600;
	}
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
</style>

@endsection