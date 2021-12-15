<?php

/**
 * @author Ebo Eppenga
 * @copyright 2021
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Binance API from JaggedSoft.
 * 
 */

// Set error reporting and config check
error_reporting(E_ALL & ~E_NOTICE);
if (!file_exists("config.php")) {echo "Error: Configuration file does not exist!"; exit();}


/** User and system variables **/
// Define application settings in config.php, below is only for expert use!

// Configuration
include "config.php";
if (empty($fee))    {$fee = 0.1;}
if (empty($markup)) {$markup = 0.5;}

// Debug
$debug          = false;    // Debug mode
$debug_buy      = 3.0;      // Buy price when debug mode is true
$debug_sell     = 3.2;      // Sell price when debug mode is true

// Binance minimum order value
$binanceMinimum = 10;

// Filenames
$id = $_GET["id"]; if (!empty($id)) {$id_temp = $id . "_";}
$log_all        = "data/log_history.csv";
$log_trades     = "data/" . $id_temp . "log_trades.csv";
$log_history    = "data/" . $id_temp . "log_history.csv";
$log_runs       = "data/" . $id_temp . "log_runs.csv";
$log_binance    = "data/" . $id_temp . "log_binance.csv";
$log_settings   = "data/" . $id_temp . "log_settings.csv";
$log_errors     = "data/" . $id_temp . "log_errors.csv";

// DIM statements
$counter        = 0;
$buy            = 0;
$buy_price      = 0;
$price          = 0;
$profit         = 0;
$quantity       = 0;
$sell           = 0;
$sell_price     = 0;
$fees           = 0;
$markups        = 0;
$total_fees     = 0;
$total_orders   = 0;
$total_profit   = 0;
$total_quantity = 0;
$total_sell     = 0;
$history        = "";
$message        = "";
$order          = "";
$pair           = "";
$trades         = "";
$paper          = true;

/** Functions **/
include "functions.php";


/** Query string **/

// Get and validate key
$get_url_key = $_GET["key"];
if (!empty($url_key)) {
  if ($get_url_key <> $url_key) {
    $message = date("Y-m-d H:i:s") . ",Error: Security key did not validate";
    echo $message;
    logCommand($message, "error");
    exit();
  }
}

// Get ordertype
$tradetype = strtoupper($_GET["trade"]);
if (empty($tradetype)) {
  $tradetype = "PAPER";
} elseif (($tradetype <> "LIVE") &&
          ($tradetype <> "PAPER")) {
  $message = date("Y-m-d H:i:s") . ",Error: Trading type incorrect";
  echo $message;
  logCommand($message, "error");
  exit();            
}
if ($tradetype == "PAPER") {$paper = true;} else {$paper = false;}
if ($paper) {$tradetype = "PAPER";} else {$tradetype = "LIVE";}

// Get BUY (False) or SELL (True) command 
$command = strtoupper($_GET["action"]);
if ($command == "BUY") {
  $action = False;
} elseif ($command == "SELL") {
  $action = True;
} else {
  $message = date("Y-m-d H:i:s") . ",Error: No BUY or SELL";
  echo $message;
  logCommand($message, "error");
  exit();
}

// Get pair
$pair = strtoupper($_GET["pair"]);
if (empty($pair)) {
  $message = date("Y-m-d H:i:s") . ",Error: No pair given";
  echo $message;
  logCommand($message, "error");
  exit();
}

// Override spread
$temp_spread = $_GET["spread"];
if ($temp_spread <> "") {
  if (($temp_spread >= 0) && ($temp_spread < 5)) {
    $spread = $temp_spread;
  }  
}

// Override profit
$temp_markup = $_GET["markup"];
if (($temp_markup > 0) && ($temp_markup < 25)) {
  $markup = $temp_markup;
}


/** Connect to Binance **/
require 'vendor/autoload.php';
$api = new Binance\API($binance_key,$binance_secret);


/** Check if all files exist and if not create empty files **/
if (!file_exists("data/"))      {mkdir("data/");}
if (!file_exists($log_all))     {file_put_contents($log_all, "");}
if (!file_exists($log_trades))  {file_put_contents($log_trades, "");}
if (!file_exists($log_runs))    {file_put_contents($log_runs, "");}
if (!file_exists($log_history)) {file_put_contents($log_history, "");}
if (!file_exists($log_binance)) {file_put_contents($log_binance, "");}
if (!file_exists($log_errors))  {file_put_contents($log_errors, "");}


/** START PROGRAM **/
echo '<!DOCTYPE HTML>
<html>
<head>
  <meta http-equiv="content-type" content="text/html" />
  <title>GoldStar</title>
