<?php

header('Access-Control-Allow-Origin: *');

require_once('config.php');


if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $files = $_FILES["files"];
  
  // Kataloog, kuhu failid salvestatakse
  $uploadDir = "uploads/";

  // Veenduge, et kataloog eksisteerib
  if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
  }

  foreach ($files["tmp_name"] as $key => $tmpName) {
    $fileName = basename($files["name"][$key]);
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($tmpName, $targetPath)) {
      echo "Fail $fileName edukalt üles laetud.";
    } else {
      echo "Üleslaadimine ebaõnnestus faili $fileName puhul.";
    }
  }
}
