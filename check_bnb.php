<?php

/**
 * @author Ebo Eppenga
 * @copyright 2022
 *
 * GoldStar Buy and Sell bot based on signals from for example TradeView
 * or any other platform using PHP Binance API from JaggedSoft.
 * 
 * Checks if there is enough BNB for discount or orders to pay fees
 * 
 */


/** Check if we need more BNB for paying fees **/ 
if ($set_coin['balanceBNB'] < (0.5 * $bnb)) {

  // Check if we have enough quote balance to buy
  $quantityQuote = $set_coin['balanceQuote'];
  if ($quantityQuote < (2 * $bnb)) {
    $message = date("Y-m-d H:i:s") . "," . $id . ",Error: Insufficient " . $set_coin['quoteAsset'] . " to buy BNB!";
    echo "<font color=\"red\"><b>" . $message . "</b></font><br /><br />";
    logCommand($message, "error");
    exit();
  }
  
  // Report
  echo "<i>Owning " . $set_coin['balanceBNB'] . " BNB, buying an additional " . $bnb . " BNB to pay fees...</i><br /><br /><hr /><br />"; 

  // Acquire BNB for paying fees
  $order = $api->marketBuy("BNB" . $set_coin['quoteAsset'], $bnb);
  logCommand($order, "binance");        
}

 ?>