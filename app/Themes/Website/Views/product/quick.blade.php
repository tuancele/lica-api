@if(isset($gallerys) && !empty($gallerys))
<div class="row">
    <div class="col-12">
        <div class="app-figure" id="zoom-fig">
            <a id="Zoom-1" class="BandecZoom" title="{{$detail->name}}" href="{{getImage($detail->image)}}">
                <img src="{{getImage($detail->image)}}" alt="{{$detail->name}}"/>
            </a>
            <div class="selectors owl-carousel owl-theme mt-2">
                <div class="item">
                    <a data-zoom-id="Zoom-1" href="{{getImage($detail->image)}}" data-image="{{getImage($detail->image)}}">
                        <img width="100%" src="{{getImage($detail->image)}}" alt="{{$detail->name}}"/>
                    </a>
                </div>
                @foreach($gallerys as $key => $image)
                <div class="item">
                    <a data-zoom-id="Zoom-1" href="{{getImage($image)}}" data-image="{{getImage($image)}}">
                        <img width="100%" src="{{getImage($image)}}" alt="{{$detail->name}}"/>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-12 mt-3">
        <div class="des-quick">
            <div class="brand-btn">
                @if($detail->brand)<a href="/thuong-hieu/{{$detail->brand->slug}}">{{$detail->brand->name}}</a>@endif
            </div>
            <h1 class="title-product">{{$detail->name}}</h1>
            <div class="rating">
                {!!getStar($t_rates->sum('rate'),$t_rates->count())!!}
                <div class="count-rate">({{$t_rates->count()??'0'}})</div>
            </div>
            <div class="price-detail">
                <div class="price">{!!checkSale($detail->id)!!}</div>
            </div>
            <div class="divider-horizontal mt-3 mb-3"></div>
            @if($colors->count() > 0 && $colors[0]->color_id != 0)
            <div class="box-variant box-color" @if($colors[0]->color->id == '22') style="display:none" @endif>
                <div class="label">
                    <strong>Màu sắc:</strong>
                    <span>{{$colors[0]->color->name??''}}</span>
                    <input type="hidden" name="color_id" value="{{$colors[0]->color->id??''}}">
                </div>
                <div class="list-variant">
                    @foreach($colors as $key => $color)
                    <div class="item-variant @if($key == 0) active @endif" data-id="{{$color->color->id??''}}" data-text="{{$color->color->name??''}}">
                        <span style="background-color:{{$color->color->color??''}}"></span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            <div class="box-variant box-size">
                {!!getSizes($detail->id,$colors[0]->color->id??'')!!}
            </div>
            <input type="hidden" name="variant_id"  value="{{$first->id}}">
            <div class="group-cart product-action align-center mt-3 space-between">
                <div class="quantity align-center quantity-selector">
                    <button class="btn_minus entry" type="button" @if($detail->stock == 0) disabled @endif>
                        <span role="img" class="icon"><svg width="14" height="2" viewBox="0 0 14 2" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 0C0.447715 0 0 0.447715 0 1C0 1.55228 0.447715 2 1 2L1 0ZM13 2C13.5523 2 14 1.55228 14 1C14 0.447715 13.5523 0 13 0V2ZM1 2L13 2V0L1 0L1 2Z" fill="black"></path></svg></span>
                    </button>
                    <input @if($detail->stock == 0) disabled @endif type="text" class="form-quatity quantity-input" value="1" min="1">
                    <button @if($detail->stock == 0) disabled @endif class="btn_plus entry" type="button">
                        <span role="img" class="icon"><svg width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M1 6C0.447715 6 0 6.44772 0 7C0 7.55228 0.447715 8 1 8L1 6ZM13 8C13.5523 8 14 7.55228 14 7C14 6.44772 13.5523 6 13 6V8ZM1 8L13 8V6L1 6L1 8Z" fill="black"></path><path d="M6 13C6 13.5523 6.44772 14 7 14C7.55228 14 8 13.5523 8 13L6 13ZM8 1C8 0.447715 7.55228 -2.41411e-08 7 0C6.44771 2.41411e-08 6 0.447715 6 1L8 1ZM8 13L8 1L6 1L6 13L8 13Z" fill="black"></path></svg></span>
                    </button>
                </div>
                <div class="item-action">
                    <button @if($detail->stock == 0) disabled @endif type="button" class="addCartDetail">
                        <span role="img" class="icon"><svg width="22" height="19" viewBox="0 0 22 19" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M21 6.99953H16.21L11.83 0.439531C11.64 0.159531 11.32 0.0195312 11 0.0195312C10.68 0.0195312 10.36 0.159531 10.17 0.449531L5.79 6.99953H1C0.45 6.99953 0 7.44953 0 7.99953C0 8.08953 0.00999996 8.17953 0.04 8.26953L2.58 17.5395C2.81 18.3795 3.58 18.9995 4.5 18.9995H17.5C18.42 18.9995 19.19 18.3795 19.43 17.5395L21.97 8.26953L22 7.99953C22 7.44953 21.55 6.99953 21 6.99953ZM11 2.79953L13.8 6.99953H8.2L11 2.79953ZM17.5 16.9995L4.51 17.0095L2.31 8.99953H19.7L17.5 16.9995ZM11 10.9995C9.9 10.9995 9 11.8995 9 12.9995C9 14.0995 9.9 14.9995 11 14.9995C12.1 14.9995 13 14.0995 13 12.9995C13 11.8995 12.1 10.9995 11 10.9995Z" fill="white"></path></svg></span>
                        <span>Thêm vào giỏ hàng</span>
                    </button>
                </div>
            </div>
            <div class="mt-3">
                <a href="{{getSlug($detail->slug)}}" class="view-detail-quick">Xem chi tiết sản phẩm <i class="fa fa-angle-double-right"></i></a>
            </div>
        </div>
    </div>
</div>
<script>
    $('.selectors').owlCarousel({
        navText:['<i class="fa fa-angle-left"></i>','<i class="fa fa-angle-right"></i>'],
        responsiveclass: true,
        margin: 10,
        autoplay: false,
        dots:false,
        loop:false,
        responsive: {
            0: {
                items: 4,
                nav: true
            },
            768: {
                items: 4,
                nav: true
            },
            1000: {
                items: 4,
                nav: true,
            }
        }
    });
    $('.BandecZoom').BandecZoom();
</script>
@endif