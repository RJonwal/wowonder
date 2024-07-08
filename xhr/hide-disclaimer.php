<?php 
if ($f == 'hide-disclaimer') {
    // Set the name of the cookie
    $userid = $wo['user']['user_id'];
    $cookie_name = "hide-disclaimer-$userid";

    // Set the value of the cookie
    $cookie_value = true;

    // Set the expiration time for 24 hours from now
    $expiry_time = time() + (24 * 60 * 60); // 24 hours * 60 minutes * 60 seconds

    // Set the cookie
    setcookie($cookie_name, $cookie_value, $expiry_time);
}
