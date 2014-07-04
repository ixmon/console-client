# Ixmon 
###### Information eXtraction and MONitoring tool
A terminal based
(rss/atom/youtube/vine/instagram/weather/stocks/cryptocurrency/kitchen sink)
feed reader and monitor written in php.

![Alt text](/screenshots/ixmon_console.png "Ixmon console screenshot")

### Installation

You will need php 5 or higher with ncurses support (adding with yum or apt to
an existing php installation should go off without a hitch), xterm, and
optionally, "fortune", and the imagemagick command line tools for generating
image previews. 

For dependencies on Ubuntu 14.04 Trusty Tahr, try

apt-get install php5 php5-dev libncursesw5-dev php-pear fortune imagemagick 
pecl install ncurses


To build ixmon, run the Makefile one level up from the src/ directory

    make
    make test
    make install

It will try to install to /usr/local/bin/ixmon by default, to install as a
non-root user, just copy off the resulting executable "ixmon" script wherever
you'd like. 



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
    # The following example would create a group called 'trend data' with two
    # feed urls,  a group called 'news' with two items, and a group called
    # 'financial' with three items
    #
    # --trend data
    # http://www.google.com/trends/hottrends/atom/feed?pn=p1
    # http://feeds.feedburner.com/RsoeEdis-EmergencyAndDisasterInformation
    # --news
    # https://github.com/ixmon.atom
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

