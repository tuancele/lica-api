@extends('Layout::layout')
@section('title','Tạo nhóm quyền mới')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Tạo nhóm quyền mới',
])
<section class="content">
<div class="box">
  <div class="box-body">
<form role="form" id="tblForm" method="post" ajax="/admin/role/create">
  @csrf
    <div class="row">
      <div class="col-md-9">
          <div class="form-group">
             <label>Tên quyền <span>*</span></label>
            <input type="text" name="name" value="" class="form-control">
          </div>
      </div>
      <div class="col-md-3">
        <div class="form-group">
           <label>Trạng thái</label>
           <select class="form-control" name="status">
              <option value="1">Kích hoạt</option>
              <option value="0">Ẩn</option>
           </select>
        </div>
      </div>
    </div> 
    <div class="form-group">
      <label>Mô tả</label>
      <textarea name="description" class="form-control" rows="4"></textarea>
    </div> 
    <label class="m-b-15" style="margin-bottom: 20px;">Quyền thao tác</label>
    <div class="row group-per">
      @if($permissions->count() > 0)
      @foreach($permissions as $permission)
       <div class="col-md-4">
          <label><input type="checkbox" class="wgr-checkbox" name="per[]" value="{{$permission->id}}"> <span>{{$permission->title}}</span></label>
          @php $parents =  $permission->childen;@endphp
          @if($parents->count() > 0)
          @foreach($parents as $parent)
          <label class="m-l-15" style="font-weight: normal;"><input type="checkbox" name="per[]" class="wgr-checkbox" value="{{$parent->id}}"> <span>{{$parent->title}}</span></label>
          @endforeach
          @endif
       </div>
       @endforeach
      @endif
    </div>  
    <div class="text-right">
      <button type="submit" class="btn btn-success waves-effect waves-light"><i class="ti-save mr-1"></i> Lưu</button>
      <button type="button" class="btn btn-danger waves-effect" data-dismiss="modal"><i class="ti-close mr-1"></i> Đóng</button>
    </div>  
</form>
</div>
</div>
</section>
<style type="text/css">
  .group-per label{
    display: block;
    cursor: pointer;
    margin-bottom: 10px;
  }
  .m-l-15{
    margin-left: 15px;
  }
  .box-body .group-per label .wgr-checkbox{
    float: left;
  }
</style>
@endsection