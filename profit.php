<?php

/**
 * @author Ebo Eppenga
 * @copyright 2021
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Binance API from JaggedSoft.
 * 
 * profit.php
 * Calculate the profits for a bot
 * 
 */

// Set error reporting and config check
setlocale(LC_MONETARY, 'en_US');
error_reporting(E_ALL & ~E_NOTICE);
if (!file_exists("config.php")) {echo "Error: Configuration file does not exist!"; exit();}


/** User and system variables **/
// Define application settings in config.php, below is only for expert use!

// Configuration
include "config.php";

/** Functions **/
include "functions.php";

// Set variables
$id         = "profit.php";
$data       = "data/";
$bags       = 0;
$base       = 0;
$balance    = 0;
$fees       = 0;
$profit     = 0;
$quote      = 0;

// Filenames
if (isset($_GET["id"])) {
  $id             = $_GET["id"];
  $log_trades     = "data/" . $id . "_log_trades.csv";      // Trades
  $log_history    = "data/" . $id . "_log_history.csv";     // History
  $log_fees       = "data/" . $id . "_log_fees.csv";        // Fees
  $log_runs       = "data/" . $id . "_log_runs.csv";        // Executing log
  $log_binance    = "data/" . $id . "_log_binance.csv";     // Responses from Binance
  $log_settings   = "data/" . $id . "_log_settings.csv";    // Binance settings
  $log_errors     = "data/" . $id . "_log_errors.csv";      // Errors
} else {
  $message = date("Y-m-d H:i:s") . ",Error: ID not set\n";
  echo $message;
  if (!file_exists("data/")) {mkdir("data/");}
  file_put_contents("data/log_errors.csv", $message, FILE_APPEND | LOCK_EX);  
  exit();
}

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

// Check if we have enough files to analyze
$check_analyze = false;
if (file_exists($log_trades))  {$check_analyze = true;}
if (file_exists($log_fees))    {$check_analyze = true;}
if (file_exists($log_history)) {$check_analyze = true;}
if (!$check_analyze) {
  $message = date("Y-m-d H:i:s") . "," . $id . ",Error: Files missing for analysis";
  echo $message;
  logCommand($message, "error");
  exit();
}

/** Connect to Binance **/
require 'vendor/autoload.php';
$api = new Binance\API($binance_key,$binance_secret);


/*** START PROGRAM ***/
echo '<!DOCTYPE HTML>
<html>
<head>
  <meta http-equiv="content-type" content="text/html" />
  <title>GoldStar</title>
</head>

<body>';

echo "<pre>";
echo "<h2>Goldstar Analyzer</h2>";

// Count the trades
$handle = fopen($log_trades, "r");
while (($line = fgetcsv($handle)) !== false) {
  
  // Determine sign
  $bags = $bags + 1;
}
fclose($handle);

// Loop through history file and calculate
$handle = fopen($log_history, "r");
while (($line = fgetcsv($handle)) !== false) {
  
  // BUYs and SELLs
  if ($line[4] == "BUY") {
    $base_buys  = $base_buys + $line[5];
    $quote_buys = $quote_buys + $line[6];
    $total_buys = $total_buys + 1;
  }
  if ($line[4] == "SELL") {
    $base_sells  = $base_sells + $line[5];
    $quote_sells = $quote_sells + $line[6];
  }

  // Calculate profits and fees
  $profit = $profit + $line[7];
  @$fees   = $fees + $line[8];      // Remove later when fees are fully integrated!!

}
fclose($handle);

// Find the correct pair
$settings      = explode(",", file_get_contents($log_settings));
$pair          = $settings[0];
$basecoin      = $settings[2];
$quotecoin     = $settings[3];

// Get price of pair
$price         = $api->price($pair);

// Calculate balances and revenue
$ticker             = $api->prices();
$balances           = $api->balances($ticker);
$base_balance       = $balances[$basecoin]['available'] + $balances[$basecoin]['onOrder']; 
$quote_balance      = $base_balance * $price;
$base_revenue       = $base_sells - $base_buys;
$quote_revenue      = $quote_sells - $quote_buys;
$quote_profit       = $quote_balance + $quote_revenue;
$net_worth          = $quote_sells + (-1 * $base_revenue * $price);
$avg_base_order     = $base_buys / $total_buys;

echo "<b>Info</b><br />";
echo "Bot ID       : " . $id . "<br />";
echo "Date         : " . date("Y-m-d H:i:s") . "<br />";
echo "Pair         : " . $pair . "<br />";
echo "Current price: " . $price . " " . $quotecoin . "<br />";
echo "Average order: " . $avg_base_order . " " . $basecoin . "<br /><br />";

echo "<b>Balances</b><br />";
echo "Base balance : " . $base_balance . " " . $basecoin . "<br />";
echo "Quote balance: " . $quote_balance . " " . $quotecoin . "<br /><br />";


echo "<b>Net worth</b><br />";
echo "Base BUYs    : " . $base_buys . " " . $basecoin . "<br />";
echo "Base SELLs   : " . $base_sells . " " . $basecoin . "<br />";
echo "Unfilled     : " . $base_revenue . " " . $basecoin . "<br /><br />";

echo "Quote BUYs   : " . $quote_buys . " " . $quotecoin . "<br />";
echo "Quote SELLs  : " . $quote_sells . " " . $quotecoin . "<br />";
echo "Margin       : " . $quote_revenue . " " . $quotecoin . "<br /><br />";

echo "Net worth    : " . $net_worth . " " . $quotecoin . "<br /><br />";

echo "<b>Fees</b><br />";
echo "Fees         : " . $fees . " " . $quotecoin . "<br /><br />";

echo "<b>Mains</b><br />";
echo "Trades       : " . $bags . " Open orders<br />";
echo "Balance      : " . $quote_balance . " " . $quotecoin . "<br />";
echo "Fees         : " . $fees . " " . $quotecoin . "<br />";
echo "Profit       : <b><u>" . $profit . " " . $quotecoin . "</u></b><br /><br />";


// End program
echo "<i>Ending program...</i><br />";
echo "</pre>

</body>
</html>";

?>