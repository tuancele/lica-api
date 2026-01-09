@extends('Layout::layout')
@section('title','Thêm địa chỉ lấy hàng')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Thêm địa chỉ lấy hàng',
])
<section class="content">
    <form role="form" id="tblForm" method="post" ajax="{{route('pick.store')}}">
        @csrf
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="inputEmail3" class="control-label">Tên kho: </label>
                            <input type="text" name="name" class="form-control" data-validation="required length" data-validation-error-msg="Không được bỏ trống" data-validation-length="max250" data-validation-error-msg-length="Không được vượt quá 250 ký tự!">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Điện thoại: </label>
                            <input type="text" name="tel" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="control-label">Email: </label>
                            <input type="text" name="email" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Tỉnh/Thành phố: </label>
                            <select class="form-control" name="province_id" id="province">
                                {!!$province!!}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Quận/Huyện: </label>
                            <select class="form-control" name="district_id" id="district">
                                <option value="">---</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Phường/Xã: </label>
                            <select class="form-control" name="ward_id" id="ward">
                                <option value="">---</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label class="control-label">Tên đường: </label>
                            <input type="text" name="street" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="control-label">Địa chỉ: </label>
                            <input type="text" name="address" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="fix_action">
            @include('Layout::action',['link'=>route('pick')])
        </div>
    </form>
</section>
<script>
    $('#province').change(function(){
        var province = $(this).val();
        $("#district").load("/admin/pick/district/"+province);
    });
    $('#district').change(function(){
        var district = $(this).val();
        $("#ward").load("/admin/pick/ward/"+district);
    });
</script>
@endsection