<div class="checkout-form-grid">
    <div class="form-group form-group-full">
        <div class="form-floating">
            <input type="text" 
                   class="form-control" 
                   id="full_name" 
                   name="full_name" 
                   placeholder="Họ và tên" 
                   required>
            <label for="full_name">Họ và tên <span class="required">*</span></label>
        </div>
    </div>
    
    <div class="form-group form-group-half">
        <div class="form-floating">
            <input type="tel" 
                   class="form-control" 
                   id="phone" 
                   name="phone" 
                   placeholder="Số điện thoại" 
                   required>
            <label for="phone">Số điện thoại <span class="required">*</span></label>
        </div>
    </div>
    
    <div class="form-group form-group-half">
        <div class="form-floating">
            <input type="email" 
                   class="form-control" 
                   id="email" 
                   name="email" 
                   placeholder="Email">
            <label for="email">Email</label>
        </div>
    </div>
</div>

<div class="form-section-divider">
    <h3 class="section-subtitle">Thông tin nhận hàng</h3>
</div>

<div class="checkout-form-grid">
    <div class="form-group form-group-full">
        <div class="form-floating position-relative">
            <input type="text" 
                   class="form-control" 
                   id="search_location_input" 
                   autocomplete="off" 
                   placeholder="Nhập Xã, Huyện, Tỉnh để tìm địa chỉ"
                   required>
            <label for="search_location_input">Địa chỉ <span class="required">*</span></label>
            <div id="search_location_results" class="autocomplete-results"></div>
            <input type="hidden" name="province_id" id="province_id" required>
            <input type="hidden" name="district_id" id="district_id" required>
            <input type="hidden" name="ward_id" id="ward_id" required>
        </div>
    </div>
    
    <div class="form-group form-group-full">
        <div class="form-floating">
            <input type="text" 
                   class="form-control" 
                   id="address_detail" 
                   name="address" 
                   placeholder="Số nhà, tên đường, phường/xã" 
                   required>
            <label for="address_detail">Chi tiết địa chỉ <span class="required">*</span></label>
        </div>
    </div>
</div>





