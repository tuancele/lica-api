@php $subtotal = $order->total - $order->sale;$feeship = 0; @endphp
@if($success)
 	@php $feeship = $fee->fee;@endphp
@else
	<script>alert('{{$message}}')</script>
@endif
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <h5 class="fs-15">Cửa hàng/Kho lấy hàng</h5>
            <select class="form-control form-group" name="pick_id">
                @if(isset($picks) && !empty($picks))
                @foreach($picks as $pick)
                <option value="{{$pick->id}}">{{$pick->address}}, {{$pick->ward->name??''}}, {{$pick->district->name??''}}, {{$pick->province->name??''}}</option>
                @endforeach
                @endif
            </select>
        </div>
        <h5 class="fs-15">Thông tin đơn hàng</h5>
        <table class="table table-bordered">
            <tr>
                <td style="width: 30%">Giá trị đơn hàng:<p style="margin-bottom: 0px;font-size:12px;">(Tính cả phụ phí)</p></td>
                <td style="width: 70%"><strong>{{number_format($subtotal)}} VND</strong></td>
            </tr>
            <tr>
                <td style="width: 30%">Phí ship:</td>
                <td style="width: 70%"><strong>{{number_format($feeship)}} VND</strong>
                    <p style="margin-bottom: 0px;font-size:12px;">(Phí ship này là có thể không phải của GHTK. Khi đăng đơn lên GHTK phí ship sẽ được tính lại)</p>
                    <input type="hidden" name="feeship" value="{{$feeship}}">
                </td>
            </tr>
        </table>
        <h5 class="fs-15">Thông tin người nhận hàng</h5>
        <table class="table table-bordered">
            <tr>
                <td style="width: 40%"><strong>SĐT:</strong></td>
                <td style="width: 60%">{{$order->phone}}</td>
            </tr>
            <tr>
                <td style="width: 40%"><strong>Họ tên:</strong></td>
                <td style="width: 60%">{{$order->name}}</td>
            </tr>
            <tr>
                <td style="width: 40%"><strong>Tên thôn/ấp/xóm/tổ/…</strong></td>
                <td style="width: 60%">
                    <input type="text" name="hamlet" class="form-control" value="Khác">
                </td>
            </tr>
            <tr>
                <td style="width: 40%"><strong>Địa chỉ:</strong></td>
                <td style="width: 60%">{{$order->address}}</td>
            </tr>
            <tr>
                <td style="width: 40%"><strong>Xã/phường:</strong></td>
                <td style="width: 60%">{{$order->ward->name??''}}</td>
            </tr>
            <tr>
                <td style="width: 40%"><strong>Quận/huyện:</strong></td>
                <td style="width: 60%">{{$order->district->name??''}}</td>
            </tr>
            <tr>
                <td style="width: 40%"><strong>Tỉnh/thành phố:</strong></td>
                <td style="width: 60%">{{str_replace(array('Tỉnh ','Thành phố '),'', $order->province->name??'')}}</td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h5 class="fs-15" style="margin-bottom: 3px;">Thông tin đăng đơn lên GHTK</h5>
        <p style="color:red;font-size:12px">(Chỉnh sửa thông số sẽ ảnh hưởng tới phí ship hiện tại)</p>
        <table class="table table-bordered">
            <tr>
                <td style="width: 30%"><strong>Phí ship:</strong></td>
                <td style="width: 70%">
                    <label style="margin-right: 15px;font-weight: normal;"><input type="radio" value="0" checked="" style="margin-right: 7px;float:left;margin-top:2px" name="is_freeship">Khách trả</label>
                    <label style="font-weight: normal;"><input style="margin-right: 7px;float:left;margin-top:2px" type="radio" value="1" name="is_freeship">Shop trả</label>
                </td>
            </tr>
            <tr>
                <td style="width: 30%"><strong>Tiền thu hộ:</strong></td>
                <td style="width: 70%">
                    <input data-subtotal="{{$subtotal}}" data-total="{{$subtotal + $feeship}}" type="text" value="{{$subtotal}}" class="form-control" name="thuho">
                    <label style="width: 100%;margin-top:5px;font-weight: normal;"><input style="float:left;margin-top:2px;    margin-right: 5px;" type="checkbox" name="payment" id="payment"> Khách đã chuyển khoản thì thu hộ = 0</label>
                </td>
            </tr>
            <tr>
                <td style="width: 30%"><strong>Gửi hàng tại bưu cục?:</strong></td>
                <td style="width: 70%">
                    <label style="margin-right: 15px;font-weight: normal;width: 100%;"><input type="radio" value="cod" checked="" style="margin-right: 7px;float:left;margin-top:2px" name="pick_option">Shipper đến lấy hàng</label>
                    <label style="font-weight: normal; width: 100%;"><input style="margin-right: 7px;float:left;margin-top:2px" type="radio" value="post" name="pick_option">Shop gửi hàng tại bưu cục</label>
                </td>
            </tr>
            <tr>
                <td style="width: 30%"><strong>Hình thức vận chuyển:</strong></td>
                <td style="width: 70%">
                    <label style="margin-right: 15px;font-weight: normal;"><input type="radio" value="road" checked="" style="margin-right: 7px;float:left;margin-top:2px" name="transport">Đường bộ</label>
                    <label style="font-weight: normal;margin-right: 15px"><input  style="margin-right: 7px;float:left;margin-top:2px" type="radio" value="fly" name="transport">Đường bay</label>
                    <label style="font-weight: normal;"><input  style="margin-right: 7px;float:left;margin-top:2px" type="radio" value="xteam" name="transport">xFast</label>
                </td>
            </tr>
            <tr>
                <td style="width: 30%"><strong>Hàng dễ vỡ:</strong></td>
                <td style="width: 70%">
                    <label style="margin-right: 15px;font-weight: normal;"><input type="radio" value="0" checked="" style="margin-right: 7px;float:left;margin-top:2px" name="tags">Tiêu chuẩn</label>
                    <label style="font-weight: normal;"><input style="margin-right: 7px;float:left;margin-top:2px" type="radio" value="1" name="tags">Dễ vỡ</label>
                </td>
            </tr>
            <tr>
                <td style="width: 30%"><strong>Giá trị hàng hóa:</strong></td>
                <td style="width: 70%"><input value="{{$order->total - $order->sale}}" type="text" name="giatridon" class="form-control"><p style="font-size: 12px;margin-top:5px;">Áp dụng để tính bảo hiểm đơn hàng. Có thể thay đổi để tránh phí bảo hiểm.</p></td>
            </tr>
            <tr>
                <td style="width: 30%"><strong>Tổng khối lượng đơn hàng:</strong></td>
                <td style="width: 70%">
                    <div style="overflow: hidden;"><input style="width: 150px;" type="number" min="0" name="total_weight" value="{{$weight}}" class="form-control pull-left"> <span class="pull-left" style="line-height: 34px;margin-left: 5px;">kg</span></div> <div style="margin-top:5px;">(Nếu để trống hệ thống sẽ tự động lấy khối lượng của các sản phẩm)</div></td>
            </tr>
            <tr>
                <td style="width: 30%"><strong>Ca lấy hàng:</strong></td>
                <td style="width: 70%">
                    <select class="form-control pull-left" style="width:150px" name="pick_work_shift">
                        <option value="">Mặc định</option>
                        <option value="1">Buổi sáng</option>
                        <option value="2">Buổi chiều</option>
                        <option value="3">Buổi tối</option>
                    </select>
                    <span class="pull-left" style="line-height: 34px;margin-left: 5px;">Không bắt buộc.</span>
                </td>
            </tr>
            <tr>
                <td style="width: 30%"><strong>Hẹn ngày lấy hàng:</strong></td>
                <td style="width: 70%"><input style="width:150px" type="date" name="pick_date" class="form-control pull-left"><span class="pull-left" style="line-height: 34px;margin-left: 5px;">Không bắt buộc.</span></td>
            </tr>
            <tr>
                <td style="width: 30%"><strong>Ghi chú cho GHTK khi giao hàng:</strong></td>
                <td style="width: 70%"><textarea name="note" class="form-control" rows="3"></textarea></td>
            </tr>
        </table>
    </div>
</div>
<script>
	$('#payment').click(function(){
        if(this.checked){
            $('input[name="thuho"]').val('0');
        }else{
            var subtotal = $('input[name="thuho"]').attr('data-subtotal');
            var is_freeship = $('input[name="is_freeship"]:checked').val();
            if(is_freeship == 0){
                $('input[name="thuho"]').val(subtotal);
            }else{
                var feeship = $('input[name="feeship"]').val();
                var total = parseInt(subtotal) + parseInt(feeship);
                $('input[name="thuho"]').val(total);
            }
        }
    });
    $('input[name="is_freeship"]').click(function(){
        var is_freeship = $('input[name="is_freeship"]:checked').val();
        var subtotal = $('input[name="thuho"]').attr('data-subtotal');
        if($('#payment').is(':checked')){
        	$('input[name="thuho"]').val('0');
        }else{
        	if(is_freeship == 0){
	            $('input[name="thuho"]').val(subtotal);
	        }else{
	            var feeship = $('input[name="feeship"]').val();
	            var total = parseInt(subtotal) + parseInt(feeship);
	            $('input[name="thuho"]').val(total);
	        }
        }
    });
</script>