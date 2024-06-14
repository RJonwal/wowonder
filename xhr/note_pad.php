<?php

if ($f == "note_pad") {
    require "function/function.php";

    global $sqlConnect;

    if (empty($_POST['note'])) {
        $errors[] = $error_icon . $wo['lang']['please_check_details'];
    }


    if (empty($errors)) {

        if (empty($_POST['title'])) {
            $_POST['title'] = date("Y-m-d H:i:s");
        }

        $nodePadData = array(
            'title' => Wo_Secure($_POST['title']),
            'note' => Wo_Secure($_POST['note']),
            'cby' => $wo['user']['id'],
            'cip' => ipAddress(),
            'cstatus' => 1,
            'cdate' => date("Y-m-d H:i:s")
        );

        if (Wo_saveMyNote($nodePadData)) {
            // $lastId = mysqli_insert_id($sqlConnect);
            if (!empty($_POST['autosave'])) {
                $data = array(
                    'message' => $success_icon . 'note added.',
                    'status' => 200,
                );
            }
            $data = array(
                'message' => $success_icon . 'note added.',
                'status' => 200,
                // 'location' => Wo_SeoLink('index.php?link1=notepad')
                // 'location' => Wo_SeoLink('index.php?link1=edit-note-pad&npid=' . $lastId)
                'location' => Wo_SeoLink('index.php?link1=note-pad')
            );
        }
    } else {
        $data = array(
            'status' => 200,
            'message' => $errors
        );
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
