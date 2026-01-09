@extends('Layout::layout')
@section('title','Crawl sản phẩm')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Crawl sản phẩm',
])
<section class="content">
    <div class="box">
    <div class="box-header with-border">
        <form method="post" id="formCrawl">
        @csrf
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Trang lấy dữ liệu</label>
                    <select class="form-control" id="choseStore" name="store_id" required="">
                        <option value="">Chọn trang lấy dữ liệu</option>
                        @if($stores->count() > 0)
                        @foreach($stores as $store)
                        <option value="{{$store->id}}">{{$store->name}}</option>
                        @endforeach
                        @endif
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Khoảng dữ liệu</label>
                    <select class="form-control" id="choseOffset" name="offset" required="">
                        <option value="">Chọn khoảng lấy dữ liệu</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary" style="margin-top:25px">Lấy dữ liệu</button>
            </div>
        </div>
        </form>
    </div><!-- /.box-header -->
    <div class="box-body">
        <div class="totalBrand"><strong></strong></div>
        <div class="totalProduct" style="margin-top:10px;"><strong></strong></div>
    </div>
</div>
</section>
<script>
    $('#choseStore').change(function(){
        var id = $(this).val();
        $.ajax({
            type: 'post',
            url: '{{route("compare.getBrand")}}',
            data: {id:id},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
                $('.box_img_load_ajax').removeClass('hidden');
            },
            success: function (res) {
                $('.box_img_load_ajax').addClass('hidden');
                $('#choseOffset').html(res.html);
                $('.totalBrand strong').html(res.total);
            },
            error: function(xhr, status, error){
                $('.box_img_load_ajax').addClass('hidden');
                alert('Có lỗi xảy ra, xin vui lòng thử lại');
                window.location = window.location.href;
            }
        })
    });
    $("#formCrawl").on("submit", function (e) {
        e.preventDefault();
      $.ajax({
        type: 'post',
        url: '{{route("compare.getProduct")}}',
        data:  $('#formCrawl').serialize(),
        beforeSend: function () {
            $('.box_img_load_ajax').removeClass('hidden');
        },
        success: function (res) {
           $('.box_img_load_ajax').addClass('hidden');
           if(res.status == 'success'){
                $('.totalProduct strong').html(res.total);
           }else{
                //alert(res.message);
           }
        },error: function(xhr, status, error){
            $('.box_img_load_ajax').addClass('hidden');
            //alert('Có lỗi xảy ra, xin vui lòng thử lại');
         }
      })
    });
</script>
@endsection