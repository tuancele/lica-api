@extends('Layout::layout')
@section('title','Sửa xuất xứ')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Sửa xuất xứ',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('origin.update')}}">
        @csrf
        <div class="row">
            <div class="col-lg-6">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Tiêu đề:</label>
                                    <input type="text" name="name" class="form-control" value="{{$detail->name}}" data-validation="required length" data-validation-error-msg="Không được bỏ trống" data-validation-length="max250" data-validation-error-msg-length="Không được vượt quá 250 ký tự!">
                                    <input type="hidden" name="id" value="{{$detail->id}}">
                                </div>
                            </div>
                        </div>
                      @include('Layout::status',['status' => $detail->status])
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
            </div>
            <!-- /.col-lg-12 -->
        </div>
        <div class="fix_action">
            @include('Layout::action',['link'=>route('origin')])
        </div>
    </form>
</section>
@endsection