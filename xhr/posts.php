<?php 
if ($f == 'posts') {
    if ($s == 'fetch_url') {
        if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $_POST["url"], $match)) {
            $youtube_video = Wo_Secure($match[1]);
            $api_request   = file_get_contents('https://www.googleapis.com/youtube/v3/videos?id=' . $youtube_video . '&key=AIzaSyDoOC41IwRzX5XvP7bNiCJXJfcK14HalM0&part=snippet,contentDetails,statistics,status');
            $thumbnail     = '';
            if (!empty($api_request)) {
                $json_decode = json_decode($api_request);
                if (!empty($json_decode->items[0]->snippet)) {
                    if (!empty($json_decode->items[0]->snippet->thumbnails->maxres->url)) {
                        $thumbnail = $json_decode->items[0]->snippet->thumbnails->maxres->url;
                    }
                    if (!empty($json_decode->items[0]->snippet->thumbnails->medium->url)) {
                        $thumbnail = $json_decode->items[0]->snippet->thumbnails->medium->url;
                    }
                    $info        = $json_decode->items[0]->snippet;
                    $title       = $info->title;
                    $description = $info->description;
                    if (!empty($json_decode->items[0]->snippet->tags)) {
                        if (is_array($json_decode->items[0]->snippet->tags)) {
                            foreach ($json_decode->items[0]->snippet->tags as $key => $tag) {
                                $tags_array[] = $tag;
                            }
                            $tags = implode(',', $tags_array);
                        }
                    }
                }
                $output = array(
                    'title' => $title,
                    'images' => array(
                        $thumbnail
                    ),
                    'content' => $description,
                    'url' => $_POST["url"]
                );
                echo json_encode($output);
                exit();
            }
        } else if (isset($_POST["url"])) {
            $page_title = '';
            $image_urls = array();
            $page_body  = '';
            $get_url    = $_POST["url"];
            include_once("assets/libraries/simple_html_dom.inc.php");
            $get_image = getimagesize($get_url);
            if (!empty($get_image)) {
                $image_urls[] = $get_url;
                $page_title   = 'Image';
            } else {
                $get_content = file_get_html($get_url);
                foreach ($get_content->find('title') as $element) {
                    @$page_title = $element->plaintext;
                }
                if (empty($page_title)) {
                    $page_title = '';
                }
                @$page_body = $get_content->find("meta[name='description']", 0)->content;
                $page_body = mb_substr($page_body, 0, 250, "utf-8");
                if ($page_body === false) {
                    $page_body = '';
                }
                if (empty($page_body)) {
                    @$page_body = $get_content->find("meta[property='og:description']", 0)->content;
                    $page_body = mb_substr($page_body, 0, 250, "utf-8");
                    if ($page_body === false) {
                        $page_body = '';
                    }
                }
                $image_urls = array();
                @$page_image = $get_content->find("meta[property='og:image']", 0)->content;
                if (!empty($page_image)) {
                    if (preg_match('/[\w\-]+\.(jpg|png|gif|jpeg)/', $page_image)) {
                        $image_urls[] = $page_image;
                    }
                } else {
                    foreach ($get_content->find('img') as $element) {
                        if (!preg_match('/blank.(.*)/i', $element->src)) {
                            if (preg_match('/[\w\-]+\.(jpg|png|gif|jpeg)/', $element->src)) {
                                $image_urls[] = $element->src;
                            }
                        }
                    }
                }
            }
            $output = array(
                'title' => $page_title,
                'images' => $image_urls,
                'content' => $page_body,
                'url' => $_POST["url"]
            );
            echo json_encode($output);
            exit();
        }
    }
    if ($s == 'search_for_posts') {
        $html = '';
        if (!empty($_GET['search_query'])) {
            $search_data = Wo_SearchForPosts($_GET['id'], $_GET['search_query'], 20, $_GET['type']);
            if (count($search_data) == 0) {
                $html = Wo_LoadPage('story/filter-no-stories-found');
            } else {
                foreach ($search_data as $wo['story']) {
                    $html .= Wo_LoadPage('story/content');
                }
            }
            $data = array(
                'status' => 200,
                'html' => $html
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_new_hashtag_posts') {
        $html = '';
        if (!empty($_GET['before_post_id']) && !empty($_GET['hashtagName'])) {
            $posts = Wo_GetHashtagPosts($_GET['hashtagName'], 0, 20, $_GET['before_post_id']);
            foreach ($posts as $wo['story']) {
                if (!empty($_GET['api'])) {
                    echo Wo_LoadPage('story/api-posts');
                } else {
                    echo Wo_LoadPage('story/content');
                }
            }
        }
        exit();
    }
    if ($s == 'insert_new_post') {
        $media         = '';
        $mediaFilename = '';
        $post_photo    = '';
        $mediaName     = '';
        $video_thumb   = '';
        $html          = '';
        $recipient_id  = 0;
        $page_id       = 0;
        $group_id      = 0;
        $event_id      = 0;
        $invalid_file  = false;
        $errors        = false;
        $image_array   = array();
        $blur          = 0;
        if (Wo_CheckSession($hash_id) === false) {
            return false;
            die();
        }
        if (isset($_POST['recipient_id']) && !empty($_POST['recipient_id'])) {
            $recipient_id = Wo_Secure($_POST['recipient_id']);
        } else if (isset($_POST['event_id']) && !empty($_POST['event_id'])) {
            $event_id = Wo_Secure($_POST['event_id']);
        } else if (isset($_POST['page_id']) && !empty($_POST['page_id'])) {
            $page_id = Wo_Secure($_POST['page_id']);
        } else if (isset($_POST['group_id']) && !empty($_POST['group_id'])) {
            $group_id = Wo_Secure($_POST['group_id']);
            $group    = Wo_GroupData($group_id);
            if (!empty($group['id'])) {
                if ($group['privacy'] == 1) {
                    $_POST['postPrivacy'] = 0;
                } else if ($group['privacy'] == 2) {
                    $_POST['postPrivacy'] = 2;
                }
            }
        }
        if (isset($_FILES['postFile']['name'])) {
            if ($_FILES['postFile']['size'] > $wo['config']['maxUpload']) {
                $invalid_file = 1;
            } else if (Wo_IsFileAllowed($_FILES['postFile']['name']) == false) {
                $invalid_file = 2;
            } else {
                $fileInfo = array(
                    'file' => $_FILES["postFile"]["tmp_name"],
                    'name' => $_FILES['postFile']['name'],
                    'size' => $_FILES["postFile"]["size"],
                    'type' => $_FILES["postFile"]["type"]
                );
                $media    = Wo_ShareFile($fileInfo);
                if (!empty($media)) {
                    $mediaFilename = $media['filename'];
                    $mediaName     = $media['name'];
                }
            }
        }
        if (isset($_FILES['postVideo']['name']) && empty($mediaFilename)) {
            if ($_FILES['postVideo']['size'] > $wo['config']['maxUpload']) {
                $invalid_file = 1;
            } else if (Wo_IsFileAllowed($_FILES['postVideo']['name']) == false) {
                $invalid_file = 2;
            } else {
                $fileInfo = array(
                    'file' => $_FILES["postVideo"]["tmp_name"],
                    'name' => $_FILES['postVideo']['name'],
                    'size' => $_FILES["postVideo"]["size"],
                    'type' => $_FILES["postVideo"]["type"],
                    'types' => 'mp4,m4v,webm,flv,mov,mpeg'
                );
                $media    = Wo_ShareFile($fileInfo);
                if (!empty($media)) {
                    $mediaFilename = $media['filename'];
                    $mediaName     = $media['name'];
                    $img_types     = array(
                        'image/png',
                        'image/jpeg',
                        'image/jpg',
                        'image/gif'
                    );
                    if (!empty($_FILES['video_thumb']) && in_array($_FILES["video_thumb"]["type"], $img_types)) {
                        $fileInfo = array(
                            'file' => $_FILES["video_thumb"]["tmp_name"],
                            'name' => $_FILES['video_thumb']['name'],
                            'size' => $_FILES["video_thumb"]["size"],
                            'type' => $_FILES["video_thumb"]["type"],
                            'types' => 'jpeg,png,jpg,gif',
                            'crop' => array(
                                'width' => 525,
                                'height' => 295
                            )
                        );
                        $media    = Wo_ShareFile($fileInfo);
                        if (!empty($media)) {
                            $video_thumb = $media['filename'];
                        }
                    }
                }
            }
        }
        if (isset($_FILES['postMusic']['name']) && empty($mediaFilename)) {
            if ($_FILES['postMusic']['size'] > $wo['config']['maxUpload']) {
                $invalid_file = 1;
            } else if (Wo_IsFileAllowed($_FILES['postMusic']['name']) == false) {
                $invalid_file = 2;
            } else {
                $fileInfo = array(
                    'file' => $_FILES["postMusic"]["tmp_name"],
                    'name' => $_FILES['postMusic']['name'],
                    'size' => $_FILES["postMusic"]["size"],
                    'type' => $_FILES["postMusic"]["type"],
                    'types' => 'mp3,wav'
                );
                $media    = Wo_ShareFile($fileInfo);
                if (!empty($media)) {
                    $mediaFilename = $media['filename'];
                    $mediaName     = $media['name'];
                }
            }
        }
        $multi = 0;
        if (isset($_FILES['postPhotos']['name']) && empty($mediaFilename) && empty($_POST['album_name'])) {
            if (count($_FILES['postPhotos']['name']) == 1) {
                if ($_FILES['postPhotos']['size'][0] > $wo['config']['maxUpload']) {
                    $invalid_file = 1;
                } else if (Wo_IsFileAllowed($_FILES['postPhotos']['name'][0]) == false) {
                    $invalid_file = 2;
                } else {
                    
                        $fileInfo = array(
                            'file' => $_FILES["postPhotos"]["tmp_name"][0],
                            'name' => $_FILES['postPhotos']['name'][0],
                            'size' => $_FILES["postPhotos"]["size"][0],
                            'type' => $_FILES["postPhotos"]["type"][0]
                        );
                        $media    = Wo_ShareFile($fileInfo);
                        if (!empty($media)) {
                            $image_file = Wo_GetMedia($media['filename']);
                            $upload = true;
                            if ($wo['config']['adult_images'] == 1  && !detect_safe_search($image_file) && $wo['config']['adult_images_action'] == 1) {
                                $blur = 1;
                            }
                            elseif ($wo['config']['adult_images'] == 1  && detect_safe_search($image_file) == false && $wo['config']['adult_images_action'] == 0) {
                                $invalid_file = 3;
                                $upload = false;
                                @unlink($media['filename']);
                                Wo_DeleteFromToS3($media['filename']);
                            }
                            $mediaFilename = $media['filename'];
                            $mediaName     = $media['name'];
                        }
                }
            } else {
                $multi = 1;
            }
        }
        if (empty($_POST['postPrivacy'])) {
            $_POST['postPrivacy'] = 0;
        }
        $post_privacy  = 0;
        $privacy_array = array(
            '0',
            '1',
            '2',
            '3'
        );
        if (isset($_POST['postPrivacy'])) {
            if (in_array($_POST['postPrivacy'], $privacy_array)) {
                $post_privacy = $_POST['postPrivacy'];
            }
        }
        if (empty($page_id)) {
            setcookie("post_privacy", $post_privacy, time() + (10 * 365 * 24 * 60 * 60));
        }
        $import_url_image = '';
        $url_link         = '';
        $url_content      = '';
        $url_title        = '';
        if (!empty($_POST['url_link']) && !empty($_POST['url_title'])) {
            $url_link  = $_POST['url_link'];
            $url_title = $_POST['url_title'];
            if (!empty($_POST['url_content'])) {
                $url_content = $_POST['url_content'];
            }
            if (!empty($_POST['url_image'])) {
                $import_url_image = @Wo_ImportImageFromUrl($_POST['url_image']);
            }
        }
        $post_text = '';
        $post_map  = '';
        if (!empty($_POST['postText']) && !ctype_space($_POST['postText'])) {
            $post_text = $_POST['postText'];
        }
        if (!empty($_POST['postMap'])) {
            $post_map = $_POST['postMap'];
        }
        $album_name = '';
        if (!empty($_POST['album_name'])) {
            $album_name = $_POST['album_name'];
        }
        if (!isset($_FILES['postPhotos']['name'])) {
            $album_name = '';
        }
        $traveling = '';
        $watching  = '';
        $playing   = '';
        $listening = '';
        $feeling   = '';
        if (!empty($_POST['feeling_type'])) {
            $array_types = array(
                'feelings',
                'traveling',
                'watching',
                'playing',
                'listening'
            );
            if (in_array($_POST['feeling_type'], $array_types)) {
                if ($_POST['feeling_type'] == 'feelings') {
                    if (!empty($_POST['feeling'])) {
                        if (array_key_exists($_POST['feeling'], $wo['feelingIcons'])) {
                            $feeling = $_POST['feeling'];
                        }
                    }
                } else if ($_POST['feeling_type'] == 'traveling') {
                    if (!empty($_POST['feeling'])) {
                        $traveling = $_POST['feeling'];
                    }
                } else if ($_POST['feeling_type'] == 'watching') {
                    if (!empty($_POST['feeling'])) {
                        $watching = $_POST['feeling'];
                    }
                } else if ($_POST['feeling_type'] == 'playing') {
                    if (!empty($_POST['feeling'])) {
                        $playing = $_POST['feeling'];
                    }
                } else if ($_POST['feeling_type'] == 'listening') {
                    if (!empty($_POST['feeling'])) {
                        $listening = $_POST['feeling'];
                    }
                }
            }
        }
        if (isset($_FILES['postPhotos']['name'])) {
            $allowed = array(
                'gif',
                'png',
                'jpg',
                'jpeg'
            );
            for ($i = 0; $i < count($_FILES['postPhotos']['name']); $i++) {
                $new_string = pathinfo($_FILES['postPhotos']['name'][$i]);
                if (!in_array(strtolower($new_string['extension']), $allowed)) {
                    $errors[] = $error_icon . $wo['lang']['please_check_details'];
                }
                
            }
        }
        if (!empty($_POST['answer']) && array_filter($_POST['answer'])) {
            if (!empty($_POST['postText'])) {
                foreach ($_POST['answer'] as $key => $value) {
                    if (empty($value) || ctype_space($value)) {
                        $errors = 'Answer #' . ($key + 1) . ' is empty.';
                    }
                }
            } else {
                $errors = 'Please write the question.';
            }
        }
        if (empty($errors) && $invalid_file == false) {
            $is_option = false;
            if (!empty($_POST['answer']) && array_filter($_POST['answer'])) {
                $is_option = true;
            }
            $post_data = array(
                'user_id' => Wo_Secure($wo['user']['user_id']),
                'page_id' => Wo_Secure($page_id),
                'group_id' => Wo_Secure($group_id),
                'event_id' => Wo_Secure($event_id),
                'postText' => Wo_Secure($post_text),
                'recipient_id' => Wo_Secure($recipient_id),
                'postRecord' => Wo_Secure($_POST['postRecord']),
                'postFile' => Wo_Secure($mediaFilename, 0),
                'postFileName' => Wo_Secure($mediaName),
                'postMap' => Wo_Secure($post_map),
                'postPrivacy' => Wo_Secure($post_privacy),
                'postCategory' => Wo_Secure($_POST['postCategory']),
                'postLinkTitle' => Wo_Secure($url_title),
                'postLinkContent' => Wo_Secure($url_content),
                'postLink' => Wo_Secure($url_link),
                'postLinkImage' => Wo_Secure($import_url_image, 0),
                'album_name' => Wo_Secure($album_name),
                'multi_image' => Wo_Secure($multi),
                'postFeeling' => Wo_Secure($feeling),
                'postListening' => Wo_Secure($listening),
                'postPlaying' => Wo_Secure($playing),
                'postWatching' => Wo_Secure($watching),
                'postTraveling' => Wo_Secure($traveling),
                'postFileThumb' => Wo_Secure($video_thumb),
                'time' => time(),
                'blur' => $blur,
                'multi_image_post' => 0
            );
            if (isset($_POST['postSticker']) && Wo_IsUrl($_POST['postSticker']) && empty($_FILES) && empty($_POST['postRecord'])) {
                $post_data['postSticker'] = $_POST['postSticker'];
            } else if (empty($_FILES['postPhotos']) && preg_match_all('/https?:\/\/(?:[^\s]+)\.(?:png|jpg|gif|jpeg)/', $post_data['postText'], $matches)) {
                if (!empty($matches[0][0]) && Wo_IsUrl($matches[0][0])) {
                    $post_data['postPhoto'] = @Wo_ImportImageFromUrl($matches[0][0]);
                }
            }
            if (!empty($is_option)) {
                $post_data['poll_id'] = 1;
            }
            if (!empty($_POST['post_color']) && !empty($post_text) && empty($_POST['postRecord']) && empty($mediaFilename) && empty($mediaName) && empty($post_map) && empty($url_title) && empty($url_content) && empty($url_link) && empty($import_url_image) && empty($album_name) && empty($multi) && empty($video_thumb) && empty($post_data['postPhoto'])) {
                $post_data['color_id'] = Wo_Secure($_POST['post_color']);
            }
            $id = Wo_RegisterPost($post_data);
            if ($id) {
                Wo_CleanCache();
                Wo_UpdateUserDetails($wo['user'], true, false, false, true);
                if ($is_option == true) {
                    foreach ($_POST['answer'] as $key => $value) {
                        $add_opition = Wo_AddOption($id, $value);
                    }
                }
                if (isset($_FILES['postPhotos']['name'])) {
                    if (count($_FILES['postPhotos']['name']) > 0) {
                        for ($i = 0; $i < count($_FILES['postPhotos']['name']); $i++) {
                            $fileInfo = array(
                                'file' => $_FILES["postPhotos"]["tmp_name"][$i],
                                'name' => $_FILES['postPhotos']['name'][$i],
                                'size' => $_FILES["postPhotos"]["size"][$i],
                                'type' => $_FILES["postPhotos"]["type"][$i],
                                'types' => 'jpg,png,jpeg,gif'
                            );
                            $file     = Wo_ShareFile($fileInfo, 1);
                            $image_file = Wo_GetMedia($file['filename']);
                            if ($wo['config']['adult_images'] == 1  && !detect_safe_search($image_file) && $wo['config']['adult_images_action'] == 1) {
                                $blur = 1;
                            }
                            elseif ($wo['config']['adult_images'] == 1 && detect_safe_search($image_file) == false && $wo['config']['adult_images_action'] == 0) {
                                $invalid_file = 3;
                                $errors[] = $error_icon . $wo['lang']['adult_image_file'];
                                Wo_DeletePost($id);
                                @unlink($file['filename']);
                                Wo_DeleteFromToS3($file['filename']);
                            }
                            if (!empty($file)) {
                                $media_album = Wo_RegisterAlbumMedia($id, $file['filename']);
                                $post_data['multi_image'] = 0;
                                $post_data['multi_image_post'] = 1;
                                $post_data['album_name'] = '';
                                $post_data['postFile'] = $file['filename'];
                                $post_data['postFileName'] = $file['name'];
                                $new_id = Wo_RegisterPost($post_data);
                                $media_album = Wo_RegisterAlbumMedia($new_id, $file['filename'],$id);
                            }
                        }
                    }
                }
                $wo['story'] = Wo_PostData($id);
                $html .= Wo_LoadPage('story/content');
                $data = array(
                    'status' => 200,
                    'html' => $html,
                    'invalid_file' => $invalid_file,
                    'post_count' => (!empty($wo['story']['publisher']['details']) ? $wo['story']['publisher']['details']['post_count'] : 0)
                );
            } else {
                $data = array(
                    'status' => 400,
                    'invalid_file' => $invalid_file
                );
            }
        } else {
            header("Content-type: application/json");
            echo json_encode(array(
                'status' => 400,
                'errors' => $errors,
                'invalid_file' => $invalid_file
            ));
            exit();
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_post' && Wo_CheckMainSession($hash_id) === true) {
        if (!empty($_GET['post_id'])) {
            //Wo_RunInBackground(array('status' => 200));
            $wo['story'] = $db->where('id',Wo_Secure($_GET['post_id']))->getOne(T_POSTS);
            $post_id = Wo_Secure($_GET['post_id']);
            $post = $db->where('id',$post_id)->getOne(T_POSTS);
            if (Wo_DeletePost($_GET['post_id']) === true) {
                if (!empty($post)) {
                    $text = $post->postText;
                    $hashtag_regex = '/(#\[([0-9]+)\])/i';
                    preg_match_all($hashtag_regex, $text, $matches);
                    $match_i = 0;
                    foreach ($matches[1] as $match) {
                        $hashkey  = $matches[2][$match_i];
                        if (!empty($hashkey)) {
                            $db->where('id',$hashkey)->update(T_HASHTAGS,array('trend_use_num' => $db->dec(1)));
                        }
                        $match_i++;
                    }
                }
                $wo['user_profile'] = Wo_UserData($wo['story']->user_id);
                $user_data = Wo_UpdateUserDetails($wo['story']->user_id, true, false, true, true);
                Wo_CleanCache();
                $data = array(
                    'status' => 200,
                    'post_count' => $user_data['details']['post_count']
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'report_comment' && Wo_CheckMainSession($hash_id) === true) {
        if (!empty($_GET['comment_id'])) {
            $post_data = array(
                'comment_id' => $_GET['comment_id']
            );
            if (Wo_ReportPost($post_data) == 'unreport') {
                $data = array(
                    'status' => 300,
                    'text' => $wo['lang']['comment_unreported']
                );
            } else {
                $data = array(
                    'status' => 200,
                    'text' => $wo['lang']['comment_reported']
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_new_posts') {
        if (!empty($_GET['before_post_id']) && isset($_GET['user_id'])) {
            $html      = '';
            $postsData = array(
                'before_post_id' => $_GET['before_post_id'],
                'publisher_id' => $_GET['user_id'],
                'limit' => 20,
                'placement' => 'multi_image_post'
            );
            $posts     = Wo_GetPosts($postsData);
            foreach ($posts as $wo['story']) {
                if (!empty($_GET['api'])) {
                    echo Wo_LoadPage('story/api-posts');
                } else {
                    echo Wo_LoadPage('story/content');
                }
            }
        }
        exit();
    }
    if ($s == 'load_more_posts') {
        $html = '';
        if ($_GET['filter_by_more'] == 'story' && isset($_GET['story_id']) && isset($_GET['user_id'])) {
            $args           = array();
            $args['offset'] = Wo_Secure($_GET['story_id']);
            if ($_GET['user_id'] > 0) {
                $args['user'] = Wo_Secure($_GET['user_id']);
            }
            foreach (Wo_GetStroies($args) as $wo['story']) {
                echo Wo_LoadPage('status/content');
            }
        } else if ($_GET['filter_by_more'] == 'most_liked') {
            $most_liked_posts = Wo_GetPosts(array(
                'filter_by' => 'most_liked',
                'after_post_id' => Wo_Secure($_GET['after_post_id']),
                'placement' => 'multi_image_post'
            ));
            foreach ($most_liked_posts as $wo['story']) {
                if ($is_api == true) {
                    echo Wo_LoadPage('story/api-posts');
                } else {
                    echo sanitize_output(Wo_LoadPage('story/content'));
                }
            }
        } else if (!empty($_GET['filter_by_more']) && !empty($_GET['after_post_id'])) {
            $page_id  = 0;
            $group_id = 0;
            $user_id  = 0;
            $story_id = 0;
            $event_id = 0;
            $ad_id    = 0;
            if (!empty($_GET['page_id']) && $_GET['page_id'] > 0) {
                $page_id = Wo_Secure($_GET['page_id']);
            }
            if (!empty($_GET['group_id']) && $_GET['group_id'] > 0) {
                $group_id = Wo_Secure($_GET['group_id']);
            }
            if (!empty($_GET['user_id']) && $_GET['user_id'] > 0) {
                $user_id = Wo_Secure($_GET['user_id']);
            }
            if (!empty($_GET['event_id']) && $_GET['event_id'] > 0) {
                $event_id = Wo_Secure($_GET['event_id']);
            }
            if (isset($_GET['ad_id']) && is_numeric($_GET['ad_id']) && $_GET['ad_id'] > 0) {
                $ad_id = Wo_Secure($_GET['ad_id']);
            }
            if (isset($_GET['story_id']) && is_numeric($_GET['story_id']) && $_GET['story_id'] > 0) {
                $story_id = Wo_Secure($_GET['story_id']);
            }
            $postsData = array(
                'filter_by' => Wo_Secure($_GET['filter_by_more']),
                'limit' => 6,
                'publisher_id' => $user_id,
                'group_id' => $group_id,
                'page_id' => $page_id,
                'event_id' => $event_id,
                'after_post_id' => Wo_Secure($_GET['after_post_id']),
                'ad-id' => $ad_id,
                'story_id' => $story_id,
                'placement' => 'multi_image_post'
            );
            $get_posts = Wo_GetPosts($postsData);
            $is_api    = false;
            if (!empty($_GET['is_api'])) {
                $is_api = true;
            }
            if (!empty($_GET['posts_count']) && !empty($get_posts) && $is_api == false) {
                if ($_GET['posts_count'] > 9 && $_GET['posts_count'] < 15) {
                    echo Wo_GetAd('post_first', false);
                } else if ($_GET['posts_count'] > 20 && $_GET['posts_count'] < 28) {
                    echo Wo_GetAd('post_second', false);
                } else if ($_GET['posts_count'] > 29) {
                    echo Wo_GetAd('post_third', false);
                }
            }
            foreach ($get_posts as $wo['story']) {
                if ($is_api == true) {
                    echo Wo_LoadPage('story/api-posts');
                } else {
                    echo sanitize_output(Wo_LoadPage('story/content'));
                }
            }
        }
        exit();
    }
    if ($s == 'edit_post') {
        $_POST['text'] = trim($_POST['text']);
        if (!empty($_POST['post_id']) && !empty($_POST['text'])) {
            $post_id = Wo_Secure($_POST['post_id']);
            $post = $db->where('id',$post_id)->getOne(T_POSTS);
            if (!empty($post)) {
                $wo['no_mention'] = array();
                $mention_regex = '/@\[([0-9]+)\]/i';
                preg_match_all($mention_regex, $post->postText, $matches);
                foreach ($matches[1] as $match) {
                    if (!empty($match)) {
                        $wo['no_mention'][] = $match;
                    }
                }
            }
            $updatePost = Wo_UpdatePost(array(
                'post_id' => $_POST['post_id'],
                'text' => $_POST['text']
            ));
            if (!empty($updatePost)) {
                if (!empty($post)) {
                    $text = $post->postText;
                    $hashtag_regex = '/(#\[([0-9]+)\])/i';
                    preg_match_all($hashtag_regex, $text, $matches);
                    $match_i = 0;
                    foreach ($matches[1] as $match) {
                        $hashkey  = $matches[2][$match_i];
                        if (!empty($hashkey)) {
                            $db->where('id',$hashkey)->update(T_HASHTAGS,array('trend_use_num' => $db->dec(1)));
                        }
                        $match_i++;
                    }
                }
                Wo_CleanCache();
                $data = array(
                    'status' => 200,
                    'html' => $updatePost
                );
                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == "update_post_privacy") {
        if (!empty($_GET['post_id']) && isset($_GET['privacy_type']) && Wo_CheckMainSession($hash_id) === true) {
            $updatePost = Wo_UpdatePostPrivacy(array(
                'post_id' => Wo_Secure($_GET['post_id']),
                'privacy_type' => Wo_Secure($_GET['privacy_type'])
            ));
            if (isset($updatePost)) {
                $data = array(
                    'status' => 200,
                    'privacy_type' => $updatePost
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_like') {
        if (!empty($_GET['post_id']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_AddLikes($_GET['post_id']) == 'unliked') {
                $data = array(
                    'status' => 300,
                    'likes' => Wo_CountLikes($_GET['post_id']),
                    'like_lang' => $wo['lang']['like']
                );
            } else {
                $data = array(
                    'status' => 200,
                    'likes' => Wo_CountLikes($_GET['post_id']),
                    'like_lang' => $wo['lang']['liked']
                );
                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }
            }
            $data['dislike'] = 0;
            if ($wo['config']['second_post_button'] == 'dislike') {
                $data['dislike']              = 1;
                $data['default_lang_like']    = $wo['lang']['like'];
                $data['default_lang_dislike'] = $wo['lang']['dislike'];
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_reaction') {
        if (!empty($_GET['post_id']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_DeleteReactions($_GET['post_id'])) {
                $data = array(
                    'status' => 200,
                    'like_lang' => $wo['lang']['like']
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_comment_reaction') {
        if (!empty($_GET['comment_id']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_DeleteCommentReactions($_GET['comment_id'])) {
                $data = array(
                    'status' => 200,
                    'reactions' => Wo_GetPostReactions($_GET['comment_id'], "comment")
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_replay_reaction') {
        if (!empty($_GET['replay_id']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_DeleteReplayReactions($_GET['replay_id'])) {
                $data = array(
                    'status' => 200,
                    'reactions' => Wo_GetPostReactions($_GET['replay_id'], "replay")
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_reaction') {
        if (!empty($_GET['post_id']) && !empty($_GET['reaction']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_AddReactions($_GET['post_id'], $_GET['reaction']) == 'reacted') {
                $data = array(
                    'status' => 200,
                    'reactions' => Wo_GetPostReactions($_GET['post_id']),
                    'like_lang' => $wo['lang']['liked']
                );
                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }
            }
            $data['dislike'] = 0;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_comment_reaction') {
        if (!empty($_GET['comment_id']) && !empty($_GET['reaction']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_AddCommentReactions($_GET['comment_id'], $_GET['reaction']) == 'reacted') {
                $data = array(
                    'status' => 200,
                    'reactions' => Wo_GetPostReactions($_GET['comment_id'], "comment"),
                    'like_lang' => $wo['lang']['liked']
                );
                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }
            }
            $data['dislike'] = 0;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_replay_reaction') {
        if (!empty($_GET['reply_id']) && !empty($_GET['reaction']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_AddReplayReactions($_GET['user_id'], $_GET['reply_id'], $_GET['reaction']) == 'reacted') {
                $data = array(
                    'status' => 200,
                    'reactions' => Wo_GetPostReactions($_GET['reply_id'], "replay"),
                    'like_lang' => $wo['lang']['liked']
                );
                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }
            }
            $data['dislike'] = 0;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_wonder') {
        if (!empty($_GET['post_id']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_AddWonders($_GET['post_id']) == 'unwonder') {
                $data                = array(
                    'status' => 300,
                    'icon' => $wo['second_post_button_icon'],
                    'wonders' => Wo_CountWonders($_GET['post_id'])
                );
                $data['wonder_lang'] = ($config['second_post_button'] == 'dislike') ? $wo['lang']['dislike'] : $wo['lang']['wonder'];
            } else {
                $data                = array(
                    'status' => 200,
                    'icon' => $wo['second_post_button_icon'],
                    'wonders' => Wo_CountWonders($_GET['post_id'])
                );
                $data['wonder_lang'] = ($config['second_post_button'] == 'dislike') ? $wo['lang']['disliked'] : $wo['lang']['wondered'];
                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }
            }
            $data['dislike'] = 0;
            if ($wo['config']['second_post_button'] == 'dislike') {
                $data['dislike']              = 1;
                $data['default_lang_like']    = $wo['lang']['like'];
                $data['default_lang_dislike'] = $wo['lang']['dislike'];
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_share') {
        if (!empty($_GET['post_id']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_AddShare($_GET['post_id']) == 'unshare') {
                $data = array(
                    'status' => 300,
                    'shares' => Wo_CountShares($_GET['post_id'])
                );
            } else {
                $data = array(
                    'status' => 200,
                    'shares' => Wo_CountShares($_GET['post_id'])
                );
                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_comment') {
        if (!empty($_POST['post_id']) && isset($_POST['text'])) {
            $html    = '';
            $page_id = '';
            if (!empty($_POST['page_id'])) {
                $page_id = $_POST['page_id'];
            }
            $comment_image = '';
            if (!empty($_POST['comment_image'])) {
                if (isset($_SESSION['file']) && $_SESSION['file'] == $_POST['comment_image']) {
                    $comment_image = $_POST['comment_image'];
                    unset($_SESSION['file']);
                }
            }
            if (empty($comment_image) && empty($_POST['text']) && empty($_POST['audio-filename'])) {
                header("Content-type: application/json");
                echo json_encode($data);
                exit();
            }
            $text_comment = '';
            if (!empty($_POST['text']) && !ctype_space($_POST['text'])) {
                $text_comment = $_POST['text'];
            }
            $C_Data = array(
                'user_id' => Wo_Secure($wo['user']['user_id']),
                'page_id' => Wo_Secure($page_id),
                'post_id' => Wo_Secure($_POST['post_id']),
                'text' => Wo_Secure($_POST['text']),
                'c_file' => Wo_Secure($comment_image),
                'time' => time()
            );
            if (!empty($_POST['audio-filename']) && isset($_FILES["audio-blob"]["tmp_name"])) {
                $fileInfo         = array(
                    'file' => $_FILES["audio-blob"]["tmp_name"],
                    'name' => $_FILES['audio-blob']['name'],
                    'size' => $_FILES["audio-blob"]["size"],
                    'type' => $_FILES["audio-blob"]["type"],
                    'types' => 'mp3,wav'
                );
                $media            = Wo_ShareFile($fileInfo);
                $C_Data['record'] = $media['filename'];
            }
            $R_Comment     = Wo_RegisterPostComment($C_Data);
            $wo['comment'] = Wo_GetPostComment($R_Comment);
            $wo['story']   = Wo_PostData($_POST['post_id']);
            if (!empty($wo['comment'])) {
                $html = Wo_LoadPage('comment/content');
                $data = array(
                    'status' => 200,
                    'html' => $html,
                    'comments_num' => Wo_CountPostComment($_POST['post_id'])
                );
                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_post_record') {
        $data = array(
            'status' => 500,
            "url" => null
        );
        if (!empty($_POST['audio-filename']) && isset($_FILES["audio-blob"]["tmp_name"])) {
            $fileInfo       = array(
                'file' => $_FILES["audio-blob"]["tmp_name"],
                'name' => $_FILES['audio-blob']['name'],
                'size' => $_FILES["audio-blob"]["size"],
                'type' => $_FILES["audio-blob"]["type"],
                'types' => 'mp3,wav'
            );
            $media          = Wo_ShareFile($fileInfo);
            $data['url']    = $media['filename'];
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_reply') {
        if (!empty($_POST['comment_id']) && !empty($_POST['text']) && Wo_CheckMainSession($hash_id) === true) {
            $html    = '';
            $page_id = '';
            if (!empty($_POST['page_id'])) {
                $page_id = $_POST['page_id'];
            }
            $C_Data      = array(
                'user_id' => Wo_Secure($wo['user']['user_id']),
                'page_id' => Wo_Secure($page_id),
                'comment_id' => Wo_Secure($_POST['comment_id']),
                'text' => Wo_Secure($_POST['text']),
                'time' => time()
            );
            $R_Comment   = Wo_RegisterCommentReply($C_Data);
            $wo['reply'] = Wo_GetCommentReply($R_Comment);
            if (!empty($wo['reply'])) {
                $html = Wo_LoadPage('comment/replies-content');
                $data = array(
                    'status' => 200,
                    'html' => $html,
                    'replies_num' => Wo_CountCommentReplies($_POST['comment_id'])
                );
                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update-reply') {
        if (!empty($_POST['id']) && !empty($_POST['text']) && Wo_CheckMainSession($hash_id) === true) {
            $id           = Wo_Secure($_POST['id']);
            $data         = array(
                'status' => 304
            );
            $update_datau = array(
                'text' => Wo_Secure($_POST['text'])
            );

            if (Wo_UpdateCommentReply($id, $update_datau)) {

                $reply = Wo_GetCommentReply($id);
                $data = array(
                    'status' => 200,
                    'text' => $reply['text'],
                    'orginal' => $reply['Orginaltext']
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_comment') {
        if (!empty($_GET['comment_id']) && Wo_CheckMainSession($hash_id) === true) {
            Wo_RunInBackground(array(
                'status' => 200
            ));
            $query = mysqli_query($sqlConnect, "SELECT `post_id`, `user_id` FROM `" . T_COMMENTS . "` WHERE `id` = " . $_GET['comment_id']);
            if (mysqli_num_rows($query) > 0) {
                $fetched_data = mysqli_fetch_assoc($query);
                $post_id      = $fetched_data['post_id'];
                $user_id      = $fetched_data['user_id'];
            }
            $DeleteComment = Wo_DeletePostComment($_GET['comment_id']);
            if ($DeleteComment === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_comment_reply') {
        if (!empty($_GET['reply_id']) && Wo_CheckMainSession($hash_id) === true) {
            $DeleteComment = Wo_DeletePostReplyComment($_GET['reply_id']);
            if ($DeleteComment === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'load_more_comments') {
        if (!empty($_GET['post_id'])) {
            $html        = '';
            $wo['story'] = Wo_PostData($_GET['post_id']);
            foreach (Wo_GetPostComments($_GET['post_id'], Wo_CountPostComment($_GET['post_id'])) as $wo['comment']) {
                $html .= Wo_LoadPage('comment/content');
            }
            $data = array(
                'status' => 200,
                'html' => $html
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }if ($s == 'load_more_comments_sort') {
        if (!empty($_GET['post_id'])) {
            $html        = '';
            $wo['story'] = Wo_PostData($_GET['post_id']);
            if (!empty($wo['story'])) {
                foreach (Wo_GetPostCommentsSort($_GET['post_id'], Wo_CountPostComment($_GET['post_id']),$_GET['type']) as $wo['comment']) {
                    $html .= Wo_LoadPage('comment/content');
                }
            }
                
            $data = array(
                'status' => 200,
                'html' => $html
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'load_more_replies') {
        if (!empty($_GET['comment_id'])) {
            $html = '';
            foreach (Wo_GetCommentReplies($_GET['comment_id'], Wo_CountCommentReplies($_GET['comment_id'])) as $wo['reply']) {
                $html .= Wo_LoadPage('comment/replies-content');
            }
            $data = array(
                'status' => 200,
                'html' => $html
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'edit_comment') {
        if (!empty($_POST['comment_id']) && !empty($_POST['text']) && Wo_CheckMainSession($hash_id) === true) {
            $updateComment = Wo_UpdateComment(array(
                'comment_id' => $_POST['comment_id'],
                'text' => $_POST['text']
            ));
            if (!empty($updateComment)) {
                $data = array(
                    'status' => 200,
                    'html' => $updateComment
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_comment_like') {
        if (!empty($_POST['comment_id']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_AddCommentLikes($_POST['comment_id'], $_POST['comment_text']) == 'unliked') {
                $data = array(
                    'status' => 300,
                    'likes' => Wo_CountCommentLikes($_POST['comment_id'])
                );
            } else {
                $data = array(
                    'status' => 200,
                    'likes' => Wo_CountCommentLikes($_POST['comment_id'])
                );
            }
            $data['dislike'] = 0;
            if ($wo['config']['second_post_button'] == 'dislike') {
                $data['dislike']   = 1;
                $data['wonders_c'] = Wo_CountCommentWonders($_POST['comment_id']);
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_comment_wonder') {
        if (!empty($_POST['comment_id']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_AddCommentWonders($_POST['comment_id'], $_POST['comment_text']) == 'unwonder') {
                $data = array(
                    'status' => 300,
                    'icon' => $wo['second_post_button_icon'],
                    'wonders' => Wo_CountCommentWonders($_POST['comment_id'])
                );
            } else {
                $data = array(
                    'status' => 200,
                    'icon' => $wo['second_post_button_icon'],
                    'wonders' => Wo_CountCommentWonders($_POST['comment_id'])
                );
            }
            $data['dislike'] = 0;
            if ($wo['config']['second_post_button'] == 'dislike') {
                $data['dislike'] = 1;
                $data['likes_c'] = Wo_CountCommentLikes($_POST['comment_id']);
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_comment_reply_like') {
        if (!empty($_POST['reply_id']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_AddCommentReplyLikes($_POST['reply_id'], $_POST['comment_text']) == 'unliked') {
                $data = array(
                    'status' => 300,
                    'likes' => Wo_CountCommentReplyLikes($_POST['reply_id'])
                );
            } else {
                $data = array(
                    'status' => 200,
                    'likes' => Wo_CountCommentReplyLikes($_POST['reply_id'])
                );
            }
            $data['dislike'] = 0;
            if ($wo['config']['second_post_button'] == 'dislike') {
                $data['dislike']   = 1;
                $data['wonders_r'] = Wo_CountCommentReplyWonders($_POST['reply_id']);
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'register_comment_reply_wonder') {
        if (!empty($_POST['reply_id']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_AddCommentReplyWonders($_POST['reply_id'], $_POST['comment_text']) == 'unwonder') {
                $data = array(
                    'status' => 300,
                    'icon' => $wo['second_post_button_icon'],
                    'wonders' => Wo_CountCommentReplyWonders($_POST['reply_id'])
                );
            } else {
                $data = array(
                    'status' => 200,
                    'icon' => $wo['second_post_button_icon'],
                    'wonders' => Wo_CountCommentReplyWonders($_POST['reply_id'])
                );
            }
            $data['dislike'] = 0;
            if ($wo['config']['second_post_button'] == 'dislike') {
                $data['dislike'] = 1;
                $data['likes_r'] = Wo_CountCommentReplyLikes($_POST['reply_id']);
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'save_post') {
        if (!empty($_GET['post_id']) && Wo_CheckMainSession($hash_id) === true) {
            $post_data = array(
                'post_id' => $_GET['post_id']
            );
            if (Wo_SavePosts($post_data) == 'unsaved') {
                $data = array(
                    'status' => 300,
                    'text' => $wo['lang']['save_post']
                );
            } else {
                $data = array(
                    'status' => 200,
                    'text' => $wo['lang']['unsave_post']
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'disable_comment') {
        if (!empty($_GET['post_id']) && Wo_CheckMainSession($hash_id) === true) {
            $type = 1;
            if (isset($_GET['type'])) {
                $types_array = array(
                    '0',
                    '1',
                );
                if (in_array($_GET['type'], $types_array)) {
                    $type = $_GET['type'];
                }
            }
            if (Wo_IsPostOnwer($_GET['post_id'], $wo['user']['user_id'])) {
                if ($type == 0) {
                    $db->where('id', Wo_Secure($_GET['post_id']))->update(T_POSTS, array('comments_status' => 1));
                    $data = array(
                        'status' => 300,
                        'text' => $wo['lang']['disable_comments']
                    );
                } else if ($type == 1) {
                    $db->where('id', Wo_Secure($_GET['post_id']))->update(T_POSTS, array('comments_status' => 0));
                    $data = array(
                        'status' => 200,
                        'text' => $wo['lang']['enable_comments']
                    );
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'pin_post') {
        if (!empty($_GET['post_id']) && Wo_CheckMainSession($hash_id) === true) {
            $type = 'profile';
            $id   = 0;
            if (!empty($_GET['type'])) {
                $types_array = array(
                    'profile',
                    'page',
                    'group',
                    'event'
                );
                if (in_array($_GET['type'], $types_array)) {
                    $type = $_GET['type'];
                }
            }
            if (!empty($_GET['id']) && is_numeric($_GET['id'])) {
                $id = $_GET['id'];
            }
            if (Wo_PinPost($_GET['post_id'], $type, $id) == 'unpin') {
                $data = array(
                    'status' => 300,
                    'text' => $wo['lang']['pin_post']
                );
            } else {
                $data = array(
                    'status' => 200,
                    'text' => $wo['lang']['unpin_post']
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'boost_post') {
        if (!empty($_GET['post_id']) && $wo['config']['pro'] == 1 && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_BoostPost($_GET['post_id']) == 'unboosted') {
                $data = array(
                    'status' => 300,
                    'text' => $wo['lang']['boost_post']
                );
            } else {
                $data = array(
                    'status' => 200,
                    'text' => $wo['lang']['unboost_post']
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'mark_as_sold_post') {
        if (!empty($_GET['post_id']) && !empty($_GET['product_id']) && Wo_CheckMainSession($hash_id) === true) {
            if (Wo_MarkPostAsSold($_GET['post_id'], $_GET['product_id'])) {
                $data = array(
                    'status' => 200,
                    'text' => $wo['lang']['sold']
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'report_post') {
        if (!empty($_GET['post_id'])) {
            $post_data = array(
                'post_id' => $_GET['post_id']
            );
            if (Wo_ReportPost($post_data) == 'unreport') {
                $data = array(
                    'status' => 300,
                    'text' => $wo['lang']['report_post']
                );
            } else {
                $data = array(
                    'status' => 200,
                    'text' => $wo['lang']['unreport_post']
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_post_reacted') {
        if (!empty($_GET['post_id']) && !empty($_GET['col'])) {
            $data         = array(
                'status' => 200,
                'html' => '',
                'title' => $wo['lang']['users_reacted_post']
            );
            $react_array = array('like' => 'Like','love' => 'Love' ,'haha' => 'HaHa' ,'wow' => 'WoW' ,'sad' => 'Sad' ,'angry' => 'Angry' );
            if (!empty($_GET['type']) && in_array($_GET['type'],array_keys($react_array)) && !empty($_GET['offset']) && is_numeric($_GET['offset']) && $_GET['offset'] > 0) {
                $reactedUsers = Wo_GetPostReactionUsers($_GET['post_id'], $react_array[$_GET['type']] ,20,Wo_Secure($_GET['offset']),Wo_Secure($_GET['col']));
            }
            else{
                $reactedUsers = Wo_GetPostReactionUsers($_GET['post_id'], $react_array[$_GET['type']],20,0,Wo_Secure($_GET['col']));
            }

            $post_info = array();

            if ($_GET['col'] == 'post') {
                $post_info = $db->where('id',Wo_Secure($_GET['post_id']))->getOne(T_POSTS);
            }
            elseif ($_GET['col'] == 'comment') {
                $comment = $db->where('id',Wo_Secure($_GET['post_id']))->getOne(T_COMMENTS);
                if (!empty($comment->post_id)) {
                    $post_info = $db->where('id',$comment->post_id)->getOne(T_POSTS);
                }
            }
            elseif ($_GET['col'] == 'replay') {
                $comment = $db->where('id',Wo_Secure($_GET['post_id']))->getOne(T_COMMENTS_REPLIES);
                if (!empty($comment->comment_id)) {
                    $comment = $db->where('id',$comment->comment_id)->getOne(T_COMMENTS);
                    if (!empty($comment->post_id)) {
                        $post_info = $db->where('id',$comment->post_id)->getOne(T_POSTS);
                    }
                }
            }
            
            
            if (count($reactedUsers) > 0) {
                foreach ($reactedUsers as $wo['WondredLikedusers']) {
                    $wo['WondredLikedusers']['page_info'] = array();
                    if (!empty($post_info) && !empty($post_info->page_id)) {
                        $wo['WondredLikedusers']['page_info'] = Wo_PageData($post_info->page_id);
                    }
                    
                    if (!empty($wo['WondredLikedusers']['page_info']) && !empty($post_info) && $post_info->page_id > 0 && $wo['WondredLikedusers']['page_info']['user_id'] == $wo['WondredLikedusers']['user_id']) {
                        $data['html'] .= Wo_LoadPage('story/page-post-likes');
                    }
                    else{
                        $data['html'] .= Wo_LoadPage('story/post-likes-wonders');
                    }
                    
                }
            } else {
                $data['message'] = $wo['lang']['no_reactions'];
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_post_likes') {
        if (!empty($_GET['post_id']) && !empty($_GET['table'])) {
            $data       = array(
                'status' => 200,
                'html' => '',
                'title' => $wo['lang']['users_liked_post']
            );
            if ($_GET['table'] == 'post') {
                if (!empty($_GET['type']) && $_GET['type'] == 'like' && !empty($_GET['offset']) && is_numeric($_GET['offset']) && $_GET['offset'] > 0) {
                    $likedUsers = Wo_GetPostLikes($_GET['post_id'],20,Wo_Secure($_GET['offset']));
                }
                else{
                    $likedUsers = Wo_GetPostLikes($_GET['post_id']);
                }
            }
            elseif ($_GET['table'] == 'comment') {
                if (!empty($_GET['type']) && $_GET['type'] == 'like' && !empty($_GET['offset']) && is_numeric($_GET['offset']) && $_GET['offset'] > 0) {
                    $likedUsers = Wo_GetPostCommentLikes($_GET['post_id'],20,Wo_Secure($_GET['offset']));
                }
                else{
                    $likedUsers = Wo_GetPostCommentLikes($_GET['post_id']);
                }
                $data['title'] = $wo['lang']['users_liked_comment'];
            }
            elseif ($_GET['table'] == 'reply') {
                if (!empty($_GET['type']) && $_GET['type'] == 'like' && !empty($_GET['offset']) && is_numeric($_GET['offset']) && $_GET['offset'] > 0) {
                    $likedUsers = Wo_GetPostCommentReplyLikes($_GET['post_id'],20,Wo_Secure($_GET['offset']));
                }
                else{
                    $likedUsers = Wo_GetPostCommentReplyLikes($_GET['post_id']);
                }
                $data['title'] = $wo['lang']['users_liked_comment'];
            }
            if (count($likedUsers) > 0) {

                $post_info = array();

                if ($_GET['table'] == 'post') {
                    $post_info = $db->where('id',Wo_Secure($_GET['post_id']))->getOne(T_POSTS);
                }
                elseif ($_GET['table'] == 'comment') {
                    $comment = $db->where('id',Wo_Secure($_GET['post_id']))->getOne(T_COMMENTS);
                    if (!empty($comment->post_id)) {
                        $post_info = $db->where('id',$comment->post_id)->getOne(T_POSTS);
                    }
                }
                elseif ($_GET['table'] == 'reply') {
                    $comment = $db->where('id',Wo_Secure($_GET['post_id']))->getOne(T_COMMENTS_REPLIES);
                    if (!empty($comment->comment_id)) {
                        $comment = $db->where('id',$comment->comment_id)->getOne(T_COMMENTS);
                        if (!empty($comment->post_id)) {
                            $post_info = $db->where('id',$comment->post_id)->getOne(T_POSTS);
                        }
                    }
                }
                
                foreach ($likedUsers as $wo['WondredLikedusers']) {
                    $wo['WondredLikedusers']['page_info'] = array();
                    if (!empty($post_info) && !empty($post_info->page_id)) {
                        $wo['WondredLikedusers']['page_info'] = Wo_PageData($post_info->page_id);
                    }
                    
                    if (!empty($wo['WondredLikedusers']['page_info']) && !empty($post_info) && $post_info->page_id > 0 && $wo['WondredLikedusers']['page_info']['user_id'] == $wo['WondredLikedusers']['user_id']) {
                        $data['html'] .= Wo_LoadPage('story/page-post-likes');
                    }
                    else{
                        $data['html'] .= Wo_LoadPage('story/post-likes-wonders');
                    }
                }
            } else {
                $data['message'] = $wo['lang']['no_likes'];
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_post_shared') {
        if (!empty($_GET['post_id'])) {
            $data       = array(
                'status' => 200,
                'html' => '',
                'title' => $wo['lang']['users_shared_post']
            );
            if (!empty($_GET['type']) && $_GET['type'] == 'share' && !empty($_GET['offset']) && is_numeric($_GET['offset']) && $_GET['offset'] > 0) {
                $sharedUsers = Wo_GetPostShared($_GET['post_id'],20,Wo_Secure($_GET['offset']));
            }
            else{
                $sharedUsers = Wo_GetPostShared($_GET['post_id']);
            }
            
            if (count($sharedUsers) > 0) {
                foreach ($sharedUsers as $wo['WondredLikedusers']) {
                    $data['html'] .= Wo_LoadPage('story/post-likes-wonders');
                }
            } else {
                $data['message'] = $wo['lang']['no_shared'];
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_post_wonders') {
        if (!empty($_GET['post_id']) && !empty($_GET['table'])) {
            $data          = array(
                'status' => 200,
                'html' => '',
                'title' => $wo['lang']['users_wondered_post']
            );
            if ($_GET['table'] == 'post') {
                if (!empty($_GET['type']) && $_GET['type'] == 'wonder' && !empty($_GET['offset']) && is_numeric($_GET['offset']) && $_GET['offset'] > 0) {
                    $WonderedUsers = Wo_GetPostWonders($_GET['post_id'],20,Wo_Secure($_GET['offset']));
                }
                else{
                    $WonderedUsers = Wo_GetPostWonders($_GET['post_id']);
                }
            }
            elseif ($_GET['table'] == 'comment') {
                if (!empty($_GET['type']) && $_GET['type'] == 'wonder' && !empty($_GET['offset']) && is_numeric($_GET['offset']) && $_GET['offset'] > 0) {
                    $WonderedUsers = Wo_GetPostCommentWonders($_GET['post_id'],20,Wo_Secure($_GET['offset']));
                }
                else{
                    $WonderedUsers = Wo_GetPostCommentWonders($_GET['post_id']);
                }
                $data['title'] = $wo['lang']['users_wondered_comment'];
            }
            elseif ($_GET['table'] == 'reply') {
                if (!empty($_GET['type']) && $_GET['type'] == 'wonder' && !empty($_GET['offset']) && is_numeric($_GET['offset']) && $_GET['offset'] > 0) {
                    $WonderedUsers = Wo_GetPostCommentReplyWonders($_GET['post_id'],20,Wo_Secure($_GET['offset']));
                }
                else{
                    $WonderedUsers = Wo_GetPostCommentReplyWonders($_GET['post_id']);
                }
                $data['title'] = $wo['lang']['users_wondered_comment'];
            }
            if (count($WonderedUsers) > 0) {
                $post_info = array();

                if ($_GET['table'] == 'post') {
                    $post_info = $db->where('id',Wo_Secure($_GET['post_id']))->getOne(T_POSTS);
                }
                elseif ($_GET['table'] == 'comment') {
                    $comment = $db->where('id',Wo_Secure($_GET['post_id']))->getOne(T_COMMENTS);
                    if (!empty($comment->post_id)) {
                        $post_info = $db->where('id',$comment->post_id)->getOne(T_POSTS);
                    }
                }
                elseif ($_GET['table'] == 'reply') {
                    $comment = $db->where('id',Wo_Secure($_GET['post_id']))->getOne(T_COMMENTS_REPLIES);
                    if (!empty($comment->comment_id)) {
                        $comment = $db->where('id',$comment->comment_id)->getOne(T_COMMENTS);
                        if (!empty($comment->post_id)) {
                            $post_info = $db->where('id',$comment->post_id)->getOne(T_POSTS);
                        }
                    }
                }

                foreach ($WonderedUsers as $wo['WondredLikedusers']) {
                    $wo['WondredLikedusers']['page_info'] = array();
                    if (!empty($post_info) && !empty($post_info->page_id)) {
                        $wo['WondredLikedusers']['page_info'] = Wo_PageData($post_info->page_id);
                    }
                    
                    if (!empty($wo['WondredLikedusers']['page_info']) && !empty($post_info) && $post_info->page_id > 0 && $wo['WondredLikedusers']['page_info']['user_id'] == $wo['WondredLikedusers']['user_id']) {
                        $data['html'] .= Wo_LoadPage('story/page-post-likes');
                    }
                    else{
                        $data['html'] .= Wo_LoadPage('story/post-likes-wonders');
                    }
                }
            } else {
                $data['message'] = ($config['second_post_button'] == 'dislike') ? $wo['lang']['no_dislikes'] : $wo['lang']['no_wonders'];
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'filter_posts') {
        if (!empty($_GET['filter_by']) && isset($_GET['id'])) {
            $html    = '';
            $options = array(
                'filter_by' => Wo_Secure($_GET['filter_by']),
                'placement' => 'multi_image_post'
            );
            if (!empty($_GET['type'])) {
                if ($_GET['type'] == 'page') {
                    $options['page_id'] = $_GET['id'];
                } else if ($_GET['type'] == 'profile') {
                    $options['publisher_id'] = $_GET['id'];
                } else if ($_GET['type'] == 'group') {
                    $options['group_id'] = $_GET['id'];
                } else if ($_GET['type'] == 'event') {
                    $options['event_id'] = $_GET['id'];
                }
            }
			if(!empty($_SESSION['rpcat'])){
				$options['postCategory'] = $_SESSION['rpcat'];
			}
            $stories = Wo_GetPosts($options);
            if (count($stories) > 0) {
                foreach ($stories as $wo['story']) {
                    $html .= Wo_LoadPage('story/content');
                }
            } else {
                $html .= Wo_LoadPage('story/filter-no-stories-found');
            }
            $loadMoreText = '<i class="fa fa-chevron-circle-down progress-icon" data-icon="chevron-circle-down"></i> ' . $wo['lang']['load_more_posts'];
            if (empty($stories)) {
                $loadMoreText = $wo['lang']['no_more_posts'];
            }
            $data = array(
                'status' => 200,
                'html' => $html,
                'text' => $loadMoreText
            );
        }
        echo $html;
        exit();
    }
    if ($s == 'add-video-view' && isset($_GET['post_id']) && is_numeric($_GET['post_id'])) {
        $post_id    = $_GET['post_id'];
        $data       = array(
            'status' => 300
        );
        $post_views = Wo_AddPostVideoView($post_id);
        if ($post_views && is_numeric($post_views)) {
            $data['status'] = 200;
            $data['views']  = $post_views;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'hide_post' && isset($_GET['post']) && is_numeric($_GET['post'])) {
        $data    = array(
            'status' => 304
        );
        $post_id = Wo_Secure($_GET['post']);
        if (Wo_HidePost($post_id)) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'share-post' && isset($_GET['id']) && is_numeric($_GET['id']) && isset($_GET['usr']) && is_numeric($_GET['usr'])) {
        $data    = array(
            'status' => 304
        );
        $post_id = Wo_Secure($_GET['id']);
        $owner   = Wo_Secure($_GET['usr']);
        if (Wo_SharePost($post_id, $owner)) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
}
