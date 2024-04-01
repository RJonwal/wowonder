<?php require('mysql_functions.php');
$msg='';
$active_user_id= $_SESSION['admin_id'];
function pr($data)
{
	echo '<pre style="margin-left: 257px;">';
	print_r($data);
	echo "</pre>";
	exit;
}
 // Make a safe SQL

$invoice_prefix = 'SPPL/2019-20/';
 
 function test_input($raw_data)
 {
	 foreach($raw_data as $key=>$value)
     {
        $value = str_replace("'","`",$value);
        $value = trim($value);
        $value = stripslashes($value);
        $value = htmlentities($value,ENT_QUOTES);
        if (!is_numeric($value))
 		{ 
			$value =mysqli_real_escape_string(get_connection(),$value);
		}
	   $filter_data[$key]= $value;
     }
	 return $filter_data;
 }
  //Get Ip address

  function ipAddress(){
    $ipaddress = '';
    if ($_SERVER['HTTP_CLIENT_IP']){

        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];}

    else if($_SERVER['HTTP_X_FORWARDED_FOR']){

        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];}

    else if($_SERVER['HTTP_X_FORWARDED']){

        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];}

    else if($_SERVER['HTTP_FORWARDED_FOR']){

        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];}

    else if($_SERVER['HTTP_FORWARDED']){

        $ipaddress = $_SERVER['HTTP_FORWARDED'];}

    else if($_SERVER['REMOTE_ADDR']){

        $ipaddress = $_SERVER['REMOTE_ADDR'];}

    else{

        $ipaddress = 'UNKNOWN';

	}
    return $ipaddress;

}

//Redirect to page & message
function reDirect($path) {
		echo '<script>window.open("'.$path.'","_top");</script>';
}
//Redirect to page & message
function giveAlert($message) {
		echo '<script>alert("'.$message.'");</script>';
}


function alert($message) {
	echo '<script>alert("'.$message.'");</script>';
}


//SET SUCCESS NOTICES

function set_msg($msga) 	
{
	$_SESSION['msg'] = '';
	$_SESSION['msg'] = $msga;
}

function table_exist($table)
{
	$connection=get_connection();
	//$qry =  mysqli_query($connection,"select * from client_management where status=1 order by name" );
    $sql = "show tables like '".$table."'";
    $res = $connection->query($sql);
    return ($res->num_rows > 0);
}



function column_exist($table,$column){

	$connection=get_connection();
    //$qry =  mysqli_query($connection,"select * from client_management where status=1 order by name" );
    $sql = "SHOW COLUMNS FROM ".$table." LIKE '".$column."'";

    $res = $connection->query($sql);

    return ($res->num_rows > 0);

}

// CREATE TABLE 

function table_create($table,$LIKE){

	$connection=get_connection();

	$sql_table = "SHOW TABLES LIKE '".$table."'";

	if(mysqli_num_rows(mysqli_query($connection,$sql_table))==1){

	 $table_exist=1;

	}else {

	  $table_exist=0;

	}

	if($table_exist==0){

  // table create

	$sql_CREATE = "create TABLE ".$table." like ".$LIKE."";

	$qry =  mysqli_query($connection,$sql_CREATE);

	}
}


//DISPLAY SUCCESS MESSAGE

function display_msg($msg='') 

{

	if (!empty($_SESSION['msg'])) 

	{

		echo "<span class='notices' style='color:#C00'>$_SESSION[msg]</span>";

		unset($_SESSION['msg']);

	}

	else if($msg)

	{

		echo "<span class='notices' style='color:#C00'>$msg</span>";

	}

}


//Generate Password

function random_code($length) {

    $characters = array(

        "A","B","C","D","E","F","G","H","J","K","L","M",

        "N","P","Q","R","S","T","U","V","W","X","Y","Z",

        "1","2","3","4","5","6","7","8","9");

    if ($length < 0 || $length > count($characters)) return null;

    shuffle($characters);

    return implode("", array_slice($characters, 0, $length));

}	



function generateRandomString( $length ) {

    $chars = array_merge(range('a', 'z'), range(0, 9));

    shuffle($chars);

    return implode(array_slice($chars, 0, $length));

}

function generateRandomNumber( $length ) {

    $chars = array_merge(range('0', '9'), range(0, 9));

    shuffle($chars);

    return implode(array_slice($chars, 0, $length));

}

function upload_image($folder_path,$image_id)

{

	$file_name=$_FILES["image"]["name"];

	$extension=end(explode(".", $file_name));

	$file_name=$image_id.".".$extension;

	$path=$folder_path.'/'.$file_name;

	if(file_exists($path))

	{

		unlink($path);		

	}

	$moved=move_uploaded_file($_FILES['image']['tmp_name'],$path);

	if($moved)

	{

		return $file_name;	

	}

}

