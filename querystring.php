<?php

/**
 * @author Ebo Eppenga
 * @copyright 2021
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Binance API from JaggedSoft.
 * 
 * querystring.php
 * Check all query string parameters.
 * 
 */


// Get and validate key
$get_url_key = $_GET["key"];
if (!empty($url_key)) {
  if ($get_url_key <> $url_key) {
    $message = date("Y-m-d H:i:s") . "," . $id . ",Error: Security key did not validate";
    echo $message;
    logCommand($message, "error");
    exit();
  }
}

// Get ordertype
$tradetype = strtoupper($_GET["trade"]);
if (empty($tradetype)) {
  $tradetype = "PAPER";
} elseif (($tradetype <> "LIVE") &&
          ($tradetype <> "PAPER")) {
  $message = date("Y-m-d H:i:s") . "," . $id . ",Error: Trading type incorrect";
  echo $message;
  logCommand($message, "error");
  exit();            
}
if ($tradetype == "PAPER") {$paper = true;} else {$paper = false;}
if ($paper) {$tradetype = "PAPER";} else {$tradetype = "LIVE";}

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
    if ($tradetype <> "LIVE") {
      $message = date("Y-m-d H:i:s") . "," . $id . ",Error: LIMIT order can only work with LIVE trading";      
      echo $message;
      logCommand($message, "error");
      exit();
    }
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

?>