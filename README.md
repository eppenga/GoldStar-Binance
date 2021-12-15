# goldstar-crypto-trading-bot
GoldStar Crypto Trading Bot which can trade based on signals from for example TradeView or any other platform. Buy and Sell actions can be connected as webhooks. You can run as many bots as you like as long as the 'id' differs per bot. All data is stored in CSV files in the data/ folder, containing trades, orders, runtime and other logs. 

GoldStar automatically determines the smallest possible order value and uses that as BUY orders. This amount is based on Binance minimum order value which is currently 10 BUSD. Also it will automatically acquire a small amount of the base currency to pay for the Binance fees. By default GoldStar is setup so it can't sell at a loss (unless you set profit to negative levels or due to any other unforeseen circumstance).

The application uses PHP Binance API from JaggedSoft to place the actual orders. You need to install that application first and put the GoldStar files in the same folder to be able to run. Please remember to set a key to prevent others from calling your BUY and SELL URLs because they are exposed to the outside world!

**How to install**

1) Install 'PHP Binance API from JaggedSoft', please see: https://github.com/jaggedsoft/php-binance-api
2) Install 'GoldStar Trading Bot' (this application)
3) Copy config.example.php to config.php and modify

**Usage**

BUY:
`http://foo.com/path/goldstar.php?id=a1&action=BUY&pair=MATICBUSD&spread=0.5&markup=0.7&trade=LIVE&key=12345`

SELL:
`http://foo.com/path/goldstar.php?id=a1&action=SELL&pair=MATICBUSD&spread=0.5&markup=0.7&trade=LIVE&key=12345`

*You do not need to set a quantity because it will automatically match the BUY order.*

**Webhooks**

Please use the URLs above in TradingView (or any other platform) and set them up as webhooks. If you do not know what webhooks are, please forget about this application :) More information on these webhooks can be found here: https://www.google.com/search?q=tradingview+webhook

**Signals**

You choose your own signals. Based on that the bot will either BUY and SELL. My personal favorite is "Market Liberator" and I use the Short and Long signals on a 1 minute timescale. Please here https://discord.me/marketliberator

**Querystring parameters**

- id       - id of the bot, multiple instances can be run
- action   - BUY or SELL, for SELL no quantity is required
- pair     - Crypto pair to be used
- key      - Add a unique key to URL to prevent unwanted execution
- trade    - LIVE or PAPER, defaults to PAPER
- spread   - Minimum spread between historical BUY orders, setting $spread to zero disables this function. Defaults to the settings in config.php
- markup   - Minimum profit. Defaults to setting in config.php

**Logfiles**

- $log_all       - History of all trades totally
- $log_trades    - Contains bag of trades per coin
- $log_history   - History of all trades per coin
- $log_runs      - Runtime log of executions per coin
- $log_settings	 - Settings per coin
- $log_binance   - Log of all Binance responses per coin
- $log_errors    - Log of all errors per coin

**Format $log_trades**

`2021-12-05 13:09:56,MATICBUSD,BUY,10,2.193000`

Date, Pair, BUY / SELL, Base, Quote

**Format of $log_history**

`2021-12-05 13:16:10,MATICBUSD,SELL,10,2.21322,0.41322,LIVE`

Date, Pair, BUY / SELL, Base, Quote, Profit, Live or Paper trade
