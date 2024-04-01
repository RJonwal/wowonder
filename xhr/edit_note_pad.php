<?php
if ($f == "edit_note_pad") {
    require "function/function.php";

    if (empty($_POST['noteId'])) {
        $errors[] = $error_icon . "No access.";
    }

    if (empty($_POST['note'])) {
        $errors[] = $error_icon . $wo['lang']['please_check_details'];
    }

    // echo "<pre>";
    // print_r($_POST);
    // echo "</pre>";

    // echo "<pre>";
    // print_r($errors);
    // echo "</pre>";
    if (empty($errors)) {
        $query = mysqli_query($sqlConnect, "SELECT * FROM wo_note_pad WHERE `id` = '{$_POST['noteId']}'");
        $fetched_data = mysqli_fetch_assoc($query);

        if (isset($fetched_data['id']) && $fetched_data['id'] > 0) {

            if (empty($_POST['title'])) {
                $_POST['title'] = date("Y-m-d H:i:s");
            }


            $updateNote = $db->where('id', $fetched_data['id'])->update(
                T_USER_NOTE_PAD,
                array(
                    'title' => Wo_Secure($_POST['title']),
                    'note' => Wo_Secure($_POST['note']),
                    'cby' => $wo['user']['id'],
                    'cip' => ipAddress(),
                    'cstatus' => 1,
                    'cdate' => date("Y-m-d H:i:s")
                )
            );

            if ($updateNote) {
                if (empty($_POST['autosave'])) {
                    $data = array(
                        'message' => $success_icon . 'note updated.',
                        'status' => 200,
                        'location' => Wo_SeoLink('index.php?link1=notepad')
                    );
                } else {
                    $data = array(
                        'message' => $success_icon . 'note updated.',
                        'status' => 200,
                    );
                }
            }
        }
    } else {
        $data = array(
            'status' => 200,
            'message' => $errors,
            'location' => Wo_SeoLink('index.php?link1=edit-note-pad&npid='.$_POST['noteId'])
        );
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
