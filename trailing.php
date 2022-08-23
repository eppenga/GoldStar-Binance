<?php

/**
 * @author Ebo Eppenga
 * @copyright 2022
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Binance API from JaggedSoft.
 * 
 * trailing.php
 * Trailing BUY 0.9
 *
 */

// Set variables
$debug   = false;
$trailer = true;

 // Set error reporting
error_reporting(E_ALL & ~E_NOTICE);

// Configuration
if (!file_exists("config.php")) {echo "Error trailing buy: Configuration file does not exist!"; exit();}
include "config.php";

// Connect to Binance
require 'vendor/autoload.php';
$api = new Binance\API($binance_key, $binance_secret);

// Get Trailing ID
if (!file_exists("data/log_trailing.csv")) {
	echo "Error trailing buy: Trailing ID file does not exist!";
	exit();
}

// Get Trailing settings
$id           = file_get_contents("data/log_trailing.csv", true);
$log_trailing = "data/" . $id . "_log_trailing.csv";
unlink("data/log_trailing.csv");

// Parse Trailing settings
if (!$debug) {
	$handle = fopen($log_trailing, "r");
	while (($settings = fgetcsv($handle)) !== false) {
		$botid     = str_replace(array("\n", "\r"), '', $settings[0]);
		$pair      = str_replace(array("\n", "\r"), '', $settings[1]);
		$quantity  = str_replace(array("\n", "\r"), '', $settings[2]);
		$tickSize  = str_replace(array("\n", "\r"), '', $settings[3]);
		$link      = str_replace(array("\n", "\r"), '', $settings[4]);
	}
	fclose($handle);
	$price     = (float)$api->price($pair);
} else {
	$botid     = "testbot";
	$pair      = "WAVESBUSD";
	$quantity  = 1.5;
	$tickSize  = 0.01;
	$link      = file_get_contents("test_link.txt");
	$price     = (float)$api->price($pair);
	$tb_distance  = 0.3;
	$tv_advice = false;
}

// Check logfiles
include "check_logfiles.php";

// Functions
include "functions.php";


/*** START PROGRAM ***/

// Place initial LIMIT order
$o_top    = $price *	(1 + ($tb_distance / 100));  														// Top price
$o_bottom = $price *	(1 - ($tb_distance / 100));  														// Bottom price
$order    = $api->buy($pair, $quantity, roundStep($o_bottom, $tickSize));		// Place order

// Get order information
$orderstatus           = extractBinance($order);
$unique_id             = $orderstatus['order'];
$predef['unique_id']   = $unique_id;
echo "Placed initial order with ID: " . $unique_id . "\n";

// Add to log file
$message = date("Y-m-d H:i:s") . "," . $botid . ",start," . $price . "," . $orderstatus['price'] . "\n";
file_put_contents("data/log_trails.csv", $message, FILE_APPEND | LOCK_EX);


// Prepare for realtime
$predef['pair']        = $pair;
$predef['quantity']    = $quantity;
$predef['price']       = $price;
$predef['bottom']      = $o_bottom;
$predef['top']      	 = $o_top;
$predef['markup']      = $markup;
$predef['spread']      = $spread;
$predef['distance']    = $tb_distance;
$predef['tradview']    = $tb_tradview;
$predef['tickSize']    = $tickSize;
$predef['botid']       = $botid;
$predef['link']        = $link;
$predef['key']         = $url_key;
$predef['log']				 = $log_trailing;
$predef['tv_advice']	 = $tv_advice;
$predef['tv_periods']  = $tv_periods;
$predef['tv_recomMin'] = $tv_recomMin;
$predef['tv_recomMax'] = $tv_recomMax;
$predef['orderslip']   = false;

// Report
echo "*** GoldStar Trailing Buy ***\n\n";
echo "Date    : " . date("Y-m-d H:i:s") . "\n";
echo "Bot ID  : " . $botid . "\n";
echo "Pair    : " . $pair . "\n";
echo "Quantity: " . $quantity . "\n";
echo "Markup  : " . $markup . "\n";
echo "Trailing: " . $tb_distance . "\n";
echo "Price   : " . $price . "\n";
echo "tickSize: " . $tickSize . "\n";
echo "Link    : " . $link . "\n\n";
if ($debug) {
	echo "* DEBUG ACTIVE *\n\n";
}


