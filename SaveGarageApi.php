<?php
    // constファイルの読み込み
    require_once "const.php";
    $Class = new ConstData();

    // header関数の読み込み
    header_func();



    // DBとの接続 ////////////////////////////////////////////////////////
    
    //接続 
    $pg_conn = pg_connect("".ConstData::DB_DATA."");

    // JSON受信
    $json_data = file_get_contents("php://input");
    // JSONを配列に変換
	$array_data = json_decode($json_data);

    ////////////////////////////////////////////////////////////////////


    //　メンテナンスで使用情報の祖帰化
    $all_data = array();


    // トランザクション
    try{

        // 受け取った配列から認証に必要なデータを取得
        $getAccessToken = $array_data -> access_token;
        $getAlgorithm = $array_data -> algorithm;
    
        // Auth0認証
        $checkedData = $Class -> auth0Chek($getAccessToken,$getAlgorithm);


        // 認証に成功したら
        if($checkedData["data"]){

            // メンテナンス状況のチェック ///////////////////////////////////////

            $loginUserAuthority = $array_data->loginUserAuthority;
            // メンテナンス情報の取得
            $result = pg_query("SELECT * FROM maintenance WHERE id = 1 ");
            $data = pg_fetch_all($result);

            // メンテナンス中かつ開発者じゃない場合
            if($data[0]["flag"] != 0 && $loginUserAuthority != 1 ){

                $all_data = [      
                    "data"=> false,
                    "reDirect"=> "./home",
                    "reDirect"=> "./maintenance",
                    "message"=> "メンテナンス中のため実行できませんでした。\n5秒後にメンテナンスページに遷移します。", 
                ];

            }else{

                //呼び出すAPIのフラグ
                $flag = $array_data -> flag;
    
                // クエリの開始
                pg_query($pg_conn,"BEGIN");
    
                switch($flag){
                
 
                    // <summery>
                    // BookingGarage・社有車予約情報の保存
                    // </summery>
                    case 'SaveReserveGarage':

                        // 保存させたいデータ (配列)
                        $SaveDataArray = $array_data->SaveData;

                        // 現在の日時
                        $today = date('Y-m-d H:i:s');

                        try {
                            // ループ処理で複数レコードを保存
                            foreach ($SaveDataArray as $SaveData) {

                                $CarId = $SaveData->car_id;
                                $UseStartDay = $SaveData->startDate;
                                $StartTime = $SaveData->startTime;
                                $UseEndDay = $SaveData->endDate;
                                $UseEndTime = $SaveData->endTime;
                                $Driver = $SaveData->driver;
                                $Place = $SaveData->place;
                                $NumOfPeople = $SaveData->people;
                                $Luggage = $SaveData->luggage;
                                $Memo = $SaveData->note;
                                $CreatedUserId = $SaveData->createdUserId;
                                $ETC = $SaveData->etc;

                                // マスター情報取得クエリ
                                $sql = "INSERT INTO reserve (
                                    car_id,
                                    use_start_day,
                                    start_time,
                                    use_end_day,
                                    end_time,
                                    driver,
                                    place,
                                    number_of_people,
                                    luggage,
                                    memo,
                                    created_day,
                                    created_user_id,
                                    etc
                                ) VALUES (
                                    '$CarId',
                                    '$UseStartDay',
                                    '$StartTime',
                                    '$UseEndDay',
                                    '$UseEndTime',
                                    '$Driver',
                                    '$Place',
                                    '$NumOfPeople',
                                    '$Luggage',
                                    '$Memo',
                                    '$today',
                                    '$CreatedUserId',
                                    '$ETC'
                                )";

                                // 実行
                                $result1 = pg_query($pg_conn, $sql);

                                // クエリ失敗時の処理
                                if ($result1 === false) {
                                    $all_data = [
                                        'status' => 0,
                                        'data' => [pg_last_error($pg_conn)],
                                        'message' => '登録失敗'
                                    ];
                                    // ロールバックして処理を中断
                                    pg_query($pg_conn, "ROLLBACK");
                                    pg_close($pg_conn);
                                    echo json_encode($all_data);
                                    exit;
                                }
                            }

                            // すべてのクエリが成功した場合
                            $all_data = [
                                'status' => 1,
                                'data' => [true],
                                'message' => '登録成功'
                            ];
                            
                            // コミット
                            pg_query($pg_conn, "COMMIT");

                        } catch (Exception $ex) {
                            var_dump($ex);

                            // ロールバック
                            pg_query($pg_conn, "ROLLBACK");
                            pg_close($pg_conn);
                        }

                    break;

                    // <summery>
                    // BookingGarage・社有車予約情報の変更
                    // </summery>
                    case 'EditReserveGarage':

                        // 保存させたいデータ
                        $SaveData = $array_data -> SaveData;

                        // 現在の日時
                        $today = date('Y-m-d H:i:s');

                        try
                        {

                            $ReserveId = $SaveData->ReserveId;
                            $UseStartDay = $SaveData->StartDate;
                            $StartTime = $SaveData->StartTime;
                            $UseEndDay = $SaveData->EndDate;
                            $UseEndTime = $SaveData->EndTime;
                            $Driver = $SaveData->Driver;
                            $Place = $SaveData->Place;
                            $NumOfPeople = $SaveData->People;
                            $Luggage = $SaveData->Luggage;
                            $Memo = $SaveData->Note;
                            $CreatedUserId = $SaveData->CreatedUserId;
                            $Etc = $SaveData->Etc;


                            // マスター情報取得クエリ
                            $sql = "UPDATE reserve SET
                                use_start_day = '$UseStartDay',
                                start_time = '$StartTime',
                                use_end_day = '$UseEndDay',
                                end_time = '$UseEndTime',
                                driver = '$Driver',
                                place = '$Place',
                                number_of_people = '$NumOfPeople',
                                luggage = '$Luggage',
                                memo = '$Memo',
                                edit_day = '$today',
                                edit_user_id = '$CreatedUserId',
                                etc = '$Etc'

                                WHERE reserve_id = '$ReserveId'
                            ";
                            

                            // 実行
                            $result1 = pg_query($pg_conn, $sql);

                             //クエリ失敗
                            if ($result === false) {
                                $all_data = [
                                'status' => 0,
                                'data' => [pg_last_error($pg_conn)],
                                'message' => $returnMessage
                                ];
                            }
                            //クエリ成功
                            else{
                                $all_data = [
                                    'status' => 1,
                                    'data' => [true],
                                    'message' => '登録成功'
                                ];
    
                                //コミット
                                pg_query($pg_conn,"COMMIT");
                            }
                        } 
                        catch (Exception $ex) {
    
                            var_dump($ex);
    
                            // クエリのロールバック
                            pg_query($pg_conn,"ROLLBACK");
                            pg_close($pg_conn);
    
                        }

                    break;

                    // <summery>
                    // BookingGarage・社有車予約情報の削除
                    // </summery>
                    case 'DeleteReserveGarage':

                        // 保存させたいデータ
                        $SaveData = $array_data -> SaveData;

                        // 現在の日時
                        $today = date('Y-m-d H:i:s');

                        try
                        {

                            $ReserveId = $SaveData->Reserve_Id;
                            $CreatedUserId = $SaveData->CreatedUserId;


                            // マスター情報取得クエリ
                            $sql = "UPDATE reserve SET
                                cancel_day = '$today',
                                cancel_user_id = '$CreatedUserId'


                                WHERE reserve_id = '$ReserveId'
                            ";
                            

                            // 実行
                            $result1 = pg_query($pg_conn, $sql);

                             //クエリ失敗
                            if ($result === false) {
                                $all_data = [
                                'status' => 0,
                                'data' => [pg_last_error($pg_conn)],
                                'message' => $returnMessage
                                ];
                            }
                            //クエリ成功
                            else{
                                $all_data = [
                                    'status' => 1,
                                    'data' => [true],
                                    'message' => '登録成功'
                                ];
    
                                //コミット
                                pg_query($pg_conn,"COMMIT");
                            }
                        } 
                        catch (Exception $ex) {
    
                            var_dump($ex);
    
                            // クエリのロールバック
                            pg_query($pg_conn,"ROLLBACK");
                            pg_close($pg_conn);
    
                        }

                    break;

                    // <summery>
                    // Cars・マスター社有車情報の追加
                    // </summery>
                    case 'AddMasterCar':

                        // 保存させたいデータ
                        $SaveData = $array_data -> SaveData;

                        // 現在の日時
                        $today = date('Y-m-d H:i:s');

                        try
                        {

                            $CarName = $SaveData->Car_name;
                            $CarNo = $SaveData->Car_no;
                            $ETC = $SaveData->Etc;
                            $Garages = $SaveData->Garages;
                            $SeatOfNumber = $SaveData->SeatOfNumber;
                            $CreateUserId = $SaveData->createdUserId;
                            $UseDisplay = $SaveData->Use_Display;
                            $UnLimited = date('Y-m-d', strtotime($SaveData->unlimited_day));

                            // 使用制限日が存在する場合
                            if (!empty($SaveData->limited_day)) {
                                $Limited = date('Y-m-d', strtotime($SaveData->limited_day));

                                // マスター情報取得クエリ
                                $sql = "INSERT INTO cars (
                                    car_name,
                                    car_no,
                                    etc,
                                    garages,
                                    creat_day,
                                    seat_of_number,
                                    create_user_id,
                                    use_display,
                                    unlimited_day,
                                    limited_day
                                    )
                                    VALUES(
                                    '$CarName',
                                    '$CarNo',
                                    '$ETC',
                                    '$Garages',
                                    '$today',
                                    '$SeatOfNumber',
                                    '$CreateUserId',
                                    $UseDisplay,
                                    '$UnLimited',
                                    '$Limited'
                                    )
                                ";
                            }
                            else {

                                // マスター情報取得クエリ
                                $sql = "INSERT INTO cars (
                                    car_name,
                                    car_no,
                                    etc,
                                    garages,
                                    creat_day,
                                    seat_of_number,
                                    create_user_id,
                                    use_display,
                                    unlimited_day
                                    )
                                    VALUES(
                                    '$CarName',
                                    '$CarNo',
                                    '$ETC',
                                    '$Garages',
                                    '$today',
                                    '$SeatOfNumber',
                                    '$CreateUserId',
                                    $UseDisplay,
                                    '$UnLimited'
                                    )
                                ";
                            }   


                            // 実行
                            $result1 = pg_query($pg_conn, $sql);

                             //クエリ失敗
                            if ($result === false) {
                                $all_data = [
                                'status' => 0,
                                'data' => [pg_last_error($pg_conn)],
                                'message' => $returnMessage
                                ];
                            }
                            //クエリ成功
                            else{
                                $all_data = [
                                    'status' => 1,
                                    'data' => [true],
                                    'message' => '登録成功'
                                ];
    
                                //コミット
                                pg_query($pg_conn,"COMMIT");
                            }
                        } 
                        catch (Exception $ex) {
    
                            var_dump($ex);
    
                            // クエリのロールバック
                            pg_query($pg_conn,"ROLLBACK");
                            pg_close($pg_conn);
    
                        }

                    break;

                    // <summery>
                    // Cars・マスター社有車情報の変更
                    // </summery>
                    case 'EditMasterCar':

                        // 保存させたいデータ
                        $SaveData = $array_data -> SaveData;

                        // 現在の日時
                        $today = date('Y-m-d H:i:s');

                        try {
                            $CarId = $SaveData->Car_ID;
                            $CarName = $SaveData->Car_name;
                            $CarNo = $SaveData->Car_no;
                            $ETC = $SaveData->ETC;
                            $Garages = $SaveData->Garages;
                            $SeatOfNumber = $SaveData->SeatOfNumber;
                            $EditUserId = $SaveData->EditUserId;
                            $UseDisplay = $SaveData->Use_Display;
                            $UnlimitedDay = date('Y-m-d', strtotime($SaveData->UnLimitedDay));
                    
                            $hasLimitedDay = !empty($SaveData->limitedDay); // 使用制限日が存在する場合
                            $hasMileage    = !empty($SaveData->New_milage); // 走行距離が存在する場合
                    
                            if ($hasLimitedDay) {
                                $LimitedDay = date('Y-m-d', strtotime($SaveData->limitedDay));
                            } else {
                                $LimitedDay = null;
                            }
                    
                            // ベースの UPDATE 文
                            $sql = "UPDATE cars SET
                                car_name = '$CarName',
                                car_no = '$CarNo',
                                etc = '$ETC',
                                garages = '$Garages',
                                seat_of_number = '$SeatOfNumber',
                                edit_day = '$today',
                                edit_user_id = '$EditUserId',
                                use_display = $UseDisplay,
                                unlimited_day = '$UnlimitedDay',
                                limited_day = " . ($LimitedDay ? "'$LimitedDay'" : "NULL");
                    
                            // 走行距離があれば new_mileage も更新
                            if ($hasMileage) {
                                $NewMilage = $SaveData->New_milage;
                                $sql .= ", new_mileage = '$NewMilage'";
                            }
                    
                            $sql .= " WHERE car_id = '$CarId'";
                    
                            // 走行距離履歴テーブルの登録も必要な場合
                            if ($hasMileage) {
                                $DifferenceMilage = $SaveData->Difference_milage;
                    
                                $sql2 = "INSERT INTO cars_mileage_history (
                                    car_id,
                                    mileage,
                                    difference_mileage,
                                    add_day,
                                    add_user_id
                                ) VALUES (
                                    '$CarId',
                                    '$NewMilage',
                                    '$DifferenceMilage',
                                    '$today',
                                    '$EditUserId'
                                )";
                            }
                    
                            // --- 実行 ---
                            $result1 = pg_query($pg_conn, $sql);
                    
                            // 走行距離履歴も同時に保存
                            if ($hasMileage && isset($sql2)) {
                                $result2 = pg_query($pg_conn, $sql2);
                            }
                    
                            // クエリ失敗時のチェック
                            if ($result1 === false || (isset($result2) && $result2 === false)) {
                                $all_data = [
                                    'status' => 0,
                                    'data' => [pg_last_error($pg_conn)],
                                    'message' => '登録に失敗しました。'
                                ];
                            } else {
                                $all_data = [
                                    'status' => 1,
                                    'data' => [true],
                                    'message' => '登録成功'
                                ];
                    
                                // コミット
                                pg_query($pg_conn, "COMMIT");
                            }
                        }
                        catch (Exception $ex) {
    
                            var_dump($ex);
    
                            // クエリのロールバック
                            pg_query($pg_conn,"ROLLBACK");
                            pg_close($pg_conn);
    
                        }

                    break;

                    // <summery>
                    // Cars・マスター削除
                    // </summery>
                    case 'DeleteMasterCar':

                        // 保存させたいデータ
                        $SaveData = $array_data -> SaveData;

                        // 現在の日時
                        $today = date('Y-m-d H:i:s');

                        try
                        {

                            $MasterCarID = $SaveData->MasterCarID;
                            $UserId = $SaveData->DeletedUserId;


                            // マスター情報取得クエリ
                            $sql = "UPDATE cars SET
                                un_useble_day = '$today',
                                un_useble_user_id = '$UserId',
                                display_no = 0

                                WHERE car_id = '$MasterCarID'
                            ";
                            

                            // 実行
                            $result1 = pg_query($pg_conn, $sql);

                             //クエリ失敗
                            if ($result === false) {
                                $all_data = [
                                'status' => 0,
                                'data' => [pg_last_error($pg_conn)],
                                'message' => $returnMessage
                                ];
                            }
                            //クエリ成功
                            else{
                                $all_data = [
                                    'status' => 1,
                                    'data' => [true],
                                    'message' => '登録成功'
                                ];
    
                                //コミット
                                pg_query($pg_conn,"COMMIT");
                            }
                        } 
                        catch (Exception $ex) {
    
                            var_dump($ex);
    
                            // クエリのロールバック
                            pg_query($pg_conn,"ROLLBACK");
                            pg_close($pg_conn);
    
                        }

                    break;

                    // <summery>
                    // マスター・車の番号を変更
                    // </summery>
                    case 'EditMasterCarDisplayNo':
                        $allResult = array();
                        // 保存させたいデータ
                        $SaveData = $array_data -> SaveData;
 
                        // 現在の日時
                        $today = date('Y-m-d H:i:s');
 
                        try
                        {
                            //配列の為、ループさせた
                            foreach ($SaveData as $i => $row) {
                                $CarId = $row->Car_id;
                                $DisplayNo = $row-> DisplayNo;
 
                                // マスター情報取得クエリ
                                $sql = "UPDATE cars SET
                                    display_no = '$DisplayNo'
                                    WHERE car_id = '$CarId'
                                ";
 
                                // 実行
                                $result1 = pg_query($pg_conn, $sql);
                                $allResult[$i] = $result1;
                            }
 
                            // 1つでも false が含まれていたら失敗とする
                            if (in_array(false, $allResult, true)) {
                                pg_query($pg_conn, "ROLLBACK");
                                $all_data = [
                                    'status' => 0,
                                    'data' => [pg_last_error($pg_conn)],
                                    'message' => 'クエリ実行中に失敗しました'
                                ];
                            }
                            // 全てtrueの場合、成功
                            else {
                                $all_data = [
                                    'status' => 1,
                                    'data' => [true],
                                    'message' => '登録成功'
                                ];
 
                                //コミット
                                pg_query($pg_conn,"COMMIT");
                            }
 
                         
                        }
                        catch (Exception $ex) {
   
                            var_dump($ex);
   
                            // クエリのロールバック
                            pg_query($pg_conn,"ROLLBACK");
                            pg_close($pg_conn);
   
                        }
 
                    break;


                }                     
            }
        }
        
        // 認証に失敗したら
        else{

            // チェックの中身を返す
            $all_data = $checkedData; 
        
        }



    } catch(Exception $e) {
        
        $all_data = array($e);

    }




    // フロントに返す //////////////////////////////////////////////////
	
    // 配列をJSONに変換
    $json_value = json_encode($all_data);

    // JSON形式で返信するためのヘッダー
    header("Content-Type: application/json; charset=utf-8");

	// JSONの書きだし
	print_r($json_value);

    ////////////////////////////////////////////////////////////////////




?>