function upload_multiple_image($folder_path,$image_id,$j)

{	

	  $file_name=$_FILES["image"]["name"]["$j"];

	  $extension=end(explode(".", $file_name));

	//echo $j;

	 $file_name=$image_id.".".$extension;

	//$path=$file_name;

	  $path=$folder_path.'/'.$file_name;

	

	if(file_exists($path))

	{

		unlink($path);		

	}

	//echo $path;

//	var_dump($_FILES['image']['tmp_name']);

	//["$j"]

	$moved=move_uploaded_file($_FILES['image']['tmp_name']["$j"],$path);

	//echo $moved;

	//die;

	if($moved)

	{

		return $file_name;	

	}

}

function check_login(){
	if(empty($_SESSION['user_id'])){
		   reDirect("login.php");			
		}
	}		



function forgot_password($user_email,$password)

{

  $from = ADMIN_EMAIL;

  $to =$user_email;

  $subject = "Password Recovery Email";

  $body = "Dear Admin,<br><br>Your login details :<br> Email ID : $user_email <br> password : $password";

  $headers = "MIME-Version: 1.0" . "\r\n";

  $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

  $headers .= 'From: <'.$from.'>' . "\r\n";

  return mail($to,$subject,$body,$headers); 

}

function send_email_reg_success($name,$email,$enc_id)
{
  $from = Eventful;
  $link = $_SERVER['HTTP_HOST'].'/verify_email.php?id='.$enc_id;
	$to =$email;
  $nm_pass=explode("-pass:", $name);
	$subject = "Registration Successful";
	$body = "Dear ".$nm_pass[0].",
    <br><br>
    Your have successfully registered at vaicon2021 with username as ".$email." and password as (".$nm_pass[1].").
      <br>
    Please login at vaicon2021.co.in to make payment and participate in the conference
      <br><br>
    Regards
      <br>
    Team VAICON 2021";

	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
	$headers .= 'From: <'.$from.'>' . "\r\n";

	$success = mail($to,$subject,$body,$headers);	
  if (!$success) {
      echo  $errorMessage = error_get_last()['message'];
	    }
  else{
    $msg='Message sent successfully !!';
  }
}

function upload_image1($folder_path,$file_name, $file_tempname,$image_id)

{

	//$file_name=$_FILES["image"]["name"];

	//$file_name=$_FILES["image"]["name"];

	$extension=end(explode(".", $file_name));

	$file_name=$image_id.".".$extension;

	

	$path=$folder_path.'/'.$file_name;

	if(file_exists($path))

	{

		unlink($path);		

	}

	$moved=move_uploaded_file($file_tempname,$path);

	if($moved)

	{

		return $file_name;	

	}

}

 
function curPageURL() {
 $pageURL = 'http';
 if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
 $pageURL .= "://";
 if ($_SERVER["SERVER_PORT"] != "80") {
  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 } else {
  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 }
 return $pageURL;
}

function getDayNumber($date){
	$dayNAme = date("l",strtotime($date));
	if($dayNAme == 'Monday'){
		return 1;
	}else if($dayNAme == 'Tuesday'){
		return 2;
	}else if($dayNAme == 'Wednesday'){
		return 3;
	}else if($dayNAme == 'Thursday'){
		return 4;
	}else if($dayNAme == 'Friday'){
		return 5;
	}else if($dayNAme == 'Saturday'){
		return 6;
	}else if($dayNAme == 'Sunday'){
		return 7;
	}
}

function getDatesFromRange($start, $end, $format = 'd-m-Y') {
    $array = array();
    $interval = new DateInterval('P1D');

    $realEnd = new DateTime($end);
    $realEnd->add($interval);

    $period = new DatePeriod(new DateTime($start), $interval, $realEnd);

    foreach($period as $date) { 
        $array[] = $date->format($format); 
    }

    return $array;
}


