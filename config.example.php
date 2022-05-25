<?php

/**
 * @author Ebo Eppenga
 * @copyright 2022
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Binance API from JaggedSoft.
 *
 * User settings *
 * $fee            - Exchange fee in percentages (BUY and SELL are the same)
 * $markup         - Minimum profit per trade, can be overriden by URL 
 * $spread         - Minimum spread between historical BUY orders
 *                   Setting $spread to zero disables this function
 *                   Can be overriden by URL
 * $multiplier		 - Multiplies the order value by this amount
 * $compounding    - Start amount of Binance account in base currency,
 * 					         0 disables this function (adviced).
 * $bnb				     - Amount of BNB to purchase for paying Binance fees
 *
 * TradingView *
 * $tv_advice      - Use TradingView advice on single orders
 * $tv_recomMinMax - Bandwith to use for TradingView recommendation
 * $tv_periods     - Periods for TradingView recommendation confirmation
 * 					         1m: 1, 5m: 5, 15m: 15, 30m: 30, 1h: 60, 2h: 120, 4h: 240, 1W: 1W, 1M: 1M, 1d: leave emtpy (default)
 *
 * Binance keys *
 * $binance_key    - Binance API key
 * $binance_secret - Binance API secret
 * $url_key        - Add to your webhook to prevent unwanted execution                 
 */ 


// User settings
$fee            = 0.10;
$markup         = 0.75;
$spread         = 0.75;
$multiplier     = 1.00;
$compounding    = 0;
$bnb            = 0; 

// TradingView
$tv_advice      = false;
$tv_recomMin    = 0.1;
$tv_recomMax    = 1;
$tv_periods     = array(15, 60);

// Binance keys (ALWAYS KEEP THESE SECRET!)
$binance_key    = "12345";
$binance_secret = "12345";

// Security key (add to your webhook URLs to prevent unwanted execution!)
$url_key        = "";

?>