<?php

/**
 * @author Ebo Eppenga
 * @copyright 2021
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Binance API from JaggedSoft.
 * 
 * checkbase.php
 * Checks if it is required to buy additional base coin to fulfill fees.
 * Only runs every 24 hours, should be more than enough.
 * 
 */

// Binance minimum order value
$binanceMinimum = 10;

// Run every 24 hours
$repeatrun = 24 * 60 * 60;

// Check if settings are less than 24h old or exist
$settings_check = false;
if (!file_exists($log_settings)) {
  $settings_check = true;
} elseif ((time() - filemtime($log_settings)) > $repeatrun) {
  $settings_check = true;
}

// Determine minNotional, baseAsset, baseAsset, etc..
if ($settings_check) {
  
  echo "<i>Creating new settings file...</i></br /><br />";
  $info = $api->exchangeInfo();
  $minimums = [];
  foreach($info["symbols"] as $symbols){
  $filters = ["minNotional" => "0.001","minQty" => "1","maxQty" => "10000000","stepSize" => "1","minPrice" => "0.00000001","maxPrice" => "100000"];
  $filters["status"] = $symbols["status"];
  $filters["baseAsset"] = $symbols["baseAsset"];
  $filters["quoteAsset"] = $symbols["quoteAsset"];
  $filters["orderTypes"] = implode(",",$symbols["orderTypes"]);
  	foreach($symbols["filters"] as $filter){
  		if ( $filter["filterType"] == "MIN_NOTIONAL" ) {
  				$filters["minNotional"] = $filter["minNotional"];
  			} else if ( $filter["filterType"] == "PRICE_FILTER" ) {
  				$filters["minPrice"] = $filter["minPrice"];
  				$filters["maxPrice"] = $filter["maxPrice"];
  			} else if ( $filter["filterType"] == "LOT_SIZE" ) {
  				$filters["minQty"] = $filter["minQty"];
  				$filters["maxQty"] = $filter["maxQty"];
  				$filters["stepSize"] = $filter["stepSize"];
  			}
  		}
  $minimums[$symbols["symbol"]] = $filters;
  }

  $set_coin['symbol']     = $pair;
  $set_coin['status']     = $minimums[$pair]['status'];
  $set_coin['baseAsset']  = $minimums[$pair]['baseAsset'];
  $set_coin['quoteAsset'] = $minimums[$pair]['quoteAsset'];
  $set_coin['stepSize']   = $minimums[$pair]['stepSize']; 

  // Get balance of coin
  $ticker   = $api->prices();
  $balances = $api->balances($ticker);
  $balance  = $balances[$set_coin['baseAsset']]['available'];
  $set_coin['balance'] = $balance;

  // Get price of coin in BUSD
  $pair_BUSD   = $set_coin['baseAsset'] . 'BUSD';
  $set_coin['priceBUSD']  = $api->price($pair_BUSD);

  // Get balance of the coin in BUSD
  $set_coin['balanceBUSD'] = $set_coin['balance'] * $set_coin['priceBUSD'];
  
  // Check to see if notional is still below the 10 BUSD threshold
  //$set_coin['balanceBUSD'] = 2; // DEBUG TEMP!!!! REMOVE!!!
  $set_coin['minBUY'] = ($binanceMinimum * 1.1) / $set_coin['priceBUSD'];
  $set_coin['minBUY'] = roundStep($set_coin['minBUY'], $set_coin['stepSize']);
  $set_coin['minBUYBUSD'] = $binanceMinimum * 1.1;  
    
  // Check if we can continue
  if ($set_coin['status'] <> "TRADING") {
    $message = date("Y-m-d H:i:s") . ",Error: Pair not trading";
    echo $message;
    logCommand($message, "error");
    exit();
  }

  // Report
  echo "<b>Settings file</b><br />";
  echo "Symbol         : " . $set_coin['symbol'] . "<br />";
  echo "Status         : " . $set_coin['status'] . "<br />";
  echo "baseAsset      : " . $set_coin['baseAsset'] . "<br />";
  echo "quoteAsset     : " . $set_coin['quoteAsset'] . "<br />";
  echo "Stepsize       : " . $set_coin['stepSize'] . "<br />";
  echo "Price in BUSD  : " . $set_coin['priceBUSD'] . "<br />";
  echo "Balance in Base: " . $set_coin['balance'] . "<br />";
  echo "Balance in BUSD: " . $set_coin['balanceBUSD'] . "<br />";
  echo "Min BUY in Base: " . $set_coin['minBUY'] . "<br />";
  echo "Min BUY in BUSD: " . $set_coin['minBUYBUSD'] . "<br /><br />";

  // Check if we need more of coin
  if ($set_coin['balanceBUSD'] < $set_coin['minBUYBUSD']) {
    // Buying more
    if (!$paper) {
      echo "<i>Not enough balance to pay fees, buying at exchange...</i><br /><br />";
      $order = $api->marketBuy($set_coin['symbol'], $set_coin['minBUY']);
      logCommand($order, "binance");
    } else {
      echo "<i>Not enough balance to pay fees, skipping because PAPER trading...</i><br /><br />";
    }
  }
  
  // Write new settings file
  // Pair, status, baseAsset, quoteAsset, stepSize, balance
  $message  = $set_coin['symbol'] . "," . $set_coin['status'] . ",";
  $message .= $set_coin['baseAsset'] . "," . $set_coin['quoteAsset'] .","; 
  $message .= $set_coin['stepSize'] . "," . $set_coin['balance'];
  file_put_contents($log_settings, $message);
  
  echo "<hr /><br />";
} else {

  if ($debug) {
    echo "<i>Settings file still up to date, doing nothing...</i><br /><br />";
    echo "<hr /><br />";    
  }
}

?>
