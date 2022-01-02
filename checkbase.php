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


// Check if settings are less than 24h old or exist
$settings_check = false;
if (!file_exists($log_settings)) {
  $settings_check = true;
} elseif ((time() - filemtime($log_settings)) > $repeatrun) {
  $settings_check = true;
}

// Determine minNotional, baseAsset, quoteAsset, etc..
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
  				$filters["tickSize"] = $filter["tickSize"];          
  			} else if ( $filter["filterType"] == "LOT_SIZE" ) {
  				$filters["minQty"] = $filter["minQty"];
  				$filters["maxQty"] = $filter["maxQty"];
  				$filters["stepSize"] = $filter["stepSize"];
  			}
  		}
  $minimums[$symbols["symbol"]] = $filters;
  }

  // Create an array of usefull values
  $set_coin['symbol']      = $pair;
  $set_coin['status']      = $minimums[$pair]['status'];
  $set_coin['baseAsset']   = $minimums[$pair]['baseAsset'];
  $set_coin['quoteAsset']  = $minimums[$pair]['quoteAsset'];
  $set_coin['minNotional'] = $minimums[$pair]['minNotional'];
  $set_coin['stepSize']    = $minimums[$pair]['stepSize'];
  $set_coin['tickSize']    = $minimums[$pair]['tickSize'];  

  // Write new settings file: pair, status, baseAsset, quoteAsset, minNotional, stepSize, tickSize
  $message  = $set_coin['symbol'] . "," . $set_coin['status'] . ",";
  $message .= $set_coin['baseAsset'] . "," . $set_coin['quoteAsset'] .","; 
  $message .= $set_coin['minNotional'] . "," . $set_coin['stepSize'] . "," . $set_coin['tickSize'];
  file_put_contents($log_settings, $message);

  // Report
  echo "<b>Settings file</b><br />";
  echo "Symbol         : " . $set_coin['symbol'] . "<br />";
  echo "Status         : " . $set_coin['status'] . "<br />";
  echo "baseAsset      : " . $set_coin['baseAsset'] . "<br />";
  echo "quoteAsset     : " . $set_coin['quoteAsset'] . "<br />";
  echo "minNotional    : " . $set_coin['minNotional'] . "<br />";
  echo "stepSize       : " . $set_coin['stepSize'] . "<br />";
  echo "tickSize       : " . $set_coin['tickSize'] . "<br /><br />";

  // Determine minimum quote quantity to meet Binance minimum order value
  $set_coin_temp = minimumQuote();
  $set_coin['priceBUSD']   = $set_coin_temp['priceBUSD'];             // Price of base in BUSD
  $set_coin['balance']     = $set_coin_temp['balance'];               // Balance in base
  $set_coin['balanceBUSD'] = $set_coin_temp['balanceBUSD'];           // Balance in BUSD
  $set_coin['minBUY']      = $set_coin_temp['minBUY'];                // Minimum BUY order in base
  $set_coin['minBUYBUSD']  = $set_coin_temp['minBUYBUSD'];            // Minimum BUY order in BUSD
   
  // Check if we can continue
  if ($set_coin['status'] <> "TRADING") {
    $message = date("Y-m-d H:i:s") . "," . $id . ",Error: Pair not trading";
    echo $message;
    logCommand($message, "error");
    exit();
  }

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
  
  echo "<hr /><br />";
} else {

  if ($debug) {
    echo "<i>Settings file still up to date, doing nothing...</i><br /><br />";
    echo "<hr /><br />";    
  }
}

?>
