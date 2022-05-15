<?php

/**
 * @author Ebo Eppenga
 * @copyright 2021
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Binance API from JaggedSoft.
 * 
 * For more information please see:
 * https://github.com/eppenga/goldstar-crypto-trading-bot
 *
 */

// Set error reporting and config check
error_reporting(E_ALL & ~E_NOTICE);
if (!file_exists("config.php")) {echo "Error: Configuration file does not exist!"; exit();}


/** User and system variables **/
// Define application settings in config.php, below is only for expert use!

// Configuration
include "config.php";
if (!isset($fee))    {$fee = 0.1;}
if (!isset($markup)) {$markup = 0.5;}

// Debug
$debug          = false;    // Debug mode
$debug_buy      = 3.0;      // Buy price when debug mode is true
$debug_sell     = 3.2;      // Sell price when debug mode is true

// Binance minimum order value
$binanceMinimum = 10;

// Recalculate pair setting every x seconds
$repeatrun = 24 * 60 * 60;

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

// DIM statements
$buy            = 0;
$buy_price      = 0;
$counter        = 0;
$price          = 0;
$profit         = 0;
$quantity       = 0;
$sell           = 0;
$sell_price     = 0;
$fees           = 0;
$markups        = 0;
$total_buy      = 0;
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
$limit          = false;
$paper          = true;
$silent         = false;

/** Functions **/
include "functions.php";

/** Query string parameters **/
include "querystring.php";

/** Connect to Binance **/
require 'vendor/autoload.php';
$api = new Binance\API($binance_key,$binance_secret);

/** Check if all files exist and if not create empty files **/
if (!file_exists("data/"))      {mkdir("data/");}
if (!file_exists($log_trades))  {file_put_contents($log_trades, "");}
if (!file_exists($log_runs))    {file_put_contents($log_runs, "");}
if (!file_exists($log_fees))    {file_put_contents($log_fees, "");}
if (!file_exists($log_profit))  {file_put_contents($log_profit, "");}
if (!file_exists($log_history)) {file_put_contents($log_history, "");}
if (!file_exists($log_binance)) {file_put_contents($log_binance, "");}
if (!file_exists($log_errors))  {file_put_contents($log_errors, "");}


/*** START PROGRAM ***/

// Start GUI
echo '<!DOCTYPE HTML>
<html>
<head>
  <meta http-equiv="content-type" content="text/html" />
  <title>GoldStar</title>
</head>

<body>';

echo "<pre>";
echo "<h2>Goldstar Bot</h2>";
if ($debug) {echo "<font color=\"red\"><b>DEBUG MODE ACTIVE</b></font><br /><br />";}

/** Get price of pair **/
$price = $api->price($pair);

/** Check if we have enough to pay fees and get important variables **/
include "checkbase.php";

/** Get all important variables **/
$set_coin = minimumQuote();

// Report
echo "Date       : " . date("Y-m-d H:i:s") . "<br />";
echo "Bot ID     : " . $id . "<br />";
echo "Pair       : " . $pair . "<br />";
echo "Spread     : " . $spread . "%<br />";
echo "Markup     : " . $markup . "%<br />";
echo "Multiplier : " . $multiplier . "x<br />";
echo "Compounding: " . $set_coin['compFactor'] . "x<br />";
if (isset($set_coin['multiplierTV'])) {
  echo "TradingView: " . $set_coin['multiplierTV'] . "x<br />";
}
if ($tv_advice) {
  echo "TradingView: " . str_replace("_", " ", implode(", ", $tv_recommend)) . " (" . $tv_period . ")<br />";
}
echo "Available  : " . $set_coin['balanceQuote'] . " " . $set_coin['quoteAsset'] . "<br />";
echo "Order value: " . $set_coin['minBUY'] * $price . " " . $set_coin['quoteAsset'] . "<br />";
echo "Command    : " . $action;
if ($limit) {echo " / LIMIT";}
echo " (" . $tradetype . ")<br /><br /><hr /><br />";

