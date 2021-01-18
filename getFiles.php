<?php

require_once "../config.php";
use \Tsugi\Core\Settings;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\SettingsForm;

// Sanity checks
// $LAUNCH = LTIX::requireData();


$i=0;
$result = array();
$dir = "svn/homepage/";
$myfile = fopen($dir."files", "r") or die("Unable to open file!");


while($row = fgetcsv($myfile, null, ",")) {
    $result[$i]['category'] = $row[0];
    $result[$i]['filename'] = $row[1]; 
    $result[$i]['expires'] = explode(" ", $row[2], 2)[0]; 
    $result[$i]['url'] = $row[3]; 
    $result[$i]['fileSize'] = $row[4]; 
    $result[$i]['fileDimensions'] = $row[5]; 
    $result[$i]['submitter'] =  explode("<", $row[6], 2)[0]; 
    $result[$i]['jiraIssue'] = $row[7]; 
    $result[$i]['created'] = explode(" ", $row[8], 2)[0]; 

    $i++;
}

if (json_last_error() == JSON_ERROR_NONE) {
    echo json_encode($result);
}

fclose($myfile);
