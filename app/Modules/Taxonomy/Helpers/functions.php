<?php

declare(strict_types=1);
if (! function_exists('listTaxonomy')) {
    function listTaxonomy($data,$parent, $str = "",$type){
	     if(isset($data) && !empty($data)){
	        foreach ($data as $val) {
	            if($val['cat_id'] == $parent){
	                $status = ($val['status'] == 1)?'<option value="1" selected=""> Hiển thị</option><option value="0"> Ẩn</option>':'<option value="1"> Hiển thị</option><option value="0" selected=""> Ẩn</option>';
	                $high = ($val['feature']==1)?'<p style="color:red">Nổi bật</p>':'';
	                echo '<tr>
	                       	<td><img src="'.$val['image'].'" style="width:50px"></td>
	                        <td><a target="_blank" href="/'.$val['slug'].'/">'.$str.' '.$val['name'].'</a></td>
	                        <td>'.formatDate($val['created_at']).'</td>
	                        <td>
	                            '.$val['user']->name.'
	                        </td>';
	                echo '<td>
	                            <select class="select_status form-control" data-id="'.$val['id'].'" data-url="/admin/'.$type.'/status">
	                                '.$status.'
	                            </select>
	                        </td>
	                        <td>
	                        	<a class="btn btn-primary btn-xs" href="/admin/'.$type.'/edit/'.$val['id'].'" style="margin-right:3px"><i class="fa fa-pencil-square-o" aria-hidden="true"></i></a>
	                            <a class="btn_delete btn btn-danger btn-xs" data-page="" data-id="'.$val['id'].'"><i class="fa fa-trash-o" aria-hidden="true"></i></a>
	                        </td>
	                    </tr>';
	                listTaxonomy($data, $val['id'], $str ."——",$type);
	            }
	        }
	    }
	}
}
?>