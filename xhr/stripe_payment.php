<?php 
if ($f == 'stripe_payment') {
    include_once('assets/includes/stripe_config.php');
    if (isset($_POST['payment_method_id']) && empty($_POST['payment_method_id'])) {
        header("Location: " . Wo_SeoLink('index.php?link1=oops'));
        exit();
    }

    try {
        $pro_types_array = array(1,2,3,4);
        $pro_type = $_GET['pro_type'];
        $is_trial = $_POST['is_trial'];

        if($is_trial == 1){
            $freeTransaction = mysqli_query($sqlConnect, "SELECT id FROM " . T_PAYMENT_TRANSACTIONS . " WHERE `userid` = {$wo['user']['user_id']} ORDER  BY id DESC LIMIT 0,1");
            if(mysqli_num_rows($freeTransaction) > 0){
                $data = array(
                    'status' => 400,
                    'error' => 'You have already taken the free trial',
                    'location' => $wo['config']['site_url']
                );
                header("Content-type: application/json");
                echo json_encode($data);
                exit();
            }
        }

        $pro_type = 0;
        if (!isset($_GET['pro_type']) || !in_array($_GET['pro_type'], $pro_types_array)) {
            $data = array(
                'status' => 200,
                'error' => 'Pro type is not set'
            );
            header("Content-type: application/json");
            echo json_encode($data);
            exit();
        }
        
        $payment_type = $_POST['payment_type'];
        $discount_code = $_POST['discount_code'];
        $amount1 = $_POST['amount'];
        $amount2 = $amount1;
        $amount1 = floatval($amount1) * 100;
        
        $is_pro = 0;
        $stop   = 0;
        $user   = Wo_UserData($wo['user']['user_id']);
        if ($user['is_pro'] == 1) {
            $stop = 1;
            if ($user['pro_type'] == 1) {
                $time_ = time() - $star_package_duration;
                if ($user['pro_time'] > $time_) {
                    $stop = 1;
                }
            } else if ($user['pro_type'] == 2) {
                $time_ = time() - $hot_package_duration;
                if ($user['pro_time'] > $time_) {
                    $stop = 1;
                }
            } else if ($user['pro_type'] == 3) {
                $time_ = time() - $ultima_package_duration;
                if ($user['pro_time'] > $time_) {
                    $stop = 1;
                }
            } else if ($user['pro_type'] == 4) {
                if ($vip_package_duration > 0) {
                    $time_ = time() - $vip_package_duration;
                    if ($user['pro_time'] > $time_) {
                        $stop = 1;
                    }
                }
            }
        }
        if ($stop == 0) {
            $pro_types_array = array(
                1,
                2,
                3,
                4
            );
            $pro_type        = 0;
            if (!isset($_GET['pro_type']) || !in_array($_GET['pro_type'], $pro_types_array)) {
                $data = array(
                    'status' => 200,
                    'error' => 'Pro type is not set'
                );
                header("Content-type: application/json");
                echo json_encode($data);
                exit();
            }
            $pro_type = $_GET['pro_type'];
            $is_pro   = 1;
        }
        if ($stop == 0) {
            $time = time();
            if ($is_pro == 1) {
                if($_POST['before_after_success'] == "before"){
                    $type = $pro_type == 2 ? 'month' : 'year';
                    if($wo['pro_packages'][$wo['pro_packages_types'][$pro_type]]['stripe_price_id'] == null && $wo['pro_packages'][$wo['pro_packages_types'][$pro_type]]['stripe_product_id'] == null){
                        $product = \Stripe\Product::create([
                            'name' => $wo['lang'][$wo['pro_packages_types'][$pro_type]],
                        ]);
                        $price = \Stripe\Price::create([
                            'product' => $product->id,
                            'unit_amount' => floatval($wo['pro_packages'][$wo['pro_packages_types'][$pro_type]]['price']) * 100,
                            'currency' => $wo['config']['stripe_currency'], 
                            // 'currency' => 'inr', 
                            'recurring' => ['interval' => $type]
                        ]); 
                        $create_price_id = mysqli_query($sqlConnect, " UPDATE " . T_MANAGE_PRO . " SET `stripe_price_id` = '{$price->id}', `stripe_product_id` = '{$product->id}' WHERE `id` = {$wo['pro_packages'][$wo['pro_packages_types'][$pro_type]]['id']}");
                        $stripe_price_id = $price->id;
                    } else {
                        $stripe_price_id = $wo['pro_packages'][$wo['pro_packages_types'][$pro_type]]['stripe_price_id'];
                    }

                    $priceData = \Stripe\Price::retrieve($stripe_price_id); 

                    //if plan price changes
                    if($priceData->unit_amount != floatval($wo['pro_packages'][$wo['pro_packages_types'][$pro_type]]['price']) * 100){
                        $price = \Stripe\Price::create([
                            'product' => $wo['pro_packages'][$wo['pro_packages_types'][$pro_type]]['stripe_product_id'],
                            'unit_amount' => floatval($wo['pro_packages'][$wo['pro_packages_types'][$pro_type]]['price']) * 100,
                            'currency' => $wo['config']['stripe_currency'], 
                            // 'currency' => 'inr', 
                            'recurring' => ['interval' => $type]
                        ]); 
                        $create_price_id = mysqli_query($sqlConnect, " UPDATE " . T_MANAGE_PRO . " SET `stripe_price_id` = '{$price->id}' WHERE `id` = {$wo['pro_packages'][$wo['pro_packages_types'][$pro_type]]['id']}");
                        
                        //to update all the existing subscription
                        $subscriptions = \Stripe\Subscription::all();
                        foreach ($subscriptions->data as $subscription) {  
                            if($subscription->items->data[0]->price->product == $wo['pro_packages'][$wo['pro_packages_types'][$pro_type]]['stripe_product_id']){
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
                    }
                    if($wo['user']['stripe_user_id']==null){
                        $customer = \Stripe\Customer::create(array(
                            'name' => $wo['user']['first_name'].' '.$wo['user']['last_name'],
                            'email' => $wo['user']['email']
                        ));
                        $source = \Stripe\Customer::createSource(
                            $customer->id,
                            [
                                'source' => $_POST['token_id'], 
                            ]
                        ); 
                        $create_user_id = mysqli_query($sqlConnect, " UPDATE " . T_USERS . " SET `stripe_user_id` = '{$customer->id}' WHERE `user_id` = {$wo['user']['user_id']}");
                    }else{
                        $customer = \Stripe\Customer::retrieve($wo['user']['stripe_user_id']);
                    }
            
                    if($payment_type == 'one-time'){
                        $charge = \Stripe\PaymentIntent::create([
                            'customer' => $customer->id, 
                            'amount' => $amount1, 
                            'currency' => $wo['config']['stripe_currency'],  
                            // 'currency' => 'inr', 
                            'confirmation_method' => 'automatic',
                            'payment_method_types' => ['card'],
                            'payment_method' => $_POST['payment_method_id'],
                        ]); 
                        $client_secret = $charge->client_secret;
                        $stripe_intent_id = $charge->id;
                        $stripe_subscription_id = null;
                    }else{
                        $data = [
                            'customer' => $customer->id,
                            'items' => [[
                                'price' => $stripe_price_id
                            ]], 
                            'payment_behavior' => 'allow_incomplete',
                            'collection_method' => 'charge_automatically',
                            'expand' => ['latest_invoice.payment_intent']
                        ];
                        if($is_trial == 1){
                            $data['trial_period_days'] = 30;
                        }
                        if($discount_code!='' && $amount1 != $stripe_price_id){
                            $coupon = \Stripe\Coupon::create([
                                'name' => $discount_code, 
                                'currency' => $wo['config']['stripe_currency'], 
                                'amount_off' => number_format((float)$_POST['discount_price'], 2, '.', '')*100, 
                                'duration' => 'once',
                            ]);
                            $data['coupon'] = $coupon->id;
                        }
                        $charge = \Stripe\Subscription::create($data); 
                        if($coupon){
                            \Stripe\Coupon::retrieve($coupon->id)->delete();
                        }
                        $client_secret = $charge->latest_invoice->payment_intent->client_secret;
                        if($is_trial == 1){
                            $stripe_intent_id = null;
                        } else {
                            $stripe_intent_id = $charge->latest_invoice->payment_intent->id;
                        }
                        $stripe_subscription_id = $charge->id;
                    }

                    if($is_trial == 1){
                        $update_array   = array(
                            'is_pro' => 1,
                            'pro_time' => time(),
                            'pro_' => 1,
                            'pro_type' => $pro_type,
                            'payment_type' => $payment_type
                        );
                        $subscription_date = date('Y-m-d', time());
                        $pro_days = $wo['pro_packages'][$wo['pro_packages_types'][$pro_type]]['time'];
    
                        $expire_date = date('Y-m-d', strtotime('+30 day', strtotime($subscription_date)));
    
                        $update_array['pro_expire_date'] = $expire_date;
                        $mysqli = Wo_UpdateUserData($wo['user']['user_id'], $update_array);
    
                        $user_details = json_encode($wo['user']);
                        $plan_details = json_encode($wo['pro_packages'][$wo['pro_packages_types'][$pro_type]]);
    
                        $discount_id = null;
                        $discount_details = null;
    
                        $create_payment_log = mysqli_query($sqlConnect, "INSERT INTO " . T_PAYMENT_TRANSACTIONS . " (`userid`, `kind`, `amount`, `notes`, `user_details`, `plan_details`, `discount_id`, `discount_details`, `stripe_intent_id`, `stripe_subscription_id`, `payment_status`, `subscription_end_date`) VALUES ({$wo['user']['user_id']}, 'FREE', 0, 'free_trial', '{$user_details}', '{$plan_details}', '{$discount_id}', '{$discount_details}', '{$stripe_intent_id}', '{$stripe_subscription_id}', 'success', '{$expire_date}')");
                        $create_payment = Wo_CreatePayment($pro_type);
                    }

                    $resData = array(
                        'status' => 200,
                        'clientSecret' => $client_secret,
                        'stripe_intent_id' => $stripe_intent_id,
                        'stripe_subscription_id' => $stripe_subscription_id,
                        'location' => Wo_SeoLink('index.php?link1=payment&type='.$wo['pro_packages_types'][$pro_type]),
                        'is_trial' => $is_trial,
                        'location' => Wo_SeoLink('index.php?link1=upgraded')
                    );
                    header("Content-type: application/json");
                    echo json_encode($resData);
                    exit();
                }else{ 
                    $update_array   = array(
                        'is_pro' => 1,
                        'pro_time' => time(),
                        'pro_' => 1,
                        'pro_type' => $pro_type,
                        'payment_type' => $payment_type
                    );
                    if (in_array($pro_type, array_keys($wo['pro_packages_types'])) && $wo['pro_packages'][$wo['pro_packages_types'][$pro_type]]['verified_badge'] == 1) {
                        // $update_array['verified'] = 1;
                    } 

                    $subscription_date = date('Y-m-d');
                    $pro_days = $pro_days = $wo['pro_packages'][$wo['pro_packages_types'][$pro_type]]['time'];

                    $expire_date = date('Y-m-d', strtotime('+'.$pro_days.' day', strtotime($subscription_date)));

                    $update_array['pro_expire_date'] = $expire_date;

                    $user_details = json_encode($wo['user']);
                    $plan_details = json_encode($wo['pro_packages'][$wo['pro_packages_types'][$pro_type]]);
                    $discount_id = null;
                    $discount_details = null;
                    if($discount_code!=''){
                        $query_one = "SELECT * FROM " . T_DISCOUNT_CODES . " WHERE `code` = '{$discount_code}'";
                        $sql_query_one = mysqli_query($sqlConnect, $query_one);
                        $fetched_data  = mysqli_fetch_assoc($sql_query_one); 
                        $discount_id = $fetched_data['id'];
                        $data_ = array(
                            'used_coupons' => $fetched_data['used_coupons']+1
                        ); 
                        $add   = Wo_UpdateDiscountCodeData($discount_id, $data_);
                        $discount_details = json_encode($fetched_data);
                    }
                    $mysqli         = Wo_UpdateUserData($wo['user']['user_id'], $update_array);
                    $notes              = $wo['lang']['upgrade_to_pro'] . " " . $img . " : Stripe";
                    $create_payment_log = mysqli_query($sqlConnect, "INSERT INTO " . T_PAYMENT_TRANSACTIONS . " (`userid`, `kind`, `amount`, `notes`, `user_details`, `plan_details`, `discount_id`, `discount_details`, `stripe_intent_id`, `stripe_subscription_id`, `subscription_end_date`) VALUES ({$wo['user']['user_id']}, 'PRO', {$amount2}, '{$notes}', '{$user_details}', '{$plan_details}', '{$discount_id}', '{$discount_details}', '{$_POST['stripe_intent_id']}', '{$_POST['stripe_subscription_id']}', '{$expire_date}')");
                    $create_payment = Wo_CreatePayment($pro_type);
                    if ($mysqli) {

                        if ((!empty($_SESSION['ref']) || !empty($wo['user']['ref_user_id'])) && $wo['config']['affiliate_type'] == 1 && $wo['user']['referrer'] == 0) {
                            if (!empty($_SESSION['ref'])) {
                                $ref_user_id = Wo_UserIdFromUsername($_SESSION['ref']);
                            }
                            elseif (!empty($wo['user']['ref_user_id'])) {
                                $ref_user_id = Wo_UserIdFromUsername($wo['user']['ref_user_id']);
                            }


                            if ($wo['config']['amount_percent_ref'] > 0) {
                                if (!empty($ref_user_id) && is_numeric($ref_user_id)) {
                                    $update_user    = Wo_UpdateUserData($wo['user']['user_id'], array(
                                        'referrer' => $ref_user_id,
                                        'src' => 'Referrer'
                                    ));
                                    $ref_amount     = ($wo['config']['amount_percent_ref'] * $amount1) / 100;
                                    $update_balance = Wo_UpdateBalance($ref_user_id, $ref_amount);
                                    unset($_SESSION['ref']);
                                }
                            } else if ($wo['config']['amount_ref'] > 0) {
                                if (!empty($ref_user_id) && is_numeric($ref_user_id)) {
                                    $update_user    = Wo_UpdateUserData($wo['user']['user_id'], array(
                                        'referrer' => $ref_user_id,
                                        'src' => 'Referrer'
                                    ));
                                    $update_balance = Wo_UpdateBalance($ref_user_id, $wo['config']['amount_ref']);
                                    unset($_SESSION['ref']);
                                }
                            }
                        }


                        $data = array(
                            'status' => 200,
                            'clientSecret' => $client_secret,
                            'location' => Wo_SeoLink('index.php?link1=upgraded')
                        );
                        header("Content-type: application/json");
                        echo json_encode($data);
                        exit();
                    }
                }
            } else {
                $data = array(
                    'status' => 400,
                    'error' => 'You are already a PRO member',
                    'location' => Wo_SeoLink('index.php?link1=upgraded')
                );
                header("Content-type: application/json");
                echo json_encode($data);
                exit();
            }
        } else {
            $data = array(
                'status' => 400,
                'error' => 'You are already a PRO member',
                'location' => Wo_SeoLink('index.php?link1=upgraded')
            );
            header("Content-type: application/json");
            echo json_encode($data);
            exit();
        }
    }
    catch (Exception $e) {
        $data = array(
            'status' => 400,
            'error' => "Something Went Wrong"
            // 'error' => $e->getMessage()
        );
        header("Content-type: application/json");
        echo json_encode($data);
        exit();
    }
}
