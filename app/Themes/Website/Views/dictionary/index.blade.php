@extends('Website::layout',['image' => ''])
@section('title', ($detail->seo_title)?$detail->seo_title:$detail->name)
@section('description',$detail->seo_description)
@section('header')
<style>
    @media(max-width: 568px){
        .filter_pc{
            display: none;
        }
        .show_mobile .filter_pc{
            display: block;
        }
    }
</style>
@endsection
@section('content')
@php $filter = Session::get('filter_ingredient'); $sort= Session::get('sortByIngredient');@endphp
<section class="blogs pt-5 pb-5">
    <div class="container-lg">
        <div class="breadcrumb">
            <ol>
                <li><a href="/">Trang chủ</a></li>
                <li><a href="/ingredient-dictionary">Ingredient Dictionary</a></li>
            </ol>
        </div>
        <h1 class="blog-title mt-4">{{$detail->name}}</h1>
        <div class="row mt-5">
            <div class="col-12 col-md-4">
                <form class="search_ingredient">
                    <label>Search Ingredients</label>
                    <div class="form-group">
                        <input type="text" autocomplete="off" class="searchinput" name="" placeholder="Search All Ingredients">
                        <button class="btn" type="submit"><i class="fa fa-search" aria-hidden="true"></i></button>
                    </div>
                    <div class="result_search">
                        <p class="title_search">SUGGESTED INGREDIENTS</p>
                        <div class="list_search">
                        </div>
                    </div>
                </form>
                
                <div class="head_sidebar d-none d-md-block">
                    FILTER BY
                </div>
                <div class="filter_pc">
                    <div class="list_filter">
                        @if($rates->count() > 0)
                        <h4>RATING</h4>
                        <div class="more item_more_1">
                            @foreach($rates as $rate)
                            <label><input @if(isset($filter['rate']) && in_array($rate->id,$filter['rate'])) checked @endif type="checkbox" name="rate[]" value="{{$rate->id}}"> {{$rate->name}}  ({{totalIngredientRate($rate->id)}})</label>
                            @endforeach
                        </div>
                        @endif
                        @if($benefits->count() > 0)
                        <h4>BENEFIT</h4>
                        <div class="more item_more_2">
                        @foreach($benefits as $benefit)
                        <label><input @if(isset($filter['benefit']) && in_array($benefit->id,$filter['benefit'])) checked @endif type="checkbox" name="benefit[]" value="{{$benefit->id}}"> {{$benefit->name}}  ({{totalIngredientBen($benefit->id)}})</label>
                        @endforeach
                        </div>
                        <a type="button" href="javascript:;" data-id="2" class="btn_more">more</a>
                        @endif
                        @if($categories->count() > 0)
                        <h4>CATEGORY</h4>
                        <div class="more item_more_3">
                        @foreach($categories as $category)
                        <label><input @if(isset($filter['category']) && in_array($category->id,$filter['category'])) checked @endif type="checkbox" name="category[]" value="{{$category->id}}"> {{$category->name}}  ({{totalIngredientCat($category->id)}})</label>
                        @endforeach
                        </div>
                        <a type="button" href="javascript:;" data-id="3" class="btn_more">more</a>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-8">
                <div class="head_sort">
                    <div class="total_result">Showing <span>{{$list->total()}}</span> results</div>
                    <div class="sort_ingredient">
                        <label>Sort By</label>
                        <select class="form-select sortIngredient">
                            <option value="best" @if(isset($sort) && $sort=='best') selected="" @endif>Best</option>
                            <option value="worst" @if(isset($sort) && $sort=='worst') selected="" @endif>Worst</option>
                            <option value="name-asc" @if(isset($sort) && $sort=='name-asc') selected="" @endif>Name A-Z</option>
                            <option value="name-desc" @if(isset($sort) && $sort=='name-desc') selected="" @endif>Name Z-A</option>
                        </select>
                    </div>
                    <button type="button" class="btn-filter">Filter & Sort</button>
                </div>
                <div class="head_ingredient">
                    <div class="left_head">RATING</div>
                    <div class="right_head">INGREDIENT</div>
                </div>
                <div class="load-ingredient">
                    <div class="content_ingredient">
                        @if($list->count() > 0)
                        @foreach($list as $val)
                        <div class="item_ingredient">
                            <div class="left_ingredient" style="color:{{$val->rate->color??''}}">{{$val->rate->name??'NOT RATED'}}</div>
                            <div class="right_ingredient">
                                <h3>{{$val->name}}</h3>
                                <p>{{$val->description}}</p>
                                <a href="/ingredient-dictionary/{{$val->slug}}">READ MORE</a>
                            </div>
                        </div>
                        @endforeach
                        @endif
                    </div>
                    <div class="bottom_ingredient">
                        <div class="show_page">
                            @php 
                            $page    = request()->page ? request()->page : 1;
                            $total   = $list->total();
                            $perPage = 10;
                            $showingTotal  = $page * $perPage;
                            $currentShowing = $showingTotal>$total ? $total : $showingTotal;
                            $showingStarted = $showingTotal - $perPage;
                            @endphp
                            Showing {{$showingStarted}} to {{$showingTotal}} of {{$total}}
                        </div> 
                        <div class="text-end"> {!!$list->links()!!}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
