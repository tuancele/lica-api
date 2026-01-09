@php $brands = App\Modules\Brand\Models\Brand::select('id','name')->where('status','1')->orderBy('sort','asc')->get(); 
	$sizes = App\Modules\Size\Models\Size::select('id','name','unit')->where('status','1')->orderBy('sort','asc')->get();
	$colors = App\Modules\Color\Models\Color::select('id','name')->where('status','1')->orderBy('sort','asc')->get();
	$origins = App\Modules\Origin\Models\Origin::select('id','name')->where('status','1')->orderBy('sort','asc')->get();
	$filter = Session::get('filter');
@endphp
<div class="list-filter d-flex">
	@if($brands->count() > 0)
	<div class="filter-brand dropdown">
	  <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
	    Thương hiệu
	  </button>
	  <ul class="dropdown-menu cols-3">
	  	@foreach($brands as $brand)
	    <li><label><input type="checkbox" name="brand[]" @if(isset($filter['brand']) && in_array($brand->id,$filter['brand'])) checked @endif value="{{$brand->id}}"> {{$brand->name}}</label></li>
	    @endforeach
	  </ul>
	</div>
	@endif
	@if($origins->count() > 0)
	<div class="filter-origin dropdown">
	  <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
	    Xuất xứ
	  </button>
	  <ul class="dropdown-menu cols-3">
	  	@foreach($origins as $origin)
	    <li><label><input type="checkbox" name="origin[]" @if(isset($filter['origin']) && in_array($origin->id,$filter['origin'])) checked @endif value="{{$origin->id}}"> {{$origin->name}}</label></li>
	    @endforeach
	  </ul>
	</div>
	@endif
	<div class="filter-price dropdown">
	  <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
	    Giá
	  </button>
	  <ul class="dropdown-menu">
	    <li><label><input type="checkbox" name="price[]" @if(isset($filter['price']) && in_array('0:500000',$filter['price'])) checked @endif value="0:500000"> Dưới 500.000đ</label></li>
	    <li><label><input type="checkbox" name="price[]" @if(isset($filter['price']) && in_array('500000:1000000',$filter['price'])) checked @endif value="500000:1000000"> 500.000đ - 1.000.000đ</label></li>
	    <li><label><input type="checkbox" name="price[]" @if(isset($filter['price']) && in_array('1000000:1500000',$filter['price'])) checked @endif value="1000000:1500000"> 1.000.000đ - 1.500.000đ</label></li>
	    <li><label><input type="checkbox" name="price[]" @if(isset($filter['price']) && in_array('1500000:2000000',$filter['price'])) checked @endif value="1500000:2000000"> 1.500.000đ - 2.000.000đ</label></li>
	    <li><label><input type="checkbox" name="price[]" @if(isset($filter['price']) && in_array('2000000:10000000',$filter['price'])) checked @endif value="2000000:10000000"> Trên 2.000.000đ</label></li>
	  </ul>
	</div>
	@if($sizes->count() > 0)
	<div class="filter-size dropdown">
	  <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
	    Kích thước
	  </button>
	  <ul class="dropdown-menu">
	    @foreach($sizes as $size)
	    <li><label><input type="checkbox" name="size[]" @if(isset($filter['size']) && in_array($size->id,$filter['size'])) checked @endif value="{{$size->id}}"> {{$size->name}}{{$size->unit}}</label></li>
	    @endforeach
	  </ul>
	</div>
	@endif
	@if($colors->count() > 0)
	<div class="filter-color dropdown">
	  <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
	    Màu sắc
	  </button>
	  <ul class="dropdown-menu cols-3">
	     @foreach($colors as $color)
	    <li><label><input type="checkbox" name="color[]" @if(isset($filter['color']) && in_array($color->id,$filter['color'])) checked @endif value="{{$color->id}}"> {{$color->name}}</label></li>
	    @endforeach
	  </ul>
	</div>
	@endif
</div>