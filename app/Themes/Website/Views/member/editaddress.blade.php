@csrf
<input type="hidden" name="id" value="{{$detail->id}}">
<div class="row">
	<div class="col-6 mb-3">
		<input type="text" class="form-control" name="first_name" value="{{$detail->first_name}}" placeholder="Họ *" autocomplete="false">
	</div>
	<div class="col-6 mb-3">
		 <input type="text" class="form-control" name="last_name" value="{{$detail->last_name}}" placeholder="Tên *" autocomplete="false">
	</div>
</div>
<div class="mb-3">
    <input type="email" class="form-control" name="email" value="{{$detail->email}}" placeholder="Email" autocomplete="false">
</div>
<div class="mb-3">
    <input type="text" class="form-control" name="phone" value="{{$detail->phone}}" placeholder="Số điện thoại *" autocomplete="false">
</div>
<div class="mb-3">
	<select class="form-control" name="provinceid" id="Province">
		<option value="">Tỉnh/Thành phố *</option>
		{!!$province!!}
	</select>
</div>
<div class="row">
	<div class="col-6 mb-3">
		<select class="form-control" name="districtid" id="District">
    		<option value="">Quận/Huyện *</option>
            {!!$district!!}
    	</select>
	</div>
	<div class="col-6 mb-3">
		<select class="form-control" name="wardid" id="Ward">
    		<option value="">Phường/Xã *</option>
            {!!$ward!!}
    	</select>
	</div>
</div>
<div class="mb-3">
    <input type="text" class="form-control" name="address" placeholder="Địa chỉ *" value="{{$detail->address}}" autocomplete="false">
</div>
<div class="mb-3">
    <label class="fw-normal">
        <input type="checkbox" name="default" value="1">
        Đặt làm địa chỉ mặc định
    </label>
</div>
<div class="text-center">
    <button class="btn btn-default w-100" type="submit">LƯU</button>
</div>
<div class="box-alert text-center"></div>