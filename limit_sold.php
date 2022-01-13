<?php

/**
 * @author Ebo Eppenga
 * @copyright 2021
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Binance API from JaggedSoft.
 * 
 * limit_sold.php
 * Checks if there are limit orders that are already sold.
 * 
 */


// Determine a random so it only checks all Binance trades every 10th time
$go_check    = false;
$limit_check = false;
if (rand(0, 10) == 5)   {$limit_check = true;}
if ($action == "CHECK") {$limit_check = true;}

// Loop through trades
$handle = fopen($log_trades, "r");
while (($line = fgetcsv($handle)) !== false) {

  if ($spread == 0) {$spread = 1;}

  $go_check  = $limit_check;
  $unique_id = $line[2];
  $buy_price = $line[6] / $line[5];
  $price_min = $price * (1 - ($spread * 2) / 100);
  $price_max = $price * (1 + ($spread * 2) / 100);
  
  // Only check Binance trades in range to save on API calls
  if (!$go_check) {
    if (($buy_price >= $price_min) && ($buy_price <= $price_max)) {
      $go_check = true;
    }
  }

  // We can check against Binance data
  if ($go_check) {
    $order       = $api->orderStatus($pair, $unique_id);
    $orderstatus = extractBinance($order);
  
    if ($debug) {echo "Now processing order ID: " . $orderstatus['order'] . "<br /><br />";}
   
    // Found a FILLED limit order, let's process it as a sales order
    if (($orderstatus['status'] == "FILLED") && ($orderstatus['type'] == "LIMIT")) {
      
      // Report
      echo "<i>LIMIT order " . $orderstatus['order'] . " was filled!</i><br /><br />";
      
      // Log Binance FILLED order
      logCommand($order, "binance");
      
      // Do some calculations and set variable
      $limit_order['pair']       = $orderstatus['symbol'];
      $limit_order['order']      = $orderstatus['order'];    
      $limit_order['quantity']   = $orderstatus['base'];
      $limit_order['quote']      = $orderstatus['quote'];
      $limit_order['commission'] = $orderstatus['commission'];
      $limit_order['profit']     = $orderstatus['quote'] - $line[6] - $limit_order['commission'];
      $limit_order['price']      = $orderstatus['price'];
      
      // Report orginal BUY and matching LIMIT (SELL) trade
      echo "<b>Original BUY trade</b><br />";
      echo "Date       : " . $line[0] . "<br />";
      echo "Order ID   : " . $line[2] . "<br />";
      echo "Quantity   : " . $line[5] . "<br />";
      echo "BUY Price  : " . ($line[6] / $line[5]) . "<br />";
      echo "BUY Total  : " . $line[6]  . "<br /><br />";
  
      echo "<b>Matching LIMIT trade</b><br />";
      echo "Quantity   : " . $limit_order['quantity'] . "<br />";
      echo "SELL Price : " . $limit_order['price'] . "<br />";
      echo "SELL Total : " . $limit_order['quote'] . "<br />";
      echo "Commission : " . $limit_order['commission'] . "<br />";
      echo "Profit     : " . $limit_order['profit'] . "<br /><br />";        
  
      // Add SELL order to $log_history and $log_runs
      $message = date("Y-m-d H:i:s") . "," . $id . "," . $limit_order['pair'] . ",SELL," . $limit_order['quantity'] . "," . $limit_order['quote'] . "\n";
      $history = date("Y-m-d H:i:s") . "," . $line[1] . "," . $limit_order['order'] . "," . $limit_order['pair'] . ",SELL," . $limit_order['quantity'] . "," . $limit_order['quote'] . "," . $limit_order['profit'] . $limit_order['commission'] . ",LIVE\n";
      logCommand($history, "history");
      logCommand($message, "run");
      
      // Add to array of to be removed unique IDs
      $unique_ids[] = $unique_id;
    }
  }
}
fclose($handle);

// Remove BUY order from $log_trades
if (!empty($unique_ids)) {
  $trades = "";
  $handle = fopen($log_trades, "r");
  while (($line = fgetcsv($handle)) !== false) {
  
    // Skip BUY order with ID
    $uid_skip = false;
    foreach ($unique_ids as &$unique_id) {
      if ($line[2] == $unique_id) {
        $uid_skip = true;
      }
    }
    if (!$uid_skip) {
      $trades .= $line[0] . "," . $line[1] . "," . $line[2] . "," . $line[3] . "," . $line[4] . "," . $line[5] . "," . $line[6] . "\n";    
    }
  }
  fclose($handle);
  file_put_contents($log_trades, $trades);
}


?>