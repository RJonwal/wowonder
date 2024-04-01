<?php
if ($wo['loggedin'] == false) {
  header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
  exit();
}
$wo['description'] = $wo['config']['siteDesc'];
$wo['keywords']    = $wo['config']['siteKeywords'];
$wo['page']        = 'create-address-book';
$wo['title']       = $wo['lang']['create_new_address'];
$wo['content']     = Wo_LoadPage('address-book/address-book');