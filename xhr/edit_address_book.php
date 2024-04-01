<?php
if ($f == "edit_address_book") {

    if (empty($_POST['addressId'])) {
        $errors[] = $error_icon . "No access.";
    }

    if (empty($_POST['name']) || empty($_POST['phone_number'])) {
        $errors[] = $error_icon . $wo['lang']['please_check_details'];
    }
    if (!empty($_POST['email'])) {
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = $error_icon . $wo['lang']['email_invalid_characters'];
        }
    }
    if (empty($errors)) {
        $query = mysqli_query($sqlConnect, "SELECT * FROM wo_address_book WHERE `id` = '{$_POST['addressId']}'");
        $fetched_data = mysqli_fetch_assoc($query);

        
        if (isset($fetched_data['id']) && $fetched_data['id'] > 0) {
            $updateAddress = $db->where('id', $fetched_data['id'])->update(
                T_USER_ADDRESS_BOOK,
                array(
                    'user' => Wo_Secure($wo['user']['id']),
                    'company' => Wo_Secure($_POST['company']),
                    'name' => Wo_Secure($_POST['name']),
                    'phone_number' =>  Wo_Secure($_POST['phone_number']),
                    'email' => Wo_Secure($_POST['email']),
                    'about' =>  Wo_Secure($_POST['about'])
                )
            );

            if ($updateAddress) {
                $data = array(
                    'message' => $success_icon . 'Contact updated.',
                    'status' => 200,
                    'location' => Wo_SeoLink('index.php?link1=addressbook')
                );
            }
        }
    } else {
        $data = array(
            'status' => 200,
            'message' => $errors,
            'location' => Wo_SeoLink('index.php?link1=edit-address-book&abid='.$_POST['addressId'])

        );
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}