<?php

/**
 * @author Ebo Eppenga
 * @copyright 2022
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Binance API from JaggedSoft.
 * 
 * querystring.php
 * Reads and checks all query string parameters.
 * 
 */


// Get and validate key
$get_url_key = $_GET["key"];
$get_url_key = str_replace("_", "", $get_url_key);
if (!empty($url_key)) {
  if ($get_url_key <> str_replace("_", "", $url_key)) {
    $message = date("Y-m-d H:i:s") . "," . $id . ",Error: Security key did not validate";
    echo $message;
    logCommand($message, "error");
    exit();
  }
}

// Get BUY or SELL
$command = strtoupper($_GET["action"]);
if ($command == "BUY") {
  $action = "BUY";
} elseif ($command == "SELL") {
  $action = "SELL";
} else {
  $message = date("Y-m-d H:i:s") . "," . $id . ",Error: No BUY or SELL";
  echo $message;
  logCommand($message, "error");
  exit();
}

// Get pair
$pair = strtoupper($_GET["pair"]);
if (empty($pair)) {
  $message = date("Y-m-d H:i:s") . "," . $id . ",Error: No pair given";
  echo $message;
  logCommand($message, "error");
  exit();
}

// Limit order
if (isset($_GET["limit"])) {
  $limit = strtoupper($_GET["limit"]);
  if ($limit == "TRUE") {
    $limit = true;
  } else {
    $limit = false;
  }
}  

// Override spread
if (isset($_GET["spread"])) {
  $temp_spread = $_GET["spread"];
  if (($temp_spread >= 0) && ($temp_spread < 5)) {
    $spread = $temp_spread;
  } else {
    $message = date("Y-m-d H:i:s") . "," . $id . ",Error: Spread can only be between 0% and 5%";      
    echo $message;
    logCommand($message, "error");
    exit();    
  }
}

// Override profit
if (isset($_GET["markup"])) {
  $temp_markup = $_GET["markup"];
  if (($temp_markup >= -10) && ($temp_markup < 25)) {
    $markup = $temp_markup;
  } else {
    $message = date("Y-m-d H:i:s") . "," . $id . ",Error: Markup can only be between -10% and 25%";      
    echo $message;
    logCommand($message, "error");
    exit();        
  }
}

// Get compounding base
if (isset($_GET["comp"])) {
  $compounding = $_GET["comp"];
}

// Get multiplier
if (isset($_GET["mult"])) {
  $temp_mult = $_GET["mult"];
  if ($temp_mult > 1) {
    $multiplier = $temp_mult;
  } else {
    $message = date("Y-m-d H:i:s") . "," . $id . ",Error: Order value multiplier must be larger than 1";
    echo $message;
    logCommand($message, "error");
    exit();
  }
}

// Get Order ID
if (isset($_GET["orderid"])) {
  $orderID = $_GET["orderid"];
} else {
  $orderID = 0;
}

// Override TradingView
if (isset($_GET["tv"])) {
  $tv_advice = strtoupper($_GET["tv"]);
  if ($tv_advice == "TRUE") {
    $tv_advice = true;
  } else {
    $tv_advice = false;
  }
}

if ($tv_advice) {
  // Get additional TradingView parameters
  $tv_recomCheck = false;
  
  // Get minimum TradingView recommendation
  if (isset($_GET["tvmin"])) {
    $tv_recomMin = $_GET["tvmin"];
    if (($tv_recomMin < 0) && ($tv_recomMin > 1)) {
      $tv_recomCheck = true;
    }
  }

  // Get maximum TradingView recommendation
  if (isset($_GET["tvmax"])) {
    $tv_recomMax = $_GET["tvmax"];
    if (($tv_recomMax < 0) && ($tv_recomMax > 1)) {
      $tv_recomCheck = true;
    }
  }

  // recomMin must be smaller than recomMax
  if ($tv_recomMin > $tv_recomMax) {
    $tv_recomCheck = true;
  }
  
  // Check if recomMin / Max are correct
  if ($tv_recomCheck) {
    $message = date("Y-m-d H:i:s") . "," . $id . ",Error: recomMin / Max are not set correct";
    echo $message;
    logCommand($message, "error");
  }

  // Get TradingView periods
  if (isset($_GET['tvpers'])) {
    $temp_tvp = $_GET['tvpers'];
    $tv_periods = explode(",",$temp_tvp);
  }
} else {

  //TradingView advice not active
  $tv_recomMin = 0;
  $tv_recomMax = 0;
  $tv_periods  = array();
}

// Debug
/*
echo "TV Advice  : ";
if ($tv_advice) {echo "True<br />";} else {echo "False<br />";}
echo "TV RecomMin: " . $tv_recomMin . "<br />";
echo "TV RecomMax: " . $tv_recomMax . "<br />";
echo "TV Periods : ";
print_r($tv_periods);
exit();
*/

?>