@section('footer')
<div class="filter_mobile">
    <div class="box_filter">
        <button id="close-handle" class="close-handle" aria-label="Đóng" title="Đóng">
            <span class="mb-menu-cls" aria-hidden="true"><span class="bar animate"></span></span>Đóng
        </button>
        <div class="head_sidebar">
            <svg viewBox="0 0 24 24"><path d="m21.5,7.87h-10.49c-.39-1.37-1.64-2.38-3.13-2.38s-2.74,1.01-3.13,2.38h-2.24c-.5,0-.9.4-.9.9s.4.9.9.9h2.24c.39,1.37,1.64,2.38,3.13,2.38s2.74-1.01,3.13-2.38h10.49c.5,0,.9-.4.9-.9s-.4-.9-.9-.9Zm-13.62,2.38c-.47,0-.89-.23-1.16-.57-.19-.25-.32-.56-.32-.9s.12-.65.32-.9c.27-.35.69-.57,1.16-.57s.89.23,1.16.57c.19.25.32.56.32.9s-.12.65-.32.9c-.27.35-.69.57-1.16.57Z"></path><path d="m21.49,14.33h-2.22c-.12-.43-.33-.83-.62-1.18-.55-.68-1.34-1.09-2.21-1.18-.88-.09-1.72.17-2.4.73-.52.43-.88.99-1.06,1.63H2.5c-.5,0-.9.4-.9.9s.4.9.9.9h10.48c.36,1.25,1.45,2.22,2.82,2.36.11.01.21.02.32.02,1.47,0,2.75-.98,3.14-2.37h2.22c.5,0,.9-.4.9-.9s-.4-.9-.9-.9Zm-5.51,2.37c-.39-.04-.75-.23-1-.53,0-.01-.02-.02-.02-.04-.23-.3-.34-.67-.3-1.04h0c.03-.29.16-.54.34-.76.06-.08.11-.17.2-.24.27-.22.59-.33.93-.33.05,0,.1,0,.15,0,.39.04.75.23,1,.53,0,.01.01.02.02.04.23.3.34.67.3,1.04-.03.29-.14.54-.3.76-.3.38-.78.62-1.31.57Z"></path></svg> Filter & Sort
        </div>
        <div class="list_collapse list_filter">
            <div class="item_collapse">
                <a class="" role="button" data-bs-toggle="collapse" href="#sortBy" aria-expanded="true" aria-controls="collapseExample">
                  Sort By <i class="fa fa-angle-up" aria-hidden="true"></i>
                </a>
                <div class="collapse show" id="sortBy">
                    <label><input type="radio" value="best" name="sortby" @if(isset($sort) && $sort=='best') checked="" @endif> Best</label>
                    <label><input type="radio" value="worst" name="sortby" @if(isset($sort) && $sort=='worst') checked="" @endif> Worst</label>
                    <label><input type="radio" value="name-asc" name="sortby" @if(isset($sort) && $sort=='name-asc') checked="" @endif> Name (A-Z)</label>
                    <label><input type="radio" value="name-desc" name="sortby" @if(isset($sort) && $sort=='name-desc') checked="" @endif> Name (Z-A)</label>
                </div>
            </div>
            @if($rates->count() > 0)
            <div class="item_collapse item_more_1">
                <a class="" role="button" data-bs-toggle="collapse" href="#filterRating" aria-expanded="false" aria-controls="filterRating">
                    Rating <i class="fa fa-angle-down" aria-hidden="true"></i>
                </a>
                <div class="collapse" id="filterRating">
                    @foreach($rates as $rate)
                    <label class="justify-content">
                        <div><input @if(isset($filter['rate']) && in_array($rate->id,$filter['rate'])) checked @endif type="checkbox" value="{{$rate->id}}" name="rate[]"> {{$rate->name}}</div>
                        <span>{{totalIngredientRate($rate->id)}}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif
            @if($benefits->count() > 0)
            <div class="item_collapse item_more_2">
                <a class="" role="button" data-bs-toggle="collapse" href="#filterBenefit" aria-expanded="false" aria-controls="filterBenefit">
                    Benefit <i class="fa fa-angle-down" aria-hidden="true"></i>
                </a>
                <div class="collapse" id="filterBenefit">
                    @foreach($benefits as $benefit)
                    <label class="justify-content">
                        <div><input @if(isset($filter['benefit']) && in_array($benefit->id,$filter['benefit'])) checked @endif type="checkbox" value="{{$benefit->id}}" name="benefit[]"> {{$benefit->name}}</div>
                        <span>{{totalIngredientBen($benefit->id)}}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif
            @if($categories->count() > 0)
            <div class="item_collapse item_more_3">
                <a class="" role="button" data-bs-toggle="collapse" href="#filterCategory" aria-expanded="false" aria-controls="filterCategory">
                    Category <i class="fa fa-angle-down" aria-hidden="true"></i>
                </a>
                <div class="collapse" id="filterCategory">
                    @foreach($categories as $category)
                    <label class="justify-content">
                        <div><input @if(isset($filter['category']) && in_array($category->id,$filter['category'])) checked @endif type="checkbox" value="{{$category->id}}" name="category[]"> {{$category->name}}</div>
                        <span>{{totalIngredientCat($category->id)}}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        <div class="bottom_fillter position-relative">
            <a href="javascript:;">Clear Filters</a>
            <button class="btn_apply" type="button">Apply</button>
        </div>
    </div>
    <div class="bottom_fillter">
        <a href="javascript:;" class="clear_filter">Clear Filters</a>
        <button class="btn_apply" type="button">Apply</button>
    </div>
