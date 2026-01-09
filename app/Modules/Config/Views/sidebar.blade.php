<div class="box box-solid">
  <div class="box-body no-padding">
    <ul class="nav nav-pills nav-stacked">
      <li @if($active == '') class="active" @endif><a href="/admin/config"> Tổng quan</a></li>
      <li @if($active == 'company') class="active" @endif><a href="/admin/config?group=company"> Thông tin Công ty</a></li>
      <li @if($active == 'verified') class="active" @endif><a href="/admin/config?group=verified"> Xác thực sản phẩm</a></li>
      <li @if($active == 'email') class="active" @endif><a href="/admin/config?group=email"> Email</a></li>
      <li @if($active == 'facebook') class="active" @endif><a href="/admin/config?group=facebook"> Facebook</a></li>
      <li @if($active == 'google') class="active" @endif><a href="/admin/config?group=google"> Google</a></li>
      <li @if($active == 'social') class="active" @endif><a href="/admin/config?group=social"> Mạng xã hội</a></li>
      <li @if($active == 'support') class="active" @endif><a href="/admin/config?group=support"> Hỗ trợ trực tuyến</a></li>
      <li @if($active == 'r2') class="active" @endif><a href="/admin/config?group=r2"> Cấu hình R2 Storage</a></li>
    </ul>
  </div><!-- /.box-body -->
</div>