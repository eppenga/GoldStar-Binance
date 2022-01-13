<?php

/**
 * @author Ebo Eppenga
 * @copyright 2021
 *
 * GoldStar Buy and Sell bot based on signals
 *
 * Settings:
 * $fee            - Exchange fee (BUY and SELL are the same)
 * $markup         - Minimum profit per trade, can be overriden by URL 
 * $spread         - Minimum spread between historical BUY orders
 *                   Setting $spread to zero disables this function
 *                   Can be overriden by URL
 * $binance_key    - Binance API key
 * $binance_secret - Binance API secret
 * $url_key        - Security key to prevent unwanted execution
 */ 


// User settings
$fee            = 0.1;      // Fee in percentages
$markup         = 0.5;      // Minimum profit per trade
$spread         = 0.75;     // Minimym spread between buy orders
$multiplier     = 1.00;     // Multiplies order value by amount set
$compounding    = 0;        // Start amount in quote currency where 0 disables
$bnb            = 0.1;      // Minimum (+/-50%) BNB to keep for paying fees 

// Binance keys (ALWAYS KEEP THESE SECRET!)
$binance_key    = "1234567890";
$binance_secret = "abcdefghij";

// Security key (add to your webhook URLs to prevent unwanted execution!)
$url_key        = "1234567890";

?>