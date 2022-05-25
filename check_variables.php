<?php

/**
 * @author Ebo Eppenga
 * @copyright 2022
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Binance API from JaggedSoft.
 * 
 * Sets and defines variables
 * 
 */

$binanceMinimum = 10;
$buy            = 0;
$buy_price      = 0;
$counter        = 0;
$price          = 0;
$profit         = 0;
$quantity       = 0;
$sell           = 0;
$sell_price     = 0;
$fees           = 0;
$markups        = 0;
$repeatrun      = 24 * 60 * 60;
$total_buy      = 0;
$total_fees     = 0;
$total_orders   = 0;
$total_profit   = 0;
$total_quantity = 0;
$total_sell     = 0;
$history        = "";
$message        = "";
$order          = "";
$pair           = "";
$trades         = "";
$limit          = false;

 ?>