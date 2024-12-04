<?php
if ($wo['loggedin'] == true) {
    header("Location: " . $wo['config']['site_url']);
    exit();
 }

$wo['description'] = $wo['config']['siteDesc'];
$wo['keywords']    = $wo['config']['siteKeywords'];
$wo['page']        = 'contact-us';
$wo['title']       = $wo['lang']['contact_us'];
$wo['content']     = Wo_LoadPage('contact/content');