/** Check if we need more BNB for paying fees **/ 
if (!$paper) {
  if ($set_coin['balanceBNB'] < (0.5 * $bnb)) {

    // Check if we have enough quote balance to buy
    $quantityQuote = $set_coin['balanceQuote'];
    if ($quantityQuote < (2 * $bnb)) {
      $message = date("Y-m-d H:i:s") . "," . $id . ",Error: Insufficient " . $set_coin['quoteAsset'] . " to buy BNB!";
      echo "<font color=\"red\"><b>" . $message . "</b></font><br /><br />";
      logCommand($message, "error");
      exit();
    }
    
    // Report
    echo "<i>Owning " . $set_coin['balanceBNB'] . " BNB, buying an additional " . $bnb . " BNB to pay fees...</i><br /><br /><hr /><br />"; 

    // Acquire BNB for paying fees
    $order = $api->marketBuy("BNB" . $set_coin['quoteAsset'], $bnb);
    logCommand($order, "binance");        
    $message = date("Y-m-d H:i:s") . "," . $id . "," . extractBinance($order)['order'] . "," . "BNB" . $set_coin['quoteAsset'] . "," . extractBinance($order)['base'] . "," . extractBinance($order)['quote'];
    logCommand($message, "fee");
  }
}


/*** BUY action ***/
if ($action == "BUY") {
  echo "<i>Trying to buy " . $set_coin['minBUY'] . " " . $set_coin['baseAsset'] . " at " . $price ." " . $set_coin['quoteAsset'] . "...</i><br /><br /><hr /><br />";
  if ($debug) {$price = $debug_buy;}

  // Check if there are sold LIMIT orders
  if ($limit) {include("limit_sold.php");}

  // Check if price is outside spread
  $nobuy     = false;
  $price_min = $price * (1 - $spread / 100);
  $price_max = $price * (1 + $spread / 100);
      
  $handle = fopen($log_trades, "r");
  while (($line = fgetcsv($handle)) !== false) {
    
    $buy_price = $line[6] / $line[5];
    if (($buy_price >= $price_min) && ($buy_price <= $price_max)) {
      $nobuy = true;
      echo "<i>Skipping buy because existing trade within " . abs(round((($price / $buy_price) - 1) * 100, 2)) . "%...</i><br /><br /><hr /><br />";
    }    
  }
  fclose($handle);

  // Check for TradingView advice
  if (($tv_advice) && (!$nobuy)) {
    $tv_advice_given = getTradingView($pair, $tv_period);
    if (!in_array($tv_advice_given, $tv_recommend)) {
      $nobuy = true;
      echo "<i>Not recommended by TradingView (". str_replace("_", " ", $tv_advice_given) . "), skipping...</i><br /><br /><hr /><br />";      
    }
  }
   
  // We can buy if spread = 0 or there is no adjacent order
  if ((!$nobuy) || ($spread == 0)) {

    // Minimum order
    $quantity   = $set_coin['minBUY'];
      
    // Caclulate buy
    $buy        = $quantity * $price;
    $commission = $buy * ($fee / 100);
    
    // Set for reporting
    $total_buy      = $buy;
    $total_quantity = $quantity;
    
    // Paper or live trading
    if ($paper) {
      echo "<b>Paper Order</b><br />";
    } else {
      echo "<b>LIVE BUY Order</b><br />";
      
      // Check if we have enough quote balance to buy
      $quantityQuote = $set_coin['balanceQuote'];
      if ($quantityQuote < (2 * $buy)) {
      $message = date("Y-m-d H:i:s") . "," . $id . ",Error: Insufficient " . $set_coin['quoteAsset'] . " to buy " . $set_coin['baseAsset'];
        echo "<font color=\"red\"><b>" . $message . "</b></font><br /><br />";
        logCommand($message, "error");
        exit();
      }
      
      // BUY BUY BUY!
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
      $unique_id      = extractBinance($order)['order'];
      $price          = extractBinance($order)['price'];
      $quantity       = extractBinance($order)['base'];
      $buy            = extractBinance($order)['quote'];
      $commission     = extractBinance($order)['commission'] * $price;
      $total_quantity = $quantity;

      // Report
      echo "<b>LIVE BUY Trade</b><br />";
      echo "Quantity   : " . $quantity . "<br />";
      echo "BUY Price  : " . $price . "<br />";   
      echo "BUY Total  : " . $buy . "<br />";
      echo "Commission : " . $commission . " (" . $fee . "%)<br /><br />";      
      echo "Symbol     : " . $order['symbol'] . "<br />";
      echo "Order ID   : " . $order['orderId'] . "<br />";
      echo "Time       : " . $order['transactTime'] . "<br />";
      echo "Status     : " . $order['status'] . "<br /><br />";
      
      // Add a limit order
      if ($limit) {include("limit.php");}
      
    }

    echo "<hr /><br />";

    // Update log files for BUY order
    if (!isset($unique_id)) {$unique_id = uniqid();}
    $message = date("Y-m-d H:i:s") . "," . $id . "," . $unique_id . "," . $pair . ",BUY," . $quantity . "," . $buy;
    logCommand($message, "buy");
    $message = date("Y-m-d H:i:s") . "," . $id . "," . $unique_id . "," . $pair . ",BUY," . $quantity . "," . $buy . ",0," . (-1 * $commission) . "," . $tradetype;
    logCommand($message, "history");

  }  
}


