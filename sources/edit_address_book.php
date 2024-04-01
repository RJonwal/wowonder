<?php

if ($wo['loggedin'] == false) {
  header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
  exit();
}

if (isset($_REQUEST['abid']) && is_numeric($_REQUEST['abid'])) {
  $isAddressIdExists = Wo_isAddressIdExists($_REQUEST['abid']);
  if (isset($isAddressIdExists) && $isAddressIdExists == true) {
    $wo['addressId']   = $_REQUEST['abid'];
    $wo['description'] = $wo['config']['siteDesc'];
    $wo['keywords']    = $wo['config']['siteKeywords'];
    $wo['page']        = 'edit-address-book';
    $wo['title']       = $wo['lang']['edit_address'];
    $wo['content']     = Wo_LoadPage('address-book/edit-address-book');
  } else {
    header("Location: " . $wo['config']['site_url']);
    exit();
  }
}
