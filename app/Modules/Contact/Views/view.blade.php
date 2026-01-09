@extends('Layout::layout')
@section('title','Chi tiết liên hệ')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Chi tiết liên hệ',
])
 <form id="tblForm" method="post" delete-url="/admin/contact/delete">
<section class="content">
<div class="box">
    <div class="box-header with-border">
        <div class="row">
            <div class="col-md-12"> 
                <table class="table table-bordered table-striped">
                    <tr>
                        <td>Họ tên</td>
                        <td>{{$contact->name}}</td>
                    </tr>
                    <tr>
                        <td>Địa chỉ</td>
                        <td>{{$contact->address}}</td>
                    </tr>
                    <tr>
                        <td>Điện thoại</td>
                        <td>{{$contact->phone}}</td>
                    </tr>
                    <tr>
                        <td>Email</td>
                        <td>{{$contact->email}}</td>
                    </tr>
                    <tr>
                        <td>Nội dung</td>
                        <td>{{$contact->content}}</td>
                    </tr>
                    <tr>
                        <td>Ngày gửi</td>
                        <td>{{$contact->created_at}}</td>
                    </tr>
                </table>
                <div class="group-button">
                    <a href="/admin/contact/" class="btn btn-default"><i class="fa fa-reply" aria-hidden="true"></i> Quay lại</a>
                    <a class="btn_delete btn btn-danger" data-page="{{ app('request')->input('page') }}" data-id="{{$contact->id}}"><i class="fa fa-trash-o" aria-hidden="true"></i> Xóa</a>
                    
                </div>
            </div>
        </div>
    </div>
</div>
</section>
</form>
@endsection