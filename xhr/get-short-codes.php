<?php 
if ($f == 'get-short-codes') {
    global $sqlConnect;
    $text = $_GET['text'];
    preg_match_all('/#(\w+)/', $text, $matches);
    // echo "<pre>";
    // print_r($matches);die;
    $status = false;
    foreach ($matches[1] as $index => $value) {
        $query = mysqli_query($sqlConnect, " SELECT `presettxt` FROM `preset_details` WHERE `short_code` =  '". $value."' AND cby =" .$wo['user']['id'] . ""); 
        $fetched_data = mysqli_fetch_assoc($query);
        if(mysqli_num_rows($query)==0){
            $array[$index] = $matches[0][$index];
        }else{
            $status = true;
            $presettxt = $fetched_data['presettxt'];

            $encode = [ 'â€¢', 'â€œ', 'â€', 'â€™', 'â€˜', 'â€”', 'â€“', 'â€¢', 'â€¦', 'â€¯', 'â€¨', 'ðŸ˜€' ];
            $decode = [ '•', '“', '”', '’', '‘', '–', '—', '-', '…', ' ', '\n', ''];

            $presettxt = str_replace($encode, $decode, $presettxt);

            $array[] = $presettxt;
        }
    } 
    $newContent = str_replace($matches[0], $array, $text); 
    header("Content-type: application/json");
    echo json_encode(['status' => $status, 'content' => $newContent]);
    exit();
}