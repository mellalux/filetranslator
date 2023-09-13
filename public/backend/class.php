<?php

header('Access-Control-Allow-Origin: *');
require_once('config.php');

class FILETRANSLATOR
{
    private $postData;
    private $fileData;
    private $uploadDir;
    private $dateLangDir;
    public $results = [];

    // Constructor
    public function __construct()
    {
        $this->postData = $_POST;
        $this->fileData = $_FILES;
        $this->dateLangDir = date('Ymd') . DIRECTORY_SEPARATOR . $this->postData['lang'];
    }

    // Destructor
    public function __destruct()
    {
    }

    public function processFormData()
    {
        // FILES
        if (count($this->fileData) > 0) {
            $this->log_info("Processing...");
            echo $this->saveFiles();
        }
    }
    
    public function error($error)
    {
        $this->results['error'] = $error;
    }

    public function log_info($text = null)
    {
        $file = DATADIR . DIRECTORY_SEPARATOR . LOG_IMFO;
        $time = date("Y-m-d H:i:s");
        if (strlen($text) > 0) {
            $message = "$time -> $text";
            $message = strip_tags($message . "\r\n");
            file_put_contents($file, $message, FILE_APPEND | LOCK_EX);
        }
    }

    private function checkDirectory () {
        // Kataloog, kuhu failid salvestatakse
        $this->uploadDir = DATADIR. DIRECTORY_SEPARATOR . $this->dateLangDir;

        if (!is_readable(DATADIR)) {
            $this->error('Diretory "' . DATADIR . '" does not exist or is not writable...');
            return false;
        }

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
                $public_url =  PUBLIC_DATA . DIRECTORY_SEPARATOR . $this->dateLangDir . DIRECTORY_SEPARATOR . $fileName;
                $fileContent = file_get_contents($tmpName);
                $aiCommand = str_replace("[lang]", $this->postData['lang'], $this->postData['aiCommand']);
                $modifiedContent = $this->openai($aiCommand, $fileContent);

                if (isset($modifiedContent)) {
                    if (file_put_contents($targetPath, $modifiedContent) !== false) {
                        $this->results['result'] .= "File '<a href=\"$public_url\" download>$fileName</a>' uploaded successfully...";
                    } else {
                        $this->error("Upload failed for file $fileName!");
                    }
                }
                unlink($tmpName);
            }
        }
        return json_encode($this->results);
    }

    public function openai($system, $user)
    {
        $apiUrl = 'https://api.openai.com/v1/chat/completions';

        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ];

        $data = [
            'model' => 'gpt-3.5-turbo-16k',
            'messages' => [
                ["role" => "system", "content" => $system],
                ["role" => "user", "content" => $user]
            ],
            "temperature" => 0,
            "max_tokens" => 13000,
            "top_p" => 1,
            "frequency_penalty" => 0,
            "presence_penalty" => 0
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        // Decode the JSON data
        $data = json_decode($response);

        if (isset($data->error->code)) {
            $this->error($data->error->code . ' - ' . $data->error->message);
        } else {
            return $data->choices[0]->message->content;
        }

        curl_close($ch);
    }

}

$FT = new FILETRANSLATOR();
$FT->processFormData();