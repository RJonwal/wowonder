<?php 
if ($f == 'update_user_information_startup' && Wo_CheckSession($hash_id) === true) {
    if (isset($_POST['user_id'])) {
        $Userdata = Wo_UserData($_POST['user_id']);
        if (!empty($Userdata['user_id'])) {
			
            $age_data = '00-00-0000';
            if (!empty($_POST['birthday']) && preg_match('@^\s*(3[01]|[12][0-9]|0?[1-9])\-(1[012]|0?[1-9])\-((?:19|20)\d{2})\s*$@', $_POST['birthday'])) {
               $newDate = date("Y-m-d", strtotime($_POST['birthday']));
               $age_data = $newDate;
            }
            else{
                if (!empty($_POST['age_year']) || !empty($_POST['age_day']) || !empty($_POST['age_month'])) {
                    if (empty($_POST['age_year']) || empty($_POST['age_day']) || empty($_POST['age_month'])) {
                        $errors[] = $error_icon . $wo['lang']['please_choose_correct_date'];
                    } else {
                        $age_data = $_POST['age_year'] . '-' . $_POST['age_month'] . '-' . $_POST['age_day'];
                    }
                }
            } 
            $Update_data = array(
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'country_id' => $_POST['country'],
                'birthday' => $age_data,
                'start_up_info' => 1
            );
            if (Wo_UpdateUserData($_POST['user_id'], $Update_data)) {
                $data = array(
                    'status' => 200
                );
			
				$field_data = array();
				if (!empty($_POST['custom_fields'])) {
					$fields = Wo_GetProfileFields('profile');
					foreach ($fields as $key => $field) {
						$name = $field['fid'];
						if (isset($_POST[$name])) {
							if (mb_strlen($_POST[$name]) > $field['length']) {
								$errors[] = $error_icon . $field['name'] . ' field max characters is ' . $field['length'];
							}
							$field_data[] = array(
								$name => $_POST[$name]
							);
						}
					}
				}
				if (!empty($field_data)) {
					$insert = Wo_UpdateUserCustomData($_POST['user_id'], $field_data);
				}
			}
        }
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
