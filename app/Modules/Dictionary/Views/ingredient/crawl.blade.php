@extends('Layout::layout')
@section('title','Cập nhật dữ liệu thành phần')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Cập nhật dữ liệu',
])
<section class="content">
    <form role="form" method="post" class="getData" ajax="">
        @csrf
        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <p>Trang lấy dữ liệu: <strong>https://www.paulaschoice.com/ingredient-dictionary/</strong></p>
                        <p>Tổng dữ liệu trên trang: <strong>{{$total}}</strong></p>
                        <div class="row">
                            <div class="col-md-4">
                                <select class="form-control" name="offset" required="">
                                    <option value="">Chọn khoảng dữ liệu</option>
                                    @if($page > 0)
                                    @for($i = 0;$i < $page;$i++)
                                    <option value="{{$i*2000}}">{{$i*2000}} - {{($i+1)*2000}}</option>
                                    @endfor
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button class="button btn btn-primary" type="submit">Lấy dữ liệu</button>
                            </div>
                        </div>
                        <div class="result_data" style="margin-top:15px">
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</section>
<script>
    $(".getData").on("submit", function (e) {
        e.preventDefault();
      $.ajax({
        type: 'post',
        url: "{{route('dictionary.ingredient.get')}}",
        data:  $('.getData').serialize(),
        beforeSend: function () {
            $('.box_img_load_ajax').removeClass('hidden');
        },
        success: function (res) {
           $('.box_img_load_ajax').addClass('hidden');
           $('.result_data').html(res.message);
        },error: function(xhr, status, error){
            $('.box_img_load_ajax').addClass('hidden');
            $('.result_data').html('<span style="font-weight:600;color:green">Lấy dữ liệu thành công</span>');
         }
      })
    });
</script>
@endsection