</head>

<body>';

echo "<pre>";
echo "<h1>Goldstar Bot</h1>";
if ($debug) {echo "<font color=\"red\"><b>DEBUG MODE ACTIVE</b></font><br /><br />";}

/** Get price of pair **/
$price = $api->price($pair);


/** Check if we have enough to pay fees **/
include "checkbase.php";


/** BUY action **/
if (!$action) {
  echo "<i>Trying to BUY...</i><br /><br /><hr /><br />";
  if ($debug) {$price = $debug_buy;}

  // Minimum order
  $quantity   = minimumQuote()['minBUY'];

  // Caclulate buy
  $buy        = $quantity * $price;
  $commission = $buy * ($fee / 100);

  // Check if price is outside spread
  $nobuy = false;
  $price_min = $price * (1 - $spread / 100);
  $price_max = $price * (1 + $spread / 100);
      
  $handle = fopen($log_trades, "r");
  while (($line = fgetcsv($handle)) !== false) {
    
    $buy_price = $line[6] / $line[5];
    if (($buy_price >= $price_min) && ($buy_price <= $price_max)) {
      $nobuy = true;
    }    
  }
  fclose($handle);
   
  if ((!$nobuy) || ($spread == 0)) {
    // We can buy if spread = 0 or there is no adjacent order
    
    // Paper or live trading
    if ($paper) {
      echo "<b>Paper Order</b><br />";
    } else {
      echo "<b>LIVE Order</b><br />";
      $order = $api->marketBuy($pair, $quantity);
      logCommand($order, "binance");
    }
  
    // Report basic information
    echo "Quantity   : " . $quantity . "<br />";
    echo "BUY Price  : " . $price . "<br />";   
    echo "BUY Total  : " . $buy  . "<br />";
    echo "Commission : " . $commission . " (" . $fee . "%)<br /><br />";    
  
    // Adjust and report Binance information
    if (!$paper) {

      // Adjust
      $price      = extractBinance($order)['price'];
      $quantity   = extractBinance($order)['base'];
      $buy        = extractBinance($order)['quote'];
      $commission = extractBinance($order)['commission'] * $price;

      // Report
      echo "<b>LIVE Trade</b><br />";
      echo "Quantity   : " . $quantity . "<br />";
      echo "BUY Price  : " . $price . "<br />";   
      echo "BUY Total  : " . $buy  . "<br />";
      echo "Commission : " . $commission . " (" . $fee . "%)<br /><br />";      
      echo "Symbol     : " . $order['symbol'] . "<br />";
      echo "Order ID   : " . $order['orderId'] . "<br />";
      echo "Time       : " . $order['transactTime'] . "<br />";
      echo "Status     : " . $order['status'] . "<br /><br />";    
    }
    echo "<hr /><br />";
  
    // Update log files
    $unique_id = uniqid();
    $message = date("Y-m-d H:i:s") . "," . $id . "," . $unique_id . "," . $pair . ",BUY," . $quantity . "," . $buy;
    logCommand($message, "buy");
    $message = date("Y-m-d H:i:s") . "," . $id . "," . $unique_id . "," . $pair . ",BUY," . $quantity . "," . $buy . "," . (-1 * $commission) . "," . $tradetype;
    logCommand($message, "history");
    logCommand($message, "all");    
  } else {
    echo "<i>Price in range of existing buy order, skipping...</i><br /><br /><hr /><br />";
  }
}


