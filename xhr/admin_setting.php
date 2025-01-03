<?php

use Aws\S3\S3Client;

if ($f == 'admin_setting' and (Wo_IsAdmin() || Wo_IsModerator())) {

    if ($s == 'search_in_pages') {
        $keyword = Wo_Secure($_POST['keyword']);
        $html = '';

        $files = scandir('./admin-panel/pages');
        $not_allowed_files = array('edit-custom-page', 'edit-lang', 'edit-movie', 'edit-profile-field', 'edit-terms-pages');
        foreach ($files as $key => $file) {
            if (file_exists('./admin-panel/pages/' . $file . '/content.phtml') && !in_array($file, $not_allowed_files)) {

                $string = file_get_contents('./admin-panel/pages/' . $file . '/content.phtml');
                preg_match_all("@(?s)<h2([^<]*)>([^<]*)<\/h2>@", $string, $matches1);

                if (!empty($matches1) && !empty($matches1[2])) {
                    foreach ($matches1[2] as $key => $title) {
                        if (strpos(strtolower($title), strtolower($keyword)) !== false) {
                            $page_title = '';
                            preg_match_all("@(?s)<h2([^<]*)>([^<]*)<\/h2>@", $string, $matches3);
                            if (!empty($matches3) && !empty($matches3[2])) {
                                foreach ($matches3[2] as $key => $title2) {
                                    $page_title = $title2;
                                    break;
                                }
                            }
                            $html .= '<a href="' . Wo_LoadAdminLinkSettings($file) . '?highlight=' . $keyword . '"><div  style="padding: 5px 2px;">' . $page_title . '</div><div><small style="color: #333;">' . $title . '</small></div></a>';
                            break;
                        }
                    }
                }

                preg_match_all("@(?s)<label([^<]*)>([^<]*)<\/label>@", $string, $matches2);
                if (!empty($matches2) && !empty($matches2[2])) {
                    foreach ($matches2[2] as $key => $lable) {
                        if (strpos(strtolower($lable), strtolower($keyword)) !== false) {
                            $page_title = '';
                            preg_match_all("@(?s)<h2([^<]*)>([^<]*)<\/h2>@", $string, $matches3);
                            if (!empty($matches3) && !empty($matches3[2])) {
                                foreach ($matches3[2] as $key => $title2) {
                                    $page_title = $title2;
                                    break;
                                }
                            }

                            $html .= '<a href="' . Wo_LoadAdminLinkSettings($file) . '?highlight=' . $keyword . '"><div  style="padding: 5px 2px;">' . $page_title . '</div><div><small style="color: #333;">' . $lable . '</small></div></a>';
                            break;
                        }
                    }
                }
            }
        }
        $data = array(
            'status' => 200,
            'html'   => $html
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }

    if ($s == 'delete_color') {
        if (!empty($_POST['id'])) {
            $id = Wo_Secure($_POST['id']);
            $color = $db->where('id', $id)->getOne(T_COLORS);
            if (!empty($color)) {
                $db->where('id', $id)->delete(T_COLORS);
                $photo_file = $color->image;
                if (file_exists($photo_file)) {
                    @unlink(trim($photo_file));
                } else if ($wo['config']['amazone_s3'] == 1 || $wo['config']['ftp_upload'] == 1) {
                    @Wo_DeleteFromToS3($photo_file);
                }
            }
        }
        $data = array(
            'status' => 200
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'add_new_image_post') {
        if (!empty($_POST['image_color']) && !empty($_FILES['image'])) {

            $fileInfo             = array(
                'file' => $_FILES["image"]["tmp_name"],
                'name' => $_FILES['image']['name'],
                'size' => $_FILES["image"]["size"],
                'type' => $_FILES["image"]["type"],
                'types' => 'jpeg,jpg,png,bmp,gif',
                'compress' => false
            );
            $media                = Wo_ShareFile($fileInfo);
            if (!empty($media['filename'])) {
                $db->insert(T_COLORS, array('text_color' => Wo_Secure($_POST['image_color']), 'image' => $media['filename'], 'time' => time()));
            }

            $data = array(
                'status' => 200
            );
        } else {
            if (!empty($_FILES["image"]["error"]) || !empty($_FILES["image"]["error"])) {
                $error = $error_icon . 'The file is too big, please increase your server upload limit in php.ini';
            } else {
                $error = $error_icon . $wo['lang']['please_check_details'];
            }
            $data = array(
                'status' => 400,
                'error' => $error
            );
        }


        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'add_new_color') {
        if (!empty($_POST['color_1']) && !empty($_POST['color_2']) && !empty($_POST['color_text'])) {
            $db->insert(T_COLORS, array('color_1' => Wo_Secure($_POST['color_1']), 'color_2' => Wo_Secure($_POST['color_2']), 'text_color' => Wo_Secure($_POST['color_text']), 'time' => time()));
        }
        $data = array(
            'status' => 200
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'remove_provider') {
        if (!empty($_POST['provider'])) {
            if (in_array($_POST['provider'], $wo['config']['providers_array'])) {
                foreach ($wo['config']['providers_array'] as $key => $provider) {
                    if ($provider == $_POST['provider']) {
                        unset($wo['config']['providers_array'][$key]);
                    }
                }
                $saveSetting = Wo_SaveConfig('providers_array', serialize($wo['config']['providers_array']));
            }
        }
        $data = array(
            'status' => 200
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'add_new_provider') {
        if (!empty($_POST['provider'])) {
            $wo['config']['providers_array'][] = Wo_Secure($_POST['provider']);
            $saveSetting = Wo_SaveConfig('providers_array', serialize($wo['config']['providers_array']));
        }
        $data = array(
            'status' => 200
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_lang_status') {
        $saveSetting = Wo_SaveConfig($_POST['name'], $_POST['value']);
        $data = array(
            'status' => 200
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'select_currency') {
        if (!empty($_POST['currency']) && in_array($_POST['currency'], $wo['config']['currency_array'])) {
            $currency = Wo_Secure($_POST['currency']);
            $saveSetting = Wo_SaveConfig('currency', $currency);
            $saveSetting = Wo_SaveConfig('ads_currency', $currency);
            if (in_array($_POST['currency'], $wo['stripe_currency'])) {
                $saveSetting = Wo_SaveConfig('stripe_currency', $currency);
            }
            if (in_array($_POST['currency'], $wo['paypal_currency'])) {
                $saveSetting = Wo_SaveConfig('paypal_currency', $currency);
            }
            if (in_array($_POST['currency'], $wo['2checkout_currency'])) {
                $saveSetting = Wo_SaveConfig('2checkout_currency', $currency);
            }
        }
        $data = array(
            'status' => 200
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'add_new_curreny') {
        if (!empty($_POST['currency']) && !empty($_POST['currency_symbol'])) {
            $wo['config']['currency_array'][] = Wo_Secure($_POST['currency']);
            $wo['config']['currency_symbol_array'][Wo_Secure($_POST['currency'])] = Wo_Secure($_POST['currency_symbol']);
            $saveSetting = Wo_SaveConfig('currency_array', serialize($wo['config']['currency_array']));
            $saveSetting = Wo_SaveConfig('currency_symbol_array', serialize($wo['config']['currency_symbol_array']));
        }
        $data = array(
            'status' => 200
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'edit_curreny') {
        if (!empty($_POST['currency']) && !empty($_POST['currency_symbol']) && in_array($_POST['currency_id'], array_keys($wo['config']['currency_array']))) {
            $wo['config']['currency_array'][$_POST['currency_id']] = Wo_Secure($_POST['currency']);
            $wo['config']['currency_symbol_array'][Wo_Secure($_POST['currency'])] = Wo_Secure($_POST['currency_symbol']);
            $saveSetting = Wo_SaveConfig('currency_array', serialize($wo['config']['currency_array']));
            $saveSetting = Wo_SaveConfig('currency_symbol_array', serialize($wo['config']['currency_symbol_array']));
        }
        $data = array(
            'status' => 200
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'remove__curreny') {
        if (!empty($_POST['currency'])) {
            if (in_array($_POST['currency'], $wo['config']['currency_array'])) {
                foreach ($wo['config']['currency_array'] as $key => $currency) {
                    if ($currency == $_POST['currency']) {
                        if (in_array($currency, array_keys($wo['config']['currency_symbol_array']))) {
                            unset($wo['config']['currency_symbol_array'][$currency]);
                        }
                        unset($wo['config']['currency_array'][$key]);
                    }
                }
                if ($wo['config']['currency'] == $_POST['currency']) {
                    if (!empty($wo['config']['currency_array'])) {
                        $saveSetting = Wo_SaveConfig('currency', reset($wo['config']['currency_array']));
                        $saveSetting = Wo_SaveConfig('ads_currency', reset($wo['config']['currency_array']));
                    }
                }
                $saveSetting = Wo_SaveConfig('currency_array', serialize($wo['config']['currency_array']));
                $saveSetting = Wo_SaveConfig('currency_symbol_array', serialize($wo['config']['currency_symbol_array']));
            }
        }
        $data = array(
            'status' => 200
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'approve_receipt') {
        if (!empty($_GET['receipt_id'])) {
            $id = Wo_Secure($_GET['receipt_id']);
            $receipt = $db->where('id', $id)->getOne('bank_receipts', array('*'));

            if ($receipt) {
                $updated = $db->where('id', $id)->update('bank_receipts', array('approved' => 1, 'approved_at' => time()));
                $updated = true;
                if ($updated === true) {
                    if ($receipt->mode == 'wallet') {
                        $amount = $receipt->price;
                        $result = mysqli_query($sqlConnect, "UPDATE " . T_USERS . " SET `wallet` = `wallet` + " . $amount . " WHERE `user_id` = '" . $receipt->user_id . "'");
                        if ($result) {
                            $create_payment_log = mysqli_query($sqlConnect, "INSERT INTO " . T_PAYMENT_TRANSACTIONS . " (`userid`, `kind`, `amount`, `notes`) VALUES ('" . $receipt->user_id . "', 'WALLET', '" . $amount . "', 'bank receipts')");
                        }
                        $notification_data_array = array(
                            'recipient_id' => $receipt->user_id,
                            'type' => 'admin_notification',
                            'url' => 'index.php?link1=wallet',
                            'text' => $wo['lang']['bank_pro'],
                            'type2' => 'no_name'
                        );
                        Wo_RegisterNotification($notification_data_array);
                    } elseif ($receipt->mode == 'donate') {
                        $fund = $db->where('id', $receipt->fund_id)->getOne(T_FUNDING);
                        if (!empty($fund)) {
                            $amount = $receipt->price;
                            $fund_id = $receipt->fund_id;


                            $notes = "Doanted to " . mb_substr($fund->title, 0, 100, "UTF-8");

                            $create_payment_log = mysqli_query($sqlConnect, "INSERT INTO " . T_PAYMENT_TRANSACTIONS . " (`userid`, `kind`, `amount`, `notes`) VALUES ({$receipt->user_id}, 'DONATE', {$amount}, '{$notes}')");

                            $admin_com = 0;
                            if (!empty($wo['config']['donate_percentage']) && is_numeric($wo['config']['donate_percentage']) && $wo['config']['donate_percentage'] > 0) {
                                $admin_com = ($wo['config']['donate_percentage'] * $amount) / 100;
                                $amount = $amount - $admin_com;
                            }
                            $user_data = Wo_UserData($fund->user_id);
                            $db->where('user_id', $fund->user_id)->update(T_USERS, array('balance' => $user_data['balance'] + $amount));
                            $fund_raise_id = $db->insert(T_FUNDING_RAISE, array(
                                'user_id' => $receipt->user_id,
                                'funding_id' => $fund_id,
                                'amount' => $amount,
                                'time' => time()
                            ));
                            $post_data = array(
                                'user_id' => $receipt->user_id,
                                'fund_raise_id' => $fund_raise_id,
                                'time' => time(),
                                'multi_image_post' => 0
                            );

                            $id = Wo_RegisterPost($post_data);

                            $notification_data_array = array(
                                'notifier_id'  => $receipt->user_id,
                                'recipient_id' => $fund->user_id,
                                'type' => 'fund_donate',
                                'url' => 'index.php?link1=show_fund&id=' . $fund->hashed_id
                            );
                            Wo_RegisterNotification($notification_data_array);

                            $notification_data_array = array(
                                'recipient_id' => $receipt->user_id,
                                'type' => 'admin_notification',
                                'url' => 'index.php?link1=show_fund&id=' . $fund->hashed_id,
                                'text' => $wo['lang']['bank_pro'],
                                'type2' => 'no_name'
                            );
                            Wo_RegisterNotification($notification_data_array);
                        }
                    } else {
                        $pro_type = $receipt->mode;
                        $update_array = array(
                            'is_pro' => 1,
                            'pro_time' => time(),
                            'pro_' => 1,
                            'pro_type' => $pro_type
                        );
                        if (in_array($pro_type, array_keys($wo['pro_packages_types'])) && $wo['pro_packages'][$wo['pro_packages_types'][$pro_type]]['verified_badge'] == 1) {
                            // $update_array['verified'] = 1;
                        }
                        $mysqli       = Wo_UpdateUserData($receipt->user_id, $update_array);

                        $user_data = Wo_UserData($receipt->user_id);

                        if (!empty($user_data['ref_user_id']) && $wo['config']['affiliate_type'] == 1 && $user_data['referrer'] == 0) {
                            $amount1 = $receipt->price;
                            $ref_user_id = $user_data['ref_user_id'];


                            if ($wo['config']['amount_percent_ref'] > 0) {
                                if (!empty($ref_user_id) && is_numeric($ref_user_id)) {
                                    $update_user    = Wo_UpdateUserData($user_data['user_id'], array(
                                        'referrer' => $ref_user_id,
                                        'src' => 'Referrer'
                                    ));
                                    $ref_amount     = ($wo['config']['amount_percent_ref'] * $amount1) / 100;
                                    $update_balance = Wo_UpdateBalance($ref_user_id, $ref_amount);
                                    unset($_SESSION['ref']);
                                }
                            } else if ($wo['config']['amount_ref'] > 0) {
                                if (!empty($ref_user_id) && is_numeric($ref_user_id)) {
                                    $update_user    = Wo_UpdateUserData($user_data['user_id'], array(
                                        'referrer' => $ref_user_id,
                                        'src' => 'Referrer'
                                    ));
                                    $update_balance = Wo_UpdateBalance($ref_user_id, $wo['config']['amount_ref']);
                                    unset($_SESSION['ref']);
                                }
                            }
                        }

                        $amount1 = $receipt->price;
                        $notes              = $wo['lang']['upgrade_to_pro'] . " " . $receipt->description . " : Bank";
                        $create_payment_log = mysqli_query($sqlConnect, "INSERT INTO " . T_PAYMENT_TRANSACTIONS . " (`userid`, `kind`, `amount`, `notes`) VALUES ({$wo['user']['user_id']}, 'PRO', {$amount1}, '{$notes}')");

                        $notification_data_array = array(
                            'recipient_id' => $receipt->user_id,
                            'type' => 'admin_notification',
                            'url' => 'index.php?link1=upgraded',
                            'text' => $wo['lang']['bank_pro'],
                            'type2' => 'no_name'
                        );
                        Wo_RegisterNotification($notification_data_array);
                    }
                    $data = array(
                        'status' => 200
                    );
                }
            }
            $data = array(
                'status' => 200,
                'data' => $receipt
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_receipt') {
        if (!empty($_GET['receipt_id'])) {
            $user_id = Wo_Secure($_GET['user_id']);
            $id = Wo_Secure($_GET['receipt_id']);
            $photo_file = Wo_Secure($_GET['receipt_file']);
            $receipt = $db->where('id', $id)->getOne('bank_receipts', array('*'));

            $notification_data_array = array(
                'recipient_id' => $receipt->user_id,
                'type' => 'admin_notification',
                'url' => 'index.php',
                'text' => $wo['lang']['bank_decline'],
                'type2' => 'no_name'
            );
            Wo_RegisterNotification($notification_data_array);

            $db->where('id', $id)->delete('bank_receipts');
            if (file_exists($photo_file)) {
                @unlink(trim($photo_file));
            } else if ($wo['config']['amazone_s3'] == 1 || $wo['config']['ftp_upload'] == 1) {
                @Wo_DeleteFromToS3($photo_file);
            }
            $data = array(
                'status' => 200
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }



    if ($s == 'delete_user_posts') {
        $data['status'] = 400;
        if (!empty($_GET['user_id'])) {
            Wo_RunInBackground(array(
                'status' => 200
            ));
            $user_id = Wo_Secure($_GET['user_id']);
            Wo_DeleteAllUserPosts($user_id);
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }

    // category
    if ($s == 'add_new_category') {
        $data['status'] = 400;
        $data['message'] = 'Please check your details';
        $types = array('page' => T_PAGES_CATEGORY, 'group' => T_GROUPS_CATEGORY, 'blog' => T_BLOGS_CATEGORY, 'product' => T_PRODUCTS_CATEGORY, 'job' => T_JOB_CATEGORY);
        if (!empty($_GET['type']) && in_array($_GET['type'], array_keys($types))) {
            $add = false;
            $insert_data = array();
            foreach (Wo_LangsNamesFromDB() as $key => $lang) {
                if (!empty($_POST[$lang])) {
                    $insert_data[$lang] = Wo_Secure($_POST[$lang]);
                    $add = true;
                }
            }
            if ($add == true && !empty($insert_data)) {
                $insert_data['type'] = 'category';
                $id = $db->insert(T_LANGS, $insert_data);
                $db->insert($types[$_GET['type']], array('lang_key' => $id));
                $db->where('id', $id)->update(T_LANGS, array('lang_key' => $id));
                $data = array('status' => 200);
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_category_langs' && !empty($_POST['lang_key'])) {
        $data['status'] = 400;
        $html = '';
        $langs = Wo_GetLangDetails($_POST['lang_key']);
        if (count($langs) > 0) {
            foreach ($langs as $key => $wo['langs']) {
                foreach ($wo['langs'] as $wo['key_'] => $wo['lang_vlaue']) {
                    $html .= Wo_LoadAdminPage('edit-lang/form-list');
                }
            }
            $data['status'] = 200;
            $data['html'] = $html;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_category' && !empty($_POST['lang_key'])) {
        $types = array('page' => T_PAGES_CATEGORY, 'group' => T_GROUPS_CATEGORY, 'blog' => T_BLOGS_CATEGORY, 'product' => T_PRODUCTS_CATEGORY, 'job' => T_JOB_CATEGORY);
        if (!empty($_GET['type']) && in_array($_GET['type'], array_keys($types))) {
            if ($_POST['lang_key'] != 'other' && $_POST['lang_key'] != 'all_') {
                $lang_key = Wo_Secure($_POST['lang_key']);
                $category = $db->where('lang_key', $lang_key)->getOne($types[$_GET['type']]);
                if (!empty($category)) {
                    $db->where('lang_key', $lang_key)->delete(T_LANGS);
                    $db->where('lang_key', $lang_key)->delete($types[$_GET['type']]);
                    if ($_GET['type'] == 'page') {
                        $db->where('page_category', $category->id)->update(T_PAGES, array('page_category' => 1));
                    }
                    if ($_GET['type'] == 'group') {
                        $db->where('category', $category->id)->update(T_GROUPS, array('category' => 1));
                    }
                    if ($_GET['type'] == 'blog') {
                        $db->where('category', $category->id)->update(T_BLOG, array('category' => 1));
                    }
                    if ($_GET['type'] == 'product') {
                        $db->where('category', $category->id)->update(T_PRODUCTS, array('category' => 0));
                    }
                    $data['status'] = 200;
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    // category
    // manage packages 
    if ($s == 'update_pro_member') {
        include_once('assets/includes/stripe_config.php');
        $data['status'] = 400;
        $types = array('star', 'hot', 'ultima', 'vip');
        $html = '';
        if (in_array($_POST['type'], $types)) {
            if (!is_numeric($_POST['price']) || $_POST['price'] < 0) {
                $data['message'] = 'The price must be numeric';
            } elseif (!is_numeric($_POST['pages_promotion']) || $_POST['pages_promotion'] < 0) {
                $data['message'] = 'pages promotion must be numeric';
            } elseif (!is_numeric($_POST['posts_promotion']) || $_POST['posts_promotion'] < 0) {
                $data['message'] = 'posts promotion must be numeric';
            } else {
                if($wo['pro_packages'][$_POST['type']]['price'])
                if (!empty($_FILES['icon'])) {
                    $fileInfo = array(
                        'file' => $_FILES["icon"]["tmp_name"],
                        'name' => $_FILES['icon']['name'],
                        'size' => $_FILES["icon"]["size"],
                        'type' => $_FILES["icon"]["type"],
                        'types' => 'jpeg,png,jpg,gif,svg',
                        'crop' => array(
                            'width' => 32,
                            'height' => 32
                        )
                    );
                    $media    = Wo_ShareFile($fileInfo);
                    if (!empty($media) && !empty($media['filename'])) {
                        $_POST['image'] = $media['filename'];
                    }
                }
                if (!empty($_POST['icon_to_use']) && $_POST['icon_to_use'] == 1) {
                    $link = substr($wo['pro_packages'][$_POST['type']]['image'], strpos($wo['pro_packages'][$_POST['type']]['image'], 'upload/'));
                    if (file_exists($link)) {
                        @unlink(trim($link));
                    } else if ($wo['config']['amazone_s3'] == 1 || $wo['config']['ftp_upload'] == 1) {
                        @Wo_DeleteFromToS3($link);
                    }
                    $_POST['image'] = '';
                }
                if ($_POST['time'] == 7) {
                    $type   = 'week';
                } else if ($_POST['time'] == 30) {
                    $type   = 'month';
                }  else if ($_POST['time'] == 365) {
                    $type   = 'year';
                }else{
                    $type   = 'day';
                } 
                if($wo['pro_packages'][$_POST['type']]['stripe_price_id'] == null && $wo['pro_packages'][$_POST['type']]['stripe_product_id'] == null){
                    $product = \Stripe\Product::create([
                        'name' => $_POST['name'],
                    ]);
                    $price = \Stripe\Price::create([
                        'product' => $product->id,
                        'unit_amount' => floatval($_POST['price']) * 100,
                        'currency' => $wo['config']['stripe_currency'], 
                        // 'currency' => 'inr', 
                        'recurring' => ['interval' => $type]
                    ]); 
                    $_POST['stripe_price_id'] = $price->id;
                    $_POST['stripe_product_id'] = $product->id;
                }else{
                    // if($_POST['price'] != $wo['pro_packages'][$_POST['type']]['price']){
                        $price = \Stripe\Price::create([
                            'product' => $wo['pro_packages'][$_POST['type']]['stripe_product_id'],
                            'unit_amount' => floatval($_POST['price']) * 100,
                            'currency' => $wo['config']['stripe_currency'], 
                            // 'currency' => 'inr', 
                            'recurring' => ['interval' => $type]
                        ]); 
                        $_POST['stripe_price_id'] = $price->id;
                        
                        $subscriptions = \Stripe\Subscription::all();
                        foreach ($subscriptions->data as $subscription) {  
                            if($subscription->items->data[0]->price->product == $wo['pro_packages'][$_POST['type']]['stripe_product_id']){
                                \Stripe\Subscription::update(
                                    $subscription->id,
                                    [
                                        'items' => [
                                            [
                                                'id' => $subscription->items->data[0]->id,
                                                'price' => $price->id, 
                                            ],
                                        ],
                                        'proration_behavior' => 'none'
                                    ]
                                ); 
                            }
                        }
                    // }
                }
                Wo_updateProInfo($_POST);
                if (!empty($_POST['name']) && $_POST['name'] != $wo['lang'][$_POST['type']]) {
                    $langs = Wo_GetLangDetails($_POST['type']);
                    if (count($langs) > 0) {
                        foreach ($langs as $key => $wo['langs']) {
                            foreach ($wo['langs'] as $wo['key_'] => $wo['lang_vlaue']) {
                                $html .= Wo_LoadAdminPage('edit-lang/form-list');
                            }
                        }
                    } else {
                        $html = "<h4>Keyword not found</h4>";
                    }
                }
                $data['status'] = 200;
                $data['html']   = $html;
                $data['type']   = $_POST['type'];
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_pro') {
        $types = array('star', 'hot', 'ultima', 'vip');
        $html = '';
        if (in_array($_POST['type'], $types)) {
            $wo['pro'] = Wo_GetProInfo($_POST['type']);
            $html .= Wo_LoadAdminPage('pro-settings/pro_form');
        }
        $data['status'] = 200;
        $data['html']   = $html;
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    // manage packages 
    if ($s == 'test_vision_api') {
        $data['status'] = 400;
        if (!empty($wo['config']['vision_api_key'])) {
            $image_file = Wo_GetMedia('upload/photos/d-avatar.jpg');
            $content = '{"requests": [{"image": {"source": {"imageUri": "' . $image_file . '"}},"features": [{"type": "SAFE_SEARCH_DETECTION","maxResults": 1},{"type": "WEB_DETECTION","maxResults": 2}]}]}';
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://vision.googleapis.com/v1/images:annotate?key=' . $wo['config']['vision_api_key']);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($content)));
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response  = curl_exec($ch);
                curl_close($ch);
                $new_data = json_decode($response);
                if (!empty($new_data->error)) {
                    $data['message'] = $data->error->message;
                }
                if (!empty($new_data->responses[0]->error)) {
                    $data['message'] = $new_data->responses[0]->error->message;
                } elseif ($new_data->responses[0]->safeSearchAnnotation->adult == 'LIKELY' || $new_data->responses[0]->safeSearchAnnotation->adult == 'VERY_LIKELY' || $new_data->responses[0]->safeSearchAnnotation->adult == 'UNKNOWN' || $new_data->responses[0]->safeSearchAnnotation->adult == 'VERY_UNLIKELY' || $new_data->responses[0]->safeSearchAnnotation->adult == 'UNLIKELY' || $new_data->responses[0]->safeSearchAnnotation->adult == 'POSSIBLE') {
                    $data['status']  = 200;
                    $data['message'] = 'Connection was successfully established!';
                } else {
                    $data['message'] = 'Something Wrong';
                }
            } catch (Exception $e) {
                $data['message'] = $e->getMessage();
            }
        } else {
            $data['message'] = 'vision api key can not be empty';
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'top_up_wallet') {
        if (!empty($_POST['amount'])) {
            $update = Wo_UpdateUserData($wo['user']['user_id'], array(
                'wallet' => $_POST['amount']
            ));
            if ($update) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'add_followers') {
        $data           = array();
        $data['status'] = 200;
        $data['error']  = false;
        if (empty($_POST['followers']) || empty($_POST['user_id'])) {
            $data['status'] = 500;
            $data['error']  = $wo['lang']['please_check_details'];
        }
        if (!is_numeric($_POST['followers']) || !is_numeric($_POST['user_id'])) {
            $data['status'] = 500;
            $data['error']  = 'Numbers only are allowed';
        }
        if ($_POST['followers'] < 0 || $_POST['user_id'] < 0) {
            $data['status'] = 500;
            $data['error']  = 'Integer numbers only are allowed';
        }
        $userData = Wo_UserData($_POST['user_id']);
        if (empty($data['error']) && $data['status'] != 500) {
            $followers = floor($_POST['followers']);
            $usersCount = $db->getValue(T_USERS, 'COUNT(*)');
            if ($followers > $usersCount) {
                $data['status'] = 500;
                $data['error']  = "Followers can't be more than your users: $usersCount";
            }
            if ($db->getValue(T_USERS, "MAX(user_id)") <= $userData['last_follow_id']) {
                $data['status'] = 500;
                $data['error']  = "No more users left to follow, all the users are following {$userData['name']}.";
            }
        }
        if (empty($data['error']) && $data['error'] != 500) {
            $users_id = array();

            $users = $db->where('user_id', $userData['last_follow_id'], ">")->get(T_USERS, $followers, 'user_id');
            foreach ($users as $key => $i) {
                $users_id[] = $i->user_id;
            }
            if (empty($data['error']) && $data['status'] != 500 && !empty($users_id)) {
                Wo_RunInBackground(array(
                    'status' => 200
                ));
                $followed  = Wo_RegisterFollow($_POST['user_id'], $users_id);
                $user_data = Wo_UpdateUserDetails($_POST['user_id'], false, false, true);
                $update_user = $db->where('user_id', $_POST['user_id'])->update(T_USERS, array("last_follow_id" => Wo_Secure(end($users_id))));
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_custom_code') {
        $data    = array(
            'status' => 400
        );
        $theme   = $wo['config']['theme'];
        $request = (isset($_POST['cheader']) && isset($_POST['cfooter']) && isset($_POST['css']));
        if ($request === true) {
            if (is_writable("themes/$theme/custom")) {
                $up_data        = array(
                    $_POST['cheader'],
                    $_POST['cfooter'],
                    $_POST['css']
                );
                $save           = Wo_CustomCode('p', $up_data);
                $data['status'] = 200;
            } else {
                $data['status'] = 500;
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'verfiy_apps') {
        $arrContextOptions             = array(
            "ssl" => array(
                "verify_peer" => false,
                "verify_peer_name" => false
            )
        );
        $data['android_status']        = 0;
        $data['windows_status']        = 0;
        $data['android_native_status'] = 0;
        if (!empty($_POST['android_purchase_code'])) {
            $android_code = Wo_Secure($_POST['android_purchase_code']);
            $file         = file_get_contents("http://www.wowonder.com/access_token.php?code={$android_code}&type=android", false, stream_context_create($arrContextOptions));
            $check        = json_decode($file, true);
            if (!empty($check['status'])) {
                if ($check['status'] == 'SUCCESS') {
                    $update                 = Wo_SaveConfig('footer_background', '#aaa');
                    $data['android_status'] = 200;
                } else {
                    $data['android_status'] = 400;
                    $data['android_text']   = $check['ERROR_NAME'];
                }
            }
        }
        if (!empty($_POST['android_native_purchase_code'])) {
            $android_code = Wo_Secure($_POST['android_native_purchase_code']);
            $file         = file_get_contents("http://www.wowonder.com/access_token.php?code={$android_code}&type=android", false, stream_context_create($arrContextOptions));
            $check        = json_decode($file, true);
            if (!empty($check['status'])) {
                if ($check['status'] == 'SUCCESS') {
                    $update                        = Wo_SaveConfig('footer_background_n', '#aaa');
                    $data['android_native_status'] = 200;
                } else {
                    $data['android_native_status'] = 400;
                    $data['android_text']          = $check['ERROR_NAME'];
                }
            }
        }
        if (!empty($_POST['windows_purchase_code'])) {
            $windows_code = Wo_Secure($_POST['windows_purchase_code']);
            $file         = file_get_contents("http://www.wowonder.com/access_token.php?code={$windows_code}&type=windows_desktop", false, stream_context_create($arrContextOptions));
            $check        = json_decode($file, true);
            if (!empty($check['status'])) {
                if ($check['status'] == 'SUCCESS') {
                    $update                 = Wo_SaveConfig('footer_text_color', '#ddd');
                    $data['windows_status'] = 200;
                } else {
                    $data['windows_status'] = 400;
                    $data['windows_text']   = $check['ERROR_NAME'];
                }
            }
        }
        if (!empty($_POST['ios_purchase_code'])) {
            $windows_code = Wo_Secure($_POST['ios_purchase_code']);
            $file         = file_get_contents("http://www.wowonder.com/access_token.php?code={$windows_code}&type=ios", false, stream_context_create($arrContextOptions));
            $check        = json_decode($file, true);
            if (!empty($check['status'])) {
                if ($check['status'] == 'SUCCESS') {
                    $update             = Wo_SaveConfig('footer_background_2', '#aaa');
                    $data['ios_status'] = 200;
                } else {
                    $data['ios_status'] = 400;
                    $data['ios_text']   = $check['ERROR_NAME'];
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_lang_key') {
        if (Wo_CheckSession($hash_id) === true) {
            $array_langs = array();
            $lang_key    = Wo_Secure($_POST['id_of_key']);
            $langs       = Wo_LangsNamesFromDB();
            foreach ($_POST as $key => $value) {
                if (in_array($key, $langs)) {
                    $key   = Wo_Secure($key);
                    $value = Wo_Secure($value);
                    $query = mysqli_query($sqlConnect, "UPDATE " . T_LANGS . " SET `{$key}` = '{$value}' WHERE `lang_key` = '{$lang_key}'");
                    if ($query) {
                        $data['status'] = 200;
                    }
                }
            }
            $image = '';
            if (!empty($_FILES['icon'])) {
                $fileInfo = array(
                    'file' => $_FILES["icon"]["tmp_name"],
                    'name' => $_FILES['icon']['name'],
                    'size' => $_FILES["icon"]["size"],
                    'type' => $_FILES["icon"]["type"],
                    'types' => 'jpeg,png,jpg,gif,svg',
                    'crop' => array(
                        'width' => 100,
                        'height' => 100
                    )
                );
                $media    = Wo_ShareFile($fileInfo);
                if (!empty($media) && !empty($media['filename'])) {
                    $image = $media['filename'];
                }
                if (!empty($image)) {
                    $gender = $db->where('gender_id', $lang_key)->getOne(T_GENDER);
                    if (!empty($gender)) {
                        $link = $gender->image;
                        if (file_exists($link)) {
                            @unlink(trim($link));
                        } else if ($wo['config']['amazone_s3'] == 1 || $wo['config']['ftp_upload'] == 1) {
                            @Wo_DeleteFromToS3($link);
                        }
                        $db->where('gender_id', $lang_key)->update(T_GENDER, array('image' => $image));
                    } else {
                        $db->insert(T_GENDER, array('gender_id' => $lang_key, 'image' => $image));
                    }
                }
            }
            if (!empty($_POST['icon_to_use']) && $_POST['icon_to_use'] == 1) {
                $gender = $db->where('gender_id', $lang_key)->getOne(T_GENDER);
                if (!empty($gender)) {
                    $link = $gender->image;
                    if (file_exists($link)) {
                        @unlink(trim($link));
                    } else if ($wo['config']['amazone_s3'] == 1 || $wo['config']['ftp_upload'] == 1) {
                        @Wo_DeleteFromToS3($link);
                    }
                    $db->where('gender_id', $lang_key)->delete(T_GENDER);
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'add_new_gender') {
        $image = '';
        if (!empty($_FILES['icon'])) {
            $fileInfo = array(
                'file' => $_FILES["icon"]["tmp_name"],
                'name' => $_FILES['icon']['name'],
                'size' => $_FILES["icon"]["size"],
                'type' => $_FILES["icon"]["type"],
                'types' => 'jpeg,png,jpg,gif,svg',
                'crop' => array(
                    'width' => 100,
                    'height' => 100
                )
            );
            $media    = Wo_ShareFile($fileInfo);
            if (!empty($media) && !empty($media['filename'])) {
                $image = $media['filename'];
            }
        }
        $insert_data = array();
        $insert_data['type'] = 'gender';
        $add = false;
        foreach (Wo_LangsNamesFromDB() as $wo['key_']) {
            if (!empty($_POST[$wo['key_']])) {
                $insert_data[$wo['key_']] = Wo_Secure($_POST[$wo['key_']]);
                $add = true;
            }
        }
        if ($add == true) {
            $id = $db->insert(T_LANGS, $insert_data);
            $db->where('id', $id)->update(T_LANGS, array('lang_key' => $id));
            if (!empty($image)) {
                $db->insert(T_GENDER, array('gender_id' => $id, 'image' => $image));
            }
            $data['status'] = 200;
        } else {
            $data['status'] = 400;
            $data['message'] = $wo['lang']['please_check_details'];
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_gender') {
        if (!empty($_GET['key']) && in_array($_GET['key'], array_keys($wo['genders']))) {
            $db->where('lang_key', Wo_Secure($_GET['key']))->delete(T_LANGS);
            $gender = $db->where('gender_id', Wo_Secure($_GET['key']))->getOne(T_GENDER);
            if (!empty($gender)) {
                $link = $gender->image;
                if (file_exists($link)) {
                    @unlink(trim($link));
                } else if ($wo['config']['amazone_s3'] == 1 || $wo['config']['ftp_upload'] == 1) {
                    @Wo_DeleteFromToS3($link);
                }
                $db->where('gender_id', Wo_Secure($_GET['key']))->delete(T_GENDER);
            }
        }
        $data['status'] = 200;
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'add_new_lang') {
        if (Wo_CheckSession($hash_id) === true) {
            $mysqli = Wo_LangsNamesFromDB();
            if (in_array($_POST['lang'], $mysqli)) {
                $data['status']  = 400;
                $data['message'] = 'This lang is already used.';
            } else {
                $lang_name = Wo_Secure($_POST['lang']);
                $lang_name = strtolower($lang_name);
                $query     = mysqli_query($sqlConnect, "ALTER TABLE " . T_LANGS . " ADD `$lang_name` TEXT CHARACTER SET utf8 COLLATE utf8_unicode_ci NULL DEFAULT NULL;");
                if ($query) {
                    $content = file_get_contents('assets/languages/extra/english.php');
                    $fp      = fopen("assets/languages/extra/$lang_name.php", "wb");
                    fwrite($fp, $content);
                    fclose($fp);
                    $english = Wo_LangsFromDB('english');
                    foreach ($english as $key => $lang) {
                        $lang  = Wo_Secure($lang);
                        $query = mysqli_query($sqlConnect, "UPDATE " . T_LANGS . " SET `{$lang_name}` = '$lang' WHERE `lang_key` = '{$key}'");
                    }
                    $data['status'] = 200;
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'add_new_lang_key') {
        if (Wo_CheckSession($hash_id) === true) {
            if (!empty($_POST['lang_key'])) {
                $lang_key  = Wo_Secure($_POST['lang_key']);
                $mysqli    = mysqli_query($sqlConnect, "SELECT COUNT(id) as count FROM " . T_LANGS . " WHERE `lang_key` = '$lang_key'");
                $sql_fetch = mysqli_fetch_assoc($mysqli);
                if ($sql_fetch['count'] == 0) {
                    $mysqli = mysqli_query($sqlConnect, "INSERT INTO " . T_LANGS . " (`lang_key`) VALUE ('$lang_key')");
                    if ($mysqli) {
                        $data['status'] = 200;
                        $data['url']    = Wo_LoadAdminLinkSettings('manage-languages');
                    }
                } else {
                    $data['status']  = 400;
                    $data['message'] = 'This key is already used, please use other one.';
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_lang') {
        if (Wo_CheckMainSession($hash_id) === true) {
            $mysqli = Wo_LangsNamesFromDB();
            if (in_array($_GET['id'], $mysqli)) {
                $lang_name = Wo_Secure($_GET['id']);
                $query     = mysqli_query($sqlConnect, "ALTER TABLE " . T_LANGS . " DROP COLUMN `$lang_name`");
                if ($query) {
                    unlink("assets/languages/extra/$lang_name.php");
                    $data['status'] = 200;
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'reset_windows_app_keys') {
        $app_key    = sha1(rand(111111111, 999999999)) . '-' . md5(microtime()) . '-' . rand(11111111, 99999999);
        $data_array = array(
            'widnows_app_api_key' => $app_key
        );
        foreach ($data_array as $key => $value) {
            $saveSetting = Wo_SaveConfig($key, $value);
        }
        if ($saveSetting === true) {
            $data['status']  = 200;
            $data['app_key'] = $app_key;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'cancel_pro') {
        $cancel = Wo_DeleteProMemebership();
        if ($cancel) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_ref_system') {
        $saveSetting = false;
        if (!empty($_POST['affiliate_type'])) {
            $_POST['affiliate_type'] = 1;
        } else {
            $_POST['affiliate_type'] = 0;
        }
        foreach ($_POST as $key => $value) {
            if ($key != 'hash_id') {
                $saveSetting = Wo_SaveConfig($key, $value);
            }
        }
        if ($saveSetting === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'mark_as_paid') {
        if (!empty($_GET['id']) && Wo_CheckSession($hash_id)) {
            $get_payment_info = Wo_GetPaymentHistory($_GET['id']);
            if (!empty($get_payment_info)) {
                $id     = $get_payment_info['id'];
                $update = mysqli_query($sqlConnect, "UPDATE " . T_A_REQUESTS . " SET status = '1' WHERE id = {$id}");
                if ($update) {
                    $body              = Wo_LoadPage('emails/payment-sent');
                    $body              = str_replace('{name}', $get_payment_info['user']['name'], $body);
                    $body              = str_replace('{amount}', $get_payment_info['amount'], $body);
                    $body              = str_replace('{site_name}', $config['siteName'], $body);
                    $send_message_data = array(
                        'from_email' => $wo['config']['siteEmail'],
                        'from_name' => $wo['config']['siteName'],
                        'to_email' => $get_payment_info['user']['email'],
                        'to_name' => $get_payment_info['user']['name'],
                        'subject' => 'New Payment | ' . $wo['config']['siteName'],
                        'charSet' => 'utf-8',
                        'message_body' => $body,
                        'is_html' => true
                    );
                    $send_message      = Wo_SendMessage($send_message_data);
                    if ($send_message) {
                        $data['status'] = 200;
                    }
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'decline_payment') {
        if (!empty($_GET['id']) && Wo_CheckSession($hash_id)) {
            $get_payment_info = Wo_GetPaymentHistory($_GET['id']);
            if (!empty($get_payment_info)) {
                $id     = $get_payment_info['id'];
                $update = mysqli_query($sqlConnect, "UPDATE " . T_A_REQUESTS . " SET status = '2' WHERE id = {$id}");
                if ($update) {
                    $body              = Wo_LoadPage('emails/payment-declined');
                    $body              = str_replace('{name}', $get_payment_info['user']['name'], $body);
                    $body              = str_replace('{amount}', $get_payment_info['amount'], $body);
                    $body              = str_replace('{site_name}', $config['siteName'], $body);
                    $send_message_data = array(
                        'from_email' => $wo['config']['siteEmail'],
                        'from_name' => $wo['config']['siteName'],
                        'to_email' => $get_payment_info['user']['email'],
                        'to_name' => $get_payment_info['user']['name'],
                        'subject' => 'Payment Declined | ' . $wo['config']['siteName'],
                        'charSet' => 'utf-8',
                        'message_body' => $body,
                        'is_html' => true
                    );
                    $send_message      = Wo_SendMessage($send_message_data);
                    if ($send_message) {
                        $data['status'] = 200;
                    }
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'add_new_page') {
        if (Wo_CheckSession($hash_id) === true && !empty($_POST['page_name']) && !empty($_POST['page_content']) && !empty($_POST['page_title'])) {
            $page_name    = Wo_Secure($_POST['page_name']);
            $page_content = Wo_Secure(str_replace(array("\r", "\n"), "", $_POST['page_content']));
            $page_title   = Wo_Secure($_POST['page_title']);
            $page_type    = 0;
            if (!empty($_POST['page_type'])) {
                $page_type = 1;
            }
            if (!preg_match('/^[\w]+$/', $page_name)) {
                $data = array(
                    'status' => 400,
                    'message' => 'Invalid page name characters'
                );
                header("Content-type: application/json");
                echo json_encode($data);
                exit();
            }
            $data_ = array(
                'page_name' => $page_name,
                'page_content' => $page_content,
                'page_title' => $page_title,
                'page_type' => $page_type
            );
            $add   = Wo_RegisterNewPage($data_);
            if ($add) {
                $data['status'] = 200;
            }
        } else {
            $data = array(
                'status' => 400,
                'message' => 'Please fill all the required fields'
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'edit_page') {
        if (Wo_CheckSession($hash_id) === true && !empty($_POST['page_id']) && !empty($_POST['page_name']) && !empty($_POST['page_content']) && !empty($_POST['page_title'])) {
            $page_name    = $_POST['page_name'];
            $page_content = $_POST['page_content'];
            $page_title   = $_POST['page_title'];
            $page_type    = 0;
            if (!empty($_POST['page_type'])) {
                $page_type = 1;
            }
            if (!preg_match('/^[\w]+$/', $page_name)) {
                $data = array(
                    'status' => 400,
                    'message' => 'Invalid page name characters'
                );
                header("Content-type: application/json");
                echo json_encode($data);
                exit();
            }
            $data_ = array(
                'page_name' => $page_name,
                'page_content' => $page_content,
                'page_title' => $page_title,
                'page_type' => $page_type
            );
            $add   = Wo_UpdateCustomPageData($_POST['page_id'], $data_);
            if ($add) {
                $data['status'] = 200;
            }
        } else {
            $data = array(
                'status' => 400,
                'message' => 'Please fill all the required fields'
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'add_new_discount_code') { 
        if (Wo_CheckSession($hash_id) === true && !empty($_POST['code']) && !empty($_POST['max_uses_per_user']) && !empty($_POST['max_uses']) && !empty($_POST['valid_from']) && !empty($_POST['valid_to'])) {
            $query_one     = "SELECT * FROM " . T_DISCOUNT_CODES . " WHERE `code` = '{$_POST['code']}'";
            $sql_query_one = mysqli_query($sqlConnect, $query_one);
            $fetched_data  = mysqli_fetch_assoc($sql_query_one);
            if($fetched_data==null){
                $code    = Wo_Secure($_POST['code']);
                $type    = Wo_Secure($_POST['type']);
                $price    = Wo_Secure($_POST['price']);
                $max_uses_per_user    = Wo_Secure($_POST['max_uses_per_user']);
                $max_uses    = Wo_Secure($_POST['max_uses']);
                $valid_from = Wo_Secure(date("Y-m-d", strtotime($_POST['valid_from']))) .' 00:00:00';
                $valid_to = Wo_Secure(date("Y-m-d", strtotime($_POST['valid_to']))) .' 23:59:59';
                $data_ = array(
                    'code' => "'" . $code . "'",
                    'type' => "'" . $type . "'",
                    'price' => $price,
                    'max_uses_per_user' => $max_uses_per_user,
                    'max_uses' => $max_uses,
                    'valid_from' => "'" . $valid_from . "'",
                    'valid_to' => "'" . $valid_to . "'"
                );
                $add   = Wo_RegisterNewDiscountCode($data_);
                if ($add) {
                    $data['status'] = 200;
                }
            }else{
                $data = array(
                    'status' => 400,
                    'message' => 'Discount Code already exists!'
                ); 
            }
        } else {
            $data = array(
                'status' => 400,
                'message' => 'Please fill all the required fields'
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'edit_discount_code') {
        if (Wo_CheckSession($hash_id) === true && !empty($_POST['code']) && !empty($_POST['max_uses_per_user']) && !empty($_POST['max_uses']) && !empty($_POST['valid_from']) && !empty($_POST['valid_to'])) { 
            $query_one     = "SELECT * FROM " . T_DISCOUNT_CODES . " WHERE `code` = '{$_POST['code']}' AND `id` != '{$_POST['discount_code_id']}'";
            $sql_query_one = mysqli_query($sqlConnect, $query_one);
            $fetched_data  = mysqli_fetch_assoc($sql_query_one);
            if($fetched_data == null){
                $code    = Wo_Secure($_POST['code']);
                $type    = Wo_Secure($_POST['type']);
                $price    = Wo_Secure($_POST['price']);
                $max_uses_per_user    = Wo_Secure($_POST['max_uses_per_user']);
                $max_uses    = Wo_Secure($_POST['max_uses']);
                $valid_from = Wo_Secure(date("Y-m-d", strtotime($_POST['valid_from']))) .' 00:00:00';
                $valid_to = Wo_Secure(date("Y-m-d", strtotime($_POST['valid_to']))) .' 23:59:59';
                $data_ = array(
                    'code' => "'" . $code . "'",
                    'type' => "'" . $type . "'",
                    'price' => $price,
                    'max_uses_per_user' => $max_uses_per_user,
                    'max_uses' => $max_uses,
                    'valid_from' => "'" . $valid_from . "'",
                    'valid_to' => "'" . $valid_to . "'"
                );
                $add   = Wo_UpdateDiscountCodeData($_POST['discount_code_id'], $data_);
                if ($add) {
                    $data['status'] = 200;
                }
            }else{
                $data = array(
                    'status' => 400,
                    'message' => 'Discount Code already exists!'
                ); 
            }
        } else {
            $data = array(
                'status' => 400,
                'message' => 'Please fill all the required fields'
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'add_new_field') {
        if (Wo_CheckSession($hash_id) === true && !empty($_POST['name']) && !empty($_POST['type']) && !empty($_POST['description'])) {
            $type              = Wo_Secure($_POST['type']);
            $name              = Wo_Secure($_POST['name']);
            $description       = Wo_Secure($_POST['description']);
            $registration_page = 0;
            if (!empty($_POST['registration_page'])) {
                $registration_page = 1;
            }
            $profile_page = 0;
            if (!empty($_POST['profile_page'])) {
                $profile_page = 1;
            }
            $length = 32;
            if (!empty($_POST['length'])) {
                if (is_numeric($_POST['length']) && $_POST['length'] < 1001) {
                    $length = Wo_Secure($_POST['length']);
                }
            }
            $placement_array = array(
                'profile',
                'general',
                'social',
                'none'
            );
            $placement       = 'profile';
            if (!empty($_POST['placement'])) {
                if (in_array($_POST['placement'], $placement_array)) {
                    $placement = Wo_Secure($_POST['placement']);
                }
            }
            $data_ = array(
                'name' => $name,
                'description' => $description,
                'length' => $length,
                'placement' => $placement,
                'registration_page' => $registration_page,
                'profile_page' => $profile_page,
                'active' => 1
            );
            if (!empty($_POST['options'])) {
                $options              = @explode("\n", $_POST['options']);
                $type                 = Wo_Secure(implode($options, ','));
                $data_['select_type'] = 'yes';
            }
            $data_['type'] = $type;
            $add           = Wo_RegisterNewField($data_);
            if ($add) {
                $data['status'] = 200;
            }
        } else {
            $data = array(
                'status' => 400,
                'message' => 'Please fill all the required fields'
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'edit_field') {
        if (Wo_CheckSession($hash_id) === true && !empty($_POST['name']) && !empty($_POST['description']) && !empty($_POST['id'])) {
            $name              = Wo_Secure($_POST['name']);
            $description       = Wo_Secure($_POST['description']);
            $registration_page = 0;
            if (!empty($_POST['registration_page'])) {
                $registration_page = 1;
            }
            $profile_page = 0;
            if (!empty($_POST['profile_page'])) {
                $profile_page = 1;
            }
            $active = 0;
            if (!empty($_POST['active'])) {
                $active = 1;
            }
            $length = 32;
            if (!empty($_POST['length'])) {
                if (is_numeric($_POST['length'])) {
                    $length = Wo_Secure($_POST['length']);
                }
            }
            $placement_array = array(
                'profile',
                'general',
                'social',
                'none'
            );
            $placement       = 'profile';
            if (!empty($_POST['placement'])) {
                if (in_array($_POST['placement'], $placement_array)) {
                    $placement = Wo_Secure($_POST['placement']);
                }
            }
            $data_ = array(
                'name' => $name,
                'description' => $description,
                'length' => $length,
                'placement' => $placement,
                'registration_page' => $registration_page,
                'profile_page' => $profile_page,
                'active' => $active
            );
            if (!empty($_POST['options'])) {
                $options              = @explode("\n", $_POST['options']);
                $data_['type']        = implode($options, ',');
                $data_['select_type'] = 'yes';
            }
            $add = Wo_UpdateField($_POST['id'], $data_);
            if ($add) {
                $data['status'] = 200;
            }
        } else {
            $data = array(
                'status' => 400,
                'message' => 'Please fill all the required fields'
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_field') {
        if (Wo_CheckMainSession($hash_id) === true && !empty($_GET['id'])) {
            $delete = Wo_DeleteField($_GET['id']);
            if ($delete) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_page') {
        if (Wo_CheckMainSession($hash_id) === true && !empty($_GET['id'])) {
            $delete = Wo_DeleteCustomPage($_GET['id']);
            if ($delete) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_discount_code') { 
        if (Wo_CheckMainSession($hash_id) === true && !empty($_GET['id'])) {
            $delete = Wo_DeleteDiscountCode($_GET['id']);
            if ($delete) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'new_backup') {
        $b = Wo_Backup($sql_db_host, $sql_db_user, $sql_db_pass, $sql_db_name);
        if ($b) {
            $data['status'] = 200;
            $data['date']   = date('d-m-Y');
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'test_paypal') {
        $PayPal               = Wo_PayPal();
        $data['status']       = 200;
        $data['respond_code'] = $PayPal;
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_general_setting' && Wo_CheckSession($hash_id) === true) {
        $saveSetting         = false;
        $delete_follow_table = 0;
        foreach ($_POST as $key => $value) {
            if (isset($wo['config'][$key]) || $key == 'googleAnalytics_en') {
                if ($key == 'googleAnalytics_en') {
                    $key   = 'googleAnalytics';
                    $value = base64_decode($value);
                }
                if ($key == 'connectivitySystem') {
                    if (isset($_POST['connectivitySystem'])) {
                        if ($config['connectivitySystem'] == 1 && $_POST['connectivitySystem'] != 1) {
                            $delete_follow_table = 1;
                        } else if ($config['connectivitySystem'] != 1 && $_POST['connectivitySystem'] == 1) {
                            $delete_follow_table = 1;
                        }
                    }
                }
                if ($key == 'ftp_upload') {
                    if ($value == 1) {
                        if ($wo['config']['amazone_s3'] == 1) {
                            $saveSetting = Wo_SaveConfig('amazone_s3', 0);
                        }
                        if ($wo['config']['spaces'] == 1) {
                            $saveSetting = Wo_SaveConfig('spaces', 0);
                        }
                    }
                }
                if ($key == 'amazone_s3') {
                    if ($value == 1) {
                        if ($wo['config']['ftp_upload'] == 1) {
                            $saveSetting = Wo_SaveConfig('ftp_upload', 0);
                        }
                        if ($wo['config']['spaces'] == 1) {
                            $saveSetting = Wo_SaveConfig('spaces', 0);
                        }
                    }
                }
                if ($key == 'spaces') {
                    if ($value == 1) {
                        if ($wo['config']['ftp_upload'] == 1) {
                            $saveSetting = Wo_SaveConfig('ftp_upload', 0);
                        }
                        if ($wo['config']['amazone_s3'] == 1) {
                            $saveSetting = Wo_SaveConfig('amazone_s3', 0);
                        }
                    }
                }


                if ($key == 'free_day_limit' && (!is_numeric($value) || $value < 1)) {
                    $value = 1000;
                }
                if ($key == 'pro_day_limit' && (!is_numeric($value) || $value < 1)) {
                    $value = 10000;
                }
                // if ($key == 'two_factor_type' && $wo['config']['two_factor_type'] != $value) {
                //     $db->where('two_factor_verified',1)->update(T_USERS,array('two_factor_email_verified' => 0,
                //                                                               'two_factor'          => 0));
                // }
                $saveSetting = Wo_SaveConfig($key, $value);
            }
        }
        if ($saveSetting === true) {
            if ($delete_follow_table == 1) {
                mysqli_query($sqlConnect, "DELETE FROM " . T_FOLLOWERS);
                mysqli_query($sqlConnect, "DELETE FROM " . T_NOTIFICATION . " WHERE type='following'");
            }
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'test_ftp') {
        include_once('assets/libraries/ftp/vendor/autoload.php');
        try {
            $array = array(
                'upload/photos/d-avatar.jpg',
                'upload/photos/d-cover.jpg',
                'upload/photos/d-group.jpg',
                'upload/photos/d-page.jpg',
                'upload/photos/d-blog.jpg',
                'upload/photos/game-icon.png',
                'upload/photos/d-film.jpg',
                'upload/photos/app-default-icon.png',
                'upload/photos/index.html',
                'upload/.htaccess'
            );
            foreach ($array as $key => $value) {
                $upload = Wo_UploadToS3($value, array(
                    'delete' => 'no'
                ));
            }
            $data['status'] = 200;
        } catch (Exception $e) {
            $data['status']  = 400;
            $data['message'] = $e->getMessage();
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'auto_friend' && Wo_CheckMainSession($hash_id) === true) {
        if (!empty($_GET['users'])) {
            $save = Wo_SaveConfig('auto_friend_users', $_GET['users']);
            if ($save) {
                $data['status'] = 200;
            }
        } else {
            $save = Wo_SaveConfig('auto_friend_users', '');
            if ($save) {
                $data['status'] = 200;
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'generate_fake_users') {
        require "assets/libraries/fake-users/vendor/autoload.php";
        $faker = Faker\Factory::create();
        if (empty($_POST['password'])) {
            $_POST['password'] = '123456789';
        }
        $count_users = $_POST['count_users'];
        $password = $_POST['password'];
        $avatar = $_POST['avatar'];
        Wo_RunInBackground(array('status' => 200));
        for ($i = 0; $i < $count_users; $i++) {
            $genders = array_keys($wo['genders']);
            $random_keys = array_rand($genders, 1);
            $gender = array_rand(array("male", "female", "not-selected"), 1);
            $gender = $genders[$random_keys];
            $re_data  = array(
                'email' => Wo_Secure(str_replace(".", "_", $faker->userName) . '_' . rand(111, 999) . "@yahoo.com", 0),
                'username' => Wo_Secure($faker->userName . '_' . rand(111, 999), 0),
                'password' => Wo_Secure($password, 0),
                'email_code' => Wo_Secure(md5($faker->userName . '_' . rand(111, 999)), 0),
                'src' => 'Fake',
                'gender' => Wo_Secure($gender),
                'lastseen' => time(),
                'active' => 1,
                'first_name' => $faker->firstName($gender),
                'last_name' => $faker->lastName
            );
            if ($avatar == 1) {
                $re_data['avatar'] = Wo_ImportImageFromFile($faker->imageUrl($wo['profile_picture_width_crop'], $wo['profile_picture_height_crop']));
            }
            $add_user = Wo_RegisterUser($re_data);
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'auto_delete' && Wo_CheckMainSession($hash_id) === true) {
        if (!empty($_GET['delete'])) {
            Wo_RunInBackground(array('status' => 200));
            $delete_data = Wo_DeleteAllData($_GET['delete']);
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'test_s3') {
        include_once('assets/libraries/s3/aws-autoloader.php');
        try {
            $s3Client = S3Client::factory(array(
                'version' => 'latest',
                'region' => $wo['config']['region'],
                'credentials' => array(
                    'key' => $wo['config']['amazone_s3_key'],
                    'secret' => $wo['config']['amazone_s3_s_key']
                )
            ));
            $buckets  = $s3Client->listBuckets();
            $result   = $s3Client->putBucketCors(array(
                'Bucket' => $wo['config']['bucket_name'], // REQUIRED
                'CORSConfiguration' => array( // REQUIRED
                    'CORSRules' => array( // REQUIRED
                        array(
                            'AllowedHeaders' => array(
                                'Authorization'
                            ),
                            'AllowedMethods' => array(
                                'POST',
                                'GET',
                                'PUT'
                            ), // REQUIRED
                            'AllowedOrigins' => array(
                                '*'
                            ), // REQUIRED
                            'ExposeHeaders' => array(),
                            'MaxAgeSeconds' => 3000
                        )
                    )
                )
            ));
            if (!empty($buckets)) {
                if ($s3Client->doesBucketExist($wo['config']['bucket_name'])) {
                    $data['status'] = 200;
                    $array          = array(
                        'upload/photos/d-avatar.jpg',
                        'upload/photos/d-cover.jpg',
                        'upload/photos/d-group.jpg',
                        'upload/photos/d-page.jpg',
                        'upload/photos/d-blog.jpg',
                        'upload/photos/game-icon.png',
                        'upload/photos/d-film.jpg',
                        'upload/photos/app-default-icon.png'
                    );
                    foreach ($array as $key => $value) {
                        $upload = Wo_UploadToS3($value, array(
                            'delete' => 'no'
                        ));
                    }
                } else {
                    $data['status'] = 300;
                }
            } else {
                $data['status'] = 500;
            }
        } catch (Exception $e) {
            $data['status']  = 400;
            $data['message'] = $e->getMessage();
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'test_spaces') {
        include_once("assets/libraries/spaces/spaces.php");
        try {
            $key        = $wo['config']['spaces_key'];
            $secret     = $wo['config']['spaces_secret'];
            $space_name = $wo['config']['space_name'];
            $region     = $wo['config']['space_region'];
            $space      = new SpacesConnect($key, $secret, $space_name, $region);
            $buckets    = $space->ListSpaces();
            $result     = $space->PutCORS(array(
                'AllowedHeaders' => array(
                    'Authorization'
                ),
                'AllowedMethods' => array(
                    'POST',
                    'GET',
                    'PUT'
                ), // REQUIRED
                'AllowedOrigins' => array(
                    '*'
                ), // REQUIRED
                'ExposeHeaders' => array(),
                'MaxAgeSeconds' => 3000
            ));
            if (!empty($buckets)) {
                if (!empty($space->GetSpaceName())) {
                    $data['status'] = 200;
                    $array          = array(
                        'upload/photos/d-avatar.jpg',
                        'upload/photos/d-cover.jpg',
                        'upload/photos/d-group.jpg',
                        'upload/photos/d-page.jpg',
                        'upload/photos/d-blog.jpg',
                        'upload/photos/game-icon.png',
                        'upload/photos/d-film.jpg',
                        'upload/photos/app-default-icon.png'
                    );
                    foreach ($array as $key => $value) {
                        $upload = Wo_UploadToS3($value, array(
                            'delete' => 'no'
                        ));
                    }
                } else {
                    $data['status'] = 300;
                }
            } else {
                $data['status'] = 500;
            }
        } catch (Exception $e) {
            $data['status']  = 400;
            $data['message'] = $e->getMessage();
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_terms_setting') {
        $saveSetting = false;
        foreach ($_POST as $key => $value) {
            if ($key != 'hash_id') {
                $saveSetting = Wo_SaveTerm($key, base64_decode($value));
            }
        }
        if ($saveSetting === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'test_message') {
        $send_message_data = array(
            'from_email' => $wo['config']['siteEmail'],
            'from_name' => $wo['config']['siteName'],
            'to_email' => $wo['user']['email'],
            'to_name' => $wo['user']['name'],
            'subject' => 'Test Message From ' . $wo['config']['siteName'],
            'charSet' => 'utf-8',
            'message_body' => 'If you can see this message, then your SMTP configuration is working fine.',
            'is_html' => false
        );
        $send_message      = Wo_SendMessage($send_message_data);
        if ($send_message === true) {
            $data['status'] = 200;
        } else {
            $data['status'] = 400;
            $data['error']  = "Error found while sending the email, the information you provided are not correct, please test the email settings on your local device and make sure they are correct. ";
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_sms_setting') {
        $saveSetting = false;
        foreach ($_POST as $key => $value) {
            if ($key != 'hash_id') {
                $saveSetting = Wo_SaveConfig($key, $value);
            }
        }
        if ($saveSetting === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'test_sms_message') {
        $message      = 'This is a test message from ' . $wo['config']['siteName'];
        $send_message = Wo_SendSMSMessage($wo['config']['sms_phone_number'], $message);
        if ($send_message === true) {
            $data['status'] = 200;
        } else {
            $data['status'] = 400;
            $data['error']  = $send_message;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_design_setting') {
        $saveSetting = false;
        if (isset($_FILES['logo']['name'])) {
            $fileInfo = array(
                'file' => $_FILES["logo"]["tmp_name"],
                'name' => $_FILES['logo']['name'],
                'size' => $_FILES["logo"]["size"]
            );
            $media    = Wo_UploadLogo($fileInfo);
        }
        if (isset($_FILES['background']['name'])) {
            $fileInfo = array(
                'file' => $_FILES["background"]["tmp_name"],
                'name' => $_FILES['background']['name'],
                'size' => $_FILES["background"]["size"]
            );
            $media    = Wo_UploadBackground($fileInfo);
        }
        if (isset($_FILES['favicon']['name'])) {
            $fileInfo = array(
                'file' => $_FILES["favicon"]["tmp_name"],
                'name' => $_FILES['favicon']['name'],
                'size' => $_FILES["favicon"]["size"]
            );
            $media    = Wo_UploadFavicon($fileInfo);
        }
        foreach ($_POST as $key => $value) {
            if ($key != 'hash_id') {
                $saveSetting = Wo_SaveConfig($key, $value);
            }
        }
        if ($saveSetting === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'updateTheme' && isset($_POST['theme'])) {
        $saveSetting = false;
        foreach ($_POST as $key => $value) {
            if ($key != 'hash_id') {
                $saveSetting = Wo_SaveConfig($key, $value);
            }
        }
        if ($saveSetting === true) {
            $data['status'] = 200;
        }
        $files = glob('cache/*'); // get all file names
        foreach ($files as $file) { // iterate files
            if (is_file($file))
                unlink($file); // delete file
        }
        if (!file_exists('cache/index.html')) {
            $f = @fopen("cache/index.html", "a+");
            @fclose($f);
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_user' && isset($_GET['user_id']) && Wo_CheckMainSession($hash_id) === true) {
        if (Wo_DeleteUser($_GET['user_id']) === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_job' && isset($_POST['job_id'])) {
        $job_id = Wo_Secure($_POST['job_id']);
        $job = $db->where('id', $job_id)->getOne(T_JOB);
        if (!empty($job)) {
            if ($job->image_type != 'cover') {
                @unlink($job->image);
                Wo_DeleteFromToS3($job->image);
            }
        }
        $db->where('id', $job_id)->delete(T_JOB);
        $db->where('job_id', $job_id)->delete(T_JOB_APPLY);
        $post = $db->where('job_id', $job_id)->getOne(T_POSTS);
        if (!empty($post)) {
            Wo_DeletePost($post->id);
        }


        $data['status'] = 200;
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_user_page' && isset($_GET['page_id'])) {
        if (Wo_DeletePage($_GET['page_id']) === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_group' && isset($_GET['group_id'])) {
        if (Wo_DeleteGroup($_GET['group_id']) === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'filter_all_users') {
        $html  = '';
        $after = (isset($_GET['after_user_id']) && is_numeric($_GET['after_user_id']) && $_GET['after_user_id'] > 0) ? $_GET['after_user_id'] : 0;
        foreach (Wo_GetAllUsers(20, 'ManageUsers', $_POST, $after) as $wo['userlist']) {
            $html .= Wo_LoadAdminPage('manage-users/list');
        }
        $data = array(
            'status' => 200,
            'html' => $html
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_more_pages') {
        $html  = '';
        $after = (isset($_GET['after_page_id']) && is_numeric($_GET['after_page_id']) && $_GET['after_page_id'] > 0) ? $_GET['after_page_id'] : 0;
        foreach (Wo_GetAllPages(20, $after) as $wo['pagelist']) {
            $html .= Wo_LoadAdminPage('manage-pages/list');;
        }
        $data = array(
            'status' => 200,
            'html' => $html
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_more_groups') {
        $html  = '';
        $after = (isset($_GET['after_group_id']) && is_numeric($_GET['after_group_id']) && $_GET['after_group_id'] > 0) ? $_GET['after_group_id'] : 0;
        foreach (Wo_GetAllGroups(20, $after) as $wo['grouplist']) {
            $html .= Wo_LoadAdminPage('manage-groups/list');;
        }
        $data = array(
            'status' => 200,
            'html' => $html
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_users_setting' && isset($_POST['user_lastseen'])) {
        $delete_follow_table = 0;
        $saveSetting         = false;
        foreach ($_POST as $key => $value) {
            $saveSetting = Wo_SaveConfig($key, $value);
        }
        if ($saveSetting === true) {
            $data['status'] = 200;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'get_more_posts') {
        $html      = '';
        $postsData = array(
            'limit' => 10,
            'after_post_id' => Wo_Secure($_GET['after_post_id'])
        );
        foreach (Wo_GetAllPosts($postsData) as $wo['story']) {
            $html .= Wo_LoadAdminPage('manage-posts/list');
        }
        $data = array(
            'status' => 200,
            'html' => $html
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_fund' && Wo_CheckSession($hash_id) === true) {
        if (!empty($_POST['fund_id'])) {
            $id = Wo_Secure($_POST['fund_id']);
            $fund = $db->where('id', $id)->getOne(T_FUNDING);
            if (!empty($fund)) {

                @Wo_DeleteFromToS3($fund->image);

                if (file_exists($fund->image)) {
                    try {
                        unlink($fund->image);
                    } catch (Exception $e) {
                    }
                }

                $db->where('id', $id)->delete(T_FUNDING);
                $raise = $db->where('funding_id', $id)->get(T_FUNDING_RAISE);
                $db->where('funding_id', $id)->delete(T_FUNDING_RAISE);
                $posts = $db->where('fund_id', $id)->get(T_POSTS);
                if (!empty($posts)) {
                    foreach ($posts as $key => $value) {
                        $db->where('parent_id', $value->id)->delete(T_POSTS);
                    }
                }

                $db->where('fund_id', $id)->delete(T_POSTS);
                foreach ($raise as $key => $value) {
                    $raise_posts = $db->where('fund_raise_id', $value->id)->get(T_POSTS);
                    if (!empty($raise_posts)) {
                        foreach ($posts as $key => $value1) {
                            $db->where('parent_id', $value1->id)->delete(T_POSTS);
                        }
                    }
                    $db->where('fund_raise_id', $value->id)->delete(T_POSTS);
                }

                $data['status'] = 200;
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_post' && Wo_CheckSession($hash_id) === true) {
        if (!empty($_POST['post_id'])) {
            if (Wo_DeletePost($_POST['post_id'])) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_reported_content' && (Wo_IsAdmin() || Wo_IsModerator())) {
        if (!empty($_GET['id']) && !empty($_GET['type']) && !empty($_GET['report_id'])) {
            $type   = Wo_Secure($_GET['type']);
            $id     = Wo_Secure($_GET['id']);
            $report = Wo_Secure($_GET['report_id']);
            if ($type == 'post' && Wo_DeletePost($id) === true) {
                $deleteReport = Wo_DeleteReport($report);
                if ($deleteReport === true) {
                    $data = array(
                        'status' => 200,
                        'html' => Wo_CountUnseenReports()
                    );
                }
            }
            if ($type == 'user' && Wo_DeleteUser($id) === true) {
                $deleteReport = Wo_DeleteReport($report);
                if ($deleteReport === true) {
                    $data = array(
                        'status' => 200,
                        'html' => Wo_CountUnseenReports()
                    );
                }
            }
            if ($type == 'page' && Wo_DeletePage($id) === true) {
                $deleteReport = Wo_DeleteReport($report);
                if ($deleteReport === true) {
                    $data = array(
                        'status' => 200,
                        'html' => Wo_CountUnseenReports()
                    );
                }
            }
            if ($type == 'group' && Wo_DeleteGroup($id) === true) {
                $deleteReport = Wo_DeleteReport($report);
                if ($deleteReport === true) {
                    $data = array(
                        'status' => 200,
                        'html' => Wo_CountUnseenReports()
                    );
                }
            }
            if ($type == 'comment' && Wo_DeletePostComment($id) === true) {
                $deleteReport = Wo_DeleteReport($report);
                if ($deleteReport === true) {
                    $data = array(
                        'status' => 200,
                        'html' => Wo_CountUnseenReports()
                    );
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'mark_as_safe') {
        if (!empty($_GET['report_id'])) {
            $deleteReport = Wo_DeleteReport($_GET['report_id']);
            if ($deleteReport === true) {
                $data = array(
                    'status' => 200,
                    'html' => Wo_CountUnseenReports()
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_verification') {
        if (!empty($_GET['id'])) {
            if (Wo_DeleteVerificationRequest($_GET['id']) === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_game') {
        if (!empty($_GET['game_id'])) {
            if (Wo_DeleteGame($_GET['game_id']) === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_gift') {
        if (!empty($_GET['gift_id'])) {
            if (Wo_DeleteGift($_GET['gift_id']) === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_sticker') {
        if (!empty($_GET['sticker_id'])) {
            if (Wo_DeleteSticker($_GET['sticker_id']) === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'verify_user' && Wo_CheckMainSession($hash_id) === true) {
        if (!empty($_GET['id'])) {
            $type = '';
            if (!empty($_GET['type'])) {
                $type = $_GET['type'];
            }
            if (Wo_VerifyUser($_GET['id'], $_GET['verification_id'], $type) === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'send_mail_to_all_users') {
        $isset_test = 'off';
        if (empty($_POST['message']) || empty($_POST['subject'])) {
            $send_errors = $error_icon . $wo['lang']['please_check_details'];
        } else {
            if (!empty($_POST['test_message'])) {
                if ($_POST['test_message'] == 'on') {
                    $isset_test = 'on';
                }
            }
            if ($isset_test == 'on') {
                $send_message_data = array(
                    'from_email' => $wo['config']['siteEmail'],
                    'from_name' => $wo['config']['siteName'],
                    'to_email' => $wo['user']['email'],
                    'to_name' => $wo['user']['name'],
                    'subject' => $_POST['subject'],
                    'charSet' => 'utf-8',
                    'message_body' => $_POST['message'],
                    'is_html' => true
                );
                $send              = Wo_SendMessage($send_message_data);
            } else {
                $users_type = 'all';
                $users      = array();
                if (isset($_POST['selected_emails']) && strlen($_POST['selected_emails']) > 0) {
                    $user_ids = explode(',', $_POST['selected_emails']);
                    if (is_array($user_ids) && count($user_ids) > 0) {
                        foreach ($user_ids as $user_id) {
                            $users[] = Wo_UserData($user_id);
                        }
                    }
                } else if ($_POST['send_to'] == 'active') {
                    $users = Wo_GetAllUsersByType('active');
                } else if ($_POST['send_to'] == 'inactive') {
                    $users = Wo_GetAllUsersByType('inactive');
                }
                Wo_RunInBackground(array('status' => 300));
                foreach ($users as $user) {
                    $send_message_data = array(
                        'from_email' => $wo['config']['siteEmail'],
                        'from_name' => $wo['config']['siteName'],
                        'to_email' => $user['email'],
                        'to_name' => $user['name'],
                        'subject' => $_POST['subject'],
                        'charSet' => 'utf-8',
                        'message_body' => $_POST['message'],
                        'is_html' => true
                    );
                    $send              = Wo_SendMessage($send_message_data);
                    $mail->ClearAddresses();
                }
            }
        }
        header("Content-type: application/json");
        if (!empty($send_errors)) {
            $send_errors_data = array(
                'status' => 400,
                'message' => $send_errors
            );
            echo json_encode($send_errors_data);
        } else {
            $data = array(
                'status' => 200
            );
            echo json_encode($data);
        }
        exit();
    }
    if ($s == 'get_users_emails' && isset($_GET['name'])) {
        $name  = Wo_Secure($_GET['name']);
        $html  = '';
        $users = Wo_GetUsersByName($name, false, 20);
        $data  = array(
            'status' => 404
        );
        if (count($users) > 0) {
            foreach ($users as $user) {
                $html .= "<p data-user='" . $user['user_id'] . "'>" . $user['username'] . "</p>";
            }
            $data['status'] = 200;
            $data['html']   = $html;
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'add_new_announcement') {
        if (!empty($_POST['announcement_text'])) {
            $html = '';
            $id   = Wo_AddNewAnnouncement(base64_decode($_POST['announcement_text']));
            if ($id > 0) {
                $wo['activeAnnouncement'] = Wo_GetAnnouncement($id);
                $html .= Wo_LoadAdminPage('manage-announcements/active-list');
                $data = array(
                    'status' => 200,
                    'text' => $html
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'delete_announcement') {
        if (!empty($_GET['id'])) {
            $DeleteAnnouncement = Wo_DeleteAnnouncement($_GET['id']);
            if ($DeleteAnnouncement === true) {
                $data = array(
                    'status' => 200
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'disable_announcement') {
        if (!empty($_GET['id'])) {
            $html                = '';
            $DisableAnnouncement = Wo_DisableAnnouncement($_GET['id']);
            if ($DisableAnnouncement === true) {
                $wo['inactiveAnnouncement'] = Wo_GetAnnouncement($_GET['id']);
                $html .= Wo_LoadAdminPage('manage-announcements/inactive-list');
                $data = array(
                    'status' => 200,
                    'html' => $html
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'activate_announcement') {
        if (!empty($_GET['id'])) {
            $html                 = '';
            $ActivateAnnouncement = Wo_ActivateAnnouncement($_GET['id']);
            if ($ActivateAnnouncement === true) {
                $wo['activeAnnouncement'] = Wo_GetAnnouncement($_GET['id']);
                $html .= Wo_LoadAdminPage('manage-announcements/active-list');
                $data = array(
                    'status' => 200,
                    'html' => $html
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_ads') {
        $updated = false;
        foreach ($_POST as $key => $ads) {
            if ($key != 'hash_id') {
                $ad_data = array(
                    'type' => $key,
                    'code' => base64_decode($ads),
                    'active' => (empty($ads)) ? 0 : 1
                );
                $update  = Wo_UpdateAdsCode($ad_data);
                if ($update) {
                    $updated = true;
                }
            }
        }
        if ($updated == true) {
            $data = array(
                'status' => 200
            );
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
    if ($s == 'update_ads_status') {
        if (!empty($_GET['type'])) {
            if (Wo_UpdateAdActivation($_GET['type']) == 'active') {
                $data = array(
                    'status' => 200
                );
            } else {
                $data = array(
                    'status' => 300
                );
            }
        }
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
}
