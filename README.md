# goldstar-trading-bot
GoldStar Crypto Trading Bot which can trade based on signals from for example TradeView or any other platform. Buy and Sell actions can be connected as webhooks. You can run as many bots as you like as long as the 'id' differs per bot. All data is stored in CSV files in the data/ folder, containing trades, orders, runtime and other logs. 

The application uses PHP Binance API from JaggedSoft to place the actual orders. You need to install that first and put these files in the same folder!

GoldStar will automatically buy a small amount of the base currency to pay for the Binance fees. Please remember to set quantity high enough on BUY orders to prevent minimum error size on Binance (ordervalue > 10 BUSD).

**Usage**
BUY: http://foo.com/path/goldstar.php?id=a1&action=BUY&pair=MATICBUSD&quantity=10&spread=0.5&markup=0.7&trade=LIVE&key=12345
SELL: http://foo.com/path/goldstar.php?id=a1&action=SELL&pair=MATICBUSD&spread=0.5&markup=0.7&trade=LIVE&key=12345

**Webhooks**
Please use the URLs above in TradingView and set them up as webhooks.

**Querystring parameters**
id       = id of the bot, multiple instances can be run
action   = BUY or SELL, for SELL no quantity is required
pair     = Crypto pair to be used
quantity = How much to BUY in MATIC
key      = Add a unique key to URL to prevent unwanted execution
trade    = LIVE or PAPER, defaults to PAPER
spread   = Minimum spread between historical BUY orders. Setting $spread to zero disables this function. Defaults to the settings in config.php
markup   = Minimum profit. Defaults to setting in config.php

**How to install**
1) Install 'PHP Binance API from JaggedSoft', please see: https://github.com/jaggedsoft/php-binance-api
2) Install 'GoldStar Trading Bot' (this application)
3) Copy config.example.php to config.php and modify

**Logfiles**
$log_trades    - Contains bag of trades
$log_history   - History of all trades
$log_runs      - Runtime log of executions
$log_settings	 - Settings of the coin
$log_binance   - Log of all Binance responses
$log_errors    - Log of all errors

**Format $log_trades**
2021-12-05 13:09:56,MATICBUSD,BUY,10,2.193000
Date, Pair, BUY / SELL, Base, Quote

**Format of $log_history**
2021-12-05 13:16:10,MATICBUSD,SELL,10,2.21322,0.41322
Date, Pair, BUY / SELL, Base, Quote, Profit
