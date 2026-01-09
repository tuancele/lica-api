@if($reviews->count() > 0)
@foreach($reviews as $rate)
<div class="item_rate">
    <div class="header_rate">
        <div class="name_rate">
            {{$rate->name}}
        </div>
        <div class="date_rate">
            {{formatDate($rate->created_at)}}
        </div>
    </div>
    <div class="content_rate">
        <div class="star_rate">
            {!!getStar($rate->rate)!!}
        </div>
        <div class="box_rate">
            {{$rate->content}}
        </div>
    </div>
</div>
@endforeach
<div class="mt-3 text-center">
 {{$reviews->links()}}
</div>
@endif
<script type="text/javascript">
  $('.list-review .pagination a').unbind('click').on('click', function(e) {
   e.preventDefault();
   var page = $(this).attr('href');
   getReviews(page);
 });
 function getReviews(page)
 {
    $.ajax({
         type: "GET",
         url: page
    })
    .success(function(res) {
        $('.list-review').html(res);
    });
 }
</script>