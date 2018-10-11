<?php

if ( !empty( $_FILES ) ) {
    header('Content-type: application/json');
    if ($_FILES['file']["error"] > 0){
    	if ($_FILES['file']["error"] == 1){
    		$answer = array( 'code' => 1, 'answer' => 'UPLOAD_ERR_INI_SIZE' );
    	}
    	elseif ($_FILES['file']["error"] == 2){
    		$answer = array( 'code' => 2,  'answer' => 'UPLOAD_ERR_FORM_SIZE' );
    	}
        elseif ($_FILES['file']["error"] == 3){
    		$answer = array( 'code' => 3,  'answer' => 'UPLOAD_ERR_PARTIAL  | 文件没有完整上传' );
    	}
        elseif ($_FILES['file']["error"] == 4){
    		$answer = array( 'code' => 4,  'answer' => 'UPLOAD_ERR_NO_FILE  | 没有上传文件 ' );
    	}
        elseif ($_FILES['file']["error"] == 5){
    		$answer = array( 'code' => 5,  'answer' => 'UPLOAD_ERROR_E   | As expliained by @Progman, removed in rev. 81792' );
    	}
        elseif ($_FILES['file']["error"] == 6){
    		$answer = array( 'code' => 6,  'answer' => 'UPLOAD_ERR_NO_TMP_DIR | 找不到临时文件夹 ' );
    	}
        elseif ($_FILES['file']["error"] == 7){
    		$answer = array( 'code' => 7,  'answer' => 'UPLOAD_ERR_CANT_WRITE | 磁盘不可写' );
    	}
        elseif ($_FILES['file']["error"] == 8){
    		$answer = array( 'code' => 8,  'answer' => 'UPLOAD_ERR_EXTENSION  | File upload stopped by extension ' );
    	}
    }
    else{
    	$file = mb_convert_encoding($_FILES[ 'file' ]['name'], "gbk", "utf-8");
		$uploadPath = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $file;
		
		$filename = basename($uploadPath);// basename($path):返回基本的文件名，如：文件名.doc
    	$extpos = strrpos($filename,'.');//返回字符串filename中'.'号最后一次出现的数字位置
    	$ext = substr($filename, $extpos+1);
    	$newname = md5(time().$_FILES[ 'file' ]['name']);
    	$file = $newname.'.'.$ext;
    	$uploadPath = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $file;
    
    	if (file_exists($uploadPath)){
    		$answer = array( 'code' => 10001,  'answer' => '文件已经存在' );
    	}
    	else{
		    $tempPath = $_FILES[ 'file' ][ 'tmp_name' ];
		    move_uploaded_file( $tempPath, $uploadPath );
		    $answer = array( 'code' => 0, 'file' => $file,  'answer' => '文件上传成功 !' );
    	}
    }
    $json = json_encode( $answer );
	echo $json;

} else {

    echo '没有文件';

}

?>