/** SELL action **/
if ($action) {
  echo "<i>Trying to SELL...</i><br /><br /><hr /><br />";

  // Loop through all the BUY orders
  $handle = fopen($log_trades, "r");
  while (($line = fgetcsv($handle)) !== false) {
    // Loop through the lines
    
    if ($line[4] == "BUY") {
      // Found a BUY order
      if ($debug) {$price = $debug_sell;}

      // Count and calculate BUY and SELL
      $counter    = $counter + 1;                                // General counter
      $quantity   = $line[5];                                    // Total BUY quantity
      $buy        = $line[6];                                    // Total BUY funds
      $buy_price  = $buy / $quantity;                            // Buy price
      $buy_fee    = $buy * ($fee / 100);                         // Buy fee
      $sell_fee   = ($quantity * $price) * ($fee / 100);         // Sell fee
      $fees       = $buy_fee + $sell_fee;                        // Total fees from BUY plus SELL
      $markups    = ($quantity * $price) * ($markup / 100);      // Total markup based on total BUY funds 
      $sell       = ($quantity * $price);                        // Total SELL
      $sell_price = $sell / $quantity;                           // Sell price
      $profit       = $sell - $buy - $fees;                      // Profit

      if ($paper) {echo "<b>Paper Order ";} else {echo "<b>LIVE Order ";}
      echo $counter . "</b><br /><br />";
      echo "<i>BUY</i>" . "<br />";
      echo "Quantity   : " . $quantity . "<br />";
      echo "BUY Price  : " . $buy_price . "<br />";
      echo "BUY Total  : " . $buy . "<br />";
      echo "Commission : " . $buy_fee . " (" . $fee . "%)<br /><br />";

      // Report basic information
      echo "<i>SELL</i>" . "<br />";
      echo "Quantity   : " . $quantity . "<br />";
      echo "SELL Price : " . $price . "<br />";
      echo "SELL Total : " . $sell . "<br />";
      echo "Markup     : " . $markups . " (" . $markup . "%)<br />";
      echo "Commission : " . $sell_fee . " (" . $fee . "%)<br />";        
      echo "Profit     : " . $profit . "<br /><br />";

      
      if (($sell - $fees - $markups) >= $buy) {
        // We can SELL with profit!!

        // Do some calculations for now and later
        $total_profit   = $total_profit + $profit;
        $total_orders   = $total_orders + 1;
        $total_quantity = $total_quantity + $quantity;
        $total_sell     = $total_sell + $sell;
        $total_fees     = $total_fees + $fees;
        
        if (!$paper) {
        // LIVE Trade          
         
          // Excute
          $order = $api->marketSell($pair, $quantity);
          logCommand($order, "binance");

          // Adjust
          $price      = extractBinance($order)['price'];
          $quantity   = extractBinance($order)['base'];
          $sell       = extractBinance($order)['quote'];    
          $commission = extractBinance($order)['commission'];
          $markups    = ($quantity * $price) * ($markup / 100);         
          $profit     = $sell - $buy - ($buy_fee + $commission);
          
          // Report
          echo "<b>LIVE Trade</b><br />";
          echo "Quantity   : " . $quantity . "<br />";
          echo "SELL Price : " . $price . "<br />";
          echo "SELL Total : " . $sell . "<br />";
          echo "Markup     : " . $markups . " (" . $markup . "%)<br />";
          echo "Commission : " . $commission . " (" . $fee . "%)<br />";        
          echo "Profit     : " . $profit . "<br /><br />";
                    
          echo "Symbol     : " . $order['symbol'] . "<br />";
          echo "Order ID   : " . $order['orderId'] . "<br />";
          echo "Time       : " . $order['transactTime'] . "<br />";
          echo "Status     : " . $order['status'] . "<br /><br />";          
        } 

        // Log to history
        echo "<i>Profit, we can sell!</i><br /><br />";
        $history .= date("Y-m-d H:i:s") . "," . $line[1] . "," . $line[2] . "," . $pair . ",SELL," . $quantity . "," . $sell . "," . $profit . "," . $tradetype . "\n";
      } else {

        // Log to trades
        echo "<i>Loss, we can not sell!</i><br /><br />";
        $trades .= $line[0] . "," . $line[1] . "," . $line[2] . "," . $line[3] . "," . $line[4] . "," . $line[5] . "," . $line[6] . "\n";
      }
      echo "<hr /><br />";
    }
  }
  fclose($handle);
}


/** Report results and end program **/
echo "<b>Results</b><br />";
if ($total_orders > 0) {
  
  // Report
  echo "Total orders  : " . $total_orders . "<br />";
  echo "Total quantity: " . $total_quantity . "<br />";
  echo "Total sell    : " . $total_sell . "<br />";
  echo "Total fees    : " . $total_fees . "<br />";
  echo "Average price : " . (($total_sell + $total_fees) / $total_quantity) . "<br />";
  echo "Total profit  : " . $total_profit . "<br /><br />";
  
  // Create new $log_trades file
  echo "<i>Creating " . $log_trades . " file...</i><br />";
  file_put_contents($log_trades, $trades);
  
  // Log to history file
  echo "<i>Updating " . $log_history . " file...</i><br />";
  LogCommand($history, "history");
  LogCommand($history, "all");
}

// Log to runtime file
echo "<i>Updating " . $log_runs . " file...</i><br />";
$message = date("Y-m-d H:i:s") . "," . $pair . "," . $command . "," . max($quantity, $total_quantity) . "," . max($buy, $total_sell);
logCommand($message, "run");

// End program
echo "<i>Ending program...</i>";

echo "</pre>

</body>
</html>";

?>