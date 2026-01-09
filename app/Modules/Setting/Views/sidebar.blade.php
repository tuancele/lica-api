<div class="box box-solid">
  <div class="box-body no-padding">
    <ul class="nav nav-pills nav-stacked">
      <li @if($active == '') class="active" @endif><a href="/admin/setting"> Tổng quan</a></li>
      <li @if($active == 'company') class="active" @endif><a href="/admin/setting?group=company"> Thông tin Công ty</a></li>
      <li @if($active == 'email') class="active" @endif><a href="/admin/setting?group=email"> Email</a></li>
      <li @if($active == 'facebook') class="active" @endif><a href="/admin/setting?group=facebook"> Facebook</a></li>
      <li @if($active == 'google') class="active" @endif><a href="/admin/setting?group=google"> Google</a></li>
      <li @if($active == 'social') class="active" @endif><a href="/admin/setting?group=social"> Mạng xã hội</a></li>
    </ul>
  </div><!-- /.box-body -->
</div>