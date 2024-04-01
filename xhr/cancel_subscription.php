<?php 
include_once('assets/includes/stripe_config.php');

if(isset($_POST['userid']) && !empty($_POST['userid']) && isset($_POST['transactionid']) && !empty($_POST['transactionid'])){
    $userid = $_POST['userid'];
    $transactionid = $_POST['transactionid'];

    if($userid == $wo['user']['user_id']){
        try {
            $stripe_customer_id = $wo['user']['stripe_user_id'];
            $active_subscriptions = \Stripe\Subscription::all([
                'customer' => $stripe_customer_id
            ]);

            foreach($active_subscriptions as $key => $subscription){
                if(empty($subscription->canceled_at)){
                    $cancel_sub = $subscription->cancel([
                        'at_period_end' => false, // Set to false to cancel immediately
                    ]);
                }
            }

            $currentDate = date('Y-m-d H:i:s');
            $q = " UPDATE " . T_PAYMENT_TRANSACTIONS . " SET `subscription_end_date` = '{$currentDate}', `subscription_status` = 0 WHERE `id` = {$transactionid}";
            $d = mysqli_query($sqlConnect, $q);

            $updateUser = [
                'is_pro' => 0,
                'pro_type' => 0,
                'pro_time' => 0,
                'pro_expire_date' => NULL,
                'payment_type' => NULL
            ];
            // print_r($updateUser);die;
            $update      = Wo_UpdateUserData($userid, $updateUser);

            $data = array(
                'status' => 200,
                'success' => true,
                'message' => "Subscription Cancel Successfully"
            );
            header("Content-type: application/json");
            echo json_encode($data);
            exit();
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
}