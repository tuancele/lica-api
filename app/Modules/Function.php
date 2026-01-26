<?php

declare(strict_types=1);
use Illuminate\Support\Facades\Session;

if (! function_exists("active")) {
	function active($active,$subactive){
	    Session::put("sidebar_active", $active);
	    Session::put("sidebar_sub_active", $subactive);
	}
}
if (! function_exists("getImage")) {
    function getImage($image, $default = null)
    {
        if (empty($image)) {
            return $default ? $default : asset("public/admin/no-image.png");
        }

        // Clean input
        $image = trim($image);
        
        // Return immediately if it's already a valid external URL (not related to our R2 or App)
        // But we need to handle R2 duplication first.
        
        $r2_public_domain = config("filesystems.disks.r2.url");
        $r2_domain_clean = !empty($r2_public_domain) ? rtrim($r2_public_domain, "/") : "";

        if (empty($r2_domain_clean)) {
            return filter_var($image, FILTER_VALIDATE_URL) ? $image : asset($image);
        }

        // Remove all occurrences of the R2 domain (http/https agnostic) to clean up duplication
        $check_r2 = str_replace(['http://', 'https://'], '', $r2_domain_clean);
        
        // Remove protocols from image for cleaning
        $clean_path = str_replace(['http://', 'https://'], '', $image);
        
        // --- FIX LOGIC: Remove domain from ANYWHERE in the path ---

        // 1. Fix "uploadscdn.lica.vn" concatenation bug
        // Replaces "uploads" + "cdn.lica.vn" -> "uploads/"
        $clean_path = str_replace('uploads' . $check_r2, 'uploads/', $clean_path);

        // 2. Remove domain from path globally (handles recursive/nested domain strings)
        // First try removing with trailing slash to keep path clean
        $clean_path = str_replace($check_r2 . '/', '', $clean_path);
        
        // Then remove domain string itself if it still exists (e.g. at end or without slash)
        $clean_path = str_replace($check_r2, '', $clean_path);

        // Also clean local domains
        $app_url = config('app.url');
        $app_domain = parse_url($app_url, PHP_URL_HOST);
        if ($app_domain) {
             $clean_path = str_replace($app_domain . '/', '', $clean_path);
             $clean_path = str_replace($app_domain, '', $clean_path);
        }
        $clean_path = str_replace('localhost/', '', $clean_path);
        
        // 3. Normalize slashes (remove double slashes)
        $clean_path = preg_replace('#/+#', '/', $clean_path);

        // 4. Deduplicate repeating folders (Fix for recursive "uploads/uploads/..." issue)
        $clean_path = preg_replace('#(uploads/)+#', 'uploads/', $clean_path);
        $clean_path = preg_replace('#(images/)+#', 'images/', $clean_path);

        $clean_path = ltrim($clean_path, '/');

        // Check if the original image was an external URL to somewhere else
        if (filter_var($image, FILTER_VALIDATE_URL)) {
             $host = parse_url($image, PHP_URL_HOST);
             $r2_host = parse_url($r2_domain_clean, PHP_URL_HOST);
             $app_host = parse_url(config('app.url'), PHP_URL_HOST);

             // If host exists and is NOT our R2 and NOT our App and NOT localhost
             if ($host && $host !== $r2_host && $host !== $app_host && $host !== 'localhost' && $host !== '127.0.0.1') {
                 // It's a valid external image (e.g. google image)
                 return $image;
             }
        }

        // Identify if it's a media file we should prefix
        if (strpos($clean_path, 'uploads/') === 0 || 
            strpos($clean_path, 'upload/') === 0 || 
            strpos($clean_path, 'image/') === 0 || 
            strpos($clean_path, 'images/') === 0 || 
            strpos($clean_path, 'files/') === 0 ||
            preg_match('/\.(jpg|jpeg|png|gif|webp|svg|mp4|webm)$/i', $clean_path)) {
             
             return $r2_domain_clean . '/' . $clean_path;
        }

        // Fallback: If it doesn't look like media, return original cleaned path? 
        // Or prepend R2 anyway? Default to R2 for consistency if not external.
        return $r2_domain_clean . '/' . $clean_path;
    }
}

