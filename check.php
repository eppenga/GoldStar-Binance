<?php

/**
 * @author Ebo Eppenga
 * @copyright 2021
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Binance API from JaggedSoft.
 * 
 * check.php
 * Check for unfilled orders at Binance.
 * 
 */

// Set error reporting and config check
error_reporting(E_ALL & ~E_NOTICE);
if (!file_exists("config.php")) {echo "Error: Configuration file does not exist!"; exit();}


/** User and system variables **/
// Define application settings in config.php, below is only for expert use!

// Configuration
include "config.php";
include "config_cycle.php";

/** Functions **/
include "functions.php";

// Set variables
$id         = "check.php";
$data       = "data/";
$action     = "CHECK";
$log_errors = "data/log_errors.csv";

/** Connect to Binance **/
require 'vendor/autoload.php';
$api = new Binance\API($binance_key,$binance_secret);


/*** START PROGRAM ***/

// Get and validate key
$get_url_key = $_GET["key"];
if (!empty($url_key)) {
  if ($get_url_key <> $url_key) {
    $message = date("Y-m-d H:i:s") . "," . $id . ",Error: Security key did not validate";
    echo $message;
    logCommand($message, "error");
    exit();
  }
}

echo '<!DOCTYPE HTML>
<html>
<head>
  <meta http-equiv="content-type" content="text/html" />
  <title>GoldStar</title>
</head>

<body>';

echo "<pre>";
echo "<h2>Goldstar Bot Check</h2>";
echo "Date: " . date("Y-m-d H:i:s") . "<br /><br />";

// Get file list
$files = scandir($data);

// Get all IDs
foreach ($files as &$file) {
  if (strpos($file, "_log_binance.txt") !== false) {
    $ids[] = str_replace("_log_binance.txt", "", $file);
  }
}

// Create batch files that can check for filled orders and profits
$message  = "";
$counter = 0;

$message = "@echo off
E:
cd \GoldStarAuto

echo *** Checking for profits and filled orders ***
echo Date: %date% / %time%
echo:

";

foreach ($ids as &$id) {
  $message .= "echo Now checking " . $id . "...\n";
  $message .= "E:\Lynx\lynx \"http://domotica.eppenga.com/goldstarauto/filled.php?csv=batch&key=" . $url_key . "&id=" . $id . "\" -dump\n";
  $message .= "E:\Lynx\lynx \"http://domotica.eppenga.com/goldstarauto/profit.php?csv=batch&key=" . $url_key . "&id=" . $id . "\" -dump\n";
  $message .= "echo:\n\n";
}

$message .= "exit\n";

/*
echo "<b>Batch file</b><br />";
echo $message;
*/

// Write new batch files
file_put_contents($folder . $checker, $message);

// End program
echo "<i>Batch file created, ending program...</i><br />";
echo "</pre>

</body>
</html>";

?>