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
                @include('Config::sidebar',['active' => 'social'])
            </div>
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label class="control-label">Fanpage Facebook:</label>
                            <input type="text" name="data[facebook]" class="form-control" value="{{getConfig('facebook')}}">
                        </div>
                        <div class="form-group">
                            <label class="control-label">Twitter:</label>
                            <input type="text" name="data[twitter]" class="form-control" value="{{getConfig('twitter')}}">   
                        </div> 
                        <div class="form-group">
                            <label class="control-label">Youtube:</label>
                            <input type="text" name="data[youtube]" class="form-control" value="{{getConfig('youtube')}}">
                        </div> 
                        <div class="form-group">
                            <label class="control-label">Instagram:</label>
                            <input type="text" name="data[instagram]" class="form-control" value="{{getConfig('instagram')}}">     
                        </div> 
                        <div class="form-group">
                            <label class="control-label">Linkedin:</label>
                            <input type="text" name="data[linkedin]" class="form-control" value="{{getConfig('linkedin')}}"> 
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