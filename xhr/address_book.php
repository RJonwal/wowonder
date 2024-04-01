<?php
if ($f == "address_book") {
    if (empty($_POST['name']) || empty($_POST['phone_number'])) {
        $errors[] = $error_icon . $wo['lang']['please_check_details'];
    }
	if(!empty($_POST['email'])){
		if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
			$errors[] = $error_icon . $wo['lang']['email_invalid_characters'];
		}
	}
    if (empty($errors)) {
        $registration_data = array(
            'user' => $wo['user']['id'],
            'company' => Wo_Secure($_POST['company']),
            'name' => Wo_Secure($_POST['name']),
            'phone_number' => Wo_Secure($_POST['phone_number']),
            'email' => Wo_Secure($_POST['email']),
            'about' => Wo_Secure($_POST['about'])
        );
		
        if (Wo_AddressBook($registration_data)) {
            $data = array(
                'message' => $success_icon . 'Contact added.',
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