<?php

/**
 * @author Ebo Eppenga
 * @copyright 2021
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Binance API from JaggedSoft.
 * 
 * limit.php
 * Adds a LIMIT order to a BUY order adding fees and markup.
 * 
 */


// Do some calculations
$buy_fee    = $commission;
$sell_fee   = ($quantity * $price) * ($fee / 100);
$fees       = $buy_fee + $sell_fee;
$markups    = ($quantity * $price) * ($markup / 100);        
$sell       = ($quantity * $price) + $fees + $markups;
$sell_price = $sell / $quantity;
$sell_price = roundStep($sell_price, minimumQuote()['tickSize']);   // Adjust for Binance

// Report
echo "<b>LIVE LIMIT Order</b><br />";
echo "Quantity   : " . $quantity . "<br />";
echo "Markup     : " . $markups . "<br />";
echo "BUY Fee    : " . $buy_fee . " (" . $fee . "%)<br />";
echo "SELL Fee   : " . $sell_fee . " (" . $fee . "%)<br />";
echo "SELL Price : " . $sell_price . "<br />";
echo "SELL Total : " . $sell . "<br /><br />";        

// Place the Limit order
$order = $api->sell($pair, $quantity, $sell_price);
logCommand($order, "binance");

// Get the correct ID so it can be matched later
$unique_id = extractBinance($order)['order'];

// Report
echo "<b>LIVE LIMIT Trade</b><br />";
echo "Symbol     : " . $order['symbol'] . "<br />";
echo "Order ID   : " . $order['orderId'] . "<br />";
echo "Time       : " . $order['transactTime'] . "<br />";
echo "Status     : " . $order['status'] . "<br /><br />";


?>