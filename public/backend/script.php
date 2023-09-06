<?php

header('Access-Control-Allow-Origin: *');

require_once('config.php');

// $res = [];
// $res['result'] = 'Böö';
// echo json_encode($res);
// exit;

class FILETRANSLATOR
{
    public $results = [];

    // Constructor
    public function __construct()
    {
    }

    // Destructor
    public function __destruct()
    {
    }

    public function error($error)
    {
        $this->results['error'] = $error;
    }
}

$res = [];
$res['result'] = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    $files = $_FILES["files"];
  
    // Kataloog, kuhu failid salvestatakse
    $uploadDir = DATADIR."/uploads/";

    // Veenduge, et kataloog eksisteerib
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    foreach ($files["tmp_name"] as $key => $tmpName) {
        $fileName = basename($files["name"][$key]);
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($tmpName, $targetPath)) {
            $res['result'] .= "File $fileName uploaded successfully -> ";
        } else {
            $res['result'] .= "Upload failed for file $fileName -> ";
        }
    }

    echo json_encode($res);
}
