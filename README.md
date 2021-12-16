# goldstar-crypto-trading-bot
GoldStar Crypto Trading Bot trades based on signals from for example TradeView or any other platform. BUY and SELL orders are triggered via webhooks. It is possible to run as many bots as you like as long as the 'id' differs per bot. Data is stored in CSV files in the 'data/' folder, containing trades, orders, runtime and other logs for further analysis. 

GoldStar automatically determines the smallest possible order value and uses that as BUY orders. This amount is based on Binance minimum order value which is currently 10 BUSD. Also it will automatically acquire a small amount of the base currency to pay for the Binance fees. By default GoldStar is setup so it can't sell at a loss (unless you set profit to negative levels or due to any other unforeseen circumstance).

It is possible to trade both in PAPER and LIVE money. When you first start, please use PAPER money to get a feeling for the bot. When using PAPER the bot calculates the commission setting from the configuration file, when using LIVE money it derives it from Binance. Please be aware that there will always be a diference between PAPER and LIVE money because of slippage and other reasons.

The application relies on PHP Binance API from JaggedSoft to place the actual orders. You need to install that application first and put the GoldStar files in the same folder. Please remember to set a key to prevent others from calling your BUY and SELL URLs because they are exposed to the outside world!

**How to install**

1) Install 'PHP Binance API from JaggedSoft', please see: https://github.com/jaggedsoft/php-binance-api
2) Install 'GoldStar Trading Bot' (this application)
3) Copy config.example.php to config.php and modify

**Usage**

BUY:
`http://foo.com/path/goldstar.php?id=a1&action=BUY&pair=MATICBUSD&spread=0.5&trade=LIVE&key=12345`

SELL:
`http://foo.com/path/goldstar.php?id=a1&action=SELL&pair=MATICBUSD&spread=0.5&markup=0.7&trade=LIVE&key=12345`

*Please make sure the BUY and SELL URL both share the same ID, else the application is unable to match the orders. The parameters markup, spread, trade and key are optional. They take their defaults either from the configuration file or have predefined values. It is recommended to use a key!*

**Webhooks**

Please use the URLs above in TradingView (or any other platform) and set them up as webhooks. If you do not know what webhooks are, please forget about this application :) More information on these webhooks can be found here: https://www.google.com/search?q=tradingview+webhook

**Signals**

You choose your own signals. Based on that the bot will either BUY or SELL. My personal favorite is "Market Liberator" and I use the Short and Long signals on a one minute timescale. They have an awesome Discord Channel here: https://discord.me/marketliberator

**Querystring parameters**

- id       - id of the bot, multiple instances can be run (required). Please make sure the BUY and SELL URLs share the same id to be able to match the trades.
- action   - BUY or SELL, for SELL no quantity is required (required)
- pair     - Crypto pair to be used (required)
- key      - Add a unique key to URL to prevent unwanted execution (optional)
- trade    - LIVE or PAPER, defaults to PAPER (optional)
- spread   - Minimum spread between historical BUY orders, setting $spread to zero disables this function. Defaults to the setting in config.php (optional)
- markup   - Minimum profit. Defaults to setting in config.php (optional)

**Logfiles**

- $log_all       - History of all trades for all coins
- $log_trades    - Contains bag of trades per coin
- $log_history   - History of all trades per coin
- $log_runs      - Runtime log of executions per coin
- $log_settings	 - Binance settings per coin
- $log_binance   - Log of all Binance responses per coin
- $log_errors    - Log of all errors per coin

**Format $log_trades**

`2021-12-05 13:09:56,MATICBUSD,BUY,10,2.193000`

Date, Pair, BUY / SELL, Base, Quote. In this file all open orders are stored, this is your bag of unsold orders.

**Format of $log_history**

`2021-12-05 13:16:10,MATICBUSD,SELL,10,2.21322,0.41322,LIVE`

Date, Pair, BUY / SELL, Base, Quote, Profit, LIVE / PAPER. The $log_history contains all actual BUY and SELLs trades for a certain pair. You can also use $log_all for a complete overview of all pairs and use for analysis.
