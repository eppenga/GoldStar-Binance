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
 * $spread         - Minimum spread between historical BUY orders,
 *                   setting $spread to zero disables this function,
 *                   can be overriden by URL
 * $multiplier     - Multiplies the order value by this amount.
 * $compounding    - Start amount of Binance account in base currency,
 *                   0 disables this function (adviced).
 * $bnb            - Amount of BNB to purchase for paying Binance fees
 *
 * TradingView *
 * $tv_advice      - Use TradingView advice on single BUY orders
 * $tv_recomMinMax - Bandwith to use for TradingView recommendation
 *                   STRONG_SELL: -1...-0.5, SELL: -0.5...-0.1
 *                   NEUTRAL: -0.1...0.1
 *                   BUY: 0.1...0.5, STRONG_BUY: 0.5...1
 * $tv_periods     - Periods for TradingView recommendation confirmation
 *                   1m: 1, 5m: 5, 15m: 15, 30m: 30, 1h: 60, 2h: 120, 4h: 240, 1W: 1W, 1M: 1M, 1d: leave emtpy (default)
 * 
 * Trailing Buy *
 * $tb_enabled     - Enable Trailing Buy
 * $tb_distance    - Any value other than 0 enables trailing buy
 * $tb_tradview    - Use TradingView advice when trailing buy
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
$tv_recomMin    = 0.20;
$tv_recomMax    = 1.00;
$tv_periods     = array(15, 60);

// Trailing Buy
$tb_enabled     = false;
$tb_distance    = 0;
$tb_tradview    = false;

// Binance keys (ALWAYS KEEP THESE SECRET!)
$binance_key    = "12345";
$binance_secret = "12345";

// Security key (add to your webhook URLs to prevent unwanted execution!)
$url_key        = "";

?>