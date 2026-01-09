@extends('Layout::layout')
@section('title','Thêm lợi ích')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thêm lợi ích',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('dictionary.benefit.store')}}">
        @csrf
        <div class="row">
            <div class="col-lg-6">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Tiêu đề: </label>
                                    <input type="text" name="name" class="form-control" data-validation="required length" data-validation-error-msg="Không được bỏ trống" data-validation-length="max250" data-validation-error-msg-length="Không được vượt quá 250 ký tự!">
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
            @include('Layout::action',['link'=>route('dictionary.benefit')])
        </div>
    </form>
</section>
@endsection