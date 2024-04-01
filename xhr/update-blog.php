<?php 
if ($f == "update-blog") {
    if (Wo_CheckSession($hash_id) === true) {
        $request   = array();
        $request[] = (empty($_POST['blog_title']) || empty($_POST['blog_content']));
        $request[] = (empty($_POST['blog_description']) || empty($_POST['blog_category']));
       // $request[] = (empty($_FILES["thumbnail"]));
	   	
        if (in_array(true, $request)) {
			//$error = $error_icon . $wo['lang']['please_check_details'];
			$fields_msg='';
			if(empty($_POST['blog_title'])){
				$fields_msg .='Title, ';
			}
			if(empty($_POST['blog_content'])){
				$fields_msg .='Content, ';
			}
			if(empty($_POST['blog_description'])){
				$fields_msg .='Description, ';
			}
			if(empty($_POST['blog_category'])){
				$fields_msg .='Category, ';
			}
			if(empty($_POST['thumbnail'])){
				//$fields_msg .='Image, ';
			}
			
			$error = $error_icon . rtrim($fields_msg,', ')." is required.";
        } else {
            if (strlen($_POST['blog_title']) < 10) {
                //$error = $error_icon . $wo['lang']['title_more_than10'];
            }
            if (strlen($_POST['blog_description']) < 32) {
                //$error = $error_icon . $wo['lang']['desc_more_than32'];
            }
            if (empty($_POST['blog_tags'])) {
                //$error = $error_icon . $wo['lang']['please_fill_tags'];
				$_POST['blog_tags'] = $_POST['blog_title'];
            }
            if (!in_array($_POST['blog_category'], array_keys($wo['blog_categories']))) {
                $error = $error_icon . $wo['lang']['error_found'];
            }
        }
        if (empty($error)) {
            $registration_data = array(
                'user' => $wo['user']['id'],
                'title' => $_POST['blog_title'],
                'content' => $_POST['blog_content'],
                'description' => $_POST['blog_description'],
                'category' => $_POST['blog_category'],
                'tags' => $_POST['blog_tags']
            );
			if($_POST['remove_image']==1){
				$registration_data['thumbnail']='upload/photos/d-blog.jpg';
			}
            if (Wo_UpdateBlog($_GET['blog_id'], $registration_data)) {
                if (isset($_FILES["thumbnail"])) {
                    $fileInfo           = array(
                        'file' => $_FILES["thumbnail"]["tmp_name"],
                        'name' => $_FILES['thumbnail']['name'],
                        'size' => $_FILES["thumbnail"]["size"],
                        'type' => $_FILES["thumbnail"]["type"],
                        'types' => 'jpeg,jpg,png,bmp,gif',
                        'crop' => array(
                            'width' => 600,
                            'height' => 380
                        )
                    );
                    $media              = Wo_ShareFile($fileInfo);
                    $mediaFilename      = $media['filename'];
                    $image              = array();
                    $image['user']      = $wo['user']['user_id'];
                    $image['thumbnail'] = $mediaFilename;
                    Wo_UpdateBlog($_GET['blog_id'], $image);
                }
                $data = array(
                    'message' => $success_icon . $wo['lang']['article_updated'],
                    'status' => 200,
                    'url' => Wo_SeoLink('index.php?link1=read-blog&id=' . $_GET['blog_id'])
                );
            }
        } else {
            $data = array(
                'status' => 500,
                'message' => $error
            );
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
