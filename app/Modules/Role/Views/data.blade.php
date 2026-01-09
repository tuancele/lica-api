<table class="table table-bordered mb-0">
    <thead class="thead-default">
        <tr>
            <th>ID</th>
            <th>Tiêu đề</th>
            <th>Người tạo</th>
            <th>Ngày tạo</th>
            <th width="10%">Trạng thái</th>
            <th width="10%">Thao tác</th>
        </tr>
    </thead>
    <tbody>
        @if($list->count() > 0)
        @foreach($list as $value)
        <tr class="item-role-{{$value->id}}">
            <td>{{$value->id}}</td>
            <td>{{$value->name}}</td>
            <td>{{$value->user->name}}</td>
            <td>{{formatDate($value->created_at)}}</td>
            <td>
              @if($value->status == 1)
              <span class="badge badge-success">Kích hoạt</span>
              @else
              <span class="badge badge-default">Ẩn</span>
              @endif
            </td>
            <td>
                @can('edit-role')
                <button class="btn btn-primary btn-sm" onclick="editRole('{{$value->id}}')" type="button"  title="Sửa" style="margin-right:3px"><i class="ti ti-pencil" aria-hidden="true"></i></button>
                @endcan
                @can('edit-role')
                @if($value->id != 1)
                <a class="btn_del_role btn btn-danger btn-sm" data-id="{{$value->id}}" title="Xóa"><i class="ti ti-trash" aria-hidden="true"></i></a>
                @endif
                @endcan
            </td>
        </tr>
        @endforeach
        @else
        <tr><td colspan="8" class="text-center"><strong>Không tìm thấy dữ liệu nào.</strong></td></tr>
        @endif
    </tbody>
</table>
{{$list->links()}}
<script type="text/javascript">
    $('.pagination a').unbind('click').on('click', function(e) {
         e.preventDefault();
         var page = $(this).attr('href').split('page=')[1];
          $.ajax({
          type: 'get',
          url: 'role/get?page='+page,
          beforeSend: function () {
              $('.box_img_load_ajax').removeClass('hidden');
          },
          success: function (data) {
            $('.box_img_load_ajax').addClass('hidden');
            $('.list-data').html(data);
          },
          error: function (xhr, ajaxOptions, thrownError) {
              if(xhr.status === 403){
                  toastr.error('Bạn không có quyền sử dụng thao tác này', 'Thông báo');
              }else{
                  toastr.error('Có lỗi xảy ra, xin vui lòng thử lại', 'Thông báo');
              }
          }
        });
   });
</script>