@extends('Layout::layout')
@section('title','Cài đặt')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Cài đặt',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/config/update">
        @csrf
        <div class="row">
            <div class="col-lg-3">
                @include('Config::sidebar',['active' => 'support'])
            </div>
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Hotline:</label>
                            <input type="text" name="data[support_hotline]" class="form-control" value="{{getConfig('support_hotline')}}">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Zalo:</label>
                            <input type="text" name="data[support_zalo]" class="form-control" value="{{getConfig('support_zalo')}}">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Message:</label>
                            <input type="text" name="data[support_message]" class="form-control" value="{{getConfig('support_message')}}">
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            <div class="form-group">
                <button type="submit" class="btn btn-success pull-right"><i class="fa fa-floppy-o" aria-hidden="true"></i> Lưu cài đặt</button>
            </div>
        </div>
    </form>
</section>
@endsection