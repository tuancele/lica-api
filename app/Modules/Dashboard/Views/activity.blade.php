@extends('Layout::layout')
@section('title','Hoạt động')
@section('content')
<link href="/public/admin/plugins/morris/morris.css" rel="stylesheet" type="text/css" />
<section class="content-header">
    <h1>
    Hoạt động
    </h1>
    <ol class="breadcrumb">
        <li><a href="/admin/dashboard">Tổng quan</a></li>
        <li><a href="/admin/dashboard/activities"> Hoạt động</a></li>
    </ol>
</section>
<div class="content-header">
  <ul class="list_breadcrumb">
    <li><a href="/admin/dashboard" >Tổng quan</a></li>
    <li><a href="/admin/dashboard/activities" class="active">Hoạt động</a></li>
  </ul>
</div>
@if($times->count() > 0)
<div class="content">
    <div class="row">
        <div class="col-md-8 col-md-offset-2">
             <ul class="timeline">
                @foreach($times as $time)
                @php $list = App\Modules\History\Models\History::whereDate('created_at',$time->datetime)->orderBy('created_at','desc')->get();@endphp
                <li class="time-label">
                  <span class="bg-default">
                    {{date('d/m/Y',strtotime($time->datetime))}}
                  </span>
                </li>
                @if($list->count() > 0)
                @foreach($list as $value)
                <li>
                    <i class="fa fa-pencil bg-blue" aria-hidden="true"></i>
                  <div class="timeline-item">
                    <span class="time"><i class="fa fa-clock-o"></i> {{date('H:i',strtotime($value->created_at))}}</span>
                    <h3 class="timeline-header fs-15">{{$value->user->name}}</h3>
                    <div class="timeline-body">
                      {!!$value->content!!}
                    </div>
                  </div>
                </li>
                @endforeach
                @endif
                @endforeach
            </ul>
            <div class="box_load hidden"><div class="ring"></div></div>
            @if($page > 0)
            <div class="text-center">
                <button class="btn btn-default btn_load" data-page="0" data-total="{{$page}}">Hiển thị thêm <i class="fa fa-angle-down" aria-hidden="true"></i></button>
            </div>
            @endif
        </div>
    </div>
</div>
@endif
<script type="text/javascript">
    $('body').on('click','.btn_load', function(){
        var page = $(this).attr('data-page');
        var total = $(this).attr('data-total');
        var now = parseInt(page) + 1;
        if(now < total){
            $(this).attr('data-page',now);
        }else{
            $(this).css('display','none');
        }
        $.ajax({
            type: 'post',
            url: '/admin/dashboard/load',
            data: {page: now},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
              $('.box_load').removeClass('hidden');
            },
            success: function (res) {
              $('.box_load').addClass('hidden');
              $('.timeline').append(res);
            }
        })
    });
</script>
@endsection