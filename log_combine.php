<?php

/**
 * @author Ebo Eppenga
 * @copyright 2021
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Binance API from JaggedSoft.
 * 
 * log_combine.php?files=history|trades
 * Combines all the data/*_log_history.csv or data/*_log_trades.csv files 
 * If left empty defaults to *_log_history.csv files
 * 
 */


// Set variables
$combined = "";
$data     = "data/";

// Combine history or trades
if (isset($_GET["files"])) {
  if ($_GET["files"] == "history") {
    $logfiles = "log_history.csv";
  } elseif ($_GET["files"] == "trades") {
    $logfiles = "log_trades.csv";
  } elseif ($_GET["files"] == "errors") {
    $logfiles = "log_errors.csv";
  } else {
    echo "Error: Undefined what combined log to create!";
    exit();
  }
} else {
  $logfiles = "log_history.csv";
}

// First check if data folder exists
if (!file_exists($data)) {echo "Error: No data folder!"; exit();}

// Get file list
$files = scandir($data);

// Filter all *_log_history.csv files
foreach ($files as &$file) {
  if (strpos($file, "_" . $logfiles) !== false) {
    $csvfiles[] = $file;
  }
}

// Throw an error if no CSV files are found
if (empty($csvfiles)) {echo "Error: No CSV files found!"; exit();}

// Output *_log_history.csv files to stdout and file
foreach ($csvfiles as &$csvfile) {
  $combined .= file_get_contents($data . $csvfile);
}
file_put_contents($data . $logfiles, $combined);
echo $combined;

?>