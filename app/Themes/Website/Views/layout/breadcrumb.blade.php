@php $array = explode(';', breadcrumbs($detail->cat_id,$detail->id)); @endphp 
<a href="/">Trang chủ</a> <span class="divider">/</span> 
  @php $i = 2; @endphp
     @if(count($array) > 0)
      @foreach($array as $key => $value)
      @php $mang = explode('::',$value);@endphp
      @if(isset($mang[0]) && isset($mang[1]))
        <a href="{{$mang[0]}}">{{$mang[1]}}</a> <span class="divider">/</span>
      @php $i = $i+1;@endphp
      @endif
      @endforeach
     @endif