if (! function_exists("formatDate")) {
	function formatDate($date){
	    if($date != ""){
	        return date("d-m-Y",strtotime($date));
	    }else{
	        return "";
	    }
	}
}
if (! function_exists("updateConfig")) {
	function updateConfig($data){
	    if(isset($data) && !empty($data)){
	        foreach ($data as $key => $value) {
                $config = App\Modules\Config\Models\Config::where("name",$key)->first();
                if($config){
                    $config->value = $value;
                    $config->save();
                }else{
                    $newConfig = new App\Modules\Config\Models\Config();
                    $newConfig->name = $key;
                    $newConfig->value = $value;
                    $newConfig->save();
                }
	        }
	    }
	}
}
if (! function_exists("getConfig")) {
	function getConfig($name){
	    $result = App\Modules\Config\Models\Config::where("name",$name)->first();
    	return (isset($result) && !empty($result))?$result->value:"";
	}
}
if (! function_exists("menuMulti")) {
	function menuMulti($data,$parent, $str = "",$select = 0){
	    if(isset($data) && !empty($data)){
	        foreach ($data as $val) {
	            if($val["cat_id"] == $parent){
	                if($select != 0 && $select == $val["id"]){
	                    echo "<option value=\"" . $val["id"] . "\" selected>" . $str . " " . $val["name"] . "</option>";
	                }else{
	                    echo "<option value=\"" . $val["id"] . "\">" . $str . " " . $val["name"] . "</option>";
	                }
	                menuMulti($data, $val["id"], $str ."---",$select);
	            }
	        }
	    }
	}
}
if (! function_exists("menusMulti")) {
	function menusMulti($data,$parent, $str = "",$select = 0){
	    if(isset($data) && !empty($data)){
	        foreach ($data as $val) {
	            if($val["parent"] == $parent){
	                if($select != 0 && $select == $val["id"]){
	                    echo "<option value=\"" . $val["id"] . "\" selected>" . $str . " " . $val["name"] . "</option>";
	                }else{
	                    echo "<option value=\"" . $val["id"] . "\">" . $str . " " . $val["name"] . "</option>";
	                }
	                menusMulti($data, $val["id"], $str ."---",$select);
	            }
	        }
	    }
	}
}
if (! function_exists("actionMulti")) {
	function actionMulti($data,$parent, $str = "",$select = 0){
	    if(isset($data) && !empty($data)){
	        foreach ($data as $val) {
	            if($val["cat_id"] == $parent){
	                if($select != 0 && $select == $val["id"]){
	                    echo "<option value=\"" . $val["slug"] . "\" selected>" . $str . " " . $val["name"] . "</option>";
	                }else{
	                    echo "<option value=\"" . $val["slug"] . "\">" . $str . " " . $val["name"] . "</option>";
	                }
	                actionMulti($data, $val["id"], $str ."---",$select);
	            }
	        }
	    }
	}
}


if (! function_exists("getStarAdmin")) {
    function getStarAdmin($number){
        $html = "";
        for($i = 0; $i < $number; $i++){
            if($number - $i == 0.5){
                $html .= "<i class=\"fa fa-star-half-o\"></i>";
            }else{
                $html .= "<i class=\"fa fa-star\"></i>";
            }
        }
        for($j = 1; $j <= 5 - $number; $j++){
            $html .= "<i class=\"fa fa-star-o\"></i>";
        }
        return $html;
    }
}
if (! function_exists("getStarAdmin")) {
    function getStarAdmin($number){
        $html = "";
        for($i = 0; $i < $number; $i++){
            if($number - $i == 0.5){
                $html .= "<i class=\"fa fa-star-half-o\"></i>";
            }
            else{
                $html .= "<i class=\"fa fa-star\"></i>";
            }
        }
        for($j = 1; $j <= 5 - $number; $j++){
            $html .= "<i class=\"fa fa-star-o\"></i>";
        }
        return $html;
    }
}
?>