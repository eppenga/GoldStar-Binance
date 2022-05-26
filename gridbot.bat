@echo off
rem *** GoldStar as a Gridbot                                      ***
rem *** Use cURL or any command line browser you see fit           ***
rem *** Lynx is a nice example: https://lynx.invisible-island.net/ ***
C:
cd \Lynx

:loop
lynx "http://foo.com/goldstar/goldstar.php?id=goldstar&pair=ONEBUSD&spread=0.75&markup=0.75&action=BUY&key=12345&limit=true" -dump
timeout 30
goto loop
