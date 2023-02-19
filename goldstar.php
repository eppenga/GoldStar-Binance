<?php

/**
 * @author Ebo Eppenga
 * @copyright 2022
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Binance API from JaggedSoft.
 * 
 * goldstar.php?id=a1&action=SELL&pair=MATICBUSD&key=12345
 * 
 * For more information please see:
 * https://github.com/eppenga/goldstar-crypto-trading-bot
 *
 */


 // Set error reporting
error_reporting(E_ALL & ~E_NOTICE);

// Log query string
$log_qstring = "data/log_string.txt";
if (!file_exists($log_qstring))   {file_put_contents($log_qstring, "");}
file_put_contents("data/log_string.txt", date("Y-m-d H:i:s") . $_SERVER['REQUEST_URI'] . "\n", FILE_APPEND | LOCK_EX);

// Debug for Trailing test
$test_Trail = false;

// Configuration
if (!file_exists("config.php")) {echo "Error: Configuration file does not exist!"; exit();}
include "config.php";

// Dispay header
include "header.php";

// Check logfiles based on Bot ID
include "check_logfiles.php";

// Check variables
include "check_variables.php";

// Functions
include "functions.php";

// Query string parameters
include "querystring.php";

// Connect to Binance
require 'vendor/autoload.php';
$api = new Binance\API($binance_key, $binance_secret);


/*** START PROGRAM ***/

/* Get price of pair */
$price = (float)$api->price($pair);

/* Check if we have enough to pay fees and get important variables */
include "check_base.php";

/* Get all important variables */
$set_coin = minimumQuote();

/* Check if we enough BNB for discount */
include "check_bnb.php";

/** Report **/
echo "<b>GoldStar</b><br />";
echo "Date       : " . date("Y-m-d H:i:s") . "<br />";
echo "Bot ID     : " . $id . "<br />";
echo "Pair       : " . $pair . "<br />";
echo "Spread     : " . $spread . "%<br />";
echo "Markup     : " . $markup . "%<br />";
echo "Multiplier : " . $multiplier . "x<br />";
echo "Compounding: " . $set_coin['compFactor'] . "x<br />";
if ($tv_advice) {echo "TradingView: (" . $tv_recomMin . "-" . $tv_recomMax . "), (" . implode(",", $tv_periods) . ")<br />";}
if ($tb_enabled) {echo "Trailing   : " . $tb_distance . "%<br />";}
echo "Order value: " . $set_coin['minBUY'] * $price . " " . $set_coin['quoteAsset'] . "<br />";
echo "Available  : " . $set_coin['balanceQuote'] . " " . $set_coin['quoteAsset'] . "<br />";
if (!empty($compounding)) {echo "Total BUSD : " . $set_coin['totalBUSD'] . " BUSD<br />";}
echo "Command    : " . $action; if ($limit) {echo " / LIMIT";} echo "<br /><br /><hr /><br />";


