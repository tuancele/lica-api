<div class="sort">
	<span id="total_product">{{$total}} Kết quả</span>
	<span>Lọc theo</span>
	<div class="dropdown">
	  <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
	  	@if(Session::has('sortBy'))
	  		@if(Session::get('sortBy') == 'price-asc')
	  		Giá tăng dần
	  		@else
	  		Giá giảm dần
	  		@endif
	  	@else
	  		Tất cả
	  	@endif
	  </button>
	  <ul class="dropdown-menu">
	    <li><a class="dropdown-item" href="javascript:;" data-sort="price-asc">Giá tăng dần</a></li>
	    <li><a class="dropdown-item" href="javascript:;" data-sort="price-desc">Giá giảm dần</a></li>
	  </ul>
	</div>
</div>