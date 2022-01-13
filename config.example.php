<?php

/**
 * @author Ebo Eppenga
 * @copyright 2021
 *
 * GoldStar Buy and Sell bot based on signals and grids
 *
 * Variables:
 * $fee            - Exchange fee in percentages (BUY and SELL are the same)
 * $markup         - Minimum profit per trade, can be overriden by URL 
 * $spread         - Minimum spread between historical BUY orders
 *                   Setting $spread to zero disables this function
 *                   Can be overriden by URL
 * $multiplier		 - Multiplies the order value by this amount
 * $compounding    - Start amount of Binance account in base currency,
										 0 disables this function (adviced).
 * $bnb						 - Amount of BNB to purchase for paying Binance fees
 * $binance_key    - Binance API key
 * $binance_secret - Binance API secret
 * $url_key        - Add to your webhook to prevent unwanted execution
 *                   
 */ 


// User settings
$fee            = 0.10;
$markup         = 0.50;
$spread         = 0.75;
$multiplier     = 1.00;
$compounding    = 0;
$bnb            = 0.10;

// Binance keys
$binance_key    = "1234567890";
$binance_secret = "abcdefghij";

// Security key
$url_key        = "1234567890";

?>