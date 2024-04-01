<?php
if ($wo['loggedin'] == false) {
  header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
    exit();
}

$wo['description'] = $wo['config']['siteDesc'];
$wo['keywords']    = $wo['config']['siteKeywords'];
$wo['page']        = 'auto_consult';
$wo['title']       = $wo['lang']['auto_consult'];

$access_autoconsult = checkAutoConsultAccess();
if($access_autoconsult){
  $wo['content']     = Wo_LoadPage('auto_consult/content');
}else{
  $wo['content']     = Wo_LoadPage('auto_consult/restricted_page');
}