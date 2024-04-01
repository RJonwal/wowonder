<?php
include_once('assets/includes/stripe_config.php');

if(isset($_GET['subscriptionId']) && !empty($_GET['subscriptionId'])){
    try{
        $status = true;
        $sub_id = $_GET['subscriptionId'];
        $query = mysqli_query($sqlConnect, "SELECT * FROM " . T_PAYMENT_TRANSACTIONS . " WHERE `stripe_subscription_id` = '{$sub_id}' ORDER BY id DESC LIMIT 0,1");
        
        if(!empty($query)){
            $transaction = mysqli_fetch_assoc($query);
            $transaction_id = $transaction['id'];
            // printr($transaction_id);
            $userid = $transaction['userid'];

            $canceledSubscription = \Stripe\Subscription::retrieve($sub_id);
            $cancel_sub = $canceledSubscription->cancel([
                'at_period_end' => false, // Set to false to cancel immediately
            ]);

            $currentDate = date('Y-m-d H:i:s');
            $q = " UPDATE " . T_PAYMENT_TRANSACTIONS . " SET `subscription_end_date` = '{$currentDate}', `subscription_status` = 0 WHERE `id` = {$transaction_id}";
            $d = mysqli_query($sqlConnect, $q);

            $updateUser = [
                'is_pro' => 0,
                'pro_type' => 0,
                'pro_time' => 0,
                'pro_expire_date' => NULL,
                'payment_type' => NULL
            ];
            $update      = Wo_UpdateUserData($userid, $updateUser);
        } else {
            $status = false;
        }
    }
    catch (Exception $e) {
        $status = false;
    }
} else {
    $status = false;
}
if(!$status){
    $url = $wo['site_url'];
    header("Location: $url");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Subscription Cancellation Confirmation</title>
    <style>
        .commanTable {
            display: flex;
            justify-content: center;
            margin-top: 100px;
        }
        .commanTable table {
            background: #f5f5f5;
        }
        .commanTable td.header {
            background-color: #28a745;
            color: #fff;
            text-align: center;
            font-size: 13px;
            display: inline-block;
            padding: 20px 30px;
            border-radius: 6px;
            margin-top: -35px;
            font-family: 'roboto', sans-serif;
        }
        .commanTable table  tr:first-child td h1{
            margin: 0px;        
        }
        .commanTable table  tr td p{
            margin: 20px 0px;        
        }

        p {
            font-family: 'Roboto', sans-serif;
            font-size: 14px;
        }
        .common-header{
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="commanTable">
        <table width="600" cellspacing="0" cellpadding="0">
            <tr class="common-header">
                <td class="header">
                    <h1>Subscription Cancellation Confirmation</h1>
                </td>
            </tr>
            <tr>
                <td style="padding: 20px;">
                    <p>Dear <?php echo $_GET['customerName']; ?>,</p>
                    <p>We have received your request to cancel your subscription.</p>
                    <p>Your subscription has been successfully canceled, and you will not be billed for any future renewals.</p>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
