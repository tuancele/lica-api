<div class="form-group">
    <label class="control-label">Trạng thái:</label>
    <select class="form-control" name="status">
        <option value="1" @if(isset($status) && $status == 1)selected @endif>Hiển thị</option>
        <option value="0" @if(isset($status) && $status == 0)selected @endif>Ẩn</option>
    </select>
</div>