</div>
<script>
    var width = window.innerWidth;
    if(width <= 568){
        $('.filter_pc').html('');
    }else{
        $('.filter_mobile').html('');
    }
</script>
<style>
    body.filter-active{
        overflow:hidden;
    }
    .head_ingredient{
        font-size: 16px;
        line-height: 1.2;
        font-weight: 700;
        text-align: left;
        padding-bottom: 5px;
        border-bottom: 2px solid rgb(51, 49, 51);
        text-transform: uppercase;
        overflow: hidden;
    }
    .head_sidebar{
        font-size: 16px;
        line-height: 1.2;
        font-weight: 700;
        text-align: left;
        padding-bottom: 5px;
        border-bottom: 2px solid rgb(51, 49, 51);
        text-transform: uppercase;
        overflow: hidden;
        margin-right: 40px;
    }
    .bottom_ingredient{
        border-top: 2px solid rgb(51, 49, 51);
        padding-top: 15px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .left_head{
        font-weight: 700;
        font-size: 16px;
        line-height: 1.2;
        width: 170px;
        float: left;
    }
    .right_head{
         width: calc(100% - 200px);
        float: left;
        margin-left: 20px;
        font-weight: 700;
        font-size: 16px;
        line-height: 1.2;
    }
	.content_ingredient{
        width: 100%;
    }
    .item_ingredient{
        width: 100%;
        padding-top:25px;
        padding-bottom: 25px;
        border-bottom: 1px solid rgb(213, 216, 220);
        overflow: hidden;
    }
    .item_ingredient .left_ingredient{
        width: 170px;
        float: left;
        font-size: 16px;
        line-height: 1.2;
        font-weight: 700;
        color:rgb(137, 138, 141);
        text-transform: uppercase;
    }
    .item_ingredient .right_ingredient{
        width: calc(100% - 200px);
        float: left;
        margin-left: 20px;
    }
    .item_ingredient .right_ingredient h3{
        font-weight: 600;
        font-size: 16px;
    }
    .item_ingredient .right_ingredient a{
        color:rgb(93, 126, 149);
        text-decoration: underline;
        text-transform: uppercase;
        font-size: 12px;
        font-weight: 700;
    }
    .pagination{
        justify-content: end;
    }
    .list_filter{
        margin-right: 40px;
        overflow: hidden;
    }
    .list_filter h4{
        font-size: 16px;
        display: block;
        margin-bottom: 10px;
        margin-top: 20px;
    }
    .list_filter label{
        width: 100%;
        overflow: hidden;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        padding-left: 10px;
        font-size: 12px;
    }
    .list_filter label input{
        margin-right: 5px;
    }
    .more{
        height: 135px;
        overflow: hidden;
    }
    .more.active{
        height: auto;
    }
    .btn_more{
        color:rgb(93, 126, 149);
        padding-left: 10px;
        display: block;
    }
    .search_ingredient{
        margin-right: 40px;
        margin-bottom: 40px;
        position: relative;
    }
    .search_ingredient .form-group{
        position: relative;
    }
    .search_ingredient input{
        height: 42px;
        border-radius: 21px;
        border: 2px solid rgb(213, 216, 220);
        padding: 8px 10px;
        width: 100%;
        font-size: 12px;
    }
    .search_ingredient button{
        position: absolute;
        background-color: initial;
        right: 0px;
        border-left: 1px solid #d1d1d1;
        height: 22px;
        width: 40px;
        padding: 0;
        top: 10px;
        line-height: 22px;
    }
    .search_ingredient label{
        font-weight: 600;
        font-size: 16px;
    }
    .head_sort{
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 35px;
        margin-top: 30px;
    }
    .sort_ingredient{
        height: 53px;
        display: flex;
        align-items: center;

    }
    .sort_ingredient select{
        height: 53px;
        width: 175px;
        margin-left: 5px;
    }
    .result_search{
        position: absolute;
        z-index: 9;
        background-color: #fff;
        border: 1px solid rgb(213, 216, 220);
        padding: 20px;
        width: 100%;
        max-height: 200px;
        display: none;
        overflow-y: scroll;
    }
    .result_search .title_search{
        font-weight: 700;
        font-size: 15px;
    }
    .result_search a{
        display: block;
        width: 100%;
        margin-bottom: 10px;
    }
    .filter_mobile{
        display: none;
    }
    .btn-filter{
        display: none;
    }
    @media(max-width:568px){
        .search_ingredient{
            margin-right: 0px;
            margin-bottom: 0px;
        }
        .sort_ingredient{
            display: none;
        }
        .left_head,.item_ingredient .left_ingredient{
            width: 100px;
        }
        .right_head,.item_ingredient .right_ingredient{
            width: calc(100% - 100px);
            margin-left: 0px;
        }
        .bottom_ingredient{
            display: block;
        }
        .bottom_ingredient .show_page{
            margin-bottom: 10px;
            text-align: center;
        }
        .bottom_ingredient .pagination{
            justify-content: center;
        }
        .head_sort{
            display: block;
        }
        .filter_mobile{
            display: block;
            width: calc(100% - 20px);
            position: fixed;
            height: 100%;
            left: 0;
            right: auto;
            top: 0;
            background: rgb(247, 239, 229);
            z-index: 60;
            -ms-transition: transform 300ms cubic-bezier(0.25, 0.46, 0.45, 0.94);
            -webkit-transition: transform 300ms cubic-bezier(0.25, 0.46, 0.45, 0.94);
            transition: transform 300ms cubic-bezier(0.25, 0.46, 0.45, 0.94);
            -ms-transform: translateX(-100%);
            -webkit-transform: translateX(-100%);
            transform: translateX(-100%);
            
        }
        .filter-active .filter_mobile {
            -webkit-transform: translateX(0);
            -moz-transform: translateX(0);
            -ms-transform: translateX(0);
            transform: translateX(0);
        }
        .filter-active #site-overlay {
            opacity: 1;
            visibility: visible;
        }
        .btn-filter{
            display: block;
            height: 46px;
            padding: 0px 30px;
            border: 1px solid #d1d1d1;
            background-color: initial;
            margin-top: 10px;
            text-transform: uppercase;
            font-weight: 600;
            border-radius: 5px;
        }
        .list_filter,.head_sidebar{
            margin-right: 0px;
        }
        .filter_mobile .sort_ingredient{
            display: block;
            height: auto;
        }
        .filter_mobile .sort_ingredient label{
            display: block;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .filter_mobile .sort_ingredient select{
            width: 100%;
            margin-left: 0px;
        }
        .filter_mobile .head_sidebar{
            font-size: 20px;
            padding-bottom: 40px;
            border-bottom: none;
            border-bottom: 1px solid rgb(227, 214, 205);
            margin-top: 40px;
        }
        .filter_mobile .head_sidebar svg{
            width: 20px;
        }
        .item_collapse{
            width: 100%;
        }
        .item_collapse a{
            font-weight: 600;
            color:#000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 60px;
            border-bottom: 1px solid rgb(227, 214, 205);
            width: 100%;
            font-size: 18px;
        }
        .item_collapse a i{
            font-size: 24px;
        }
        .item_collapse label{
            display: flex;
            width: 100%;
            align-items: center;
            padding-bottom: 10px;
            padding-top: 10px;
            font-size: 15px;
            padding-left: 0px;
            margin-bottom: 0px;
        }
        .item_collapse label input{
            width: 24px;
            height: 24px;
            margin-right: 5px;
        }
        .item_collapse label div{
            display: flex;
            align-items: center;
        }
        .item_collapse label span{
            padding-left: 10px;
        }
        .item_collapse label.justify-content{
            justify-content: space-between;
        }
        .item_collapse label:first-child{
            margin-top: 15px;
        }
        .item_collapse label:last-child{
            margin-bottom: 15px;
        }
        .bottom_fillter{
            position: fixed;
            z-index: 151;
            left: 0px;
            bottom: 0px;
            width: 100%;
            display: flex;
            -webkit-box-align: center;
            align-items: center;
            -webkit-box-pack: justify;
            justify-content: space-between;
            padding: 15px 30px;
            background-color: rgb(247, 239, 229);
            border-top: 1px solid rgb(227, 214, 205);;
        }
        .bottom_fillter button{
            position: relative;
            border: 2px solid;
            color: rgb(255, 255, 255);
            border-radius: 4px;
            padding: 11px 23px 10px;
            cursor: pointer;
            background-color: rgb(51, 49, 51);
            border-color: rgb(51, 49, 51);
        }
        .bottom_fillter a{
            color: rgb(51, 49, 51);
            text-decoration: underline;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.4;
        }
        .box_filter{
            width: 100%;
           overflow-y: auto;
           height: 100%;
           padding:30px;
           padding-bottom: 0px;
        }
        .box_filter .bottom_fillter{
            visibility: hidden;
            padding-left: 0px;
            padding-right: 0px;
            background-color: rgb(247, 239, 229);
            position: relative;
        }
    }
</style>
<script>
    $('.list_filter').on('click','.btn_more',function(){
        var id = $(this).attr('data-id');
        $('.item_more_'+id+'').addClass('active');
        $(this).hide();
    });
    $('body').on('click','.btn_apply',function(){
        $("body").removeClass('filter-active');
    })
    $('body').on('click','.item_collapse a',function(){
        var check = $(this).attr('aria-expanded');
        if(check == 'true'){
            $(this).find('.fa').removeClass('fa-angle-down').addClass('fa-angle-up');
        }else{
            $(this).find('.fa').removeClass('fa-angle-up').addClass('fa-angle-down');
        }
    });
    $(document).on('click','.filter-active #site-overlay,#close-handle',function(event){
        $("body").removeClass('filter-active')
    });
    $("body").on("click",".btn-filter",function(){
        $("body").toggleClass('filter-active');
    })
    $('.sortIngredient').change(function(){
        var sort = $(this).val();
        var url = '{{$detail->slug}}';
        $.ajax({
            type: 'post',
            url: '/ajax/sort-ingredient',
            data: {url:url,sort:sort},
            headers:
            {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
                $('.load-ingredient').html('<div class="load" style="width:100%;text-align:center;margin-top:50px;"><img style="width:50px; height:50px;display:inline-block" src="/public/image/load2.gif" alt="load" width="50" height="50"></div>');
            },
            success: function (res) {
                $('.load-ingredient').html(res.view);
                $('.total_result span').html(res.total);
            }
        })
    });
    $('body').on('click','.clear_filter',function(){
        var url = '{{$detail->slug}}';
        $.ajax({
            type: 'post',
            url: '/ajax/clear-filter',
            data: {url:url},
            headers:
            {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
                $('.load-ingredient').html('<div class="load" style="width:100%;text-align:center;margin-top:50px;"><img style="width:50px; height:50px;display:inline-block" src="/public/image/load2.gif" alt="load" width="50" height="50"></div>');
                $('body .btn_apply').html('Loading...');
            },
            success: function (res) {
                $('.list_filter label input').each(function () {
                    $(this).prop('checked', false);
                });
                $('.load-ingredient').html(res.view);
                $('.total_result span').html(res.total);
                $('body .btn_apply').html('Apply');
            }
        })
    });
    $(".list_filter label input[type='radio']").click(function () {
        var sort = '';
        if($(this).is(':checked')){
            sort = $(this).val();
        }
        var url = '{{$detail->slug}}';
        $.ajax({
            type: 'post',
            url: '/ajax/sort-ingredient',
            data: {url:url,sort:sort},
            headers:
            {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
                $('.load-ingredient').html('<div class="load" style="width:100%;text-align:center;margin-top:50px;"><img style="width:50px; height:50px;display:inline-block" src="/public/image/load2.gif" alt="load" width="50" height="50"></div>');
                $('body .btn_apply').html('Loading...');
            },
            success: function (res) {
                $('.load-ingredient').html(res.view);
                $('.total_result span').html(res.total);
                $('body .btn_apply').html('Show '+res.total+' Results');
            }
        })
    })
    $(".list_filter label input[type='checkbox']").click(function () {
        var category = [],rate = [],benefit = [],
            url = '{{$detail->slug}}';
        $(".item_more_1 label").each(function () {
            if($(this).find("input").is(':checked')){
                rate.push($(this).find("input").val());
            }
        })
        $(".item_more_2 label").each(function () {
            if($(this).find("input").is(':checked')){
                benefit.push($(this).find("input").val());
            }
        })
        $(".item_more_3 label").each(function () {
            if($(this).find("input").is(':checked')){
                category.push($(this).find("input").val());
            }
        })
        $.ajax({
            type: 'post',
            url: '/ajax/filter-ingredient',
            data: {url:url,category:category,rate:rate,benefit:benefit,orderby:''},
            headers:
            {
              'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
                $('.load-ingredient').html('<div class="load" style="width:100%;text-align:center;margin-top:50px;"><img style="width:50px; height:50px;display:inline-block" src="/public/image/load2.gif" alt="load" width="50" height="50"></div>');
                $('body .btn_apply').html('Loading...');
            },
            success: function (res) {
                $('.load-ingredient').html(res.view);
                $('.total_result span').html(res.total);
                $('body .btn_apply').html('Show '+res.total+' Results');
            }
        })
    });
    $('.searchinput').on('input', function(){ 
      var key = $(this).val();
          $.ajax({
            type: 'post',
            url: '/ajax/search-ingredient',
            data: {key:key},
            headers:
            {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            beforeSend: function () {
                $('.result_search').show();
                $('.result_search .list_search').html('<div class="load" style="width:100%;text-align:center;margin-top:20px;"><img style="width:50px; height:50px;display:inline-block" src="/public/image/load2.gif" alt="load" width="50" height="50"></div>');
            },
            success: function (res) {
                $('.result_search').show();
                $('.result_search .list_search').html(res);
            }
          })
    })
    function closeAllSelect(){
        $('.result_search').hide();
    }
    document.addEventListener("click", closeAllSelect);
</script>
@endsection