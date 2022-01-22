<?php

/**
 * @author Ebo Eppenga
 * @copyright 2021
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Binance API from JaggedSoft.
 * 
 * check.php?id=goldstar&key=123456
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
$action     = "CHECK";
$log_errors = "data/log_errors.csv";
$silent     = true;

// Get and validate key
$get_url_key = $_GET["key"];
if (!empty($url_key)) {
  if ($get_url_key <> $url_key) {
    $message = date("Y-m-d H:i:s") . ",filled.php,Error: Security key did not validate";
    echo $message;
    logCommand($message, "error");
    exit();
  }
}

// How to display on screen
$csvDisplay = strtoupper($_GET["csv"]);

// Get logfiles based on Bot ID
if (isset($_GET["id"])) {
  $id             = $_GET["id"];
  $log_trades     = "data/" . $id . "_log_trades.csv";      // Trades
  $log_history    = "data/" . $id . "_log_history.csv";     // History
  $log_fees       = "data/" . $id . "_log_fees.csv";        // Fees
  $log_profit     = "data/" . $id . "_log_profit.csv";      // Profit
  $log_runs       = "data/" . $id . "_log_runs.csv";        // Executing log
  $log_binance    = "data/" . $id . "_log_binance.txt";     // Responses from Binance
  $log_settings   = "data/" . $id . "_log_settings.csv";    // Binance settings
  $log_errors     = "data/" . $id . "_log_errors.csv";      // Errors
} else {
  $message = date("Y-m-d H:i:s") . ",Error: ID not set\n";
  echo $message;
  if (!file_exists("data/")) {mkdir("data/");}
  file_put_contents("data/log_errors.csv", $message, FILE_APPEND | LOCK_EX);  
  exit();
}

// Get $pair by reading the settings file
if (file_exists($log_settings)) {
  $settings = explode(",", file_get_contents($log_settings));    
} else {
  $message = date("Y-m-d H:i:s") . ",filled.php,Error: No settings file was created automatically!";
  echo $message;
  logCommand($message, "error");
  exit();
}
$pair = $settings[0];

/** Connect to Binance **/
require 'vendor/autoload.php';
$api = new Binance\API($binance_key,$binance_secret);


/*** START PROGRAM ***/

if (empty($csvDisplay)) {
  echo '<!DOCTYPE HTML>
  <html>
  <head>
    <meta http-equiv="content-type" content="text/html" />
    <title>GoldStar</title>
  </head>
  
  <body>';
  
  echo "<pre>";
  echo "<h2>Goldstar Bot Filled</h2>";
  echo "<b>Info</b><br />";
  echo "Date  : " . date("Y-m-d H:i:s") . "<br />";
  echo "Bot ID: " . $id . "<br />";
  echo "Pair  : " . $pair . "<br /><br />";
}

// Check on Binance for filled orders
include("limit_sold.php");

// End program
if (empty($csvDisplay)) {
  echo "<i>Ending program...</i><br />";
  echo "</pre>
  
  </body>
  </html>";
} elseif ($csvDisplay == "BATCH") {
  echo "Checked orders for " . $pair;
}

?>