function insert_log($logtype,$logtable,$lognote,$logrefid,$logstatus,$logby,$usertype){
  //if($action=='manage_activity_log'){
	$insert_array=array(
	'log_type'=>$logtype,
	'log_table'=>$logtable,
	'log_note'=>$lognote,
	'log_ref_id'=>$logrefid,
	'user_type'=>$usertype,
	'status'=>$logstatus,
	'cby'=>$logby,
	'cdate'=>date("Y-m-d H:i:s"),
	'cip'=>ipAddress());
	$update=dbRowInsert('manage_activity_log',$insert_array);
  //}   
}

   
   
  function displayPagination($per_page,$page,$page_url,$total)
 {
    $adjacents = "1"; 
    $page = ($page == 0 ? 1 : $page);  
    $start = ($page - 1) * $per_page;        
  
    $prev = $page - 1;       
    $next = $page + 1;
    $setLastpage = ceil($total/$per_page);
    $lpm1 = $setLastpage - 1;
     $setPaginate = "";
     if($setLastpage > 1)
     {
      //$setPaginate .= "<span>Showing Page $page of $setLastpage</span>"; 
      $setPaginate .= "<ul class='pagination'>";
      if ($setLastpage < 7 + ($adjacents * 2))
      { 
       for ($counter = 1; $counter <= $setLastpage; $counter++)
       {
        if ($counter == $page)
         $setPaginate.= "<li class='active'><a>$counter</a></li>";
        else
         $setPaginate.= "<li><a href='{$page_url}p=$counter'>$counter</a></li>";     
       }
      }
      else if($setLastpage > 5 + ($adjacents * 2))
      {
       if($page < 1 + ($adjacents * 2))  
       {
        for ($counter = 1; $counter < 4 + ($adjacents * 2); $counter++)
        {
         if ($counter == $page)
          $setPaginate.= "<li class='active' ><a>$counter</a></li>";
         else
          $setPaginate.= "<li><a href='{$page_url}p=$counter'>$counter</a></li>";     
        }
      //  $setPaginate.= "<li class='dot'>...</li>";
        $setPaginate.= "<li><a href='{$page_url}p=$lpm1'>$lpm1</a></li>";
        $setPaginate.= "<li><a href='{$page_url}p=$setLastpage'>$setLastpage</a></li>";  
       }
       else if($setLastpage - ($adjacents * 2) > $page && $page > ($adjacents * 2))
       {
        $setPaginate.= "<li><a href='{$page_url}'>1</a></li>";
        $setPaginate.= "<li><a href='{$page_url}'>2</a></li>";
     //   $setPaginate.= "<li class='dot'>...</li>";
        for ($counter = $page - $adjacents; $counter <= $page + $adjacents; $counter++)
        {
         if ($counter == $page)
          $setPaginate.= "<li class='active'><a>$counter</a></li>";
         else
          $setPaginate.= "<li><a href='{$page_url}p=$counter'>$counter</a></li>";     
        }
      //  $setPaginate.= "<li class='dot'>..</li>";
        $setPaginate.= "<li><a href='{$page_url}p=$lpm1'>$lpm1</a></li>";
        $setPaginate.= "<li><a href='{$page_url}p=$setLastpage'>$setLastpage</a></li>";  
       }
       else
       {
        $setPaginate.= "<li><a href='{$page_url}p=1'>1</a></li>";
        $setPaginate.= "<li><a href='{$page_url}p=2'>2</a></li>";
      //  $setPaginate.= "<li class='dot'>..</li>";
        for ($counter = $setLastpage - (2 + ($adjacents * 2)); $counter <= $setLastpage; $counter++)
        {
          if ($counter == $page)
           $setPaginate.= "<li class='active'><a>$counter</a></li>";
          else
           $setPaginate.= "<li><a href='{$page_url}p=$counter'>$counter</a></li>";     
        }
       }
      }
      
      if ($page < $counter - 1)
      { 
	    $setPaginate.= "<li><a href='{$page_url}p=$next'>Next</a></li>";
	    $setPaginate.= "<li><a href='{$page_url}p=$setLastpage'>Last</a></li>";
      }
      else
      {
          $setPaginate.= "<li class='active'><a>Next</a></li>";
          $setPaginate.= "<li class='active'><a>Last</a></li>";
      }
      $setPaginate.= "</ul>\n";  
     }
      return $setPaginate;
   }
  function cstatus(){
		return array(
		 "0"=>"Deactive", 
		 "1"=>"Active"
		 
		 );
	  }
function status_data($status){
	$status_id=status();
	return $status_id[$status];
	}
	 
function position(){
    return array( 
     "1"=>"REGISTRATION FOR CME (26TH DECEMBER 2020)",
     "2"=>"REGISTRATION FOR SPINE ENDOSCOPY WORKSHOP (27TH DECEMBER 2020)",
     "3"=>"REGISTRATION FOR BOTH CME & WORKSHOP (26TH & 27TH DECEMBER 2020) ",
     //"4"=>"Nurses and Paramedical",
     "5"=>"Test User"
     );
}
function position_cost(){
    return array( 
     "1"=>"1000",
     "2"=>"5000",
     "3"=>"6000",
     //"4"=>"525",
     "5"=>"1"
     );
}
function position_cost_data($status){
	$status_id=position_cost();
	return $status_id[$status];
}
function prefix(){
    return array( 
     "1"=>"Mr.",
     "2"=>"Mrs.",
     "3"=>"Ms.",
     "4"=>"Dr.",
     "5"=>"Prof."
     );
}	
function pay_status(){
    return array(
     "0"=>"Success", 
     "1"=>"Failure"
     );
    }
