<?php

require_once "const.php";
$Class = new ConstData();

header_func();

/*接続 */
//PostgreSQL関数で接続
//ローカル
	//$pg_conn = pg_connect("host=localhost port=5433 dbname=accountancy user=postgres password=postgres");
//サーバー
$pg_conn = pg_connect("".ConstData::DB_DATA."");

//------------------------------------------------------------------------------------
//1.JSON受信
	// JSONデータの受信処理
	//file_get_contents()で送信データを受信(JSONの場合はここがミソ。らしい。)
	$json_data = file_get_contents("php://input");
//------------------------------------------------------------------------------------

//2.JSON→配列変換
	// JSON形式データをPHPの配列型に変換
	$array_data = json_decode($json_data);
//------------------------------------------------------------------------------------


try{

	// $getAccessToken = $array_data -> access_token;
    // $getAlgorithm = $array_data -> algorithm;

    // $checkedData = $Class -> auth0Chek($getAccessToken,$getAlgorithm);

    
		$result = pg_query("SELECT * FROM release_note_data ORDER BY version_id DESC LIMIT 1");
		$data = pg_fetch_all($result);
		$all_data= $data[0]["version"];

	// 	//コミット
	// 	pg_query($pg_conn,"COMMIT");
	// if($checkedData["data"] == true){

	// }else{
	// 	$all_data = $checkedData; 
	// }

} catch (Exception $ex) {
    var_dump($ex);
    // pg_query($pg_conn,"ROLLBACK");
    pg_close($pg_conn);
}


//------------------------------------------------------------------------------------
//4.配列→JSON変換
	//応答配列をJSONに変換
 
	$json_value = json_encode($all_data);
  	
//------------------------------------------------------------------------------------
//5.応答処理
	// htmlへの返答する場合。
	// JSON形式で送信するためのヘッダー。これないとerorrになる。
	header("Content-Type: application/json; charset=utf-8");

	// JSONの書きだし
	print_r($json_value);

?>