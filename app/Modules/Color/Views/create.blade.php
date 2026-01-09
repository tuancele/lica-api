@extends('Layout::layout')
@section('title','Thêm màu sắc')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thêm màu sắc',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('color.store')}}">
        @csrf
        <div class="row">
            <div class="col-lg-6">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="form-group">
                            <label for="inputEmail3" class="control-label">Màu sắc</label>
                            <input type="text" name="name" class="form-control" data-validation="required length" data-validation-error-msg="Không được bỏ trống" data-validation-length="max250" data-validation-error-msg-length="Không được vượt quá 250 ký tự!">
                        </div>
                        <div class="form-group">
                        <label>Mã màu sắc:</label>
                        <div class="input-group my-colorpicker2">
                          <input type="text" class="form-control" name="color" data-validation="required" data-validation-error-msg="Không được bỏ trống"/>
                          <div class="input-group-addon">
                            <i></i>
                          </div>
                        </div>
                      </div>
                      @include('Layout::status')
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            @include('Layout::action',['link'=>route('color')])
        </div>
    </form>
</section>
<link href="/public/admin/plugins/colorpicker/bootstrap-colorpicker.min.css" rel="stylesheet" type="text/css" />
<script src="/public/admin/plugins/colorpicker/bootstrap-colorpicker.min.js" type="text/javascript"></script>
<script type="text/javascript">
    $(".my-colorpicker2").colorpicker();
</script>
@endsection