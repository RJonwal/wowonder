<?php
require_once('vendor/autoload.php');
$stripe = array(
  "secret_key"      =>  $wo['config']['stripe_secret'],
  "publishable_key" =>  $wo['config']['stripe_id']
);

\Stripe\Stripe::setApiKey($stripe['secret_key']);