<?php

/**
 * @author Ebo Eppenga
 * @copyright 2021
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Binance API from JaggedSoft.
 * 
 * functions.php
 * All functions required for GoldStar to run properly.
 * 
 */

/** Log to files **/
function logCommand($logcommand, $type) {
  
  // Declare some variables as global
  global $log_trades, $log_history, $log_runs, $log_errors, $log_binance, $log_all;

  if ($type <> "binance") {
    // Standard log format
    $message = $logcommand . "\n";
    $message = str_replace("\n\n", "\n", $message);
  } else {
    // Binance log format
    $message  = "==============================================================================================================\n";
    $message .= "Timestamp: " . date("Y-m-d H:i:s") . "\n\n";
    $message .= print_r($logcommand, true);
    $message .= "==============================================================================================================\n\n";    
  }

  if ($type == "buy") {
    // Store in active trade log
    file_put_contents($log_trades, $message, FILE_APPEND | LOCK_EX);
  } elseif ($type == "all") {
    // Store all trades in one file for analysis
    file_put_contents($log_all, $message, FILE_APPEND | LOCK_EX);
  } elseif ($type == "history") {
    // Store in historical log
    file_put_contents($log_history, $message, FILE_APPEND | LOCK_EX);    
    } elseif ($type == "run") {
    // Store in runtime log
    file_put_contents($log_runs, $message, FILE_APPEND | LOCK_EX);    
  } elseif ($type == "binance") {
    // Store in Binance log
    file_put_contents($log_binance, $message, FILE_APPEND | LOCK_EX);
  } elseif ($type == "error") {
    // Store in errors log
    file_put_contents($log_errors, $message, FILE_APPEND | LOCK_EX);
  } else {
    // Store in unknowns also in errors log
    $message = date("Y-m-d H:i:s") . ",Error: Unknown";
    file_put_contents($log_errors, $message, FILE_APPEND | LOCK_EX);
  }
}


/** Extract data from Binance order **/
function extractBinance($order) {
  
  // Calculate commission
  $commission = 0;
  foreach ($order['fills'] as $fill) {
    $commission = $commission + $fill['commission'];
  }
  
  // Correct Base and Quote quantity
  $base  = $order['executedQty'];
  $quote = $order['cummulativeQuoteQty'];
  //if ($order['side'] == "BUY")  {$base  = $base - $commission;}
  //if ($order['side'] == "SELL") {$quote = $quote - $commission;}
  
  // Calculate price
  $price = $order['cummulativeQuoteQty'] / $order['executedQty']; 
  
  $transaction = [
    'symbol' => $order['symbol'],
    'order' => $order['orderId'],
    'time' => $order['transactTime'],
    'status' => $order['status'],
    'type' =>  $order['type'],
    'side' =>  $order['side'],    
    'price' => $price,
    'base' => $base,
    'quote' => $quote,
    'commission' => $commission
  ]; 
  
  if ($debug) {echo "<br /><br />"; print_r($transaction); echo "<br /><br />";}
  return $transaction;
}


/** Get minimum quote for pair to meet Binance minimum order value **/
function minimumQuote() {

  // Declare some variables as global
  global $api, $binanceMinimum, $log_settings;

  // Get settings
  $settings = explode(",", file_get_contents($log_settings));
  
  $set_coin['baseAsset']   = $settings[2];
  $set_coin['quoteAsset']  = $settings[3];
  $set_coin['minNotional'] = $settings[4];
  $set_coin['stepSize']    = $settings[5];      

  // Get balance of coin
  $ticker   = $api->prices();
  $balances = $api->balances($ticker);
  $balance  = $balances[$set_coin['baseAsset']]['available'];
  $set_coin['balance'] = $balance;

  // Get price of coin in BUSD
  $pair_BUSD = $set_coin['baseAsset'] . 'BUSD';
  $set_coin['priceBUSD'] = $api->price($pair_BUSD);

  // Get balance of the coin in BUSD
  $set_coin['balanceBUSD'] = $set_coin['balance'] * $set_coin['priceBUSD'];
  
  // Check if notional is below the 10 BUSD (+10% to prevent issues) Binance threshold
  $set_coin['minBUY'] = ($binanceMinimum * 1.1) / $set_coin['priceBUSD'];
  $set_coin['minBUY'] = roundStep($set_coin['minBUY'], $set_coin['stepSize']);
  $set_coin['minBUYBUSD'] = $binanceMinimum * 1.1;  

  // Report
  if ($debug) {
  echo "<b>Minimum order</b><br />";
    echo "Price in BUSD  : " . $set_coin['priceBUSD'] . "<br />";
    echo "Balance in Base: " . $set_coin['balance'] . "<br />";
    echo "Balance in BUSD: " . $set_coin['balanceBUSD'] . "<br />";
    echo "Min BUY in Base: " . $set_coin['minBUY'] . "<br />";
    echo "Min BUY in BUSD: " . $set_coin['minBUYBUSD'] . "<br /><br />";    
  }
  
  // Return enough data
  $minQuote['balance']     = $set_coin['balance'];
  $minQuote['balanceBUSD'] = $set_coin['balanceBUSD'];
  $minQuote['minBUY']      = $set_coin['minBUY']; 
  $minQuote['minBUYBUSD']  = $set_coin['minBUYBUSD'];
  
  return $minQuote;
  
}


/** Round value to the nearest stepSize **/
function roundStep($value, $stepSize = 0.1) {

  $precision = strlen(substr(strrchr(rtrim($value,'0'), '.'), 1));
  return round((($value / $stepSize) | 0) * $stepSize, $precision);
  
}


?>