/** REALTIME TRAILING **/

$api->ticker($predef['pair'], function($api, $symbol, $ticker) {

	// Loading global variables
	global $predef, $debug;

	// Set variables
	$r_time               = $ticker['eventTime'];		// Timestamp
	$r_price              = $ticker['close'];	  		// Current price
	$limit_ok             = false;									// Can place limit order
	$exit_trailing        = false;									// Can exit trailing buy
	$exit_tview           = false;									// Can exit because of negative TradingView advice
	$orderslip            = false;									// Order slipped through
	$predef['t_prices'][] = $r_price;								// Log all prices

	// Reduce top if price near start price, set with $topfactor (now 0.20%)
	$topfactor = 1.0020;
	if ($predef['top'] > ($predef['t_prices'][0] * $topfactor)) {
		$predef['top'] = $predef['t_prices'][0] * $topfactor;
	}

	// Report
	$ratio = round((($r_price - $predef['price']) / $predef['price']) * 100, 2);
	echo date("H:i:s", substr($r_time, 0, 10)) . " | " . $predef['pair'] . " | " . roundStep($predef['t_prices'][0], $predef['tickSize']) . " | " . $ratio . "% (" . round($predef['distance'], 2) . "%) | ";
	echo roundStep($predef['bottom'], $predef['tickSize']) . " < " . roundStep($r_price, $predef['tickSize']) . " < " . roundStep($predef['top'], $predef['tickSize']) . "\n";


	// Current price moved down
	if ($r_price < $predef['price']) {

		// Report
		echo "Current price went down from " . roundStep($predef['price'], $predef['tickSize']) . " to " . roundStep($r_price, $predef['tickSize']) . "\n";

		// Check if order maybe slipped through!
		$order       = $api->orderStatus($predef['pair'], $predef['unique_id']);
		$orderstatus = extractBinance($order);
		echo "Checking order status ID: " . $predef['unique_id'] . ", status: " . $orderstatus['status'] . "\n";
		if ($orderstatus['status'] == "FILLED") {
			$orderslip = true;
			logCommand($order, "binance");
			echo "ORDER SLIPPAGE ACTIVE ID: " . $predef['unique_id'] . ", PRICE: " . roundStep($r_price, $predef['tickSize']) . "\n\n";
		}

		// Cancel old and place new order if possible
		if (!$orderslip) {

			// Do some calculations
			$predef['top']       = $r_price * (1 + ($predef['distance'] / 100));		// Top price
			$predef['bottom']    = $r_price * (1 - ($predef['distance'] / 100));		// Bottom price
			$predef['price']     = $r_price;                                      	// Mid price
	
			// Cancel OLD order if possible
			$order               = $api->cancel($predef['pair'], $predef['unique_id']);
			$orderstatus         = extractBinance($order);
			echo "Cancelling order with ID: " . $predef['unique_id'] . ", status: " . $orderstatus['status'] . "\n";

			// Place NEW order
			$order               = $api->buy($predef['pair'], $predef['quantity'], roundStep($predef['bottom'], $predef['tickSize']));
			$orderstatus         = extractBinance($order);
			$predef['unique_id'] = $orderstatus['order'];
			echo "New order placed with ID: " . $predef['unique_id'] . ", status: " . $orderstatus['status'] . "\n\n";
		}


	// Current price is between set price and top
	} elseif (($r_price >= $predef['price']) && ($r_price < $predef['top'])) {
		echo "Current price is between set price and top, doing nothing...\n\n";


	// Current price is above top
	} elseif ($r_price >= $predef['top']) {

		// Report
		echo "Current price above or equal to top (" . roundStep($r_price, $predef['tickSize']) . " >= " . roundStep($predef['top'], $predef['tickSize']) . ")\n";

		// Can place LIMIT order
		$limit_ok    = true;

		// Check if order maybe slipped through!
		$order       = $api->orderStatus($predef['pair'], $predef['unique_id']);
		$orderstatus = extractBinance($order);
		echo "Checking order status ID: " . $predef['unique_id'] . ", status: " . $orderstatus['status'] . "\n";
		if ($orderstatus['status'] == "FILLED") {
			$orderslip = true;
			logCommand($order, "binance");
			echo "ORDER SLIPPAGE ACTIVE ID: " . $predef['unique_id'] . ", PRICE: " . roundStep($r_price, $predef['tickSize']) . "\n\n";
		}

		// Cancel OLD order if no slippage
		if (!$orderslip) {
			$order       = $api->cancel($predef['pair'], $predef['unique_id']);
			$orderstatus = extractBinance($order);
			echo "Cancelling order with ID: " . $predef['unique_id'] . ", status: " . $orderstatus['status'] . "\n";	

			// Check for TradingView advice
			if ($predef['tradview']) {
				echo "TradingView recommends";
				$tv_eval = evalTradingView($predef['pair'], $predef['tv_periods'], $predef['tv_recomMin'], $predef['tv_recomMax']);

				// TradingView advice was negative
				if (!$tv_eval) {

					// We can exit later
					$exit_trailing = true;
					$exit_tview    = true;
					echo " skipping buy...\n";

					// Add to log file
					$message = date("Y-m-d H:i:s") . "," . $predef['botid'] . ",tview," . $r_price . "," . $r_price . "\n";
					file_put_contents("data/log_trails.csv", $message, FILE_APPEND | LOCK_EX);

				// TradingView advice was positive
				} else {
					echo " trying to buy...\n";
				}
			}
		}
	}

	
	// Place MARKET order
	if (($limit_ok) && (!$orderslip) && (!$exit_tview)) {
		$exit_trailing = true;
		$order         = $api->marketBuy($predef['pair'], $predef['quantity']);
		$orderstatus   = extractBinance($order);
		logCommand($order, "binance");
		echo "New market order with ID: " . $orderstatus['order'] . ", status: " . $orderstatus['status'] . "\n\n";
	}

	// Place LIMIT order and register MARKET order at GoldStar
	if ((($limit_ok) || ($orderslip)) && (!$exit_tview)) {
		$exit_trailing = true;		
		$page          = $predef['link'] . "goldstar.php?id=" . $predef['botid'] . "&action=BUY&pair=" . $predef['pair'] . "&spread=" . $predef['spread'] . "&markup=" . $predef['markup'] . "&limit=true&orderid=" . $orderstatus['order'] . "&key=" . $predef['key'];
		$result        = file_get_contents($page);
		file_put_contents("data/log_link.txt", $page);
		file_put_contents("data/log_goldstar.html", $result);
		echo "*** REGISTERING IN GOLDSTAR ***\n";
		echo "GoldStar request to register BUY:\n" . $page . "\n\n";
	}

	// Add to correct log file
	if (($limit_ok) && (!$orderslip) && (!$exit_tview)) {
		$message = date("Y-m-d H:i:s") . "," . $predef['botid'] . ",end," . $r_price . "," . $orderstatus['price'] . "\n";
		file_put_contents("data/log_trails.csv", $message, FILE_APPEND | LOCK_EX);
	} elseif (($orderslip) && (!$exit_tview)) {
		$message = date("Y-m-d H:i:s") . "," . $predef['botid'] . ",slip," . $r_price . "," . $orderstatus['price'] . "\n";
		file_put_contents("data/log_trails.csv", $message, FILE_APPEND | LOCK_EX);
	}
	
	// Exit trailing buy
	if ($exit_trailing) {

		// Adjust trailing data file
		echo "Removing trailing settings file...\n";
		echo $predef['log'] . "\n";
		unlink($predef['log']);
		
		// Exiting...
		echo "Ending program in 10 seconds...\n";
		sleep(10);
		exit();
	}
});

?>