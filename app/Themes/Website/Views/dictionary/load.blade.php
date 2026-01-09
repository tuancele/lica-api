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