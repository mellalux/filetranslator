<?php

header('Access-Control-Allow-Origin: *');
require_once('config.php');

class FILETRANSLATOR
{
    private $postData;
    private $fileData;
    private $uploadDir;
    public $results = [];

    // Constructor
    public function __construct()
    {
        $this->postData = $_POST;
        $this->fileData = $_FILES;
    }

    // Destructor
    public function __destruct()
    {
    }

    public function processFormData()
    {
        // POST
        if (isset($this->postData['field_name'])) {
            $fieldValue = $this->postData['field_name'];
        }

        // FILES
        if (count($this->fileData) > 0) {
            return $this->saveFiles();
        }
    }
    
    public function error($error)
    {
        $this->results['error'] = $error;
    }

    public function log_errors($text = null)
    {
        $file = DATADIR . DIRECTORY_SEPARATOR . LOG_ERROR;
        $time = date("Y-m-d H:i:s");
        if (strlen($text) > 0) {
            $message = "$time -> $text";
            $message = strip_tags($message . "\r\n");
            file_put_contents($file, $message, FILE_APPEND | LOCK_EX);
        }
    }

    private function checkDirectory () {
        // Kataloog, kuhu failid salvestatakse
        $this->uploadDir = DATADIR. DIRECTORY_SEPARATOR . date('Ymd');

        // Veenduge, et kataloog eksisteerib
        if (!file_exists($this->uploadDir)) {
            if (!mkdir($this->uploadDir, 0775, true)) {
                $this->error('Can\'t create diretory under the ' . $this->uploadDir . '...');
                return false;
            }
        }
        return true;
    }

    public function saveFiles() 
    {
        if ($this->checkDirectory()) {
            foreach ($this->fileData['files']['name'] as $key => $fileName) {
                $tmpName = $this->fileData['files']['tmp_name'][$key];
                $targetPath = $this->uploadDir . DIRECTORY_SEPARATOR . $fileName;

                if (move_uploaded_file($tmpName, $targetPath)) {
                    $this->results['result'] .= "File $fileName uploaded successfully...";
                } else {
                    $this->error("Upload failed for file $fileName!");
                }
            }
        }
        return json_encode($this->results);
    }

    public function openai($text)
    {
        $apiUrl = 'https://api.openai.com/v1/chat/completions';

        $data = [
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'system',  'content' => $text],
                //[ 'role' => "user", 'content' => 'Hello!' ]
            ]
        ];

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->error('Error:' . curl_error($ch));
        } else {
            $this->results['data'] = json_decode($response, true);;
        }

        curl_close($ch);
        return json_encode($this->results);
    }

}

$fileTrans = new FILETRANSLATOR();
echo $fileTrans->processFormData();