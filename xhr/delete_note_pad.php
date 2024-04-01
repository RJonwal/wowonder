<?php $_POST = $_GET;

if ($f == "delete_note_pad") {
    if (empty($_POST['noteId'])) {
        $errors[] = $error_icon . "No access.";
    }
    if (empty($errors)) {
        global $sqlConnect, $wo;
        if ($wo['loggedin'] == false) {
            return false;
        }
        $query_two = mysqli_query($sqlConnect, "DELETE FROM wo_note_pad WHERE `id` = '{$_POST['noteId']}'");

        if ($query_two) {
            $data = array(
                'message' => $success_icon . 'note deleted.',
                'status' => 200,
                'location' => Wo_SeoLink('index.php?link1=notepad')
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
