<?php

    //接続情報
    $DB_HOST = "hostname";
    $DB_USER = "user_name";
    $DB_PASS = "user_password";
    $DB_NAME = "database_name";

    //DB接続をラクにするために一連の処理をクラス化

    class easyDB{
        public $mysqli;         //mysqli()
        public $mode;           //戻り値の形式（やっぱJSON？）
        public $count;          //SQLで何とかかんとかした行数
        public $err_msg = "";   //接続エラーの時とかここにエラーメッセージを入れておく

        //コンストラクタ
        function __construct($host,$user,$pwd,$db){
            $this->mode = $mode;

            //DBに接続を試みる（ホスト名，ユーザ名，パスワード，DB）
            $this->mysqli = new mysqli($host,$user,$pwd,$db);

            if($this->mysqli->connect_error){   //接続失敗
                echo $this->mysqli->connect_error;
                exit;   //エラー出力はしない（面倒臭いのとセキュリティ考慮）
            }else{
                $this->mysqli->set_charset("utf-8");
                //utf-8で統一したい（野望）
            }
        }

        //デストラクタ
        function __destruct(){
            //接続のクローズ
            $this->mysqli->close();
        }

        function bind_query($sql, ...$bind_ags){
            //SQL文のプリペア
            if($stmt = $this->mysqli->prepare($sql)){
                //バインドする変数の型を判別して$args_strにセット
                $args_str = $this->getArgTypeStr($bind_ags);

                //バインドする変数の型パラメタと変数たちを一つの配列にまとめる
                $stmtParams = array($args_str);                         //まず型パラメタだけ埋め込んで
                
                //引数として渡されていたバインドする変数をマージ
                foreach($bind_ags as $key=>$vals){                      //配列ある分だけ
                    $stmtParams[] = &$bind_ags[$key];                   //要素を付け足し
                }
                
                //call_user_func_array()を使用し，配列を引数の集合として関数に投げる
                call_user_func_array(array($stmt,'bind_param'),$stmtParams);

            }else{
                $this->err_msg = $this->mysqli->connect_error;
                return false;
            }
            
            //実行
            $stmt->execute();

            $resp = 0;
            $result_array = array();
            $column_name = array();  //カラム名を入れる配列

            $query_result = $stmt->get_result();
            //var_dump($query_result);
            if(!$query_result){      //SELECT関連のクエリではなかった
                $resp = 0;
            }else{                  //SELECTクエリが成功

                $finfo = $query_result->fetch_fields();
                foreach ($finfo as $val) {
                    $column_name[] = $val->name;
                }

                $inum = 0;
                while ($row = $query_result->fetch_array(MYSQLI_NUM)){
                    $jnum = 0;
                    foreach ($row as $r){
                        $result_array[$inum][$column_name[$jnum]] = $r;
                        $jnum++;
                    }
                    $inum++;
                }
                $resp = 1;
            }
            
            $stmt->store_result();
            //影響のあった行数を格納
            $this->count = $stmt->num_rows;

            //終了
            $stmt->close();

            if($resp==0){
                return $query_result;
            }else{
                return $result_array;
            }
        }

        //バインドする変数の型を表すパラメタを生成
        function getArgTypeStr($vv){
            $typeC = "";    //返却用
            //配列要素を一個一個見ていく
            foreach($vv as $v){
                if(is_int($v)){           //整数
                    $typeC .= "i";
                }else if(is_double($v)){  //小数（double）
                    $typeC .= "d";
                }else{                    //あとは文字列
                    $typeC .= "s";
                }
            }
            return $typeC;
        }
        
    }

?>