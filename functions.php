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
  global $log_trades, $log_history, $log_runs, $log_errors, $log_binance;

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
  if (isset($order['fills'])) {
    foreach ($order['fills'] as $fill) {
      $commission = $commission + $fill['commission'];
    }    
  }
  
  // Correct Base and Quote quantity
  // On BUY commission is paid on base, on SELL on quote
  $base  = $order['executedQty'];
  $quote = $order['cummulativeQuoteQty'];

  // Calculate price
  if ($order['executedQty'] <> 0) {
    $price = $order['cummulativeQuoteQty'] / $order['executedQty'];    
  } else {
    $price = 0;
  }
  
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
  global $api, $binanceMinimum, $log_settings, $compounding;

  // Get settings
  if (file_exists($log_settings)) {
    $settings = explode(",", file_get_contents($log_settings));    
  } else {
    $message = date("Y-m-d H:i:s") . "," . $id . ",Error: No settings file was created automatically!";
    echo $message;
    logCommand($message, "error");
    exit();
  }
  
  $set_coin['symbol']      = $settings[0];
  $set_coin['status']      = $settings[1];
  $set_coin['baseAsset']   = $settings[2];
  $set_coin['quoteAsset']  = $settings[3];
  $set_coin['minNotional'] = $settings[4];
  $set_coin['stepSize']    = $settings[5];
  $set_coin['tickSize']    = $settings[6];

  // Get balance of coin
  $ticker       = $api->prices();
  $balances     = $api->balances($ticker);
  $balance      = $balances[$set_coin['baseAsset']]['available'];
  $balanceQuote = $balances[$set_coin['quoteAsset']]['available'];
  $set_coin['balance']      = $balance;
  $set_coin['balanceQuote'] = $balanceQuote;

  // Calculate total Binance balance and compouding in BUSD (only necessary when compounding)
  if (!empty($compounding)) {
    $TbalanceBTC  = $api->btc_total;
    $btc_price    = $api->price("BTCBUSD");
    $TbalanceBUSD = $TbalanceBTC * $btc_price;  
    if ($set_coin['quoteAsset'] <> 'BUSD') {
      $comp_pair  = $set_coin['baseAsset'] . 'BUSD';
      $comp_BUSD  = $api->price($comp_pair);
    } else {
      $comp_BUSD  = $compounding;
    }    
  }
  
  // Get price of coin in BUSD
  $pair_BUSD = $set_coin['baseAsset'] . 'BUSD';
  $set_coin['priceBUSD'] = $api->price($pair_BUSD);

  // Get balance of the coin in BUSD
  $set_coin['balanceBUSD'] = $set_coin['balance'] * $set_coin['priceBUSD'];
  
  // Check if notional is below the 10 BUSD (+10% to prevent issues) Binance threshold
  $set_coin['minBUY']     = ($binanceMinimum * 1.1) / $set_coin['priceBUSD'];
  $set_coin['minBUYBUSD'] = $binanceMinimum * 1.1;  

  // Correct for compounding only when compounding is set and in profit (> 1)
  if (!empty($compounding)) {
    if (($TbalanceBUSD / $comp_BUSD) > 1) {
      $set_coin['minBUY']     = $set_coin['minBUY'] * ($TbalanceBUSD / $comp_BUSD);
      $set_coin['minBUYBUSD'] = $set_coin['minBUYBUSD'] * ($TbalanceBUSD / $comp_BUSD);
      /*
      echo "<i>Compounding multiplier active at x" . ($TbalanceBUSD / $comp_BUSD) . "</i><br /><br /><hr /><br />";
      echo "Total Balance BUSD: " . $TbalanceBTC . "<br>";
      echo "Compound in BUSD: " . $comp_BUSD . "<br>";
      echo "minBUY: " . $set_coin['minBUY'] . "<br>";
      echo "minBUYBUSD: " . $set_coin['minBUYBUSD'] . "<br>";
      */      
    }
  }
  
  // Fix Binance stepSize precission error
  $set_coin['minBUY'] = roundStep($set_coin['minBUY'], $set_coin['stepSize']);

  // Report
  if ($debug) {
  echo "<b>Minimum order</b><br />";
    echo "Price in BUSD  : " . $set_coin['priceBUSD'] . "<br />";
    echo "Balance in Base: " . $set_coin['balance'] . "<br />";
    echo "Balance in BUSD: " . $set_coin['balanceBUSD'] . "<br />";
    if (!empty($compounding)) {echo "Compounding    : " . ($TbalanceBUSD / $comp_BUSD) . "<br />";}
    echo "Min BUY in Base: " . $set_coin['minBUY'] . "<br />";
    echo "Min BUY in BUSD: " . $set_coin['minBUYBUSD'] . "<br />";
    if (!empty($compounding)) {echo "<i>Compounding multiplies minBUY(BUSD) by factor given.</i><br />";}
    echo "<br />";
  }
  
  // Return enough data
  $minQuote['symbol']       = $set_coin['symbol'];         // Pair (also known as symbol)
  $minQuote['status']       = $set_coin['status'];         // Binance order status (ie. FILLED)
  $minQuote['baseAsset']    = $set_coin['baseAsset'];      // Quantity in base asset
  $minQuote['quoteAsset']   = $set_coin['quoteAsset'];     // Quantity in quote asset
  $minQuote['minNotional']  = $set_coin['minNotional'];    // Minimum order in base asset, however this is overruled by the Binance minimum 10 BUSD value
  $minQuote['stepSize']     = $set_coin['stepSize'];       // Incremental size for base asset
  $minQuote['tickSize']     = $set_coin['tickSize'];       // Incremental size for price asset
  $minQuote['balance']      = $set_coin['balance'];        // How much of the base asset is available on Binance
  $minQuote['balanceBUSD']  = $set_coin['balanceBUSD'];    // The above only then expressed in BUSD
  $minQuote['balanceQuote'] = $set_coin['balanceQuote'];   // How much of the quote asset is available on Binance  
  $minQuote['minBUY']       = $set_coin['minBUY'];         // Minimum BUY value in base (possibly corrected for compounding!)
  $minQuote['minBUYBUSD']   = $set_coin['minBUYBUSD'];     // Minimum BUY value in BUSD (possibly corrected for compounding!)
  
  return $minQuote;  
}


/** Round value to the nearest stepSize **/
function roundStep($value, $stepSize = 0.1) {

  $precision = log10((1 / $stepSize));
  $value = round($value, $precision);
  
  return $value;
}

?>