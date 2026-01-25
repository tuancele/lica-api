<div class="mb-2">
    <label>Tên người mua <span>*</span></label>
    <input class="form-control" type="text" name="full_name" placeholder="" required>
</div>
<div class="row mb-2">
    <div class="col-6">
        <label>Số điện thoại <span>*</span></label>
        <input class="form-control" type="text" name="phone" placeholder="" required>
    </div>
    <div class="col-6">
        <label>Email</label>
        <input class="form-control" type="email" name="email" placeholder="">
    </div>
</div>
<div class="align-center space-between mb-2 mt-3">
    <span class="fs-18 fw-bold">Thông tin nhận hàng</span>
</div>
<div class="mb-2 position-relative">
    <label>Địa chỉ: <span>*</span></label>
    <input type="text" class="form-control" id="search_location_input" autocomplete="off" placeholder="Nhập Xã, Huyện, Tỉnh để gợi ý địa chỉ">
    <div id="search_location_results" class="autocomplete-results"></div>
    <input type="hidden" name="province_id" id="province_id" required>
    <input type="hidden" name="district_id" id="district_id" required>
    <input type="hidden" name="ward_id" id="ward_id" required>
</div>
<div class="mb-2">
    <label>Chi tiết địa chỉ</label>
    <input type="text" name="address" class="form-control" required>
</div>



