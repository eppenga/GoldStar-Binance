<?php

/**
 * @author Ebo Eppenga
 * @copyright 2022
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Binance API from JaggedSoft.
 * 
 * check_logfiles.php
 * Checks if all logfiles exist and if not create empty logs.
 * 
 */


 // Check if we have a bot ID and create log files
 if (isset($_GET["id"]) || (!empty($botid))) {
  if (empty($botid)) {$id = $_GET["id"];} else {$id = $botid;}
  $log_trades     = "data/" . $id . "_log_trades.csv";      // Trades
  $log_history    = "data/" . $id . "_log_history.csv";     // History
  $log_profits    = "data/" . $id . "_log_profits.csv";     // Profits
  $log_trailing   = "data/" . $id . "_log_trailing.csv";    // Trailing
  $log_runs       = "data/" . $id . "_log_runs.csv";        // Executing log
  $log_binance    = "data/" . $id . "_log_binance.txt";     // Responses from Binance
  $log_settings   = "data/" . $id . "_log_settings.csv";    // Binance settings
  $log_errors     = "data/" . $id . "_log_errors.csv";      // Errors
} else {
  if (!$trailer) {
    $message = date("Y-m-d H:i:s") . ",Error: ID not set\n";
    echo $message;
    if (!file_exists("data/")) {mkdir("data/");}
    file_put_contents("data/log_errors.csv", $message, FILE_APPEND | LOCK_EX);
    exit();
  }
}

// Check if all files exist and if not create empty files
if (!file_exists("data/"))         {mkdir("data/");}
if (!file_exists($log_trades))   {file_put_contents($log_trades, "");}
if (!file_exists($log_history))  {file_put_contents($log_history, "");}
if (!file_exists($log_profits))  {file_put_contents($log_profits, "");}
if (!file_exists($log_runs))     {file_put_contents($log_runs, "");}
if (!file_exists($log_binance))  {file_put_contents($log_binance, "");}
if (!file_exists($log_errors))   {file_put_contents($log_errors, "");}

?>