/*** BUY action ***/
if ($action == "BUY") {
  
  // Buy cycle
  if ($orderID == 0) {
    echo "<i>Trying to buy " . $set_coin['minBUY'] . " " . $set_coin['baseAsset'] . " at " . $price ." " . $set_coin['quoteAsset'] . "...</i><br /><br /><hr /><br />";
  
    // Check if there are sold LIMIT orders
    if ($limit) {include("limit_filled.php");}
  
    // Check if price is outside spread
    $nobuy     = false;
    $price_min = $price * (1 - $spread / 100);
    $price_max = $price * (1 + $spread / 100);
  
    if (!$test_Trail) {
      $handle = fopen($log_trades, "r");
      while (($line = fgetcsv($handle)) !== false) {
        $buy_price = $line[6] / $line[5];
        if (($buy_price >= $price_min) && ($buy_price <= $price_max)) {
          $nobuy = true;
          echo "<i>Skipping buy because existing trade within " . round((($price / $buy_price) - 1) * 100, 2) . "%...</i><br /><br /><hr /><br />";
          break;
        }    
      }
      fclose($handle);
    }
  
    // Check for TradingView advice
    if (!$test_Trail) {
      if (($tv_advice) && (!$nobuy)) {
        echo "<i>TradingView says";
        $tv_eval = evalTradingView($pair, $tv_periods, $tv_recomMin, $tv_recomMax);
        if (!$tv_eval) {
          $nobuy = true;
          echo " skipping...</i><br /><br /><hr /><br />";
        } else {
          echo " buying...</i><br /><br /><hr /><br />";
        }
      }
    }
  
    // Check for trailing buy active
    if (file_exists($log_trailing) && (!$nobuy)) {
      $nobuy = true;
      echo "<i>Skipping buy because trailing buy is active...</i><br /><br /><hr /><br />";
    }
  }

  // Buy if spread = 0 or no adjacent order or to register to log files after trailing
  if ((!$nobuy) || ($spread == 0 || $orderID <> 0)) {

    // Minimum order
    $quantity   = $set_coin['minBUY'];
      
    // Caclulate buy
    $buy        = $quantity * $price;
    $commission = $buy * ($fee / 100);
    
    // Set for reporting
    $total_buy      = $buy;
    $total_quantity = $quantity;
   
    // Buy Order
    echo "<b>BUY Order</b><br /><br />";
    
    // Check if we have enough quote balance to buy
    $quantityQuote = $set_coin['balanceQuote'];
    if ($orderID == 0) {
      if ($quantityQuote < (2 * $buy)) {
      $message = date("Y-m-d H:i:s") . "," . $id . ",Error: Insufficient " . $set_coin['quoteAsset'] . " to buy " . $set_coin['baseAsset'];
        echo "<font color=\"red\"><b>" . $message . "</b></font><br /><br />";
        logCommand($message, "error");
        exit();
      }
    }
    
    // BUY BUY BUY!
    $tb_active = false;
    if (!$tb_enabled) {

      // Normal BUY
      $order = $api->marketBuy($pair, $quantity);
      logCommand($order, "binance");
    } else {

      // Trailing BUY, if an Order ID is supplied trailing buy ended and we just register
      if (($orderID == 0) || ($test_Trail)) {

        // Start trailing
        $tb_active = true;
        echo "<i>Initiating trailing buy...</i><br /><br /><hr /><br />";
        
        // Create Trailing ID file
        file_put_contents("data/log_trailing.csv", $id);

        // Create Trailing settings file
        $link    = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $link    = parse_url($link, PHP_URL_SCHEME) . "://" . parse_url($link, PHP_URL_HOST) . dirname(parse_url($link, PHP_URL_PATH)) . "/";
        $message = $id . "," . $pair . "," . $quantity . "," . $set_coin['tickSize'] . "," . $link;
        file_put_contents($log_trailing, $message);
        pclose(popen("start php trailing.php", "r"));
      } else {

        // Get order status after trailing BUY 
        $order = $api->orderStatus($pair, $orderID);
        logCommand($order, "binance");
      }
    }

    // Skip if going to trailing buy
    if (!$tb_active) {

      // Adjust and report Binance information
      $orderstatus    = extractBinance($order);
      $unique_id      = $orderstatus['order'];
      $price          = $orderstatus['price'];
      $quantity       = $orderstatus['base'];
      $buy            = $orderstatus['quote'];
      $commission     = $orderstatus['commission'] * $price;
      $total_quantity = $quantity;

      // Report
      echo "<b>LIVE BUY Trade</b><br />";
      echo "Quantity   : " . $quantity . "<br />";
      echo "BUY Price  : " . $price . "<br />";   
      echo "BUY Value  : " . $buy . "<br />";
      echo "Commission : " . $commission . " (" . $fee . "%)<br /><br />";      
      echo "Symbol     : " . $order['symbol'] . "<br />";
      echo "Order ID   : " . $order['orderId'] . "<br />";
      echo "Time       : " . $order['time'] . "<br />";
      echo "Status     : " . $order['status'] . "<br /><br />";
      
      // Add a limit order
      if ($limit) {include("limit_order.php");}
      echo "<hr /><br />";

      // Update log files for BUY order
      if (!isset($unique_id)) {$unique_id = uniqid();}
      $message = date("Y-m-d H:i:s") . "," . $id . "," . $unique_id . "," . $pair . ",BUY," . $quantity . "," . $buy;
      logCommand($message, "buy");
      $message = date("Y-m-d H:i:s") . "," . $id . "," . $unique_id . "," . $pair . ",BUY," . $quantity . "," . $buy . ",0," . (-1 * $commission) . "," . $tradetype;
      logCommand($message, "history");
    }
  }  
}


