<?php 
if ($f == 'webhook') {
    include_once('assets/includes/stripe_config.php');
    $json = file_get_contents("php://input");
    $event = null;
    
    try {
        $event = \Stripe\Event::constructFrom(
            json_decode($json, true)
        );
    } catch(\UnexpectedValueException $e) {
        // Invalid payload
        http_response_code(400);
        exit();
    }
    
    $subscription_id = $event->data->object->subscription; 
    $intent_id = $event->data->object->payment_intent; 
    $customer = $event->data->object->customer;
    $customerName = $event->data->object->customer_name;
    $sql_query_users = mysqli_query($sqlConnect, "SELECT * FROM " . T_USERS . " WHERE `stripe_user_id` = '$customer'");
    $users = mysqli_fetch_assoc($sql_query_users);
    $user_details = json_encode($users);
    $notes = $wo['lang']['upgrade_to_pro'] . " " . $img . " : Stripe";
    $amount = $event->data->object->lines->data[0]->unit_amount_excluding_tax;
    
    if($amount == 0){
        exit;
    }
    
    $amount2 = $amount/100;
    $productId = $event->data->object->lines->data[0]->plan->product;
    $sql_query_plan = mysqli_query($sqlConnect, "SELECT * FROM " . T_MANAGE_PRO . " WHERE `stripe_product_id` = '$productId'");
    $plan = mysqli_fetch_assoc($sql_query_plan);
    $plan_details = json_encode($plan);
    $current_time = time();
    $invoice = $event->data->object->hosted_invoice_url;
    $headers = 'From: wowonder@hipl-staging4.com';
    $headers .= " Subscription renewal $from\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $to = $event->data->object->customer_email;
    // $to = 'ritikajangir.hipl@gmail.com';
    $date = date('d-m-Y',  $event->data->object->created);
    switch ($event->type) {
        case 'invoice.paid':
            $html_template = file_get_contents($wo['config']['site_url'] . '/requests.php?f=successful_renewal');
            $html_content = str_replace(
                ['[CustomerName]', '[RenewalDate]', '[AmountCharged]', '[InvoiceLink]', '[PlanName]'],
                [$customerName, $date , $plan['price'], $invoice, ucfirst($plan['type'])],
                $html_template
            );
            $subject = 'Subscription Renewal Confirmation';
            
            mail($to, $subject, $html_content, $headers);
            
            $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_PAYMENT_TRANSACTIONS . " WHERE `stripe_subscription_id` = '{$subscription_id}' ORDER BY id DESC LIMIT 0,1");

            $subscription_date = date('Y-m-d', time());
            $pro_days = $plan['time'];

            $expire_date = date('Y-m-d', strtotime('+'.$pro_days.' day', strtotime($subscription_date)));
            
            mysqli_query($sqlConnect, " UPDATE " . T_USERS . " SET `is_pro` = '1', `pro_time` = '{$current_time}', `pro_type` = '{$plan['id']}', `pro_expire_date` = '{$expire_date}' WHERE `stripe_user_id` = '{$customer}'");
            
            if(!empty($query)){
                $transaction = mysqli_fetch_assoc($query);
                $transaction_id = $transaction['id'];
                $payment_status = $transaction['payment_status'];
                
                if($payment_status == 'success'){
                    mysqli_query($sqlConnect, " UPDATE " . T_PAYMENT_TRANSACTIONS . " SET `subscription_status` = 0 WHERE `id` = '{$transaction_id}'");
                    
                    mysqli_query($sqlConnect, "INSERT INTO " . T_PAYMENT_TRANSACTIONS . " (`userid`, `kind`, `amount`, `notes`, `user_details`, `plan_details`, `stripe_intent_id`, `stripe_subscription_id`, `stripe_event_details`, `payment_status`, `subscription_end_date`) VALUES ({$users['user_id']}, 'PRO', {$amount2}, '{$notes}', '{$user_details}', '{$plan_details}', '{$intent_id}', '{$subscription_id}', '{$event}', 'success', '{$expire_date}')");
                } else if($payment_status == 'processing') {
                    mysqli_query($sqlConnect, " UPDATE " . T_PAYMENT_TRANSACTIONS . " SET `stripe_event_details` = '{$event}', `payment_status` = 'success' WHERE `id` = '{$transaction_id}'");
                }
            }else{
                mysqli_query($sqlConnect, "INSERT INTO " . T_PAYMENT_TRANSACTIONS . " (`userid`, `kind`, `amount`, `notes`, `user_details`, `plan_details`, `stripe_intent_id`, `stripe_subscription_id`, `payment_status`, `subscription_end_date`) VALUES ({$users['user_id']}, 'PRO', {$amount2}, '{$notes}', '{$user_details}', '{$plan_details}', '{$intent_id}', '{$subscription_id}', 'success', '{$expire_date}')");
            }
            break;
            
        case 'invoice.payment_failed':
            $html_template = file_get_contents($wo['config']['site_url'] . '/requests.php?f=renewal_failed');
            $html_content = str_replace(
                ['[CustomerName]', '[RenewalDate]', '[AmountCharged]', '[InvoiceLink]', '[PlanName]'],
                [$customerName, $date , $plan['price'], $invoice, ucfirst($plan['type'])],
                $html_template
            );
            $subject = 'Subscription Payment Failed';
            
            mail($to, $subject, $html_content, $headers);
            
            
            $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_PAYMENT_TRANSACTIONS . " WHERE `stripe_subscription_id` = '{$subscription_id}' ORDER BY id DESC LIMIT 0,1");
            
            mysqli_query($sqlConnect, " UPDATE " . T_USERS . " SET `is_pro` = '0', `pro_time` = '0', `pro_type` = '0' WHERE `stripe_user_id` = '{$customer}'");
            
            if(!empty($query)){
                $transaction = mysqli_fetch_assoc($query);
                $transaction_id = $transaction['id'];
                mysqli_query($sqlConnect, " UPDATE " . T_PAYMENT_TRANSACTIONS . " SET `stripe_event_details` = '{$event}', `payment_status` = 'fail' WHERE `id` = '{$transaction_id}'");
            }else{
                mysqli_query($sqlConnect, "INSERT INTO " . T_PAYMENT_TRANSACTIONS . " (`userid`, `kind`, `amount`, `notes`, `user_details`, `plan_details`, `stripe_intent_id`, `stripe_subscription_id`, `payment_status`) VALUES ({$users['user_id']}, 'PRO', {$amount2}, '{$notes}', '{$user_details}', '{$plan_details}', '{$intent_id}', '{$subscription_id}', 'fail')");
            }
            
            break;
            
        case 'invoice.upcoming':
            
            $renewalDate = strtotime('+7 days');
        
            $cancel_subscription = $wo['config']['site_url'] . '/requests.php?f=cancel_renewal&subscriptionId='.$subscription_id.'&customerName='.$customerName;
            $html_template = file_get_contents($wo['config']['site_url'] . '/requests.php?f=upcoming_renewal');
            $html_content = str_replace(
                ['[CustomerName]', '[RenewalDate]', '[AmountCharged]', '[PlanName]', '[CancellationLink]'],
                [$customerName, date('d-m-Y', $renewalDate) , $plan['price'], ucfirst($plan['type']), $cancel_subscription],
                $html_template
            );
            $subject = 'Upcoming Subscription Renewal';
            
            mail($to, $subject, $html_content, $headers);
            
            break;
        default:
            echo 'Received unknown event type ' . $event->type;
    }
    
    http_response_code(200);
}
