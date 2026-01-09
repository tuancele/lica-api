@extends('Layout::layout')
@section('title','Sửa link redirect')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Sửa link redirect',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="/admin/redirection/edit">
        @csrf
        <div class="row">
            <div class="col-lg-9">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Link gốc <span style="font-weight:normal;font-style:italic">(Không để ký tự / cuối link)</span></label>
                                    <input type="text" name="link_from" placeholder="https://digiplus.vn/thiet-bi-kiem-soat" value="{{$detail->link_from}}" class="form-control" data-validation="required length" data-validation-error-msg="Không được bỏ trống" data-validation-length="max250" data-validation-error-msg-length="Không được vượt quá 250 ký tự!">   
                                    <input type="hidden" name="id" value="{{$detail->id}}">          
                                </div>
                                <div class="form-group">
                                    <label for="inputEmail3" class="control-label">Link đến</label>
                                    <input type="text" name="link_to" placeholder="https://digiplus.vn/khoa-dien-tu/" value="{{$detail->link_to}}" class="form-control" data-validation="required length" data-validation-error-msg="Không được bỏ trống" data-validation-length="max250" data-validation-error-msg-length="Không được vượt quá 250 ký tự!">             
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label">Loại</label>
                                            <select class="form-control" name="type">
                                                <option value="301" @if($detail->type == '301') selected="" @endif>301</option>
                                                <option value="302" @if($detail->type == '302') selected="" @endif>302</option>
                                                <option value="307" @if($detail->type == '307') selected="" @endif>307</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label class="control-label">Trạng thái</label>
                                            <select class="form-control" name="status">
                                                <option value="1" @if($detail->status == '1') selected="" @endif>Hiển thị</option>
                                                <option value="0" @if($detail->status == '0') selected="" @endif>Ẩn</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-success"><i class="fa fa-floppy-o" aria-hidden="true"></i> Lưu lại</button>
                            <button type="reset" class="btn btn-info"><i class="fa fa-refresh" aria-hidden="true"></i> Nhập lại</button>
                            <a href="/admin/redirection" class="btn btn-primary"><i class="fa fa-list" aria-hidden="true"></i> Danh sách</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>
@endsection