function lobby_field(){
    return array(
     "1"=>"Link1", 
     "2"=>"Link2",
     "3"=>"Link3",
     "4"=>"popup1",
     "5"=>"popup2",
     "6"=>"popup3"
     );
    }
function user_type(){
    return array(
     "1"=>"admin", 
     "2"=>"lecturer"
     );
}
function getlobbydata()
{
  $data=array();
  $i=0;
  $result=getaxecuteQuery_fn("select * from lobby_details order by id desc limit 5");
  while($row=mysqli_fetch_assoc($result))
  {
    $data[0]['bg']=$row['background_img'];
    if($row['lobby_field']=="Link1")
    {
      $data['Link1']['field']=$row['lobby_field'];
      $data['Link1']['field']=$row['lobby_field'];
      $data['Link1']['title']=$row['title'];
      $data['Link1']['content']=$row['content'];
    }
    if($row['lobby_field']=="Link2")
    {
      $data['Link2']['field']=$row['lobby_field'];
      $data['Link2']['title']=$row['title'];
      $data['Link2']['content']=$row['content'];
    }
    if($row['lobby_field']=="popup1")
    {
      $data['popup1']['field']=$row['lobby_field'];
      $data['popup1']['title']=$row['title'];
      $data['popup1']['content']=$row['content'];
    }
    if($row['lobby_field']=="popup2")
    {
      $data['popup2']['field']=$row['lobby_field'];
      $data['popup2']['title']=$row['title'];
      $data['popup2']['content']=$row['content'];
    }
    if($row['lobby_field']=="popup3")
    {
      $data['popup3']['field']=$row['lobby_field'];
      $data['popup3']['title']=$row['title'];
      $data['popup3']['content']=$row['content'];
    }
    $i++;
  }
  return $data;
}
function getlink1data()
{
  $result=getaxecuteQuery_fn("select * from field_details where lobby_field='4' order by id desc limit 1");
  if(mysqli_num_rows($result)>0)
    return mysqli_fetch_assoc($result);
  else
    return FALSE;
}
function getlink2data()
{
  $result=getaxecuteQuery_fn("select id,background_img from field_details where lobby_field='1' order by id desc");
  if(mysqli_num_rows($result)>0)
    return $result;
  else
    return FALSE;
}
function getlink3data()
{
  $result=getaxecuteQuery_fn("select id,background_img from lobby_details where id='5' limit 1");
  if(mysqli_num_rows($result)>0)
    return mysqli_fetch_assoc($result);
  else
    return FALSE;
}
function getlink2dataid($id)
{
  $result=getaxecuteQuery_fn("select * from field_details where lobby_field='1' and id='$id' order by id desc");
  if(mysqli_num_rows($result)>0)
    return mysqli_fetch_assoc($result);
  else
    return FALSE;
}
if($_REQUEST['action']=='update_dorder' && isset($_REQUEST['catid']) && isset($_REQUEST['dorder']) && isset($_REQUEST['ul_id'])){

if($_REQUEST['data_type']=="preset"){
  $query = "UPDATE preset_details SET cdate='".date("Y-m-d H:i:s")."', dorder = '".($_REQUEST['dorder'])."', cat_id = '".($_REQUEST['ul_id'])."' WHERE id = '".$_REQUEST['catid']."'";
  getaxecuteQuery_fn($query);
}else{
  if($_REQUEST['catid']==$_REQUEST['ul_id']){
    //$query = "UPDATE category_details SET cdate='".date("Y-m-d H:i:s")."', dorder = '".($_REQUEST['dorder'])."' WHERE id = '".$_REQUEST['catid']."'";
    $query = "UPDATE category_details SET cdate='".date("Y-m-d H:i:s")."', dorder = '".($_REQUEST['dorder'])."', parent_id = '0'  WHERE id = '".$_REQUEST['catid']."'";
    getaxecuteQuery_fn($query);
  }else{
    $query = "UPDATE category_details SET cdate='".date("Y-m-d H:i:s")."', dorder = '".($_REQUEST['dorder'])."', parent_id = '".($_REQUEST['ul_id'])."' WHERE id = '".$_REQUEST['catid']."'";
    getaxecuteQuery_fn($query);
  }
}
//echo $query;
}
function limit_text ($text,$limit=58){
  $string = strip_tags($text);
  if (strlen($string) > $limit) {

      // truncate string
      $stringCut = substr($string, 0, $limit);
      $endPoint = strrpos($stringCut, ' ');
      //if the string doesn't contain any space then it will cut without word basis.
      $string = $endPoint? substr($stringCut, 0, $endPoint) : substr($stringCut, 0);
      $string .= '...';
  }
  return $string;
}
?>