/*** SELL action ***/
if ($action == "SELL") {
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
      $buy        = $line[6];                                    // Total BUY funds (quantity * price)
      $buy_price  = $buy / $quantity;                            // Buy price
      $buy_fee    = $buy * ($fee / 100);                         // Buy fee
      $sell_fee   = ($quantity * $price) * ($fee / 100);         // Sell fee
      $fees       = $buy_fee + $sell_fee;                        // Total fees(BUY plus SELL fees)
      $markups    = ($quantity * $price) * ($markup / 100);      // Total markup based on total BUY funds 
      $sell       = ($quantity * $price);                        // Total SELL
      $sell_price = $sell / $quantity;                           // Sell price
      $profit     = $sell - $buy - $fees;                        // Profit

      if ($paper) {echo "<b>Paper Order ";} else {echo "<b>LIVE SELL Order ";}
      echo $counter . "</b><br /><br />";
      echo "<i>BUY</i>" . "<br />";
      echo "Quantity   : " . $quantity . "<br />";
      echo "BUY Price  : " . $buy_price . "<br />";
      echo "BUY Total  : " . $buy . "<br />";
      echo "Commission : " . $buy_fee . "<br /><br />";

      // Report basic information
      echo "<i>SELL</i>" . "<br />";
      echo "Quantity   : " . $quantity . "<br />";
      echo "SELL Price : " . $price . "<br />";
      echo "SELL Total : " . $sell . "<br />";
      echo "Markup     : " . $markups . "<br />";
      echo "Commission : " . $sell_fee . "<br />";        
      echo "Profit     : " . $profit . "<br /><br />";

      // We can SELL with profit!!
      if (($sell - $fees - $markups) >= $buy) {

        // Do some calculations for now and later
        $total_orders   = $total_orders + 1;
        $total_profit   = $total_profit + $profit;
        $total_quantity = $total_quantity + $quantity;
        $total_sell     = $total_sell + $sell;
        $total_fees     = $total_fees + $fees;
        
        // Executing LIVE Trade
        if (!$paper) {
         
          // SELL SELL SELL!
          $order = $api->marketSell($pair, $quantity);
          logCommand($order, "binance");

          // Adjust
          $price      = extractBinance($order)['price'];
          $quantity   = extractBinance($order)['base'];
          $sell       = extractBinance($order)['quote'];    
          $commission = extractBinance($order)['commission'];
          $markups    = ($quantity * $price) * ($markup / 100);
          $fees       = $buy_fee + $commission;
          $profit     = $sell - $buy - $fees;

          // Report
          echo "<b>LIVE SELL Trade</b><br />";
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
        $history .= date("Y-m-d H:i:s") . "," . $line[1] . "," . $line[2] . "," . $pair . ",SELL," . $quantity . "," . $sell . "," . $profit . "," . $commission . "," . $tradetype . "\n";
      } else {

        // Log to trades
        echo "<i>Insufficient profit, we can not sell!</i><br /><br />";
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
  
  // Create new trades log file
  echo "<i>Creating " . $log_trades . " file...</i><br />";
  file_put_contents($log_trades, $trades);
  
  // Log to history file
  echo "<i>Updating " . $log_history . " file...</i><br />";
  logCommand($history, "history");
}

// Log to runtime file
echo "<i>Updating " . $log_runs . " file...</i><br />";
$message = date("Y-m-d H:i:s") . "," . $id . "," . $pair . "," . $action . "," . $total_quantity . "," . max($total_buy, $total_sell);
logCommand($message, "run");

// End program
echo "<i>Ending program...</i><br />";

echo "</pre>

</body>
</html>";

?>