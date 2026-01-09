<?php 

namespace App\Traits;
use App\History;
trait Admin
{
	public function addHistory($id,$content){
		$history = History::insertGetId(
            [
                'user_id' => $id,
                'content' => $content,
                'created_at' => date('Y-m-d H:i:s')
            ]
        );
        if($history > 0){
        	return true;
        }else{
        	return false;
        }
	}
}