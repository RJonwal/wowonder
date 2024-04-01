<?php $_POST= $_GET;
if ($f == "delete_address_book") {
    if (empty($_POST['add_id'])) {
        $errors[] = $error_icon . "No access.";
    }
    if (empty($errors)) {
		global $sqlConnect, $wo;
		if ($wo['loggedin'] == false) {
			return false;
		}
        $query_two = mysqli_query($sqlConnect, "DELETE FROM wo_address_book WHERE `id` = '{$_POST['add_id']}'");
		
        if ($query_two) {
            $data = array(
                'message' => $success_icon . 'Contact deleted.',
                'status' => 200,
                'location' => Wo_SeoLink('index.php?link1=addressbook')
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