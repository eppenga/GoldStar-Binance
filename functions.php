<?php

/**
 * @author Ebo Eppenga
 * @copyright 2022
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
  
  // Declare some variables as global
  global $api, $set_coin;

  // Calculate commission
  $counter    = 0;
  $commission = 0;
  if (isset($order['fills'])) {
    foreach ($order['fills'] as $fill) {
      if ($counter == 0) {
        $commasset = $fill['commissionAsset'];
        $commpair  = $commasset . $set_coin['quoteAsset'];
        $commprice = (float)$api->price($commpair);
      }
      $commission = $commission + $fill['commission'] * $commprice;
      $counter = $counter + 1;
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
    $message = date("Y-m-d H:i:s") . ",Error: No settings file was created automatically!";
    echo $message;
    logCommand($message, "error");
    exit();
  }
  
  // Assign settings
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

  // Assign balances
  $set_coin['balance']      = $balance;
  $set_coin['balanceQuote'] = $balanceQuote;
  $set_coin['onOrder']      = $balances[$set_coin['baseAsset']]['onOrder'];

  // Get balance and price of BNB
  $balanceBNB              = $balances['BNB']['available'];
  $priceBNB                = $api->price("BNB" . $set_coin['quoteAsset']);
  $set_coin['balanceBNB']  = $balanceBNB;
  $set_coin['priceBNB']    = $priceBNB;

  // Calculate total Binance balance and compouding in USDT (only necessary when compounding)
  if (!empty($compounding)) {
    
    // Calculate total current Binance balance in USDT and BTC
    $btc_price               = floatval($api->price("BTCUSDT"));
    $total_BTC               = floatval($api->btc_total);
    $total_USDT              = $total_BTC * $btc_price;
    $set_coin['totalBTC']    = $total_BTC;
    $set_coin['totalUSDT']   = $total_USDT;
    
    // Calculate total start balance in USDT
    if ($set_coin['quoteAsset'] <> "USDT") {
      $comp_pair             = $set_coin['quoteAsset'] . "USDT";
      $comp_USDT             = floatval($api->price($comp_pair)) * $compounding;
      $set_coin['startUSDT'] = $comp_USDT;
    } else {
      $set_coin['startUSDT'] = $compounding;
    }
  }

  // Get price of coin in USDT
  $pair_USDT               = $set_coin['baseAsset'] . 'USDT';
  $set_coin['priceUSDT']   = floatval($api->price($pair_USDT));

  // Get balance and price of the coin in USDT
  $set_coin['balanceUSDT'] = $set_coin['balance'] * $set_coin['priceUSDT'];
  $set_coin['onOrderUSDT'] = $set_coin['onOrder'] * $set_coin['priceUSDT'];
  
  // Check if notional is below the 10 USDT (+10% to prevent issues) Binance threshold
  $set_coin['minBUY']      = ($binanceMinimum * 1.1) / $set_coin['priceUSDT'];
  $set_coin['minBUYUSDT']  = $binanceMinimum * 1.1; 

  // Correct for compounding only when compounding is set and in profit (> 1)
  $set_coin['compFactor']     = 1;
  if (!empty($compounding)) {
    $set_coin['compFactor']   = $set_coin['totalUSDT'] / $set_coin['startUSDT'];
    if ($set_coin['compFactor'] > 1) {
      $set_coin['minBUY']     = $set_coin['minBUY'] * $set_coin['compFactor'];
      $set_coin['minBUYUSDT'] = $set_coin['minBUYUSDT'] * $set_coin['compFactor'];
    }
  }

  // Correct for multiplier
  $set_coin['multiplier'] = $multiplier;
  $set_coin['minBUY']     = $set_coin['minBUY'] * $multiplier;
  $set_coin['minBUYUSDT'] = $set_coin['minBUYUSDT'] * $multiplier;
  
  // Fix Binance stepSize precission error
  $set_coin['minBUY'] = roundStep($set_coin['minBUY'], $set_coin['stepSize']);

  // Report
  $debug = false;
  if ($debug) {
  echo "<b>Minimum order</b><br />";
    echo "Price in USDT  : " . $set_coin['priceUSDT'] . "<br />";
    echo "Balance in Base: " . $set_coin['balance'] . "<br />";
    echo "Balance in USDT: " . $set_coin['balanceUSDT'] . "<br />";
    echo "Start in USDT  : " . $set_coin['startUSDT'] . "<br />";
    echo "Now in USDT    : " . $set_coin['totalUSDT'] . "<br />";
    if (!empty($compounding)) {echo "Compounding    : " . $set_coin['compFactor'] . "<br />";}
    echo "Min BUY in Base: " . $set_coin['minBUY'] . "<br />";
    echo "Min BUY in USDT: " . $set_coin['minBUYUSDT'] . "<br />";
    if (!empty($compounding)) {echo "<br /><i>Compounding multiplies minBUY(USDT) by factor given.</i><br />";}
    echo "<br />";
  }
  
  // Return data
  $minQuote['symbol']       = $set_coin['symbol'];         // Pair (also known as symbol)
  $minQuote['status']       = $set_coin['status'];         // Binance order status (ie. FILLED)
  $minQuote['baseAsset']    = $set_coin['baseAsset'];      // Quantity in base asset
  $minQuote['quoteAsset']   = $set_coin['quoteAsset'];     // Quantity in quote asset
  $minQuote['minNotional']  = $set_coin['minNotional'];    // Minimum order in base asset, however this is overruled by the Binance minimum 10 BUSD value
  $minQuote['stepSize']     = $set_coin['stepSize'];       // Incremental size for base asset
  $minQuote['tickSize']     = $set_coin['tickSize'];       // Incremental size for price asset
  $minQuote['priceUSDT']    = $set_coin['priceUSDT'];      // Price of base asset in USDT
  $minQuote['balance']      = $set_coin['balance'];        // How much of the base asset is available on Binance
  $minQuote['balanceUSDT']  = $set_coin['balanceUSDT'];    // How much of the base asset is available on Binance in USDT
  $minQuote['balanceQuote'] = $set_coin['balanceQuote'];   // How much of the quote asset is available on Binance
  $minQuote['onOrder']      = $set_coin['onOrder'];        // How much of the base asset is onOrder
  $minQuote['onOrderUSDT']  = $set_coin['onOrderUSDT'];    // How much of the base asset is onOrder in USDT
  $minQuote['minBUY']       = $set_coin['minBUY'];         // Minimum BUY value in base (possibly corrected for compounding!)
  $minQuote['minBUYUSDT']   = $set_coin['minBUYUSDT'];     // Minimum BUY value in BUSD (possibly corrected for compounding!)
  $minQuote['balanceBNB']   = $set_coin['balanceBNB'];     // Current amount of BNB for paying fees
  $minQuote['priceBNB']     = $set_coin['priceBNB'];       // Current price of BNB for paying fees 
  $minQuote['compFactor']   = $set_coin['compFactor'];     // Compounding factor
  $minQuote['multiplier']   = $set_coin['multiplier'];     // Order value multiplier
  
  
  if ($debug) {
    print_r($minQuote);
    exit();
  }

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
  if (($j > -1) && ($j < 1)) {
    $recommendation = $j;
  }

  return $recommendation;
}

/** Evaluate TradingView recommendation for array of periods **/
// STRONG_SELL: -1...-0.5, SELL: -0.5...-0.1, NEUTRAL: -0.1...0.1, BUY: 0.1...0.5, STRONG_BUY: 0.5...1
function evalTradingView($symbol, $periods, $tv_recomMin, $tv_recomMax) {

  // Get recommendations for periods
  foreach ($periods as $period) {
    $recommendation    = getTradingView($symbol, $period);
    $recommendations[] = $recommendation;
    echo " " . $period . ":" . round($recommendation, 2). ",";
  }

  // Determine total recommendation
  $evalTV = true;
  foreach ($recommendations as $recommendation) {
    if (($recommendation < $tv_recomMin) || ($recommendation > $tv_recomMax)) {
      $evalTV = false;
    }
  }

  return $evalTV;
}

?>