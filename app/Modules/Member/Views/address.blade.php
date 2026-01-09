<div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Chỉnh sửa địa chỉ</h4>
      </div>
      <form role="form" method="post" class="formEditAdress">
@csrf
<div class="modal-body">
  <div class="row">
    <div class="col-md-6">
        <div class="form-group">
          <label class="fw-700">Họ</label>
          <input type="text" name="first_name" class="form-control" value="{{$detail->first_name}}">
          <input type="hidden" name="id" value="{{$detail->id}}">
          <input type="hidden" name="member_id" value="{{$detail->member_id}}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
          <label class="fw-700">Tên</label>
          <input type="text" name="last_name" class="form-control" value="{{$detail->last_name}}">
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
        <label class="fw-700">Điện thoại</label>
        <input type="text" name="phone" class="form-control" value="{{$detail->phone}}">
        </div>
    </div>
    <div class="col-md-6">
    <div class="form-group">
      <label class="fw-700">Email</label>
      <input type="text" name="email" class="form-control" value="{{$detail->email}}">
    </div>
    </div>
  </div>

    <div class="form-group">
       <label class="fw-700">Địa chỉ nhà</label>
       <input type="text" name="address" class="form-control" value="{{$detail->address}}">
    </div>
    <div class="row">
        <div class="col-md-4">
           <div class="form-group">
               <label class="fw-700">Tỉnh/Thành phố</label>
               <select class="form-control" name="provinceid" id="province">
                   {!!$province!!}
               </select>
           </div> 
        </div>
        <div class="col-md-4">
           <div class="form-group">
               <label class="fw-700">Quận/Huyện</label>
               <select class="form-control" name="districtid" id="district">
                   {!!$district!!}
               </select>
           </div> 
        </div>
        <div class="col-md-4">
           <div class="form-group">
               <label class="fw-700">Phường/Xã</label>
               <select class="form-control" name="wardid" id="ward">
                   {!!$ward!!}
               </select>
           </div> 
        </div>
    </div>
  <div class="alert-box"></div>
</div>
<div class="modal-footer">
  <button type="submit" class="btn btn-primary">Lưu</button>
  <button type="button" class="btn btn-default" data-dismiss="modal">Hủy</button>
</div>
</form>
<script type="text/javascript" src="/public/admin/jquery.validate.min.js"></script>
<script type="text/javascript">
    $('#province').change(function(){
        var province = $(this).val();
        $("#district").load("/admin/member/district/"+province);
    });
    $('#district').change(function(){
        var district = $(this).val();
        $("#ward").load("/admin/member/ward/"+district);
    });
    $('.formEditAdress').validate({
      rules: { 
          address: {
             required: true,
          },
          first_name:{
              required: true, 
          },
          last_name:{
              required: true, 
          },
          phone: {
             required: true, 
             number: true,
          },
      },
      messages: {
          address: {
             required: "Không được bỏ trống",
          },
          first_name:{
              required: "Không được bỏ trống"
          },
          last_name:{
              required: "Không được bỏ trống"
          },
          phone: {
             required:"Không được bỏ trống",
             number: "Số điện thoại không đúng",
          }
      },
      submitHandler: function (form) {
          $.ajax({
              type: 'post',
              url:  '/admin/member/edit-address',
              data: $(form).serialize(),
              beforeSend: function () {
                  $('.box_img_load_ajax').removeClass('hidden');
              },
              success: function (res) {
                  $('.box_img_load_ajax').addClass('hidden');
                  if(res.status == 'error'){
                      var errTxt = '';
                      if(res.errors !== undefined) {
                          Object.keys(res.errors).forEach(key => {
                              errTxt += '<li>'+res.errors[key][0]+'</li>';
                          });
                      } else {
                          errTxt = '<li>'+res.message+'</li>';
                      }
                      $('body .alert-box').html('<div class="alert alert-danger alert-dismissible" role="alert"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button><h5>Thông báo!</h5><ul>'+errTxt+'</ul>');
                  }else{
                    $('body #editAddress').modal('hide');
                    $('.list_address').html(res.data);
                  }
              }
          });
          return false;
      }
  });
</script>