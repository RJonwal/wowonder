<?php 
if ($f == "insert-blog") {
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
                'title' => Wo_Secure($_POST['blog_title']),
                'content' => Wo_Secure($_POST['blog_content'], 0, false),
                'description' => substr(Wo_Secure($_POST['blog_description']), 0, 290),
                'posted' => time(),
                'category' => Wo_Secure($_POST['blog_category']),
                'tags' => Wo_Secure($_POST['blog_tags'])
            );
			
            $last_id           = Wo_InsertBlog($registration_data);
            if ($last_id && is_numeric($last_id)) {
                if (!empty($_FILES["thumbnail"]["tmp_name"])) {
                    $fileInfo      = array(
                        'file' => $_FILES["thumbnail"]["tmp_name"],
                        'name' => $_FILES['thumbnail']['name'],
                        'size' => $_FILES["thumbnail"]["size"],
                        'type' => $_FILES["thumbnail"]["type"],
                        'types' => 'jpeg,jpg,png,bmp,gif',
//                        'crop' => array(
//                            'width' => 600,
//                            'height' => 380
//                        )
                    );
                    $media         = Wo_ShareFile($fileInfo);
                    $mediaFilename = $media['filename'];
                    Wo_UpdateBlog($last_id, array(
                        "thumbnail" => $mediaFilename
                    ));
                }
                $tags     = '';
                $tags_all = explode(',', $_POST['blog_tags']);
                foreach ($tags_all as $key => $tag) {
                    $tags .= "#$tag ";
                }
                $register = Wo_RegisterPost(array(
                    'user_id' => Wo_Secure($wo['user']['user_id']),
                    'blog_id' => Wo_Secure($last_id),
                    'postText' => Wo_Secure($_POST['blog_title']) . ' | ' . $tags,
                    'time' => time(),
                    'postPrivacy' => '0'
                ));
                if ($register) {
                    $data = array(
                        'message' => $success_icon . $wo['lang']['article_added'],
                        'status' => 200,
                        'url' => Wo_SeoLink('index.php?link1=read-blog&id=' . $last_id)
                    );
                }
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
