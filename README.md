
## IXmon 

Information eXtraction and MONitoring tool
==============

A terminal based
(rss/atom/youtube/vine/instagram/weather/stocks/cryptocurrency/kitchen sink)
feed reader and monitor written in php.

### Installation

You will need php 5 or higher with ncurses support (adding with yum or apt to
an existing php installation should go off without a hitch), xterm, and
optionally, "fortune", and the imagemagick command line tools for generating
image previews. 

### Configuration

The php script installs to /usr/local/bin/ixmon by default, and configuration
files are kept in ~/.ixmon/

The ~/.ixmon/config file is where you specify the RSS/etc URLS you wish to
monitor

    #### Sample Config

    # lines begining with a "#" are comments
    # lines begining with a -- are group names
    #
    #
    # the following example would create a group called 'trend data' with two feed urls 
    # and a group called 'my crypto' tracking bitcoin and litecoin
    # 
    # --trend data
    # http://www.google.com/trends/hottrends/atom/feed?pn=p1
    # http://feeds.feedburner.com/RsoeEdis-EmergencyAndDisasterInformation
    # --news
    # http://www.infowars.com/rss
    # --financial
    # coin://bitcoin
    # coin://dogeecoin
    # stock://GLD




#### Help Documentation

Pressing ESC from within the program will show you keyboard shortcuts (VIM and
arrow keys, plus a few more) and some quick information about the syntax of the
configuration file, and please check out http://Ixmon.com to see this program
reincarnated as an ajax webapp.

