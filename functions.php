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
  global $log_trades, $log_history, $log_fees, $log_runs, $log_errors, $log_binance;

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
  } elseif ($type == "fee") {
    // Store in historical log
    file_put_contents($log_fees, $message, FILE_APPEND | LOCK_EX);    
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
    'symbol'     => $order['symbol'],
    'order'      => $order['orderId'],
    'time'       => $order['transactTime'],
    'status'     => $order['status'],
    'type'       => $order['type'],
    'side'       => $order['side'],    
    'price'      => $price,
    'base'       => $base,
    'quote'      => $quote,
    'commission' => $commission
  ]; 
  
  if ($debug) {echo "<br /><br />"; print_r($transaction); echo "<br /><br />";}
  return $transaction;
}


/** Get minimum quote for pair to meet Binance minimum order value **/
function minimumQuote() {

  // Declare some variables as global
  global $api, $binanceMinimum, $log_settings, $compounding, $multiplier;

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
  $set_coin['onOrder']      = $balances[$set_coin['baseAsset']]['onOrder'];

  // Get balance and price of BNB
  $balanceBNB = $balances['BNB']['available'];
  $priceBNB   = $api->price("BNB" . $set_coin['quoteAsset']);
  $set_coin['balanceBNB'] = $balanceBNB;
  $set_coin['priceBNB']   = $priceBNB;

  // Calculate total Binance balance and compouding in BUSD (only necessary when compounding)
  if (!empty($compounding)) {
    $TbalanceBTC = $api->btc_total;
    $btc_price   = $api->price("BTCBUSD");
    $TbalanceBUSD = $TbalanceBTC * $btc_price;  
    if ($set_coin['quoteAsset'] <> 'BUSD') {
      $comp_pair = $set_coin['baseAsset'] . 'BUSD';
      $comp_BUSD = $api->price($comp_pair);
    } else {
      $comp_BUSD = $compounding;
    }    
  }
  
  // Get price of coin in BUSD
  $pair_BUSD = $set_coin['baseAsset'] . 'BUSD';
  $set_coin['priceBUSD'] = $api->price($pair_BUSD);

  // Get balance and price of the coin in BUSD
  $set_coin['balanceBUSD'] = $set_coin['balance'] * $set_coin['priceBUSD'];
  $set_coin['onOrderBUSD'] = $set_coin['onOrder'] * $set_coin['priceBUSD'];
  
  // Check if notional is below the 10 BUSD (+10% to prevent issues) Binance threshold
  $set_coin['minBUY']     = ($binanceMinimum * 1.1) / $set_coin['priceBUSD'];
  $set_coin['minBUYBUSD'] = $binanceMinimum * 1.1;  

  // Correct for compounding only when compounding is set and in profit (> 1)
  $set_coin['compFactor'] = 1;
  if (!empty($compounding)) {
    if (($TbalanceBUSD / $comp_BUSD) > 1) {
      $set_coin['minBUY']     = $set_coin['minBUY'] * ($TbalanceBUSD / $comp_BUSD);
      $set_coin['minBUYBUSD'] = $set_coin['minBUYBUSD'] * ($TbalanceBUSD / $comp_BUSD);
      $set_coin['compFactor'] = $TbalanceBUSD / $comp_BUSD;
    }
  }
  
  // Correct for multiplier
  $set_coin['multiplier'] = $multiplier;
  $set_coin['minBUY']     = $set_coin['minBUY'] * $multiplier;
  $set_coin['minBUYBUSD'] = $set_coin['minBUYBUSD'] * $multiplier;
  
  // Correct for number of bots (TradingView integration)
  $multiplierTV    = 0;
  $log_tradingview = "data/log_tradingview.csv";
  if (file_exists($log_tradingview)) {
    
    // Find last line in file
    $handle = fopen($log_tradingview, "r");
    while (($line = fgetcsv($handle)) !== false) {
      
      $bots_requested = $line[1];
      $bots_received  = $line[2];
    }
    fclose($handle);
    
    // Cap to a maximum of 250%
    if (($bots_requested / $bots_received) > 2.5) {
      $multiplierTV = 2.5;
    } elseif (($bots_requested / $bots_received) < 1) {
      $multiplierTV = 1;     
    } else {
      $multiplierTV = $bots_requested / $bots_received;
    }
    $set_coin['multiplierTV'] = $multiplierTV;
  
    // Adjust minimum order value
    $set_coin['minBUY']     = $set_coin['minBUY'] * $multiplierTV;
    $set_coin['minBUYBUSD'] = $set_coin['minBUYBUSD'] * $multiplierTV;
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
  $minQuote['onOrder']      = $set_coin['onOrder'];        // How much of the base asset is onOrder
  $minQuote['onOrderBUSD']  = $set_coin['onOrderBUSD'];    // How much of the base asset is onOrder in BUSD  
  $minQuote['minBUY']       = $set_coin['minBUY'];         // Minimum BUY value in base (possibly corrected for compounding!)
  $minQuote['minBUYBUSD']   = $set_coin['minBUYBUSD'];     // Minimum BUY value in BUSD (possibly corrected for compounding!)
  $minQuote['balanceBNB']   = $set_coin['balanceBNB'];     // Current amount of BNB for paying fees
  $minQuote['priceBNB']     = $set_coin['priceBNB'];       // Current price of BNB for paying fees 
  $minQuote['compFactor']   = $set_coin['compFactor'];     // Compounding factor
  $minQuote['multiplier']   = $set_coin['multiplier'];     // Order value multiplier
  $minQuote['multiplierTV'] = $set_coin['multiplierTV'];   // Order value multiplier for TradingView integration
  
  return $minQuote;  
}


/** Round value to the nearest stepSize **/
function roundStep($value, $stepSize = 0.1) {

  $precision = log10((1 / $stepSize));
  $value = round($value, $precision);
  
  return $value;
}

/** Get TradingView recommendation **/
// $period = 1m: 1, 5m: 5, 15m: 15, 30m: 30, 1h: 60, 2h: 120, 4h: 240, 1W: 1W, 1M: 1M, 1d: leave emtpy (default)
function getTradingView($symbol, $period) {
	
	// Retrieve from TradingView
	$curl = curl_init();
  $postField = '{"symbols":{"tickers":["BINANCE:' . $symbol . '"],"query":{"types":[]}},"columns":["Recommend.All|' . $period . '"]}';
  curl_setopt_array($curl, array(
  	CURLOPT_URL => "https://scanner.tradingview.com/crypto/scan",
  	CURLOPT_RETURNTRANSFER => true,
  	CURLOPT_CUSTOMREQUEST => "POST",
  	CURLOPT_POSTFIELDS => $postField,
  	CURLOPT_HTTPHEADER => array(
  		"accept: */*",
  		"accept-language: en-GB,en-US;q=0.9,en;q=0.8",
  		"cache-control: no-cache",
  		"content-type: application/x-www-form-urlencoded",
  		"origin: https://www.tradingview.com",
  		"referer: https://www.tradingview.com/",
  		"user-agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.96 Safari/537.36"
  	)
	));

  // Store result
  try {
    $result = curl_exec($curl);
    if (isset($result) && !empty($result)) {
  	    $j = json_decode($result, true);
  		if (isset($j['data'][0]['d'][0])) {
  			$j = $j['data'][0]['d'][0];
  		}
    }
	} catch (Exception $e) { echo "Error: " . $e; }
	
  // Set recommendation
  $recommendation = "ERROR";
  if (($j >= -1) && ($j < -0.5)) {
    $recommendation = "STRONG_SELL";
  } elseif (($j >= -0.5) && ($j < -0.1)) {
    $recommendation = "SELL";
  } elseif (($j >= -0.1) && ($j <= 0.1)) {
    $recommendation = "NEUTRAL";
  } elseif (($j > 0.1)   && ($j <= 0.5)) {
    $recommendation = "BUY";
  } elseif (($j > 0.5)   && ($j <= 1.0)) {
    $recommendation = "STRONG_BUY";
  }

  return $recommendation;
}

?>