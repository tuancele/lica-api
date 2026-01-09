@extends('Layout::layout')
@section('title','Sửa nhóm quyền')
@section('content')
@include('Layout::breadcrumb',[
    'title' => 'Sửa nhóm quyền',
])
<section class="content">
<div class="box">
  <div class="box-body">
<form role="form" id="tblForm" method="post" ajax="/admin/role/edit">
  @csrf
    <div class="row">
      <div class="col-md-9">
          <div class="form-group">
            <label>Tên quyền <span>*</span></label>
            <input type="text" name="name" value="{{$detail->name}}" class="form-control">
            <input type="hidden" name="id" value="{{$detail->id}}">
          </div>
      </div>
      <div class="col-md-3">
        <div class="form-group">
           <label>Trạng thái</label>
           <select class="form-control" name="status">
              <option value="1" @if($detail->status == 1) selected="" @endif>Kích hoạt</option>
              <option value="0" @if($detail->status == 0) selected="" @endif>Ẩn</option>
           </select>
        </div>
      </div>
      
    </div>  
    <div class="form-group">
      <label>Mô tả</label>
      <textarea name="description" class="form-control" rows="4">{{$detail->description}}</textarea>
    </div> 
    <label class="m-b-15">Quyền thao tác</label>
    <div class="row group-per">
      @if($permissions->count() > 0)
      @foreach($permissions as $permission)
       <div class="col-md-4">
          <label><input type="checkbox" name="per[]" value="{{$permission->id}}" class="wgr-checkbox" @if(isset($arr) && !empty($arr) && in_array($permission->id,$arr)) checked @endif> {{$permission->title}}</label>
          @php $parents =  $permission->childen; @endphp
          @if($parents->count() > 0)
          @foreach($parents as $parent)
          <label class="m-l-15" style="font-weight: normal;"><input type="checkbox" name="per[]" class="wgr-checkbox" value="{{$parent->id}}" @if(isset($arr) && !empty($arr) && in_array($parent->id,$arr)) checked @endif> {{$parent->title}}</label>
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