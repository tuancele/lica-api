<!DOCTYPE html>
<html lang="vi">
  <head>
    <meta charset="UTF-8">
    <title>@yield('title') | CMS Website</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <!-- Bootstrap 3.3.4 -->
    <base href="{{asset('')}}">
    <link href="/public/admin/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- FontAwesome 4.3.0 -->
    <link href="/public/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
    <!-- Ionicons 2.0.0 -->
    <link href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css" rel="stylesheet" type="text/css" />
    <!-- Theme style -->
    <link href="/public/admin/dist/css/AdminLTE.min.css" rel="stylesheet" type="text/css" />
    <link href="/public/admin/dist/css/skins/_all-skins.min.css" rel="stylesheet" type="text/css" />
    <!-- iCheck -->
    <link href="/public/admin/plugins/iCheck/flat/blue.css" rel="stylesheet" type="text/css" />
    
    <!-- Morris chart -->
    <link href="/public/admin/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css" rel="stylesheet" type="text/css" />
    <link href="/public/admin/plugins/morris/morris.css" rel="stylesheet" type="text/css" />
    <link href="/public/admin/toastr.css" rel="stylesheet">
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $apiUser = auth()->user() ?? auth('admin')->user() ?? auth('web')->user();
    @endphp
    <meta name="api-token" content="{{ $apiUser->api_token ?? '' }}">
    <script src="/public/admin/plugins/jQuery/jQuery-2.1.4.min.js"></script>
    @stack('styles')
  </head>
  <body class="skin-blue sidebar-mini">
    <div class="wrapper">
        <?php $user = Auth::user(); ?>
      <header class="main-header">
        <!-- Logo -->
        <a href="/admin/dashboard" class="logo">
          <!-- mini logo for sidebar mini 50x50 pixels -->
          <span class="logo-mini"><b>C</b>MS</span>
          <!-- logo for regular state and mobile devices -->
          <span class="logo-lg"><b>CMS</b>Website</span>
        </a>
        <!-- Header Navbar: style can be found in header.less -->
        <nav class="navbar navbar-static-top" role="navigation">
          <!-- Sidebar toggle button-->
          <a href="javascript:void(0)" class="sidebar-toggle" data-toggle="offcanvas" role="button">
            <span class="sr-only">Toggle navigation</span>
          </a>
          <div class="navbar-custom-menu">
            <ul class="nav navbar-nav">
              <li><a href="{{asset('')}}" target="_blank">Xem trang</a></li>
              @php $orders = App\Modules\Order\Models\Order::where('status','0')->orderBy('id','desc')->get(); @endphp
              <li class="dropdown messages-menu">
                <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown">
                  <i class="fa fa-shopping-cart" aria-hidden="true"></i>
                  <span class="label label-danger">{{$orders->count()}}</span>
                </a>
                <ul class="dropdown-menu">
                  <li class="header">Bạn có {{$orders->count()}} đơn hàng mới</li>
                  <li>
                    <!-- inner menu: contains the actual data -->
                    <ul class="menu">
                      @if($orders->count() > 0)
                      @foreach($orders as $order)
                      <li>
                        <a href="/admin/order/view/{{$order->code}}">
                          <h4 style="margin:0px;font-size:13px;margin-bottom:5px">
                            <strong class="text-blue">{{$order->code}}</strong> - {{$order->name}}
                          </h4>
                          <p style="margin:0px"><strong>{{number_format($order->total)}}₫</strong></p>
                          <small style="color:#aaa"><i class="fa fa-clock-o"></i> {{date('d/m/Y H:i:s',strtotime($order->created_at))}}</small>
                        </a>
                      </li>
                      @endforeach
                      @endif
                    </ul>
                  </li>
                  <li class="footer"><a href="/admin/order">Xem tất cả</a></li>
                </ul>
              </li>
              @php $contacts = App\Modules\Contact\Models\Contact::where('status','0')->orderBy('id','desc')->get(); @endphp
              <li class="dropdown messages-menu">
                <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown">
                  <i class="fa fa-envelope-o"></i>
                  <span class="label label-success">{{$contacts->count()}}</span>
                </a>
                <ul class="dropdown-menu">
                  <li class="header">Bạn có {{$contacts->count()}} tin nhắn mới</li>
                  <li>
                    <!-- inner menu: contains the actual data -->
                    <ul class="menu">
                      @if($contacts->count() > 0)
                      @foreach($contacts as $contact)
                      <li>
                        <a href="/admin/contact/read/{{$contact->id}}">
                          <h4 style="margin:0px;font-size:13px;margin-bottom:5px">
                            {{$contact->name}}
                          </h4>
                          <small style="color:#aaa"><i class="fa fa-clock-o"></i> {{date('d/m/Y H:i:s',strtotime($contact->created_at))}}</small>
                        </a>
                      </li>
                      @endforeach
                      @endif
                    </ul>
                  </li>
                  <li class="footer"><a href="/admin/contact">Xem tất cả</a></li>
                </ul>
              </li>
              <li class="dropdown user user-menu">
                <a href="javascript:void(0)" class="dropdown-toggle" data-toggle="dropdown">
                  <img src="/public/admin/dist/img/user2-160x160.jpg" class="user-image" alt="{{$user['name']}}" />
                  <span class="hidden-xs">{{$user['name']}}</span>
                </a>
                <ul class="dropdown-menu">
                  <!-- User image -->
                  <li class="user-header">
                    <img src="/public/admin/dist/img/user2-160x160.jpg" class="img-circle" alt="{{$user['name']}}" />
                    <p>
                        {{$user['name']}}
                      <small>{{date('d/m/Y H:i:s',strtotime($user['created_at']))}}</small>
                    </p>
                  </li>
                  <li class="user-footer">
                    <div class="pull-left">
                      <a href="/admin/change-password" class="btn btn-default btn-flat">Đổi mật khẩu</a>
                    </div>
                    <div class="pull-right">
                      <a href="/admin/logout" class="btn btn-default btn-flat">Đăng xuất</a>
                    </div>
                  </li>
                </ul>
              </li>
            </ul>
          </div>
        </nav>
      </header>
      <aside class="main-sidebar">
        <section class="sidebar">
          <!-- Sidebar user panel -->
          <div class="user-panel">
            <div class="pull-left image">
              <img src="/public/admin/dist/img/user2-160x160.jpg" class="img-circle" alt="User Image" />
            </div>
            <div class="pull-left info">
              <p>{{$user['name']}}</p>
              <a href="/admin/profile"><i class="fa fa-circle text-success"></i> Online</a>
            </div>
          </div>
          <ul class="sidebar-menu">
            <li class="header">BÁN HÀNG</li>
            
            <li class="@if(Session::get('sidebar_active')=='dashboard') active @endif treeview">
              <a href="/admin/dashboard">
                <i class="fa fa-dashboard"></i> <span>Tổng quan</span>
              </a>
            </li>
           	
            <li class="treeview @if(Session::get('sidebar_active')=='order') active @endif">
              <a href="/admin/order">
                <i class="fa fa-th"></i> <span>Đơn hàng</span> 
                <i class="fa fa-angle-left pull-right"></i>
              </a>
              <ul class="treeview-menu">
                <li @if(Session::get('sidebar_sub_active')=='list') class="active" @endif><a href="/admin/order"><i class="fa fa-circle-o"></i> Danh sách đơn hàng</a></li>
              </ul>
            </li>
          
            <li class="treeview @if(Session::get('sidebar_active')=='product') active @endif">
              <a href="#">
              <i class="fa fa-star" aria-hidden="true"></i>
                <span>Sản phẩm</span>
                <i class="fa fa-angle-left pull-right"></i>
              </a>
              <ul class="treeview-menu">
                <li @if(Session::get('sidebar_sub_active')=='list') class="active" @endif><a href="{{route('product')}}"><i class="fa fa-circle-o"></i> Danh sách sản phẩm</a></li>
                <li @if(Session::get('sidebar_sub_active')=='taxonomy') class="active" @endif><a href="{{route('taxonomy')}}"><i class="fa fa-circle-o"></i> Danh mục sản phẩm</a></li>
                <li @if(Session::get('sidebar_sub_active')=='origin') class="active" @endif><a href="{{route('origin')}}"><i class="fa fa-circle-o"></i> Xuất xứ</a></li>
                <li @if(Session::get('sidebar_sub_active')=='brand') class="active" @endif><a href="{{route('brand')}}"><i class="fa fa-circle-o"></i> Thương hiệu</a></li>
                {{-- Removed legacy admin ingredient module (/admin/ingredient) --}}
                {{-- Removed color and size menu items - routes have been removed --}}
                {{-- <li @if(Session::get('sidebar_sub_active')=='color') class="active" @endif><a href="{{route('color')}}"><i class="fa fa-circle-o"></i> Màu sắc</a></li> --}}
                {{-- <li @if(Session::get('sidebar_sub_active')=='size') class="active" @endif><a href="{{route('size')}}"><i class="fa fa-circle-o"></i> Kích thước</a></li> --}}
                <li @if(Session::get('sidebar_sub_active')=='rate') class="active" @endif><a href="/admin/rate"><i class="fa fa-circle-o"></i> Đánh giá sản phẩm</a></li>
                <li @if(Session::get('sidebar_sub_active')=='promotion') class="active" @endif><a href="/admin/promotion"><i class="fa fa-circle-o"></i> Mã giảm giá</a></li>
                <li @if(Session::get('sidebar_sub_active')=='search') class="active" @endif><a href="/admin/search"><i class="fa fa-circle-o"></i> Từ khóa tìm kiếm</a></li>
              </ul>
            </li>
            <li class="treeview @if(Session::get('sidebar_active')=='dictionary') active @endif">
              <a href="#">
              <i class="fa fa-book" aria-hidden="true"></i>
                <span>Thư viện thành phần</span>
                <i class="fa fa-angle-left pull-right"></i>
              </a>
              <ul class="treeview-menu">
                <li @if(Session::get('sidebar_sub_active')=='ingredient') class="active" @endif><a href="{{route('dictionary.ingredient')}}"><i class="fa fa-circle-o"></i> Danh sách thành phần</a></li>
                <li @if(Session::get('sidebar_sub_active')=='category') class="active" @endif><a href="{{route('dictionary.category')}}"><i class="fa fa-circle-o"></i> Danh mục thành phần</a></li>
                <li @if(Session::get('sidebar_sub_active')=='rate') class="active" @endif><a href="{{route('dictionary.rate')}}"><i class="fa fa-circle-o"></i> Đánh giá</a></li>
                <li @if(Session::get('sidebar_sub_active')=='benefit') class="active" @endif><a href="{{route('dictionary.benefit')}}"><i class="fa fa-circle-o"></i> Lợi ích</a></li>
              </ul>
            </li>
            <li class="treeview @if(Session::get('sidebar_active')=='compare') active @endif">
              <a href="#">
              <i class="fa fa-clone" aria-hidden="true"></i>
                <span>So sánh</span>
                <i class="fa fa-angle-left pull-right"></i>
              </a>
              <ul class="treeview-menu">
                <li @if(Session::get('sidebar_sub_active')=='list') class="active" @endif><a href="{{route('compare')}}"><i class="fa fa-circle-o"></i> Danh sách sản phẩm</a></li>
                <li @if(Session::get('sidebar_sub_active')=='store') class="active" @endif><a href="{{route('compare.store')}}"><i class="fa fa-circle-o"></i> Website lấy dữ liệu</a></li>
              </ul>
            </li>
            <li class="treeview @if(in_array(Session::get('sidebar_active'), ['marketing', 'flashsale', 'deal'])) active @endif">
              <a href="#">
                  <i class="fa fa-bullhorn"></i> <span>Kênh Marketing</span>
                  <i class="fa fa-angle-left pull-right"></i>
              </a>
              <ul class="treeview-menu">
                  <li @if(Session::get('sidebar_sub_active')=='campaign') class="active" @endif><a href="{{route('marketing.campaign.index')}}"><i class="fa fa-circle-o"></i> Chương trình khuyến mại</a></li>
                  <li @if(Session::get('sidebar_active')=='flashsale') class="active" @endif><a href="{{route('flashsale')}}"><i class="fa fa-bolt" aria-hidden="true"></i> Flash Sale</a></li>
                  <li @if(Session::get('sidebar_active')=='deal') class="active" @endif><a href="{{route('deal')}}"><i class="fa fa-handshake-o" aria-hidden="true"></i> Deal sốc</a></li>
              </ul>
            </li>
            <li class="treeview @if(Session::get('sidebar_active')=='member') active @endif">
              <a href="{{route('member')}}">
              <i class="fa fa-user-circle" aria-hidden="true"></i>
                <span>Thành viên</span>
              </a>
            </li>
            <li class="treeview @if(Session::get('sidebar_active')=='warehouse') active @endif">
              <a href="#">
              <i class="fa fa-cubes" aria-hidden="true"></i>
                <span>Kho hàng</span>
                <i class="fa fa-angle-left pull-right"></i>
              </a>
              <ul class="treeview-menu">
                <li @if(Session::get('sidebar_sub_active')=='accounting') class="active" @endif><a href="{{route('warehouse.accounting')}}"><i class="fa fa-circle-o"></i> Quản lý phiếu</a></li>
                <li @if(Session::get('sidebar_sub_active')=='accounting-create') class="active" @endif><a href="{{route('warehouse.accounting.create')}}"><i class="fa fa-circle-o"></i> Tạo phiếu mới</a></li>
                <li @if(Session::get('sidebar_sub_active')=='warehouse') class="active" @endif><a href="{{route('warehouse')}}"><i class="fa fa-circle-o"></i> Tồn kho</a></li>
              </ul>
            </li>
            <li class="treeview @if(Session::get('sidebar_active')=='delivery') active @endif">
              <a href="#">
              <i class="fa fa-truck" aria-hidden="true"></i>
                <span>Vận chuyển</span>
                <i class="fa fa-angle-left pull-right"></i>
              </a>
              <ul class="treeview-menu">
                @if(getConfig('ghtk_status'))
                <li @if(Session::get('sidebar_sub_active')=='ghtk') class="active" @endif><a href="{{route('ghtk')}}"><i class="fa fa-circle-o"></i> Đơn hàng GHTK</a></li>
                @endif
                <li @if(Session::get('sidebar_sub_active')=='pick') class="active" @endif><a href="{{route('pick')}}"><i class="fa fa-circle-o"></i> Địa chỉ lấy hàng</a></li>
                <li @if(Session::get('sidebar_sub_active')=='setting') class="active" @endif><a href="{{route('delivery.setting')}}"><i class="fa fa-circle-o"></i> Cài đặt</a></li>
              </ul>
            </li>
            <li class="treeview @if(Session::get('sidebar_active')=='statistical') active @endif">
              <a href="#">
              <i class="fa fa-bar-chart" aria-hidden="true"></i>
                <span>Báo cáo thống kê</span>
                <i class="fa fa-angle-left pull-right"></i>
              </a>
              <ul class="treeview-menu">
                <li @if(Session::get('sidebar_sub_active')=='quantity') class="active" @endif><a href="{{route('quantity')}}"><i class="fa fa-circle-o"></i> Số lượng sản phẩm</a></li>
                <li @if(Session::get('sidebar_sub_active')=='revenue') class="active" @endif><a href="{{route('revenue')}}"><i class="fa fa-circle-o"></i> Doanh thu</a></li>
              </ul>
            </li>
            <li class="header">WEBSITE</li>
            
            <li class="treeview @if(Session::get('sidebar_active')=='themes') active @endif">
              <a href="#">
                <i class="fa fa-laptop"></i> <span>Giao diện</span>
                <i class="fa fa-angle-left pull-right"></i>
              </a>
              <ul class="treeview-menu">
              	
                <li @if(Session::get('sidebar_sub_active')=='header') class="active" @endif><a href="/admin/themes/header"><i class="fa fa-circle-o"></i> Đầu trang</a></li>
                
                <li @if(Session::get('sidebar_sub_active')=='footer') class="active" @endif><a href="/admin/themes/footer"><i class="fa fa-circle-o"></i> Cuối trang</a></li>
                
                <li @if(Session::get('sidebar_sub_active')=='footer-block') class="active" @endif><a href="/admin/footer-block"><i class="fa fa-circle-o"></i> Block Footer</a></li>
              </ul>
            </li>
           
            <li class="treeview @if(Session::get('sidebar_active')=='post') active @endif">
              <a href="#">
                <i class="fa fa-edit"></i> <span>Blog</span>
                <i class="fa fa-angle-left pull-right"></i>
              </a>
              <ul class="treeview-menu">
                <li @if(Session::get('sidebar_sub_active')=='list') class="active" @endif><a href="/admin/post"><i class="fa fa-circle-o"></i> Tất cả bài viết</a></li>
               
                <li @if(Session::get('sidebar_sub_active')=='category') class="active" @endif><a href="/admin/category"><i class="fa fa-circle-o"></i> Chuyên mục</a></li>
                
              </ul>
            </li>
           
            <li class="treeview @if(Session::get('sidebar_active')=='menu') active @endif">
              <a href="/admin/menu">
                <i class="fa fa-list-ul" aria-hidden="true"></i> <span>Menu</span>
              </a>
            </li>
            
            <li class="treeview @if(Session::get('sidebar_active')=='page') active @endif">
              <a href="/admin/page">
                <i class="fa fa-file"></i> <span>Trang nội dung</span>
              </a>
            </li>
            <li class="treeview @if(Session::get('sidebar_active')=='media') active @endif">
              <a href="#">
                <i class="fa fa-picture-o" aria-hidden="true"></i> <span>Media</span>
                <i class="fa fa-angle-left pull-right"></i>
              </a>
              <ul class="treeview-menu">
                <li @if(Session::get('sidebar_sub_active')=='slider') class="active" @endif><a href="/admin/slider"><i class="fa fa-circle-o"></i> Slider</a></li>
                <li @if(Session::get('sidebar_sub_active')=='banner') class="active" @endif><a href="/admin/banner"><i class="fa fa-circle-o"></i> Banner</a></li>
                <li @if(Session::get('sidebar_sub_active')=='right') class="active" @endif><a href="/admin/right"><i class="fa fa-circle-o"></i> Quyền lợi</a></li>
              </ul>
            </li>
            <li class="treeview @if(Session::get('sidebar_active')=='contact') active @endif">
              <a href="/admin/contact">
                <i class="fa fa-envelope"></i> <span>Liên hệ</span>
              </a>
            </li>
            <li class="treeview @if(Session::get('sidebar_active')=='subcriber') active @endif">
              <a href="/admin/subcriber">
                <i class="fa fa-envelope-o" aria-hidden="true"></i> <span>Email đăng ký</span>
              </a>
            </li>
            <li class="header">Hệ thống</li>
            
            <li class="treeview @if(Session::get('sidebar_active')=='user') active @endif">
              <a href="/admin/user">
                <i class="fa fa-user"></i></i> <span>Tài khoản quản trị</span>
              </a>
            </li>
            
            <li class="treeview @if(Session::get('sidebar_active')=='role') active @endif">
              <a href="/admin/role">
                <i class="fa fa-share-alt" aria-hidden="true"></i> <span>Nhóm quyền</span>
              </a>
            </li>
            <li class="treeview @if(Session::get('sidebar_active')=='redirection') active @endif">
              <a href="/admin/redirection">
                <i class="fa fa-share" aria-hidden="true"></i> <span>Redirect</span>
              </a>
            </li>
            <li class="treeview @if(Session::get('sidebar_active')=='config') active @endif">
              <a href="/admin/config">
                <i class="fa fa-cog" aria-hidden="true"></i> <span>Cấu hình</span>
              </a>
            </li>
            
          </ul>
        </section>
      </aside>
      <div class="content-wrapper">
        @yield('content')
      </div>
      <footer class="main-footer">
        <div class="pull-right hidden-xs">
          <b>Version</b> 1.1.0
        </div>
        <strong>{{ url('/') }}
      </footer>
      <div class="control-sidebar-bg"></div>
    </div>
    <div class="box_img_load_ajax hidden">
      <div class="img_load_ajax">        
          <img src="/public/image/load.gif">
      </div>    
    </div>
    <!-- jQuery UI 1.11.4 -->
    <script src="https://code.jquery.com/ui/1.11.4/jquery-ui.min.js" type="text/javascript"></script>
    <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
    <script type="text/javascript">
      $.widget.bridge('uibutton', $.ui.button);
    </script>
    <!-- Bootstrap 3.3.2 JS -->
    <script src="/public/admin/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>
    <script src="/public/admin/dist/js/app.min.js" type="text/javascript"></script>
    <script src="/public/admin/dist/js/demo.js" type="text/javascript"></script>
    <script src="/public/admin/toastr.js"></script>
    <script src="/public/admin/form.validator.min.js"></script>
    <script src="/public/admin/ControlPanel.js"></script>
    <script src="/js/admin-product-edit.js?v=1"></script>
    <script type="text/javascript">
      toastr.options = {
          closeButton: true,
          debug: $('#debugInfo').prop('checked'),
          newestOnTop: $('#newestOnTop').prop('checked'),
          progressBar: $('#progressBar').prop('checked'),
          rtl: $('#rtl').prop('checked'),
          positionClass: $('#positionGroup input:radio:checked').val() || 'toast-top-right',
          preventDuplicates: $('#preventDuplicates').prop('checked'),
          onclick: null
      };
      // DEPRECATED: FileManager code - replaced by R2 upload
      // $('.content-wrapper').on('click','.addImageMore',function(){
      //     var number = $(this).attr('number');
      //     window.open('/filemanager?type=Images', 'FileManager', 'width=900,height=600');
      //     window.SetUrl = function (items) {
      //         var fileUrl = items[0].url;
      //         $('.list_image').append('<div class="col-md-3 item'+number+'"><img src="'+fileUrl+'"><input type="hidden" value="'+fileUrl+'" name="imageOther[]"><a data-id="'+number+'" href="javascript:;" title="Xóa ảnh" class="delete_image"><i class="fa fa-times"   aria-hidden="true"></i></a></div>');
      //     };
      //     number1 = parseInt(number) + 1;
      //     $(this).attr('number',number1);
      // });
      
      // Keep delete_image handler for backward compatibility
      $('.content-wrapper').on('click','.delete_image',function(){
        var number = $(this).attr('data-id');
        if (number) {
            $('.item'+number+'').remove();
        } else {
            // If no data-id, remove the parent container
            $(this).closest('.has-img, .col-md-3').remove();
        }
      });
      
      // DEPRECATED: FileManager code - replaced by R2 upload
      // $('body').on('click','.btnImage',function(){
      //       var number = $(this).attr('number');
      //       window.open('/filemanager?type=Images', 'FileManager', 'width=900,height=600');
      //       window.SetUrl = function (items) {
      //           var fileUrl = items[0].url;
      //           $('#ImageUrl'+number+'').val(fileUrl);
      //           $('.avantar'+number+'').html('<img src="'+fileUrl+'">');
      //       };
      //   });
      
      // Keep btn_delete_image handler for backward compatibility
      $('body').on('click','.btn_delete_image',function(){
        var number = $(this).attr('number');
        if (number) {
            $('#ImageUrl'+number+'').val('');
            $('.avantar'+number+'').html('<img src="{{asset("public/admin/no-image.png")}}">');
        }
        $('.avantar'+number+'').html('<img src="/public/admin/no-image.png">');
      });
      $('body').on('click','.click_noti',function(){
        var id = $(this).attr('data-id');
        $.ajax({
            type: 'post',
            url: '/admin/notification/click',
            data: {id: id},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (res) {
                window.location = res;
            }
        })
      });
        $('body').on('click','.delete_image',function(){
          var number = $(this).attr('data-id');
          $('.item'+number+'').remove();
        });
        
      </script>
    <script type="text/javascript"  src="/public/admin/slugify.js"></script>
    <script>
        jQuery(function ($) {
            $('#slug-target').slugify('#slug-source');
        });
    </script>
    <script src="/public/admin/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js" type="text/javascript"></script>
    <script type="text/javascript">
      $(function () {
        $(".description").wysihtml5();
      });
    </script>
    @yield('footer')
    <style type="text/css">
      .form-group img{
        width: 100% !important;
      }
      .box_img_load_ajax{    
    background: none repeat scroll 0 0 #000;
    bottom: 0;
    left: 0;
    opacity: 0.4;
    overflow: auto;
    position: fixed;
    right: 0;
    text-align: center;
    top: 0;
    z-index: 99999;
}
.img_load_ajax{
    position: absolute;
    top: 40%;
    left: 49%;
}
.delete_image{
        position: absolute;
        top: -7px;
        z-index: 999;
        color: #d73925;
        width: 16px;
        height: 16px;
        display: block;
        border: 1px solid #d73925;
        border-radius: 10px;
        text-align: center;
        line-height: 14px;
        font-size: 11px;
        right: 8px;
        background-color: #fff;
      }
      .delete_image:hover{
        background-color: #d73925;;
        color:#fff;
      }
      
    </style>
    @stack('scripts')
  </body>
</html>
