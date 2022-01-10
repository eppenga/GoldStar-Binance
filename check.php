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

/** Functions **/
include "functions.php";

// Set variables
$id         = "check.php";
$data       = "data/";
$action     = "CHECK";
$log_errors = "data/log_errors.csv";            // Where to store the errors

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
echo "<h2>Goldstar Bot Checker@" . date("Y-m-d H:i:s") . "</h2>";

// Get file list
$files = scandir($data);

// Get all IDs
foreach ($files as &$file) {
  if (strpos($file, "_log_binance.csv") !== false) {
    $ids[] = str_replace("_log_binance.csv", "", $file);
  }
}

echo "<b>Bots found</b><br />";
foreach ($ids as &$id) {
  echo $id . "<br />";
}
echo "<br /><hr /><br />";

// Check per bot if Binance LIMIT SELL orders are filled
foreach ($ids as &$id) {

  // Set the filenames from the ID
  $log_trades     = "data/" . $id . "_log_trades.csv";      // Trades
  $log_history    = "data/" . $id . "_log_history.csv";     // History
  $log_fees       = "data/" . $id . "_log_fees.csv";        // Fees
  $log_runs       = "data/" . $id . "_log_runs.csv";        // Executing log
  $log_binance    = "data/" . $id . "_log_binance.csv";     // Responses from Binance
  $log_settings   = "data/" . $id . "_log_settings.csv";    // Binance settings
  $log_errors     = "data/" . $id . "_log_errors.csv";      // Errors

  // Find the correct pair
  $settings       = explode(",", file_get_contents($log_settings));
  $pair           = $settings[0];

  // Report
  echo "<b>Now checking '" . $id . "' for pair '" . $pair . "'</b><br /><br />";
  
  // Check on Binance
  include("limit_sold.php");
  echo "<hr /><br />";
  
}

// End program
echo "<i>Ending program...</i><br />";
echo "</pre>

</body>
</html>";

?>