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
error_reporting(E_ALL & ~E_NOTICE);
if (!file_exists("config.php")) {echo "Error: Configuration file does not exist!"; exit();}


/** User and system variables **/
// Define application settings in config.php, below is only for expert use!

// Configuration
include "config.php";

/** Functions **/
include "functions.php";

// Set variables
$id          = "profit.php";
$data        = "data/";
$bags        = 0;
$base        = 0;
$balance     = 0;
$fees        = 0;
$profit      = 0;
$quote       = 0;
$base_buys   = 0;
$base_sells  = 0;
$quote_sells = 0;
$quote_buys  = 0;

// Filenames
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

// How to display on screen FULL, SHORT or BATCH
$csvDisplay = strtoupper($_GET["csv"]);

/** Connect to Binance **/
require 'vendor/autoload.php';
$api = new Binance\API($binance_key,$binance_secret);


/*** START PROGRAM ***/

// Count the trades
$handle = fopen($log_trades, "r");
while (($line = fgetcsv($handle)) !== false) {
  
  // Determine sign
  $bags = $bags + 1;
}
fclose($handle);

// Loop through history file and calculate
$counter = 0;
$handle  = fopen($log_history, "r");
while (($line = fgetcsv($handle)) !== false) {
  
  // Determine start date
  if ($counter == 0) {
    $date_start = $line[0];
  }
  $counter  = $counter + 1;
  $date_end = $line[0];
  
  // BUYs and SELLs
  if ($line[4] == "BUY") {
    $base_buys   = $base_buys + $line[5];
    $quote_buys  = $quote_buys + $line[6];
    @$buy_fees   = $buy_fees - $line[8];   // Remove @ later when fees are fully integrated!!
    $total_buys  = $total_buys + 1;
  }
  if ($line[4] == "SELL") {
    $base_sells  = $base_sells + $line[5];
    $quote_sells = $quote_sells + $line[6];
    @$sell_fees  = $sell_fees + $line[8];  // Remove @ later when fees are fully integrated!!
    $total_sells = $total_sells + 1;
  }

  // Calculate profits and fees
  $profit = $profit + $line[7];

}
fclose($handle);

// Find the correct pair
$settings      = explode(",", file_get_contents($log_settings));
$pair          = $settings[0];
$base_coin      = $settings[2];
$quote_coin     = $settings[3];

// Get price of pair
$price         = $api->price($pair);

// Calculate balances and revenue
$date               = date("Y-m-d H:i:s");
$ticker             = $api->prices();
$balances           = $api->balances($ticker);
$base_balance       = $balances[$base_coin]['available'] + $balances[$base_coin]['onOrder']; 
$quote_balance      = $base_balance * $price;
$base_revenue       = $base_sells - $base_buys;
$quote_revenue      = $quote_sells - $quote_buys;
$quote_profit       = $quote_balance + $quote_revenue;
$net_worth          = $quote_sells + (-1 * $base_revenue * $price);
$avg_base_order     = $base_buys / $total_buys;
$revenue            = $net_worth - $quote_buys;
$total_orders       = $total_buys + $total_sells;

// Don't use real fees for now! Adjust this later!
$buy_fees           = $quote_buys * 0.001;
$sell_fees          = 0;
$fees               = $buy_fees + $sell_fees;

// Total profit
$total_profit       = $revenue - $fees;

// Calculate profit per day
$seconds            = strtotime(date("Y-m-d H:i:s")) - strtotime($date_end);
$day_profit         = 60 * 60 * 24 * ($total_profit / $seconds);

if (empty($csvDisplay)) {
  
  echo '<!DOCTYPE HTML>
  <html>
  <head>
    <meta http-equiv="content-type" content="text/html" />
    <title>GoldStar</title>
  </head>
  
  <body>';
  
  echo "<pre>";
  echo "<h2>Goldstar Analyzer</h2>";
  
  echo "<b>Info</b><br />";
  echo "Bot ID       : " . $id . "<br />";
  echo "Pair         : " . $pair . "<br />";
  echo "Now          : " . $date . "<br />";
  echo "First trade  : " . $date_start . "<br />";
  echo "Last trade   : " . $date_end . "<br />";
  echo "Current price: " . $price . " " . $quote_coin . "<br />";
  echo "Average order: " . $avg_base_order . " " . $base_coin . "<br />";
  echo "Trade profit : " . $profit . " " . $quote_coin . "<br /><br />";
  
  echo "<b>Balances</b><br />";
  echo "Base balance : " . $base_balance . " " . $base_coin . "<br />";
  echo "Quote balance: " . $quote_balance . " " . $quote_coin . "<br /><br />";
  
  
  echo "<b>Net worth</b><br />";
  /*
  echo "Base BUYs    : " . $base_buys . " " . $base_coin . "<br />";
  echo "Base SELLs   : " . $base_sells . " " . $base_coin . "<br />";
  echo "Unfilled     : " . $base_revenue . " " . $base_coin . "<br /><br />";
  */
  
  echo "Quote BUYs   : " . $quote_buys . " " . $quote_coin . "<br />";
  echo "Quote SELLs  : " . $quote_sells . " " . $quote_coin . "<br />";
  echo "Margin       : " . $quote_revenue . " " . $quote_coin . "<br /><br />";
  
  echo "Revenue      : " . $revenue . " " . $quote_coin . "<br />";
  echo "Net worth    : " . $net_worth . " " . $quote_coin . "<br /><br />";
  
  echo "<b>Fees</b><br />";
  echo "Fees BUY     : " . $buy_fees . " " . $quote_coin . "<br />";
  echo "Fees SELL    : " . $sell_fees . " " . $quote_coin . "<br />";
  echo "Total        : " . $fees . " " . $quote_coin . "<br /><br />";
  
  echo "<b>Mains</b><br />";
  echo "Bags         : " . $bags . " Open orders<br />";
  echo "Trades       : " . $total_orders . " Filled orders<br />";
  echo "Balance      : " . $quote_balance . " " . $quote_coin . "<br />";
  echo "Revenue      : " . $revenue . " " . $quote_coin . "<br />";
  echo "Fees         : " . $fees . " " . $quote_coin . "<br />";
  echo "Profit       : <b><u>" . $total_profit . " " . $quote_coin . "</u></b><br /><br />";
  
  // End program
  echo "<i>Ending program...</i><br />";
  echo "</pre>
  
  </body>
  </html>";
}

// Write profit log file
// Format: Date, Bot ID, Pair, Start date, End date, Bags, Balance, Revenue, Fees, Profit
$message  = $date . "," . $id . "," .  $pair . "," . $date_start . "," . $date_end . "," . $profit . ",";
$message .= $bags . "," . $base_balance . "," . $price  . "," . $revenue . "," . $fees . "," . $total_profit . "\n";
file_put_contents($log_profit, $message, FILE_APPEND | LOCK_EX);

if ($csvDisplay == "FULL") {
  echo $message;
} elseif ($csvDisplay == "SHORT") {
  echo $total_profit;
} elseif ($csvDisplay == "BATCH") {
  echo "Checked profit for " . $pair;
}

?>