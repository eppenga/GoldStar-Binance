# goldstar-crypto-trading-bot
GoldStar Crypto Trading Bot trades based on signals from for example TradeView or any other platform. BUY and SELL orders are triggered via webhooks. It is possible to run as many bots as you like as long as the 'id' differs per bot. Data is stored in CSV files in the 'data/' folder, containing trades, orders, runtime and other logs for further analysis. 

GoldStar automatically determines the smallest possible order value and uses that as BUY orders. This amount is based on Binance minimum order value which is currently 10 BUSD. Also it will automatically acquire a small amount of the base currency to pay for the Binance fees. By default GoldStar is setup so it can't sell at a loss (unless you set profit to negative levels or due to any other unforeseen circumstance).

It is possible to trade both in PAPER and LIVE money. When you first start, please use PAPER money to get a feeling for the bot. When using PAPER the bot calculates the commission setting from the configuration file, when using LIVE money it derives it from Binance. Please be aware that there will always be a diference between PAPER and LIVE money because of slippage and other reasons.

The application relies on PHP Binance API from JaggedSoft to place the actual orders. You need to install that application first and put the GoldStar files in the same folder. Please remember to set a key to prevent others from calling your BUY and SELL URLs because they are exposed to the outside world! Preferably also using an https connection on your server.

**How to install**

1) Install 'PHP Binance API from JaggedSoft', please see: https://github.com/jaggedsoft/php-binance-api
2) Install 'GoldStar Trading Bot' (this application)
3) Copy config.example.php to config.php and modify

**Using GoldStar as a signalbot**

Please make sure the BUY and SELL URL both share the same ID, else the application is unable to match the orders. The parameters markup, spread, trade and key are optional. They take their defaults either from the configuration file or have predefined values. It is recommended to use a key and an https connection! Please find below some real world setups on how to run GoldStar.

Simple example where you only set the required parameters initiating PAPER trades on the pair MATICBUSD, usefull for local testing to get to know the bot:

BUY:
`http://foo.com/path/goldstar.php?id=a1&action=BUY&pair=MATICBUSD`

SELL:
`http://foo.com/path/goldstar.php?id=a1&action=SELL&pair=MATICBUSD`

The normal way to run GoldStar using LIVE trading preferably via an SSL (https) connection:

BUY:
`https://foo.com/path/goldstar.php?id=a2&action=BUY&pair=MATICBUSD&trade=LIVE&key=12345`

SELL:
`https://foo.com/path/goldstar.php?id=a2&action=SELL&pair=MATICBUSD&trade=LIVE&key=12345`

A more complicated example where you override the spread and markup (profit) parameters. In a normal situation you would define the spread and markup in the configuration file:

BUY:
`https://foo.com/path/goldstar.php?id=a3&action=BUY&pair=MATICBUSD&spread=0.5&trade=LIVE&key=12345`

SELL:
`https://foo.com/path/goldstar.php?id=a3&action=SELL&pair=MATICBUSD&markup=0.7&trade=LIVE&key=12345`

**Using GoldStar as a gridbot**

GoldStar can also be used as a gridbot. In that case it will only execute BUY MARKET orders on Binance and schedule LIMIT SELL orders with a predefined profit percentage. You will need some external tooling to call GoldStar every so many seconds or minutes. Usually a timeschedule of every minute is more than enough. A normal CURL, or if you would like to monitor the output the command line browser Lynx suffices, please see: https://lynx.invisible-island.net/

If you execute the example below every minute you will deploy a grid bot trading on SYSLIMIT spreading the BUY orders 0.9% between each other and setting the profit margin to 0.9% by using a LIMIT SELL order. Gridbots can only be executed using LIVE trading, not PAPER, because it is currently not possible to deal with LIMIT SELL orders on PAPER.

BUY:
`http://foo.com/path/goldstar.php?id=syslimit&pair=SYSBUSD&spread=0.9&markup=0.9&action=BUY&key=12345&limit=true&trade=live`

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

**Logfiles and analyses**

All logs reside in the 'data/' folder and are seperated per bot (usually you run a bot per pair so per pair). Also there is some special functionality that allows you to retreive a combined log of all bots. 

- \*_log_history.csv  - History of all trades per coin
- \*_log_trades.csv   - All active trades per coin (also known as bags)
- \*_log_runs.csv     - Runtime log of executions per coin
- \*_log_settings.csv	- Binance settings per coin
- \*_log_binance.csv  - Log of all Binance responses per coin
- \*_log_errors.csv   - Log of all errors per coin

http://foo.com/path/log_combine.php?files=history|trades|errors displays in the browser and creates files below which can be used in for Google Sheets.
- log_history.csv     - History of all trades for all coins
- log_trades.csv      - All active trades for all coins
- log_errors.csv      - Log of all errors for all coins

**Format $log_trades**

`2021-12-05 13:09:56,MATICBUSD,BUY,10,2.193000`

Date, Pair, BUY / SELL, Base, Quote. In this file all open orders are stored, this is your bag of unsold orders.

**Format of $log_history**

`2021-12-05 13:16:10,MATICBUSD,SELL,10,2.21322,0.41322,LIVE`

Date, Pair, BUY / SELL, Base, Quote, Profit, LIVE / PAPER. The $log_history contains all actual BUY and SELLs trades for a certain pair.