/*** SELL action ***/
if ($action == "SELL") {
  echo "<i>Trying to SELL...</i><br /><br /><hr /><br />";

  // Loop through all the BUY orders
  $handle = fopen($log_trades, "r");
  while (($line = fgetcsv($handle)) !== false) {
    // Loop through the lines
    
    // Found a BUY order
    if ($line[4] == "BUY") {

      // Count and calculate BUY and SELL
      $counter    = $counter + 1;                                // General counter
      $quantity   = $line[5];                                    // Total BUY quantity
      $buy        = $line[6];                                    // Total BUY value (quantity * price)
      $buy_price  = $buy / $quantity;                            // Buy price
      $buy_fee    = $buy * ($fee / 100);                         // Buy fee
      $sell_fee   = ($quantity * $price) * ($fee / 100);         // Sell fee
      $fees       = $buy_fee + $sell_fee;                        // Total fees(BUY plus SELL fees)
      $markups    = ($quantity * $price) * ($markup / 100);      // Total markup based on total BUY funds 
      $sell       = ($quantity * $price);                        // Total SELL value
      $sell_price = $sell / $quantity;                           // Sell price
      $profit     = $sell - $buy - $fees;                        // Profit

      echo "<b>SELL Order ";
      echo $counter . "</b><br /><br />";
      echo "<i>BUY</i>" . "<br />";
      echo "Quantity   : " . $quantity . "<br />";
      echo "BUY Price  : " . $buy_price . "<br />";
      echo "BUY Value  : " . $buy . "<br />";
      echo "Commission : " . $buy_fee . "<br /><br />";

      // We can SELL with profit!!
      if (($sell - $fees - $markups) >= $buy) {

        // Do some calculations for now and later
        $total_orders   = $total_orders + 1;
        $total_profit   = $total_profit + $profit;
        $total_quantity = $total_quantity + $quantity;
        $total_sell     = $total_sell + $sell;
        $total_fees     = $total_fees + $fees;
                
        // SELL SELL SELL!
        $order = $api->marketSell($pair, $quantity);
        logCommand($order, "binance");

        // Adjust
        $orderstatus = extractBinance($order);
        $price       = $orderstatus['price'];
        $quantity    = $orderstatus['base'];
        $sell        = $orderstatus['quote'];    
        $commission  = $orderstatus['commission'];
        $markups     = ($quantity * $price) * ($markup / 100);
        $fees        = $buy_fee + $commission;
        $profit      = $sell - $buy - $fees;

        // Report
        echo "<b>LIVE SELL Trade</b><br />";
        echo "Quantity   : " . $quantity . "<br />";
        echo "SELL Price : " . $price . "<br />";
        echo "SELL Value : " . $sell . "<br />";
        echo "Markup     : " . $markups . " (" . $markup . "%)<br />";
        echo "Commission : " . $commission . " (" . $fee . "%)<br />";        
        echo "Profit     : " . $profit . "<br /><br />";
                  
        echo "Symbol     : " . $order['symbol'] . "<br />";
        echo "Order ID   : " . $order['orderId'] . "<br />";
        echo "Time       : " . $order['time'] . "<br />";
        echo "Status     : " . $order['status'] . "<br /><br />";          

        // Log to history
        echo "<i>Profit, we can sell!</i><br /><br />";
        $history .= date("Y-m-d H:i:s") . "," . $line[1] . "," . $line[2] . "," . $pair . ",SELL," . $quantity . "," . $sell . "," . $profit . "," . $commission . "\n";
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