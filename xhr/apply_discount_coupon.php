<?php 
if ($f == 'apply_discount_coupon') {
    if (!empty($_GET['discount_code'])) {
        $errorMessage = '';
        $newPrice = '';
        $code = $_GET['discount_code'];
        $query_one     = "SELECT * FROM " . T_DISCOUNT_CODES . " WHERE `code` = '{$code}'";

        $sql_query_one = mysqli_query($sqlConnect, $query_one);

        $fetched_data  = mysqli_fetch_assoc($sql_query_one);
        $date_now = date("Y-m-d H:i:s"); 
        if (empty($fetched_data) || !($fetched_data['valid_from'] < $date_now && $fetched_data['valid_to'] > $date_now)) {
            $data = array(
                'status' => 400,
                'error' => 'Invalid Coupon'
            );
            header("Content-type: application/json");
            echo json_encode($data);
            exit();
        }
        $query_two     = "SELECT count(id) FROM " . T_PAYMENT_TRANSACTIONS . " WHERE `discount_id` = '{$fetched_data['id']}' AND `userid` = '{$wo['user']['user_id']}'";

        $sql_query_two = mysqli_query($sqlConnect, $query_two);

        $fetched_data2  = mysqli_fetch_assoc($sql_query_two);
        
        if($fetched_data['max_uses'] == $fetched_data['used_coupons'] || ($fetched_data2['count(id)'] == $fetched_data['max_uses_per_user'])){
            $data = array(
                'status' => 400,
                'error' => 'Max limit exceed'
            );
            header("Content-type: application/json");
            echo json_encode($data);
            exit();
        }
        if($fetched_data['type'] == 'percentage'){
            $discounted_price = (floatval($_GET['price']) * floatval($fetched_data['price']) / 100);
        }
        if($fetched_data['type'] == 'amount'){
            $discounted_price = floatval($fetched_data['price']);
        } 
        $newPrice = floatval($_GET['price']) - $discounted_price;
        
        if(floatval($newPrice) < 1){
            $data = array(
                'status' => 400,
                'error' => 'Plan price should be greater than discount price'
            );
            header("Content-type: application/json");
            echo json_encode($data);
            exit();
        }
        $data = array(
            'status' => 200,
            'price' => floor($newPrice * 100) / 100,
            'discounted_price' => floor($discounted_price * 100) / 100,//number_format((float)$discounted_price, 2, '.', '')
        );
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
