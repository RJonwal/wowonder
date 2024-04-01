<?php

if ($wo['loggedin'] == false) {
  header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
  exit();
}
$wo['description'] = $wo['config']['siteDesc'];
$wo['keywords']    = $wo['config']['siteKeywords'];
$wo['page']        = 'create-note-pad';
$wo['title']       = $wo['lang']['create_new_note'];
$wo['content']     = Wo_LoadPage('note-pad/note-pad');