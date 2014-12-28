<?php

class data_grid { function data_grid() {} }

class ix_spider extends data_grid {

var $handlers = array();

  function ix_spider () {
  $this->data_grid();
  $this->result = array();




  }


function cache_get($url) {
/*
$cache = "./old_cache/" . md5($url);
if (file_exists($cache) ) { 
return file_get_contents($cache);
}
*/
// some remote feeds are huge for no reason ... can use -T to limit size, think about this
// [darkstar@untitled ~]: wget --tries 1 -T 0.1 'http://www.blogtalkradio.com/freedomizerradio.rss' -O test

$user_agent = 'Mozilla/6.0 (Windows NT 6.2; WOW64; rv:16.0.1) Gecko/20121011 Firefox/16.0.1';
$wget_cmd   = "wget -q -t 1 -T 10 -U '$user_agent' '$url' -O - ";
$data       = `$wget_cmd`;
$megs       = sprintf("%.6f", (strlen($data) / 1000000));
$user_count = $this->scalar("select count(*) from user_feeds where fid='$this->fid'");
$this->query("insert into net_log (id, fid, megs, user_count, date) values('', '$this->fid', '$megs', '$user_count', NOW()) ");

/*
$fh = fopen($cache, "w");
fputs($fh, $data);
*/
return $data;
}

