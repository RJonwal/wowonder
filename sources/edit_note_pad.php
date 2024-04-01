<?php

if ($wo['loggedin'] == false) {
  header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
  exit();
}

if (isset($_REQUEST['npid']) && is_numeric($_REQUEST['npid'])) {
  $isNoteIdExists = Wo_isNoteIdExists($_REQUEST['npid']);
  if (isset($isNoteIdExists) && $isNoteIdExists == true) {
  $wo['noteId']   = $_REQUEST['npid'];
  $wo['description'] = $wo['config']['siteDesc'];
  $wo['keywords']    = $wo['config']['siteKeywords'];
  $wo['page']        = 'edit-note-pad';
  $wo['title']       = $wo['lang']['edit_note'];
  $wo['content']     = Wo_LoadPage('note-pad/edit-note-pad');
  } else {
    header("Location: " . $wo['config']['site_url']);
    exit();
  }
}
