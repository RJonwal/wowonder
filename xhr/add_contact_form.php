<?php
if ($f == "add_contact_form") {
    if (isset($_POST) && Wo_CheckSession($hash_id) === true) {
        global $sqlConnect;

        if (empty($_POST['first_name']) or empty($_POST['last_name']) or empty($_POST['email']) or empty($_POST['message'])) {
            $errors[] = $error_icon . $wo['lang']['please_check_details'];
        } else {
            if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = $error_icon . $wo['lang']['email_invalid_characters'];
            }
            if (empty($errors)) {
                $row = [
                    'first_name' => $_POST['first_name'],
                    'last_name'  => $_POST['last_name'],
                    'email'      => $_POST['email'],
                    'message'    => addslashes($_POST['message']),
                    'created_by' => $wo["user"]["id"],
                ];
                $fields = '`' . implode('`, `', array_keys($row)) . '`';
                $values   = '"' . implode('", "', $row) . '"';
                
                $q = "INSERT INTO " . T_CONTACT_DETAIL . " ({$fields}) VALUES ({$values})";

                if (mysqli_query($sqlConnect, $q)) {
                    $first_name = $_POST['first_name'];
                    $last_name  = $_POST['last_name'];
                    $email      = $_POST['email'];
                    $message    = $_POST['message'];
                    $name       = $first_name . ' ' . $last_name;

                    $email_template = file_get_contents($wo['config']['site_url'] . '/admin-panel/partials/email-templates/contact_form.php');
                    $email_content = str_replace(
                        [ '[SITE_NAME]', '[NAME]', '[EMAIL]', '[MESSAGE]' ], 
                        [ $wo['config']['siteName'], $name, $email, $message], 
                        $email_template
                    );
                    
                    $send_message_data = array(
                        'from_email' => $wo['config']['siteEmail'],
                        'from_name' => $name,
                        'reply-to' => $email,
                        'to_email' => CONTACT_EMAIL,
                        'to_name' => $wo['config']['siteName'],
                        'subject' => 'Contact Form new message',
                        'charSet' => 'utf-8',
                        'message_body' => $email_content,
                        'is_html' => true
                    );
                    $send = Wo_SendMessage($send_message_data);

                    if($send){
                        $data = array(
                            'status' => 200,
                            // 'message' => $success_icon . 'Contact details send to admin'
                            'message' => $success_icon . $wo['lang']['email_sent']
                        );
                    } else {
                        $errors[] = $error_icon . "Something went wrong!";
                    }
                    
                } else {
                    $errors[] = $error_icon . "Something went wrong!";
                }
            }
        }
    }
    header("Content-type: application/json");
    if (isset($errors) && !empty($errors)) {
        echo json_encode(array(
            'errors' => $errors
        ));
    } else {
        echo json_encode($data);
    }
    exit();
}