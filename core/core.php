<?php

// include db connect class
require_once __DIR__ . '/db_connect.php';

if (!function_exists('json_esc')) {
    function json_esc($input, $esc_html = true) {
        $result = '';
        if (!is_string($input)) {
            $input = (string) $input;
        }

        $conv = array("\x08" => '\\b', "\t" => '\\t', "\n" => '\\n', "\f" => '\\f', "\r" => '\\r', "'" => "\\'");
        if ($esc_html) {
            $conv['<'] = '\\u003C';
            $conv['>'] = '\\u003E';
        }

        for ($i = 0, $len = strlen($input); $i < $len; $i++) {
            if (isset($conv[$input[$i]])) {
                $result .= $conv[$input[$i]];
            }
            else if ($input[$i] < ' ') {
                $result .= sprintf('\\u%04x', ord($input[$i]));
            }
            else {
                $result .= $input[$i];
            }
        }

        return $result;
    }
}

function coreReturnRecordset($sql, $param) {
    // connecting to db
    try {
        $db = new DB_CONNECT();
        $conn = $db->connect();

        $conn->exec("set names utf8mb4");
        $result = $conn->prepare($sql);
        if ($param != null) {
            $result->execute($param);
        } else {
            $result->execute();
        }
    } catch (Exception $ex) {
        $result = null;
        return null;
    }
    return $result;
}

function coreReturnArray($sql, $param) {
    $result = coreReturnRecordset($sql, $param);

    try {
        if ($result != null) {
            $resultArray = $result->fetchAll(PDO::FETCH_ASSOC);
            $result = null;
        }
    } catch (Exception $ex) {
        $result = null;
        return null;
    }
    return $resultArray;
}

function getLastID($sql, $param){
    $db = new DB_CONNECT();
    $conn = $db->connect();
    $conn->exec("set names utf8mb4");
    $result = $conn->prepare($sql);
    if ($param != null) {
        $result->execute($param);
    } else {
        $result->execute();
    }
    $lastId = $conn->lastInsertId();
    return $lastId;
}

function coreReturnJSON($result_table_name, $sql, $param, $timezone) {
    // array for JSON response
    $response = array();
    $Error = "Error";
    $Message = "Message";

    // connecting to db
    try {
        $db = new DB_CONNECT();
        $conn = $db->connect();

        $conn->exec("set names utf8mb4");
        $result = $conn->prepare($sql);
        if ($param != null) {
            $result->execute($param);
        } else {
            $result->execute();
        }
    } catch (Exception $ex) {
        $result = null;
        $response[$Error] = 1;
        $response[$Message] = "1103|failed on processing request|" . $ex.$Message;
        // echoing JSON response
        return json_encode($response);
    }

    if($result) {
        $rows_returned = $result->rowCount();

        if ($rows_returned > 0) {
            $response[$result_table_name] = array();
            $field = array();

            for ($i = 0; $i < $result->columnCount(); $i++) {
                $col = $result->getColumnMeta($i);
                $field[] = $col['name'];
                $type[] = $col['native_type'];
            }

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $result_table_array = array();

                for ($i = 0; $i < sizeof($field); $i++) {
                    $fieldname = $field[$i];
                    $fieldtype = $type[$i];

                    $result_table_array[$fieldname] = $row[$fieldname];
                    if ($timezone) {
                        if ($fieldtype == 'DATETIME' || $fieldtype == 'DATE' || $fieldtype == 'TIME' || $fieldtype == 'TIMESTAMP') {
                            $result_table_array[$fieldname] = date("Y-m-d\TH:i:s.000\Z", strtotime($result_table_array[$fieldname]));
                        }
                    } else {
                        if ($fieldtype == 'DATETIME' || $fieldtype == 'TIMESTAMP') {
                            $result_table_array[$fieldname] = date("Y-m-d H:i:s", strtotime($result_table_array[$fieldname]));
                        } else if ($fieldtype == 'DATE') {
                            $result_table_array[$fieldname] = date("Y-m-d", strtotime($result_table_array[$fieldname]));
                        } else if ($fieldtype == 'TIME') {
                            $result_table_array[$fieldname] = date("H:i:s", strtotime($result_table_array[$fieldname]));
                        }
                    }
                    $result_table_array[$fieldname] = mb_check_encoding($result_table_array[$fieldname], 'UTF-8') ? $result_table_array[$fieldname] : utf8_encode($result_table_array[$fieldname]);

                }

                array_push($response[$result_table_name], $result_table_array);
            }
            $result->closeCursor();

            $response[$Error] = 0;
            $response[$Message] = $rows_returned . " record found";
        } else {
            $response[$Error] = 1;
            $response[$Message] = "1100|no record found";
        }
    } else {
        $response[$Error] = 1;
        $response[$Message] = $conn->errorCode() . "|" . implode("|",$conn->errorInfo());
    }

    // echoing JSON response
    $result = null;
    return json_encode($response);
}

function coreNoReturn($sql, $param) {
// array for JSON response
    $response = array();
    $success = "success";
    $message = "message";

    // connecting to db
    try {
        $db = new DB_CONNECT();
        $conn = $db->connect();

        $conn->exec("set names utf8mb4");
        $result = $conn->prepare($sql);
        if ($param != null) {
            $result->execute($param);
        } else {
            $result->execute();
        }
    } catch (Exception $ex) {
        $result = null;
        if ($ex->getCode() == 23000) {
            $response[$success] = 0;
            $response[$message] = "1104|failed on processing request|" . $ex.$message;
        } else {
            $response[$success] = 0;
            $response[$message] = "1103|failed on processing request|" . $ex.$message;
        }
        // echoing JSON response
        // return json_encode($response);
        return $response;
    }

    if($result) {
        $response[$success] = 1;
        $response[$message] = "";
    }

    // echoing JSON response
    $result = null;
    // return json_encode($response);
    return $response;
}

function coreNoReturnTF($sql, $param) {
    // connecting to db
    try {
        $db = new DB_CONNECT();
        $conn = $db->connect();

        $conn->exec("set names utf8mb4");
        $result = $conn->prepare($sql);
        if ($param != null) {
            $result->execute($param);
        } else {
            $result->execute();
        }
    } catch (Exception $ex) {
        $result = null;
        return false;
    }

    if($result) {
        $result = null;
        return true;
    } else {
        $result = null;
        return false;
    }
}

function coreNoReturnEM($sql, $param) {
    // connecting to db
    try {
        $db = new DB_CONNECT();
        $conn = $db->connect();

        $conn->exec("set names utf8mb4");
        $result = $conn->prepare($sql);
        if ($param != null) {
            $result->execute($param);
        } else {
            $result->execute();
        }
    } catch (Exception $ex) {
        $result = null;
        return "1103|failed on processing request|" . $ex->getMessage();
    }

    if($result) {
        $result = null;
        return "";
    } else {
        $result = null;
        return "1103|failed on processing request|";
    }
}

?>