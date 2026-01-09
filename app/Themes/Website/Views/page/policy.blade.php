@extends('Website::layout',['image' => $detail->image])
@section('title', ($detail->seo_title)?$detail->seo_title:$detail->name)
@section('canonical',getSlug($detail->slug))
@section('content')
<section class="mb-5">
	<div class="container-lg">
		<div class="breadcrumb">
            <ol>
                <li><a href="/">Trang chủ</a></li>
                <li><a href="/">Hỗ trợ</a></li>
            </ol>
        </div>
		<div class="row mt-3">
			<div class="col-3">
				<ul class="list_sidebar">
					@if($recents->count() > 0)
					@foreach($recents as $recent)
					<li><a href="{{getSlug($recent->slug)}}" @if($detail->id == $recent->id) class="active" @endif>{{$recent->name}}</a></li>
					@endforeach
					@endif
				</ul>
			</div>
			<div class="col-9">
				<h1 class="support-title mt-0">{{$detail->name}}</h1>
				<div class="sub-title mb-3 opacity-05">Cập nhật lần cuối: {{date('M',strtotime($detail->created_at))}} {{date('d',strtotime($detail->created_at))}}, {{date('Y',strtotime($detail->created_at))}}</div>
				<div class="entry-content">
					{!!$detail->content!!}
				</div>
			</div>
		</div>
	</div>
</section>
@endsection