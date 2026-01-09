@extends('Website::layout',['image' => '','noindex' => '1'])
@section('title', 'Không tìm thấy trang')
@section('description','Không tìm thấy trang')
@section('content')
<section class="page-not-found mt-5 mb-5">
	<div class="container text-center">
		<h1 class="fs-50">404</h1>
		<h3>Oops! That page can’t be found.</h3>
		<p>It looks like nothing was found at this location. Maybe try one of the links below or a search?</p>
	</div>
</section>
<style>
	h1.fs-50{
		font-size: 50px;
	}
</style>
@endsection