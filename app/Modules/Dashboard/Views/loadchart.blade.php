<script type="text/javascript" src="/public/admin/moment.min.js"></script>
<script type="text/javascript" src="/public/admin/daterangepicker.js"></script>
<link rel="stylesheet" type="text/css" href="/public/admin/daterangepicker.css" />
<script type="text/javascript">
  $(document).ready(function() {
    $('#reservation').daterangepicker({
      maxDate:'{{date("m/d/Y")}}',
    });
  });
</script>
  <div class="col-md-8">
    <label>Thời gian</label>
    <div class="input-group" style="width: 300px">
      <input type="text" class="form-control pull-right" id="reservation" value="{{$start}} - {{$end}}" />
      <div class="input-group-addon">
        <i class="fa fa-calendar"></i>
      </div>
    </div><!-- /.input group -->
   <div class="chart" id="line-chart" style="height: 300px;"></div>
</div>
<div class="col-md-4">
  <h4 style="font-weight: bold;font-size: 16px;margin-bottom: 0px;">Sản phẩm bán chạy</h4> 
  @if($sale_hot->count() > 0)
  <ul class="products-list product-list-in-box">
    @foreach($sale_hot as $valsale)
    @php $product = App\Modules\Product\Models\Product::find($valsale->product_id);@endphp
    <li class="item">
      <div class="product-img">
        <img src="{{$product->image}}" alt="{{$product->name}}">
      </div>
      <div class="product-info">
        <a href="/admin/product/edit/{{$product->id}}" target="_blank" class="product-title" style="color:#3c8dbc;font-size: 14px;font-weight: 400">{{$product->name}}</a>
        <span class="product-description" style="color:#333">
          <strong>Bán {{$valsale->soluong}} sản phẩm</strong>
          @php $variant = $product->variant($product->id); @endphp
          <span class="pull-right">@if($variant && $variant->sale != 0) {{number_format($variant->sale)}}đ @else {{number_format($variant->price)}}đ @endif</span>
        </span>
      </div>
    </li><!-- /.item -->
    @endforeach
  </ul>
  @else
  <p class="mt-10">Không có sản phẩm bán chạy nào</p>
  @endif
</div>  
<script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
<script src="/public/admin/plugins/morris/morris.min.js" type="text/javascript"></script>
<script type="text/javascript">
   var line = new Morris.Line({
          element: 'line-chart',
          resize: true,
          data: {!!$statis!!},
          xLabels: "Ngày",
          xkey: 'date',
          ykeys: ['money'],
          labels: ['Doanh thu'],
          lineColors: ['#3c8dbc'],
          hideHover: 'auto'
        });
</script>
<script type="text/javascript">
  $('#reservation').change(function(){
    var time = $(this).val();
    $.ajax({
        type: 'post',
        url: '/admin/dashboard/loadchart',
        data: {time: time},
        headers:
        {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        beforeSend: function () {
          $('.loadchart .box_load').removeClass('hidden');
        },
        success: function (res) {
          $('.loadchart .box_load').addClass('hidden');
          $('.loadchart').html(res);
        }
    })
  })
</script>