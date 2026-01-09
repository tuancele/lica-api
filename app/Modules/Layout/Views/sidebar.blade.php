<ul class="sidebar-menu">
  @can('dashboard')
  <li class="@if(Session::get('sidebar_active')=='dashboard') active @endif treeview">
    <a href="/admin/dashboard">
      <i class="fa fa-dashboard"></i> <span>Tổng quan</span>
    </a>
  </li>
 	@endcan
  @can('display')
  <li class="treeview @if(Session::get('sidebar_active')=='website') active @endif">
    <a href="#">
      <i class="fa fa-laptop"></i> <span>Giao diện</span>
      <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
    	@can('display-home')
      <li @if(Session::get('sidebar_sub_active')=='home') class="active" @endif><a href="/admin/website/home"><i class="fa fa-circle-o"></i> Trang chủ</a></li>
      @endcan
    </ul>
  </li>
  @endcan
  @can('menu')
  <li class="treeview @if(Session::get('sidebar_active')=='menu') active @endif">
    <a href="/admin/menu">
      <i class="fa fa-list-ul" aria-hidden="true"></i> <span>Menu</span>
    </a>
  </li>
  @endcan
  @can('post')
  <li class="treeview @if(Session::get('sidebar_active')=='post') active @endif">
    <a href="#">
      <i class="fa fa-edit"></i> <span>Bài viết</span>
      <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
      <li @if(Session::get('sidebar_sub_active')=='list') class="active" @endif><a href="/admin/post"><i class="fa fa-circle-o"></i> Tất cả bài viết</a></li>
      @can('category')
      <li @if(Session::get('sidebar_sub_active')=='category') class="active" @endif><a href="/admin/category"><i class="fa fa-circle-o"></i> Chuyên mục</a></li>
      @endcan
    </ul>
  </li>
  @endcan

  @can('page')
  <li class="treeview @if(Session::get('sidebar_active')=='page') active @endif">
    <a href="/admin/page">
      <i class="fa fa-file"></i> <span>Trang nội dung</span>
    </a>
  </li>
  @endcan
  @can('media')
  <li class="treeview @if(Session::get('sidebar_active')=='media') active @endif">
    <a href="#">
      <i class="fa fa-picture-o" aria-hidden="true"></i> <span>Media</span>
      <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
      <li @if(Session::get('sidebar_sub_active')=='slider') class="active" @endif><a href="/admin/slider"><i class="fa fa-circle-o"></i> Slide</a></li>
      <li @if(Session::get('sidebar_sub_active')=='banner') class="active" @endif><a href="/admin/banner"><i class="fa fa-circle-o"></i> Banner trang chủ</a></li>
    </ul>
  </li>
  @endcan
  @can('contact')
  <li class="treeview @if(Session::get('sidebar_active')=='contact') active @endif">
    <a href="#">
      <i class="fa fa-envelope"></i></i> <span>Liên hệ</span>
      <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
      <li @if(Session::get('sidebar_sub_active')=='list') class="active" @endif><a href="/admin/contact"><i class="fa fa-circle-o"></i> Danh sách</a></li>
      <li @if(Session::get('sidebar_sub_active')=='create') class="active" @endif><a href="/admin/contact/create"><i class="fa fa-circle-o"></i>Gửi email</a></li>
    </ul>
  </li>
  @endcan
  <li class="treeview @if(Session::get('sidebar_active')=='marketing') active @endif">
    <a href="#">
        <i class="fa fa-bullhorn"></i> <span>Kênh Marketing</span>
        <i class="fa fa-angle-left pull-right"></i>
    </a>
    <ul class="treeview-menu">
        <li @if(Session::get('sidebar_sub_active')=='campaign') class="active" @endif><a href="{{route('marketing.campaign.index')}}"><i class="fa fa-circle-o"></i> Chương trình khuyến mại</a></li>
    </ul>
  </li>
  @can('setting')
  <li class="header">Hệ thống</li>
  @can('user')
  <li class="treeview @if(Session::get('sidebar_active')=='user') active @endif">
    <a href="/admin/user">
      <i class="fa fa-user"></i></i> <span>Tài khoản quản trị</span>
    </a>
  </li>
  @endcan
  @can('role')
  <li class="treeview @if(Session::get('sidebar_active')=='role') active @endif">
    <a href="/admin/role">
      <i class="fa fa-share-alt" aria-hidden="true"></i> <span>Nhóm quyền</span>
    </a>
  </li>
  @endcan
  @can('config')
  <li class="treeview @if(Session::get('sidebar_active')=='setting') active @endif">
    <a href="/admin/setting">
      <i class="fa fa-envelope"></i></i> <span>Cài đặt</span>
    </a>
  </li>
  @endcan
  @endcan
</ul>
