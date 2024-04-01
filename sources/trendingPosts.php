<?php
if ($wo['loggedin'] == false) {
   header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
   exit();
}
$wo['description'] = $wo['config']['siteDesc'];
$wo['keywords']    = $wo['config']['siteKeywords'];
$wo['page']        = 'trending_posts';
$wo['title']       = $wo['lang']['trending_posts'] . ' | ' . $wo['config']['siteTitle'];
$wo['content']     = Wo_LoadPage('trending-posts/content');