 function html2text3 ($html) {
  $html = substr($html, 0, 10000000);
  // $html = preg_replace("/\n/i", " ", $html);
  // $html = preg_replace("/\r/i", " ", $html);
  $html = preg_replace("/</i", "xtag_delimiter<", $html);
  $cleaner = '';
  foreach ( explode("xtag_delimiter", $html) as $line) {
    if ( preg_match("/<script/i", $line) ) {
    continue;
    }
    elseif ( preg_match("/<style/i", $line) ) {
    continue;
    }
    else {
  $cleaner .= "$line";
    }
  }
  $html = $cleaner;

  // $html = preg_replace("/<sup.*\/sup>/si", "", $html);
  $html = preg_replace("/<[^>]+>/", " ", $html);
  // $html = preg_replace("/<script .*<\/script>/im", "", $html);
  // $html = preg_replace("/wgCurRevision/is", "fuck", $html);


  $html = preg_replace("/&[a-z0-9]+;/si", "", $html);
  $html = preg_replace("/amp;/si", "", $html);
  $html = preg_replace("/(http:\/\/[^\s]+\s+)/s", " ", $html);



  return $html;
  }


function html2text($data) {
/*
$str = preg_replace("/<script>[^<]+<\/script>/i", "", $str);
$str = preg_replace("/<script [^>]+>[^<]+<\/script>/i", "", $str);

$str = preg_replace("/&quot;/", "", $str);
$str = preg_replace("/&apos;/", "", $str);
$str = preg_replace("/&amp;/", "", $str);
$str = preg_replace("/&nbsp;/", "", $str);
$str = preg_replace("/&#[^;]+;/", "", $str);
$str = preg_replace("/#\d+;/", "", $str);
$str = preg_replace("/<[^>]+>/", "", $str);
*/
$data = preg_replace("/&amp;/", "&", $data);
$data = preg_replace("/&#x2F;/", "/", $data);
  $data = preg_replace("/&#039;/s", "'", $data);


  $data = $this->html2text3($data);

  $data = preg_replace("/&#[a-z0-9]+;/si", "", $data);

  $data = preg_replace("/&#[a-z0-9]+;/si", "", $data);
  // $data = preg_replace("/<sup.*\/sup>/si", "fuck", $data);
  // $data = preg_replace("/\([^\)]+\)/s", "", $data);
$data = htmlspecialchars_decode($data);
  $data = preg_replace("/\[[^\]]+\]/s", "", $data);
  $data = preg_replace("/  /s", " ", $data);
  $data = preg_replace("/\n+/s", "\n", $data);
  $data = preg_replace("/&#10;/s", "\n", $data);
  $data = preg_replace("/&#13;/s", "\n", $data);

  $data = preg_replace("/&#39;/s", "'", $data);
  $data = preg_replace("/&quot;/s", '"', $data);
  $data = preg_replace("/”/s", '"', $data); // the windows sneaky quote, not a quote

  $data = preg_replace("/&#160;/s", ' ', $data); // sneaky nonbreaking space &nbsp; breaks text patterns

  // http://www.w3schools.com/tags/ref_ascii.asp
  // this is the way to go $data = preg_replace("/&#(\d+);/s", chr("$1"), $data);
  $data = preg_replace("/&\w+;/s", "", $data);
$crazy_dash = "–"; // this is not a -
  $data = str_replace($crazy_dash, "-", $data);
  $data = preg_replace("/[^a-z0-9'\"\-\!\?;\.\:\/@#\$%\&\*\(\)\s\n\r,]/is", "", $data);
  $data = preg_replace("/[ ]+/s", " ", $data);
  $data = str_replace(" ,", ",", $data);
  $data = trim($data);

  // $data = preg_replace("/\s+/", " ", $data);
  return $data;


}

function parse_domain($str) {
$ar = parse_url($str);
return $ar['host'];
}

function parse_attr($tag,$element, $line, $var) {
if ($var) { return $var; }
// print "$tag -> $element -> $line - $var\n";

  if ( preg_match("/<$tag ([^<]+)/i", $line, $match) ) {
    if ( preg_match("/$element=\"([^\"]+)\"/i", $match[1], $el_match) ) {
    $ret = $el_match[1];
    }
    elseif ( preg_match("/$element='([^']+)'/i", $match[1], $el_match) ) {
    $ret = $el_match[1];
    }
  }
$ret = str_replace("&gt;", ">", $ret);
$ret = str_replace("&lt;", "<", $ret );
return trim($ret);
}


function parse_tag ($tag, $line, $var) {

if ($var) { return $var; }

  if ( preg_match("/<$tag>([^<]+)/i", $line, $match) ) {
  $ret = $match[1];
  }
  elseif ( preg_match("/<$tag>/i", $line, $match) ) {
  $ret = '';
  }
  elseif ( preg_match("/<$tag [^>]+>([^<]+)/i", $line, $match) ) {
  $ret = $match[1];
  }
$ret = str_replace("&gt;", ">", $ret);
$ret = str_replace("&lt;", "<", $ret );
return trim($ret);
}


  

function parse_img ($str, $img) {

if ($img) {
return $img;
}
$img_url = '';


   if (
        preg_match("/(https?\:\/\/\S+jpg)/i", $str, $matches)
        || preg_match("/(https?\:\/\/\S+gif)/i", $str, $matches)
        || preg_match("/(https?\:\/\/\S+png)/i", $str, $matches)
        || preg_match("/(https?\:\/\/\S+jpeg)/i", $str, $matches)
        ) {
    $img_url = $matches[1];
        }
  elseif (preg_match("/(https?\:\/\/[^\/]+)/i", $str, $matches) )  {
    $img_url = $matches[1] ."/favicon.ico";
  }

return $img_url;
}

function parse_youtube_img ($str, $youtube) {
    if ($youtube) { return $youtube; }

    if ( preg_match("/http\:\/\/www.youtube.com\/v\/([a-zA-Z0-9\-_]+)/i", $str, $matches) ) {
    $image = "https://i.ytimg.com/vi/".$matches[1]."/mqdefault.jpg";
    }
    elseif ( preg_match("/youtube.com\/watch\?v=([a-zA-Z0-9_\-]+)feature/i", $str, $matches) ) {
    $image = "https://i.ytimg.com/vi/".$matches[1]."/mqdefault.jpg";
    }
    elseif ( preg_match("/youtube.com\/watch\?v=([a-zA-Z0-9_\-]+)/i", $str, $matches) ) {
    $image = "https://i.ytimg.com/vi/".$matches[1]."/mqdefault.jpg";
    }
    elseif ( preg_match("/youtube.com\/embed\/([a-zA-Z0-9_\-]+)\?/i", $str, $matches) ) {
    $image = "https://i.ytimg.com/vi/".$matches[1]."/mqdefault.jpg";
    }
    // 12/27/2014 ... youtube's new multi-channel stuff
    elseif ( preg_match("/youtube.com\/channel\/([a-zA-Z0-9_\-]+)\?/i", $str, $matches) ) {
    $image = "https://i.ytimg.com/vi/".$matches[1]."/mqdefault.jpg";
    }

return $image;

}

function parse_weather($url) {

  if ( 
  preg_match("|weather://([^/#]+)/([^/#]+)|", $url, $matches) 
  ) {
  list($undef, $city, $state) = $matches;
  }
  else {
  return false;
  }

$city  = preg_replace("/\s+/", "_", ucwords($city));
$state = preg_replace("/\s+/", "_", ucwords($state));


// pretty sneaky
$this->parse_rss("http://rss.wunderground.com/auto/rss_full/$state/$city.xml?units=english");

// $this->add_item($feed_title, $title, $desc, $link, $img);

$this->detected_handler = "weather";
return true;


}



function parse_search($url) {

  if ( 
  preg_match("|search://([^/#]+)|", $url, $matches) 
  ) {
    $query = $matches[1];
  }
  else {
  return false;
  }
$query = urldecode($query);


// pretty sneaky
$this->parse_rss("https://news.google.com/news/feeds?pz=1&cf=all&ned=us&hl=en&output=rss&q=".urlencode($query) );

// $this->add_item($feed_title, $title, $desc, $link, $img);

$this->detected_handler = "search";
return true;


}





function parse_vine($url) {

  if ( 
  preg_match("|https?://vine\.co/u/([^/#]+)|", $url, $matches) 
  ) {
    $user_id = $matches[1];
  }
  elseif ( 
  preg_match("|https?://vine\.co/([^/#]+)|", $url, $matches) 
  ) {
  $username = $matches[1];
  
  }
  else {
  return false;
  }





if ($username) {

$vine = $this->json_decode_wrapper($this->cache_get("https://vine.co/api/users/profiles/vanity/$username"));
// $user_id = $vine->data->userId - 0;
$user_id = sprintf("%.0f", $vine->data->userId);
}



$user_vine = $this->json_decode_wrapper($this->cache_get("https://api.vineapp.com/timelines/users/$user_id"));
// print_r($user_vine);


// $insta = $this->json_decode_wrapper(`cat /tmp/jen`);

foreach ($user_vine->data->records as $item) {
$desc = $item->description;
$title = $desc;
$link = $item->permalinkUrl;
$img  = $item->thumbnailUrl;
if (!$username) {
$username = $item->username;
}

$feed_title = "Vine: $username";

if (strlen($title) > 30) {
$title = substr($title, 0, 30) . "...";
}

/*
print "
feed_title: $feed_title
title: $title
link: $link
img: $img
desc: $desc
";
*/

$this->add_item($feed_title, $title, $desc, $link, $img);
}

$this->detected_handler = "vine";
return true;


}





function parse_instagram($url) {

  if ( !preg_match("|https?://instagram\.com/([^/#]+)|", $url, $matches) ) {
  return false;
  }

  $user = $matches[1];

$insta = $this->json_decode_wrapper($this->cache_get("http://instagram.com/$user/media?"));
// $insta = $this->json_decode_wrapper(`cat /tmp/jen`);
$feed_title = "Instagram: $user";

foreach ($insta->items as $item) {

$desc = $item->caption->text;
$title = $desc;
$link = $item->link;
$img  = $item->images->standard_resolution->url;
if (strlen($title) > 30) {
$title = substr($title, 0, 30) . "...";
}

/*
print "
feed_title: $feed_title
title: $title
link: $link
img: $img
desc: $desc
";
*/

$this->add_item($feed_title, $title, $desc, $link, $img);

}




$this->detected_handler = "instagram";
return true;
}



function parse_cryptocoin($url) {

  if ( !preg_match("|coin://([a-zA-Z0-9]+)|", $url, $matches) ) {
  return false;
  }


$date = Date("l jS \of F Y h:i:s A");
$coin = strtoupper($matches[1]);


$ixmon_web = $this->json_decode_wrapper($this->cache_get("http://ixmon.com/json/crypto-prices", '', false, (60 * 60 * 3) ) );

foreach ($ixmon_web->items as $coin_ar) {
$label     = $coin_ar->label;
if (strtolower($label) != strtolower($coin) ) { continue; }
$this_coin = strtoupper(trim($coin_ar->symbol));
$price     = $coin_ar->usd_price;

  // print "\t$price\n";
  // add_coin($ix, $this_coin, $label, $avg_btc, $price);

}

$feed_title = "$coin = $price";
$title = "One $coin is worth $price";
$desc = "At $date 1 $coin is $price on Ixmon.com\n";
$img = '';
$link = "http://ixmon.com/crypto-prices/";



$date = Date("l jS \of F Y h:i:s A");

if (!$price) {
$desc = "Error fetching data... will retry";
}
    
$this->add_item($feed_title, $title, $desc, $link, $img, 'force as new');

$this->detected_handler = "cryptocoin";
return true;
}

function parse_youtube ($url) {

  if ( !preg_match("/youtube\.com/i", $url, $matches) ) {
  return false;
  }

  if ( preg_match('|/user/([^/\?]+)|', $url, $matches) ) {
  $youtube_channel = $matches[1];
  $final_url = "https://www.youtube.com/user/$youtube_channel";
  }
  elseif ( preg_match('|/channel/([^/\?]+)|', $url, $matches) ) {
  $youtube_channel = $matches[1];
  $final_url = "https://www.youtube.com/channel/$youtube_channel";
  $just_channel = true;
  }
  else {
  $data = $this->cache_get($url);

    if ( preg_match('|href="/user/([^\?]+)\?feature=watch|', $data, $matches) ) {
    $youtube_channel = $matches[1];
    $final_url = $this->scrub("https://www.youtube.com/user/$youtube_channel", 500) ;
    }

  }


if ($this->fid && $url != $final_url) {
    $this->query("update feeds set url='$final_url' where id='$this->fid' limit 1");
    `echo 'changing $url to $final_url' >> /tmp/youtube`;
    }


  if ($just_channel) {
  $channel_url = "https://www.youtube.com/channel/$youtube_channel/videos";
  }
  else {
  $channel_url = "https://www.youtube.com/user/$youtube_channel/videos?flow=grid&view=0";
  }


  $data = $this->cache_get($channel_url);

  if ($just_channel) {
  $feed_title = "Youtube Channel: " . preg_replace("/-\s+YouTube\s?$/", "", $this->parse_tag("title", $data, $feed_title));
  }
  else {
  $feed_title = "Youtube Channel: $youtube_channel";
  }

    
   // foreach ( preg_split("/content-item/", $data) as $chunk ) {
   foreach ( preg_split("/yt-lockup-content/", $data) as $chunk ) {
  // $chunk = $this->tag_format($chunk);

/*
they dun changed their shit! 2/14/2014

      $video_title = $this->parse_attr("div", "data-context-item-title", $chunk);
      $video_id    = $this->parse_attr("div", "data-context-item-id", $chunk);
      $video_type  = $this->parse_attr("div", "data-context-item-type", $chunk);
      $video_time  = $this->parse_attr("div", "data-context-item-time", $chunk);
      $video_views = $this->parse_attr("div", "data-context-item-views", $chunk);
      $video_user  = $this->parse_attr("div", "data-context-item-user", $chunk);
*/
// print "\n$chunk\n---------------------------------------------------------------------\n";

      $video_title = $this->parse_attr("a", "title", $chunk);
      $video_id_str    = $this->parse_attr("a", "href", $chunk);
      $video_id = '';
      if ( preg_match("|/watch\?v=([^&]+)|",  $video_id_str, $matches) )  {
      $video_id = $matches[1];
      }
      // print "$video_title -> $video_id\n";

      // $video_type  = $this->parse_attr("div", "data-context-item-type", $chunk);
      // $video_time  = $this->parse_attr("div", "data-context-item-time", $chunk);
      // $video_views = $this->parse_attr("div", "data-context-item-views", $chunk);
      // $video_user  = $this->parse_attr("div", "data-context-item-user", $chunk);



      if ($video_title && $video_id ) {
      $video_url = "https://www.youtube.com/watch?v=$video_id";
      // print "$video_title -> https://www.youtube.com/$video_url\n";


      $img = '';
      $title = $this->html2text($video_title);
      $desc = $video_url;
      $link = $video_url;
      $img = $this->parse_youtube_img($video_url);
      // print "$feed_title, $title, $desc, $link, $img\n";
      $this->add_item($feed_title, $title, $desc, $link, $img);
      }

    }

$this->detected_handler = "youtube";
return true;
}


function parse_twitter($url) {

if (
  !preg_match("/https?\:\/\/twitter\.com\/([^\/]+)/i", $url, $matches)
  ) {
return false;
}

$twitter_username = $matches[1];
$feed_title = "Twitter: $twitter_username";

$data = $this->cache_get( $url );

  // foreach (explode('<div class="stream-item-header">', $data) as $chunk )  {
  // foreach (explode('<div class="ProfileTweet-contents">', $data) as $chunk )  {
  foreach (explode('<div class="ProfileTweet-authorDetails">', $data) as $chunk )  {
  $img = $desc = $comments = $link = '';
  $img = $this->parse_img($chunk, $img);

    if ( preg_match('/href="(\/[^\/]+\/status\/[^"\/]+)"/s', $chunk, $matches) ) {
    $link = "https://twitter.com" . $matches[1];
    }

    // twitter format change detected on 11/8/2014
    if ( preg_match('/<p class="ProfileTweet-text js-tweet-text u-dir"[^>]+>(.*)/is', $chunk, $matches) ) {
    // if ( preg_match('/<p class="ProfileTweet-text js-tweet-text u-dir"\s+dir="ltr">(.*)/is', $chunk, $matches) ) {
    // list($desc, $extra)  = explode ('<span class="expand-stream-item',  $matches[1]);
    // list($desc, $extra)  = explode ('<ul class="ProfileTweet-actionList u-cf js-actions">',  $matches[1]);
    list($desc, $extra)  = preg_split('/<\w+ class="ProfileTweet-action[^>]+>/',  $matches[1]);

    // $desc = $matches[1];
    $desc = preg_replace("/<[^>]+>/", "", $desc);
    $desc = preg_replace("/\r|\n/", " ", $desc);
    $desc = preg_replace("/http\:\/\/\s+/", "http://", $desc );
    $desc = preg_replace("/#\s+/", "#", $desc ); # hashtag formatting
    $desc = preg_replace("/@\s+/", "@", $desc ); # twitter @handle formatting
    $desc = $this->html2text($desc);
    }



    if ($desc ) {
        $title = "Twitter: $twitter_username - $desc";
        $desc = "Tweet------\n$desc\nLink-----\n$link\n";
        // link
        // img
      $this->add_item($feed_title, $title, $desc, $link, $img);

    }

  }

$this->detected_handler = "twitter";

return true;
}


function parse_stock($url) {
// disallowing comma in the regex, only allow one stock here now
if (
   !preg_match("/stock\:\/\/([^\/,]+)/i", $url, $matches) 
  ) {
  return false;
  }

$symbol_str = $matches[1];
$symbols = explode(",", $symbol_str);


$feed_title = "Stock Quote: " . $matches[1];

  $yf_fields = array(
  "n"  => "Name",
  "c"  => "Change & Percent Change",
  "b2" => "Ask (Real-time)", 
  "l"  => "Last Trade (With Time)",
  "r"  => "P/E Ratio",
  "g"  => "Day's Low",
  "h"  => "Day's High",
  "m4" => "200-day Moving Average",
  "w"  => "52-week Range",
  "f6" => "Float Shares",
  "r2" => "P/E Ratio (Real-time)",
  "s"  => "Symbol",
  "a"  =>  "Ask",   
  "d1" => "Last Trade Date",
  "a2" => "Average Daily Volume", 
  "a5" => "Ask Size",
  "b"  => "Bid", 
  "b3" => "Bid (Real-time)",
  "b4" => "Book Value",
  "b6" => "Bid Size",
  "c1" => "Change",
  "c3" => "Commission",
  "c6" => "Change (Real-time)",
  "c8" => "After Hours Change (Real-time)",
  "d"  => "Dividend/Share",
  "d2" => "Trade Date",
  "e"  => "Earnings/Share",
  "e1" => "Error Indication (returned for symbol changed / invalid)",
  "e7" => "EPS Estimate Current Year",
  "e8" => "EPS Estimate Next Year",
  "e9" => "EPS Estimate Next Quarter",
  "j"  => "52-week Low",
  "k"  => "52-week High",
  "g1" => "Holdings Gain Percent",
  "g3" => "Annualized Gain",
  "g4" => "Holdings Gain",
  "g5" => "Holdings Gain Percent (Real-time)",
  "g6" => "Holdings Gain (Real-time)",
  "i"  => "More Info",
  "i5" => "Order Book (Real-time)",
  "j1" => "Market Capitalization",
  "j3" => "Market Cap (Real-time)",
  "j4" => "EBITDA",
  "j5" => "Change From 52-week Low",
  "j6" => "Percent Change From 52-week Low",
  "k1" => "Last Trade (Real-time) With Time",
  "k2" => "Change Percent (Real-time)",
  "k3" => "Last Trade Size",
  "k4" => "Change From 52-week High",
  "k5" => "Percebt Change From 52-week High",
  "l1" => "Last Trade (Price Only)",
  "l2" => "High Limit",
  "l3" => "Low Limit",
  "m"  => "Day's Range",
  "m2" => "Day's Range (Real-time)",
  "m3" => "50-day Moving Average",
  "m5" => "Change From 200-day Moving Average",
  "m6" => "Percent Change From 200-day Moving Average",
  "m7" => "Change From 50-day Moving Average",
  "m8" => "Percent Change From 50-day Moving Average",
  "n4" => "Notes",
  "o"  => "Open",
  "p"  => "Previous Close",
  "p1" => "Price Paid",
  "p2" => "Change in Percent",
  "p5" => "Price/Sales",
  "p6" => "Price/Book",
  "q"  => "Ex-Dividend Date",
  "r1" => "Dividend Pay Date",
  "r5" => "PEG Ratio",
  "r6" => "Price/EPS Estimate Current Year",
  "r7" => "Price/EPS Estimate Next Year",
  "s1" => "Shares Owned",
  "s7" => "Short Ratio",
  "t1" => "Last Trade Time",
  // "t6" => "Trade Links",
  "t7" => "Ticker Trend",
  "t8" => "1 yr Target Price",
  "v"  => "Volume",
  "v1" => "Holdings Value",
  "v7" => "Holdings Value (Real-time)",
  "w1" => "Day's Value Change",
  "w4" => "Day's Value Change (Real-time)",
  "x"  => "Stock Exchange",
  "y"  => "Dividend Yield",
  );

  $f = join("", array_keys($yf_fields) );

  $data = $this->cache_get("http://download.finance.yahoo.com/d/quotes.csv?s=$symbol_str&f=$f");


  $fields = array_keys($yf_fields);
      
$count = 0;
  foreach (explode("\n", $data) as $line) {
  $symbol = $symbols[$count];
   $line = str_replace('"', '', $line); 
   $line = str_replace("\r", '', $line); 
    // list($symbol_resolved, $last_trade_price, $last_trade_date, $last_trade_time, $numeric_change, $perc_change) = explode(",", $line);
    $stack_line  = explode(",", $line);
    $ar_line = array();
    $cell = 0;
    $desc = '';
    $symbol_resolved = '';
  $price = $stack_line[5];

      foreach( $fields as $field) {
      $ar_line[$field] = $stack_lines[$cell] ;
        if ($yf_fields[$field] == "Name") {
        $symbol_resolved =  trim($stack_line[$cell]) ;
        }
      $cell_data = $this->html2text($stack_line[$cell]) ;
      $cell_data = trim(preg_replace("/\s+/", " ", $cell_data));
      $desc .= $yf_fields[$field] .": $cell_data\n";
      $cell++;
      }

      if (!$symbol_resolved) { $count++; continue; }
       $desc; // "Stock Symbol: $symbol_resolved\nPrice: $price\nDate: $day";
       $link = "http://www.google.com/finance?q=$symbol";

      // $this->track_age("Stock Quotes", $symbol_resolved, $desc, $link, '', $category);
      $title = "$symbol_resolved = $price";
      // $title = "$title " . Date("m/d h:i A");
      $img = '';
      $this->add_item($feed_title, $title, $desc, $link, $img, 'force as new');
      // $this->add_item($feed_title, $title, $desc, $link, $img);
  $count++;
  }


$this->detected_handler = "stock";
return true;
}

function parse_facebook ($url) {
  if (
   !preg_match("/https?\:\/\/www\.facebook\.com\/pages\/([^\/]+)\//i", $url, $matches) 
   &&  !preg_match("/https?\:\/\/www\.facebook\.com\/(.*)/i", $url, $matches) 
  ) {
  return false;
  }

$facebook_handle = trim($matches[1]);

  $feed_title = "Facebook: $facebook_handle";

  $data = $this->cache_get( $url);

  foreach (explode("userContentWrapper", $data) as $chunk )  {
  $ximg = $img = $desc = $comments = $link = '';

      // $img = $this->parse_img($chunk, $img); 
  // if ( preg_match('/<img class="fbPhotoImage img" id="fbPhotoImage" src="([^"]+)"/', $chunk, $matches) ) {

  if ( preg_match('/<img class="img" src="([^"]+)"/', $chunk, $matches) ) {
  $ximg = $matches[1];
    if ( preg_match('/url=(.*)/', $ximg, $matches) ) {
    $img = urldecode($matches[1]);
// print "$img\n"; sleep(3);
    }

  }


  if ( preg_match('/<span class="userContent">(.*)<\/span/', preg_replace("/\s+/", " ", $chunk), $matches) ) {
  $desc = $matches[1];
  $parts = explode("</span>", $desc);
  $desc = array_shift($parts);
  $desc = $this->html2text($desc);
  foreach( explode("actorDescription", join("</span>", $parts)) as $comment_chunk) {
  $comments .=  $this->html2text("<div " .$comment_chunk. ">") ."\n---\n";
  }

  // $desc = preg_replace("/Like Comment Share/", "\n------\n", $desc);
  }

  if ( 
  preg_match('/<span class="userContent">[^<]+<a href="([^"]+)"/', $chunk, $matches) 
  || preg_match('/<a href="([^"]+)"/', $chunk, $matches) 
  ) {

  $link = $matches[1];

    if (preg_match('/https?\:\/\/www\.facebook\.com\/l\.php\?u=([^&]+)&amp;/', $link, $matches2) ) {
    $link = urldecode($matches2[1]);
    }
  }

  if ($desc) {
  $desc = $this->html2text($desc);

      $title = $desc;
      $desc = "Post------\n$desc\nLink-----\n$link\nComments-----\n$comments\n";
      $this->add_item($feed_title, $title, $desc, $link, $img);
  }

  }


$this->detected_handler = "facebook";
return true;
}

function parse_gmail($url) {

  if ( !preg_match("/gmail\:\/\/(.*)/i", $url, $matches) ) {
  return false;
  }

  list($gmail_user, $gmail_pass) = explode("/", trim($matches[1]));

  $inbox = imap_open('{imap.gmail.com:993/imap/ssl}INBOX', $gmail_user, $gmail_pass) or die('Cannot connect to Gmail: ' . imap_last_error());

  $emails = imap_search($inbox,'ALL');

$feed_title = "$gmail_user@gmail.com";

  if($emails) {

    rsort($emails);

    foreach($emails as $email_number) {

      $overview = imap_fetch_overview($inbox,$email_number,0);
      $message = imap_fetchbody($inbox,$email_number, '1.1');
      if (!$message) {
      $message = quoted_printable_decode(imap_fetchbody($inbox,$email_number,1));
      if (! preg_match("/ /", $message) && preg_match("/ /", base64_decode($message)) ) {
      $message = base64_decode($message);
      }
      /*
      $message = preg_replace("/=\d*\r\n/i", "", $message);
      $message = preg_replace("/[ \t]+/i", " ", $message);
      */
      }
      $message = preg_replace("/\r\n/i", "\n", $message);
      $message = preg_replace("/<[^>]+>/i", " ", $message);
      $message = preg_replace("/  /i", " ", $message);

      $date = $overview[0]->date;
      $from = $overview[0]->from;
      $subject = $overview[0]->subject;
      $subject = preg_replace("/[<>]+/i", "", $subject);
      $from = preg_replace("/[<>]+/i", "", $from);

  $desc = base64_encode("
  Date: $date
  From: $from
  Subject: $subject
  Message: $message
  ");



  $title = "$from -- $subject";
  $link = "http://gmail.com"; 
  $img = '';
  $this->add_item($feed_title, $title, $desc, $link, $img);

    /*
    $fh = fopen($this->root ."/cache/".md5("email$email_number"), "w");
    fputs($fh, base64_decode($desc));
    fclose($fh);
    */
    }
  }

  imap_close($inbox);

$this->detected_handler = "gmail";
return true;
}


function parse_rss($url) {
$this->debug("parsing rss\n");
$data = $this->cache_get( $url);
$data = $this->tag_format($data);


$feed_title = '';

  if ( preg_match("/<item/i", $data) ) {
  $item_tag = "item";
  $description_tag = "description";
  }
  elseif ( preg_match("/<entry/i", $data) ) {
  $item_tag = "entry";
  $description_tag = "content";
  }
  else{
  $item_tag = "item_not_gonna_match_9999999";
  $description_tag = "content_not_gonna_match_999999";
  }

$in_item = false;


if ( !preg_match("/<" . $item_tag."/i", $data) ) {
return false;
}


  foreach ( explode("\n", $data) as $line ) {
  if (!strlen(trim($line)) ) { continue; }
  // print "$line\n";
    if ( preg_match("/<" . $item_tag."/i", $line) ) {
    $in_item = true;
    $youtube = $img = $item = $date = $title = $orig_link = $link = $desc = $desc2 = '';
    }

    if (!$in_item) { 
        if (!$feed_title) {
        $feed_title = $this->parse_tag("title", $line, $feed_title);
        }

    continue; 
    }
    else {
    if (!$feed_title) {
    $feed_title = $this->parse_domain($url, $feed_title);
    }

  $title = $this->parse_tag("title", $line, $title);


  $link = $this->parse_tag("link", $line, $link);
  if (!$link) {
  $link = $this->parse_tag("feedburner:origLink", $line, $orig_link);
  }
  if (!$link) {
  $link = $this->parse_attr("link", "href", $line, $link);
  }

  $desc = $this->parse_tag("$description_tag", $line, $desc);
  // $desc2 .= $this->parse_tag("ht:news_item_title", $line, '');
  $desc2 = $this->parse_tag("ht:news_item_snippet", $line, '');
  if ($desc2) {
  // $desc = "$desc\n-----\n$desc2\n";
  $desc = $desc2;
  }
$desc = $this->html2text($desc);
if (!$desc) {
// $desc = "...";
}
  // $date = $this->parse_tag("pubDate", $line, $date);

  // $youtube = $this->parse_attr("media:thumbnail", "url", $line, $youtube); // huh ? test me

if(!$img || preg_match("/favicon\.ico/i", $img) ) {
$img = $this->parse_attr("media:content", "url", $line, $img); 
}
if(!$img || preg_match("/favicon\.ico/i", $img) ) {
$img = $this->parse_attr("media:thumbnail", "url", $line, $img); 
}
if(!$img || preg_match("/favicon\.ico/i", $img) ) {
$img = $this->parse_img($this->parse_tag("content:encoded",  $line, ''));
}

$ht_img = $this->parse_tag("ht:picture", $line, '');
if ($ht_img) { $img = $ht_img; }

$youtube = $this->parse_youtube_img($line, $youtube);
if ($youtube && (!$img || preg_match("/favicon\.ico/i", $img) ) ) {
$img= $youtube; 
}
  
$img = $this->parse_img($line, $img); 


  if ($date && !is_numeric($date)) { $date = strtotime($date); }

  if ( $title && preg_match("/<\/" . $item_tag .">/", $line) ) {

    if (preg_match("/godlike/i", $url) 
    && ! preg_match("/\(Pinned\)/", $title ) 
    && ! preg_match("/\(Error fetching\)/i", $title ) 
    ) {
    return;
    }

      if ($orig_link) { $link = $orig_link; }

  if (!$title) {
  $title = $this->parse_domain($url, $title);
  }


  $title = $this->html2text($title);

  if ($img) {
  $this->debug("\tfound image $img for $feed_title $title", 5);
  }
  if ($is_gmail) {
  }
  
  if ( preg_match('/(https?\:\/\/[^"]+)/', $link, $matches) ) {
  $link = $matches[1];
  }

  $this->add_item($feed_title, $title, $desc, $link, $img);
  }


  }

  }

$this->detected_handler = "rss";
return true;
}

function parse_html($url) {
  $title = $this->parse_tag("title", $data, $title);
  $feed_title = "Static - $title";

  $text = $this->html2text( $data );
/*
  $this->write("$cache.a", $text);


    if ( file_exists("$cache.b") ) {
    $diff =`diff '$cache.a' '$cache.b' | grep '^>' `;
    }

    if (!strlen($diff) ) {
    copy("$cache.a", "$cache.b");
    $desc = "No change";
    }
    else {
    $desc = "Data Changed !:\n$diff";
    }
*/

$desc = $text;
$link = $url;

$this->add_item($feed_title, $title, $desc, $link, $img);
$this->detected_handler = "static-html";

return true;
}

function tag_format($data) {

$data = preg_replace("/&#x2F;/", "/", $data);
$data = preg_replace("/&#039;/", "'", $data);

$data = preg_replace("/&#[^;]+;/", "", $data);
$data = preg_replace("/\s+|\r+|\n+/", " ", $data);
// $data = preg_replace("/>/", ">\n", $data);
$data = str_replace(">", ">\n", $data);
$in_cdata = false;

foreach (explode("\n", $data) as $line) {

if ( preg_match("/<\!\[CDATA\[/", $line) ) {
$in_cdata = true;
$line = preg_replace("/<\!\[CDATA\[(.*)/", "$1", $line);
}
if ( preg_match("/\]\]>/", $line) ) {
$in_cdata =false;
$line = preg_replace("/(.*)\]\]>/", "$1", $line);
}
if ( $in_cdata ) {
// $line = htmlspecialchars($line);
$line = preg_replace("/</", "&lt;", $line);
$line = preg_replace("/>/", "&gt;", $line);
}
$new_data .= $line;
}

$new_data = preg_replace("/\s+|\r+|\n+/", " ", $new_data);
// $new_data = preg_replace("/</", "\n<", $new_data);
$new_data = str_replace("<", "\n<", $new_data);



return $new_data;

}

function scrub($str, $max_len=32) {
$str = trim($str);
$str = substr($str,0, $max_len);
$str = str_replace("'", "", $str);
$str = mysql_real_escape_string($str);
return $str;
}


function add_item($feed_title, $title, $desc, $link, $img, $force_new=false) {

$this->elapsed = $this->start - microtime(true);
$category = $this->category;



$desc  = preg_replace("/'/", "&quot;", $desc);
$title = preg_replace("/'/", "&quot;", $title);
$feed_title = preg_replace("/'/", "&quot;", $feed_title);
$link  = preg_replace("/'/", "&quot;", $link);
// print "link $link\n";
// $link = preg_replace("/&#x2F;/", "/", $link);
// print "became $link\n";
$img   = preg_replace("/'/", "&quot;", $img);

$feed_title = $this->scrub($feed_title, 200);
$title = $this->scrub($title, 200);
$desc = $this->scrub($desc, 20000);


$data = "
fid=$this->fid
feed=$feed_title
title=$title
desc=$desc
link=$link
img=$img
cat=$category
";

if (!$feed_title || !$title) {
return;
}

$this->response_good = true; // we got something, if we are here, don't need to check http status, I think

if ($this->mode == "test") {
$this->result[] = array(
'title' => $feed_title,
'item' => $title,
'excerpt' => $desc,
'url' => $link,
'image' => $img,
);
return;
}



$md5 = md5($this->fid . ":::". $title);

if (!$this->fname) {
$this->query("update feeds set name='$feed_title' where id='$this->fid'");
$this->debug("\tnew feed name , '$feed_title' ");
}


if ($force_new) {
$old = false;
// $this->query("delete from feed_data where md5='$md5' limit 1");
$this->query("delete from feed_data where fid='$this->fid' limit 1");
}
else {
$old = $this->scalar("select count(*) from feed_data where md5='$md5' limit 1");
}

// slim this down
$data ='';


if (!$old) {
$this->response_new  = true;
$this->query("insert into feed_data (id, fid, md5, data, status, ftitle, title, txt, link, image, video, response_time, last_mod) values('', '$this->fid', '$md5', '$data', '200', '$feed_title', '$title', '$desc', '$link', '$img', '$video', '$this->elapsed', NOW())");
$this->debug("\tnew data, inserting '".substr($title, 0, 300)."' ");
}
else {
$this->debug("\tskipping old '".substr($title, 0, 300)."' ");
}

// print $this->mysql['query'];
// print mysql_error();

/*
| id            | int(10) unsigned     | NO   | PRI | NULL    | auto_increment |
| fid           | int(10) unsigned     | NO   | MUL | NULL    |                |
| data          | text                 | NO   |     | NULL    |                |
| status        | varchar(10)          | NO   |     | NULL    |                |
| title         | varchar(255)         | NO   |     | NULL    |                |
| txt           | text                 | NO   |     | NULL    |                |
| link          | text                 | NO   |     | NULL    |                |
| image         | varchar(255)         | NO   |     | NULL    |                |
| video         | varchar(255)         | NO   |     | NULL    |                |
| response_time | float(10,5) unsigned | NO   |     | NULL    |                |
| last_mod      | datetime             | YES  |     | NULL    |                |
+---------------+----------------------+------+-----+---------+----------------+
*/




}

function parse_url ($url) {
  $this->start  = microtime(true);

   0 // makes it easier to add and move
  || $this->parse_weather($url)
  || $this->parse_search($url)
  || $this->parse_vine($url)
  || $this->parse_instagram($url)
  || $this->parse_cryptocoin($url)
  || $this->parse_youtube($url) 
  || $this->parse_twitter($url)
  || $this->parse_stock($url)
  || $this->parse_facebook($url)
  || $this->parse_gmail($url)
  || $this->parse_rss($url)
  || $this->parse_html($url)
  ;
}

function process() {

foreach ( $this->rows("
select 
id, name, url, category, poll_interval, NOW() from feeds 
where (
  unix_timestamp(NOW()) - unix_timestamp(last_poll)
 > poll_interval
)
or last_poll = 0
order by rand()
") as $row ) {
list ($fid, $name, $url, $cat, $poll_interval, $now) = $row;

  if (preg_match("/gmail/", $url) ) {
  continue;
  }
$this->response_good = false;
$this->response_new = false;

$this->debug("Processing $fid :: $cat :: $url");

$this->fid = $fid;
$this->category = $cat;
$this->fname = $name;


$this->parse_url($url);



$wobble = rand(1,5);

$one_hour  = (60 * 60) + $wobble;
$one_day   = $one_hour * 24;
$one_week = $one_day * 7;
$one_month = $one_day * 30;


if ( preg_match("/^coin/i", $url)  ) {
$poll_interval = 60 * 60 ;
/*
There is a pre-caching system that runs every 3 hours in the
/qbin/fetch_coin_prices.php cron 
coin prices are ALWAYS from this cache, but stock prices may be pulled on the fly
*/

$this->response_new = true;
$this->response_good = true;
}

if ( preg_match("/^stock/i", $url) ) {
$poll_interval = 60 * 60 * 4;
/*
There is a pre-caching system that runs every 3 hours in the
/qbin/fetch_stock_prices.php cron 
yahoo allows fetching in batches of 50, very
beneficial boost in performance to pull these in that way, while at the same
time only polling a stock once for all subscribed users via the feed
abstraction
*/

$this->response_new = true;
$this->response_good = true;
}

if (!$poll_interval) {
$poll_interval = 1;
}


if ($this->response_new && $this->response_good) {
if ($poll_interval >= $one_month) {
$new_poll_interval = $one_week;
}
elseif ($poll_interval >= $one_week) {
$new_poll_interval = $one_day;
}
elseif ($poll_interval >= $one_day) {
$new_poll_interval = $one_hour;
}
else {
// leave it alone
// $new_poll_interval = $poll_interval - $one_hour;
}

  if (intval($new_poll_interval) <= $one_hour  ) {
  $new_poll_interval = $one_hour;
  $this->debug("\tnew data, poll_interval maxed out , leaving at $new_poll_interval");
  }
  else {
  $this->debug("\tnew data, poll_interval adj faster from $poll_interval to $new_poll_interval");
  }

//  $this->debug("\tpurging items older than $now");
// $this->query("delete from feed_data where fid='$fid' and last_mod < '$now'");
}
elseif($this->response_good) {
$new_poll_interval = ($poll_interval + $wobble) * 2;

  if (intval($poll_interval) > ($one_month * 2) ) {
  $new_poll_interval = $one_month;
  $this->debug("\tno change, poll_interval adj at botom leaving at $new_poll_interval");
  }
  else {
  $this->debug("\tno change, poll_interval adj slower from $poll_interval to $new_poll_interval");
  }

}
else {
// $new_poll_interval = $poll_interval * 2;
$new_poll_interval = ($poll_interval + $wobble) * 2;

  if (intval($poll_interval) > ($one_month * 2) ) {
  $poll_interval = $one_month;
  $this->debug("\t$fid :: $cat :: $url - feed error, poll_interval adj at bottom leaving at $new_poll_interval");
  }
  else {
  $this->debug("\t$fid :: $cat :: $url - feed error, poll_interval adj slower from $poll_interval to $new_poll_interval");
  }

}
$new_poll_interval = ceil($new_poll_interval);

$this->query("update feeds set last_poll='$now', poll_interval='$new_poll_interval'  where id='$fid' limit 1");

}
  

// end function 
}

function json_decode_wrapper ($str) {

$json = new Services_JSON();

return $json->decode($str);



}

function debug ($str) {
if (!$_SERVER['REQUEST_URI'] ) {
print "$str\n";
}
}


}


?>
