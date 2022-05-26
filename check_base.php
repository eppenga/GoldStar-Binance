<?php

/**
 * @author Ebo Eppenga
 * @copyright 2022
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Binance API from JaggedSoft.
 * 
 * check_base.php
 * Checks if it is required to buy additional base coin to fulfill fees.
 * Only runs every 24 hours, should be more than enough.
 * 
 */

// Check if the settings file exists and is up to date
$settings_check = false;
if (!file_exists($log_settings)) {
  $settings_check = true;
} elseif ((time() - filemtime($log_settings)) > $repeatrun) {
  $settings_check = true;
}

// Create a new settings file
if ($settings_check) {
  
  // Determine minNotional, baseAsset, quoteAsset, etc..
  $info = $api->exchangeInfo();
  $minimums = [];

  foreach($info["symbols"] as $symbols) {
    $filters = ["minNotional" => "0.001","minQty" => "1","maxQty" => "10000000","stepSize" => "1","minPrice" => "0.00000001","maxPrice" => "100000"];
    $filters["status"] = $symbols["status"];
    $filters["baseAsset"] = $symbols["baseAsset"];
    $filters["quoteAsset"] = $symbols["quoteAsset"];
    $filters["orderTypes"] = implode(",",$symbols["orderTypes"]);
  
  	foreach($symbols["filters"] as $filter) {
  		if ($filter["filterType"] == "MIN_NOTIONAL" ) {
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

  // Write new settings file: pair, status, baseAsset, quoteAsset, minNotional, stepSize, tickSize
  $message  = $pair . "," . $minimums[$pair]['status'] . ",";
  $message .= $minimums[$pair]['baseAsset'] . "," . $minimums[$pair]['quoteAsset'] .","; 
  $message .= $minimums[$pair]['minNotional'] . "," . $minimums[$pair]['stepSize'] . "," . $minimums[$pair]['tickSize'];
  file_put_contents($log_settings, $message);

  // Report
  echo "<b>Settings</b><br />";
  echo "Symbol    : " . $pair . "<br />";
  echo "Status    : " . $minimums[$pair]['status'] . "<br />";
  echo "baseAsset : " . $minimums[$pair]['baseAsset'] . "<br />";
  echo "quoteAsset: " . $minimums[$pair]['quoteAsset'] . "<br />";
  echo "minNotion : " . $minimums[$pair]['minNotional'] . "<br />";
  echo "stepSize  : " . $minimums[$pair]['stepSize'] . "<br />";
  echo "tickSize  : " . $minimums[$pair]['tickSize'] . "<br /><br />";
    
  // Check if we can continue
  if ($minimums[$pair]['status'] <> "TRADING") {
    $message = date("Y-m-d H:i:s") . "," . $id . ",Error: Pair not trading";
    echo $message;
    logCommand($message, "error");
    exit();
  }
  
  echo "<hr /><br />";
}

?>
