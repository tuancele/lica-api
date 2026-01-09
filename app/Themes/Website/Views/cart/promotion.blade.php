@if($list->count() > 0)
@foreach($list as $promotion)
	<div class="item-promotion item-promotion-{{$promotion->id}}">
		<div class="left-promotion">
		<div class="code-promotion">
			<span role="img" class="me-1"><svg width="24" height="17" viewBox="0 0 24 17" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21.042 16.32H0V0H16.605V1.35436H1.34454V14.9656H19.6975V9.99967H21.042V16.32Z" fill="#060404"></path><path d="M21.042 0H19.6975V7.31353H21.042V0Z" fill="#060404"></path><path d="M24 2.97958H16.7395V4.33394H24V2.97958Z" fill="#060404"></path><path d="M14.1176 9.18705H6.92437V0H14.1176V9.18705ZM8.26891 7.8327H12.7731V1.35436H8.26891V7.8327Z" fill="#060404"></path></svg></span>
			<span>{{$promotion->code}}</span>
		</div>
		<div class="date-time"><span role="img"><svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.267 3.63376H11.7337V3.14891C11.7337 2.88224 11.4937 2.66406 11.2003 2.66406C10.907 2.66406 10.667 2.88224 10.667 3.14891V3.63376H5.33366V3.14891C5.33366 2.88224 5.09366 2.66406 4.80033 2.66406C4.50699 2.66406 4.26699 2.88224 4.26699 3.14891V3.63376H3.73366C3.14699 3.63376 2.66699 4.07012 2.66699 4.60346V12.361C2.66699 12.8944 3.14699 13.3307 3.73366 13.3307H12.267C12.8537 13.3307 13.3337 12.8944 13.3337 12.361V4.60346C13.3337 4.07012 12.8537 3.63376 12.267 3.63376ZM11.7337 12.361H4.26699C3.97366 12.361 3.73366 12.1429 3.73366 11.8762V6.058H12.267V11.8762C12.267 12.1429 12.027 12.361 11.7337 12.361Z" fill="#868686"></path></svg></span>
			{{date('d/m/Y',strtotime($promotion->start))}}
		</div>
		<div class="name-promotion"><span role="img"><svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4.5 2.5H5.5V3.5H4.5V2.5ZM4.5 4.5H5.5V7.5H4.5V4.5ZM5 0C2.24 0 0 2.24 0 5C0 7.76 2.24 10 5 10C7.76 10 10 7.76 10 5C10 2.24 7.76 0 5 0ZM5 9C2.795 9 1 7.205 1 5C1 2.795 2.795 1 5 1C7.205 1 9 2.795 9 5C9 7.205 7.205 9 5 9Z" fill="black"></path></svg></span> {{$promotion->name}}</div>
		<button type="button" class="btn-dieukien" data-id="{{$promotion->id}}">Điều kiện</button>
		<div class="alert-item-promotion"></div>
		</div>
		<div class="right_promotion">
			@if(Session::has('ss_counpon') && Session::get('ss_counpon')['code'] == $promotion->code)
			<button type="button" data-id="{{$promotion->id}}" data-code="{{$promotion->code}}" class="btn_cancel_promotion">Hủy</button>
			@else
			<button type="button" data-id="{{$promotion->id}}" data-code="{{$promotion->code}}" class="btn_apply">Áp dụng</button>
			@endif
		</div>
	</div> 
@endforeach

<style>
	.item-promotion{
		display: flex;
		align-items: center;
	}
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
	.left-promotion{
		width: 100%;
		margin-right: 20px;
    	border-right: 1px dashed rgb(236, 236, 236);
	}
	.right_promotion{
	    width: 100px;
	}
	.right_promotion button{
		background-color: initial;
		color:#c73130;
	}
	.item-promotion{
	    border: 1px solid rgb(236, 236, 236);
	    position: relative;
	    border-radius: 10px;
	    height: 100%;
	    position: relative;
	    padding:20px;
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
@endif