<?php

class monitor extends ix_spider {

  function monitor () {
  $this->ix_spider();
  $this->categories = array();

  $this->home_dir = $_SERVER['HOME'];
  $this->root = $_SERVER['HOME'] ."/.ixmon";
  $this->refresh_limit = 10;
  $this->refreshed_total = 0;
  $this->refresh_time = 86400;
  $this->refresh_histo = false;
  $this->debug_level = 10;

  $this->feed_titles = array();
  $this->category_feed_titles = array();
  $this->article_titles = array();
  $this->article_desc = array();
  $this->article_link = array();
  $this->article_img  = array();


    $this->mkdir("$this->root");
    $this->mkdir("$this->root/seen");
    $this->mkdir("$this->root/cache");
    $this->mkdir("$this->root/images");
 
  $this->load_urls();


  }

function init () {


  if (!$this->cache_initialized ) {

  $this->cache();
  $this->window = 0;
  //$monitor->update_data();
  $this->category_filter(0);
  }


}

function mkdir($dir) {

    if (!is_dir( $dir ) ) {
    mkdir( $dir ) || die ("Couldn't create $dir !");
    $this->debug("creating $this->root/seen/ dir", 1);
    }

}

function sparse_update () {

$action_performed = false;
  if ( $GLOBALS["sparse_update_lock"] == 1 ) {
  return;
  }
$GLOBALS["sparse_update_lock"] = 1;

$now = time();

$schedule = array(
  "feed-update" => 60 * 60, // once an hour 
  "ascii-update" => 10, // once every 10 seconds
);

$action_performed = false;

foreach ($schedule as $job => $interval ) {

if (!$this->last_update[$job] ) {
  $this->last_update[$job] = $now;
  }

if ( $now - $this->last_update[$job] > $interval ) {

  if ($job == "feed-update") {
  $this->refreshed_total = 0;
  $this->cache("nuke");
  $GLOBALS["ncurses"]->refresh();
  $msg = `fortune`;
  $this->debug ="\n$msg\n" . substr($this->debug, 0, 10000);
  $msg = line_break($msg, 30);
  $action_performed = true;
  }
  elseif ($job == "ascii-update") {
    if ( $this->ascii_job['url'] ) {
    $this->ascii_get($this->ascii_job['url'], $this->ascii_job['file'], $this->ascii_job['ascii_file'] );
    $this->ascii_job = array();
    $this->debug = update_debug_pane();
    $GLOBALS["ncurses"]->refresh();
    $action_performed = true;
    }
    else {
    $this->debug = '';
    }

  }

  $this->last_update[$job] = $now;
}


}

$GLOBALS["sparse_update_lock"] = 0;

return $action_performed;

}

function delayed_ascii_get ($img_url, $img_file, $ascii_file ) {

$this->ascii_job = array(
      'type' => 'ascii_retrieval',
      'url' => $img_url,
      'file' => $img_file,
      'ascii_file' => $ascii_file,
    );
}

function ascii_get ($img_url, $img_file, $ascii_file ) {

if (!$img_url || !$img_file || !$ascii_file) {
return;
}

      if (!file_exists($img_file) && $img_url ) {
      $this->cache_get($img_url, $img_file, false, 999999999999999999999999999999); 
      $this->debug("LOADED youtube img_file=$img_file, img_url=$img_url", 1);
      touch($img_file);
      }

      if (file_exists($img_file) && !file_exists($ascii_file ) ) {
      $debug = $this->ascii_cat($img_file);
      $this->write($ascii_file, $debug);
      touch($ascii_file);
      }
}

function ascii_cat ($image) {
if (!file_exists($image)) { return; }

$icon_geom = "32x60";
$geom = "55x55";
$in_data = 0;
$data    = '';



$is_favicon = false;

  if ( preg_match( "/MS Windows icon resource/i", `file '$image'` ) ) {
  $is_favicon=true;
  }

  for($contrast = 31; $contrast < 100; $contrast += 10 ) {
    if ($is_favicon) {
    $ascii = `convert -coalesce -flatten -alpha off  -background none -modulate $contrast,1,1 -monochrome  -geometry $icon_geom ico:$image  xpm:- 2>&1`;
    }
    else {
    $ascii = `convert  -modulate $contrast,1,1 -monochrome  -geometry $geom $image  xpm:- 2>&1`;
    }

  }



  foreach ( preg_split("/[\r\n]/",  $ascii ) as $line ) {

  $line = preg_replace('/^"|",|"$/', "", $line);

    if ( preg_match("/};/", $line) ) { break; }

    if ( $in_data ) {
    $line = trim($line);
    $line = preg_replace("/[^\s]/", "#", $line );
    $line = preg_replace("/# /", ". ", $line );
    $line = preg_replace("/ #/", " .", $line );
    $data .= "$line\n" ;
    }

    if ( preg_match("/pixels/", $line) ) {
    $in_data++ ;
    }

  }

  if ( preg_match("/\S/", $data) ) {
  return $data;
  }

}




function record_failure ($cache) {
// touch("$cache.failed");
}
function failed ($cache) {

/*
  if ( file_exists("$cache.failed") ) {
  return true;
  }
*/

}

function get ($url) {
$user_agent = 'Mozilla/6.0 (Windows NT 6.2; WOW64; rv:16.0.1) Gecko/20121011 Firefox/16.0.1';
//  $wget_cmd   = "wget -q -t 1 -T 10 -U '$user_agent' '$url' -o /dev/null -O - ";

$process = curl_init($url);

$this->debug("GETTING $url", 1);

// $headers[] = "Accept-Charset: " .$_SERVER['Accept-Charset'];
// $headers[] = "Accept-Encoding: " .$_SERVER['Accept-Encoding'];
// $headers[] = "Accept-Language: " .$_SERVER['Accept-Language'];
$headers[] = "Connection: Close";
curl_setopt($process, CURLOPT_HTTPHEADER, $headers);
curl_setopt($process, CURLOPT_USERAGENT, $user_agent);

// curl_setopt($process,CURLOPT_ENCODING , $this->compression);
curl_setopt($process,CURLOPT_ENCODING , '');
curl_setopt($process, CURLOPT_TIMEOUT, 10);
curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
$data = curl_exec($process);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($process);

// if ($http_status < 400) 

return $data;



}

  function cache_get($url, $cache='', $tag_format=false, $poll_interval_override='') {

  $this->debug("cache_get() called for $url", 5);

  $hit_network = 0;
  $poll_interval = $this->feed_poll_interval;

    if (!strlen($cache)) {
    $cache = $this->root ."/cache/" . md5($url);
    }
  
    $age = $this->age($cache);

 
// time to refresh
    if ( !file_exists($cache) || $this->is_refresh ) {
    $this->debug("$url last poll interval is '$poll_interval', age of cache is '$age', refresh_time is '$this->refresh_time' retrieved $this->refreshed_total of $this->refresh_limit allowed ", 2);
    
    $GLOBALS['ncurses']->splash_dialog(
      "Retrieving...        "
      , short_str($category                     , 60) ."\n" 
      . short_str($url                          , 60) ."\n" 
      . short_str("poll interval=$poll_interval", 60) ."\n"
      ,rand(1,8) 
      );

      ncurses_mvwaddstr ($this->window, 1, 1,  "updating $url");
      // try 1 time with a timeout of 10 seconds
      $data = $this->get($url); // `$wget_cmd`;
      $hit_network++;
      $this->debug .= "$url updated " . date('l jS \of F Y h:i:s A') . "\n";

        if ($tag_format) {
        $data = $this->tag_format($data);
        }

        if (strlen($data) ) {
        $this->response_new = $this->write($cache, $data, "check for a change");
        $this->debug("cache updated", 2); 
        }
        else {
        $this->response_good = false;
        $this->debug("no data was available from network pull !  cache=$cache age=$age, poll_interval=$poll_interval url=$url ", 2);
        }
      
      $this->refreshed_total++;
    }
// NOT time to refresh
    else {
    $GLOBALS['ncurses']->splash_dialog(
      "Loading from cache..."
      , short_str($category                     , 60) ."\n" 
      . short_str($url                          , 60) ."\n" 
      . short_str("poll interval=$poll_interval", 60) ."\n"
      ,rand(1,8) 
      );
        $this->debug("cache NOT updated", 3);
    $data = file_get_contents($cache);
    }

    if (!strlen($data) || $data == "error") {
        $this->response_good = false;

        $this->debug("no data was available from previous network pull ! $url ", 2);
$data = "
<rss>
<item>
<title>Error fetching $url </title>
<link>$cache</link>
<description>Unavailable ... cache file is $cache\n</description>
</item>
</rss>
";
    
        
        unlink($cache); // better to alert the user to the bad url
        $this->refreshed_total--;
    }

return $data;

}

function get_poll_interval($cache) {

  if (!file_exists("$cache") && !file_exists("$cache.poll") ){
  $poll_interval = 0;
  }
  else {
  $poll_interval = intval(file_get_contents("$cache.poll"));
  }

return $poll_interval;
}

function update_poll_interval($cache, $poll_interval) {

/*
$age = $this->age($cache);
$poll_interval = $this->get_poll_interval($cache);

if ( !file_exists($cache) ) {
// poll interval should be set to default
}
elseif (md5($data) == md5(file_get_contents($cache)) ) {
// if the new data is the same as the old data, wait at least 1 more $this->refresh_time than last time before checking again .. grows arithmetically larger to infinity
$poll_interval += $age; 
}
else {
// if the new data is different than the old data, update the cache twice as frequently as the last time ... grows exponentially smaller down to 1
$poll_interval = ceil($age / 2);  
}
*/

$this->write("$cache.poll", $poll_interval);
$this->debug("poll interval set to '$poll_interval' ", 2);

}

function debug ($msg, $level=1) {
if ($level > $this->debug_level ) {
return;
}
$time = date('l jS \of F Y h:i:s A') ;
$this->append( $this->root . "/monitor.log", "$time --- $msg\n");
}

function append($filename, $data) {

$fh = fopen($filename, "a+");
fputs($fh, $data);
fclose($fh);

}

function write($filename, $data, $check_change=false) {

$new = false;

if ( $check_change && md5(file_get_contents($filename)) != md5($data) ) {
$new = true;
}

$fh = fopen($filename, "w");
fputs($fh, $data);
fclose($fh);

return $new;

}

function delete($index) {
// $index--;
// print_r($this->active_urls); sleep(20); exit;
// $url = $this->active_urls[$index];

$url = $this->cache[$this->selected_category]["feed_urls"][$index];
// print "$index .. $url"; sleep(20);
$cache = $this->root ."/cache/" . md5($url);

  if (file_exists($cache)) {
  $last_poll_interval = $this->get_poll_interval($cache);
  $this->update_poll_interval($cache, '' );
  // touch($cache, time() - $last_poll_interval - 1);
  // unlink($cache);
  }

}

function load_urls() {

$this->urls = array();

$seen = array();


$categories['Everything']++;

$category = "Unsorted";
foreach( file($this->root . "/config") as $line) {


  if ( preg_match('/^#/i', $line, $matches) ) {
  continue;
  }

  if ( preg_match('/--([^-]+)/i', $line, $matches) ) {
  $category = $matches[1];
  }
  elseif ( 
      preg_match('/(https?\:\/\/[^"]+)/i', $line, $matches) 
      || preg_match('/(gmail\:\/\/[^"]+)/i', $line, $matches) 
      || preg_match('/(stock\:\/\/[^"]+)/i', $line, $matches) 
      || preg_match('/(weather\:\/\/[^"]+)/i', $line, $matches) 
      || preg_match('/(coin\:\/\/[^"]+)/i', $line, $matches) 
      || preg_match('/(search\:\/\/[^"]+)/i', $line, $matches) 
      ) {
  $url = trim($matches[1]);
    if (!$seen[$url]) {
    $this->urls[] = $url;
    $this->cat_urls[$url] = $category;
    $this->all_urls[$url]++;
    $categories[$category]++;
    $seen[$url]++;
    }
  }
  else {
  continue;
  }

}

  $this->categories = array_keys(  $categories ) ;

$this->selected_category = $this->categories[0];
}


function update_data() {


$this->all_text = '';

$this->index = 2;
$this->new = array();

/*
foreach($this->new as $key => $value) {
$this->new[$key] = array();
}
*/
foreach (array("feed_urls", "feed_titles", "article_titles", "article_desc", "article_link", "article_img") as $key) {
$this->$key = array();
array_splice($this->key, $this->index );
}



$this->feed_titles[0] = "--- Most recent All Items";
$this->feed_urls  [0] = "recent://";
$this->feed_titles[1] = "--- Most recent Video";
$this->feed_urls  [1] = "recent://video/";

$histo_cache = $this->root ."/histo";
$histo_age = time() - @filemtime($histo_cache);

  if ( !file_exists($histo_cache) || !filesize($histo_cache) || $histo_age > $this->refresh_time) {
  $this->refresh_histo = true;
  }

for($i=0;$i<sizeof($this->urls);$i++ ) {
$url = $this->urls[$i];
$this->category = $this->cat_urls[$url];
if ($this->category != $this->selected_category && $this->selected_category != "Everything" ) { continue; }

$this->is_refresh    = false;
$this->response_good = false;
$this->response_new  = false;

$feed_cache_file = $this->root . "/cache/"  .md5($url);
$this->feed_poll_interval = $this->get_poll_interval($feed_cache_file);
if (!$this->feed_poll_interval) { $this->feed_poll_interval=30; }
$age = $this->age($feed_cache_file);

// || ($this->refreshed_total < $this->refresh_limit  
    //  && ( $age > $this->feed_poll_interval )

  if (  
  !$this->feed_poll_interval
  || !file_exists($feed_cache_file)
  || $age > $this->feed_poll_interval 
  ) {
    $this->is_refresh = true;
    $this->parse_url($url); 
    $new_poll_interval = $this->adjust_poll_interval($this->feed_poll_interval);
    $this->update_poll_interval($feed_cache_file, $new_poll_interval);
    touch($feed_cache_file);
    }
    else {
    $this->parse_url($url); 
    }

// used for connecting the actual (and potentially offscreen!) index of feed to other data, i.e. delete()
$this->feed_urls[] = $url;
$this->index++;

}



if ( $this->refresh_histo ) {
// $this->global_histo= $this->text_histogram($this->all_text, 150 );
$this->global_histo= $this->phrase_finder($this->all_text, 150 );

copy($histo_cache, "$histo_cache.last");
  $fh = fopen($histo_cache, "w");
  fputs($fh, $this->global_histo);
  fclose($fh);

}
else {
$this->global_histo = file_get_contents($histo_cache);
}

$index = 0;
// $this->feed_titles[] = "new stuff";

 // arsort($this->new['titles'], SORT_NUMERIC);
 asort($this->new['titles'], SORT_NUMERIC);
// asort($this->words, SORT_NUMERIC);
$html = "
<html>
<head>
<style>
body {
font-family: arial;
font-size:10px;
}
td {
border: 1px solid #eee;
}
</style>
</head>
<body>
<table cellpadding='5' >
";
$img_html = $html;

foreach($this->new['titles'] as $title => $value) {

$this->article_titles[0][] = $title;
$this->article_desc  [0][] = $this->new['desc'][$title];
$this->article_link  [0][] = $this->new['link'][$title];
$this->article_img   [0][]  = $this->new['img'][$title]; // hmm ?

if ( 
preg_match("/youtube/i", $link) 
|| preg_match("/vimeo/i", $link) 
) {
$type = "[-video-]";


$this->article_titles[1][] = $title;
$this->article_desc  [1][] = $this->new['desc'][$title];
$this->article_link  [1][] = $this->new['link'][$title];
$this->article_img   [1][]  = $this->new['img'][$title]; // hmm ?

}
else {
$type = "";
}





list($f, $t) = explode("->", $title);
$cat = $this->cat_urls[$link];
$link = $this->new['link'][$title];
$img = $this->new['img'][$title];
$desc = $this->new['desc'][$title];

$color = substr(md5($f), 0, 6);
$color2 = substr(md5($cat), 0, 6);

if ($count < 50) {
$img_html .= "
<a style='float:left;' target='n' href='$link'><img src='$img' alt='$t' /></a> 
";
}
if ($img) {
$img = "
<a style='float:right;' target='n' href='$link'>
<img style='margin:5px;max-width:300px;max-height:300px;' src='$img'  />
</a>
";
}
$type = "
<div style='padding:5px; width:500px;float:right;'>
<b>$t</b> 
$img
<br>
<br>
$desc
<br>
<br>
<a style='float:right;' target='n' href='$link'>view</a> 
</div>
";

if ($count < 100) {
if ($f == $last_f) {
$html .= "
<tr>
<td style='font-weight:bold;color:#fff;background:#$color2;'>$cat</td>
<td>$type </td>
</tr>\n
";
}
else {
$html .= "
<tr><td colspan='2' style='font-weight:bold;background:#$color;'> $f</td> </tr>
<tr>
<td style='font-weight:bold;color:#fff;background:#$color2;'>$cat</td>
<td>$type </td>
</tr>\n
";
}
}

// print "$title\n";
$last_f = $f;
$count++;
}
$html .= "
</table>
</body>
</html>
";
$i_html .= "
</table>
</body>
</html>

";

if ($this->selected_category == "Everything") {
$this->write("/tmp/news.html", $html);
$this->write("/tmp/inews.html", $img_html);
}


$this->cache[$this->selected_category]["article_titles"] = $this->article_titles;
$this->cache[$this->selected_category]["article_desc"] = $this->article_desc;
$this->cache[$this->selected_category]["article_link"] = $this->article_link;
$this->cache[$this->selected_category]["article_img"] = $this->article_img;
$this->cache[$this->selected_category]["feed_titles"] = $this->feed_titles;
$this->cache[$this->selected_category]["feed_urls"] = $this->feed_urls;

$this->write($this->root . "/ixmon.cache",  serialize($this)  );

}

function add_item ( $feed_title, $title, $desc, $link='', $img='', $force_new='') {
$this->response_good = true; // must have parsed something successfully if we are here
if (!$this->category) { $this->category = "unsorted";}

if (!$feed_title || !$title) { return;}

$first_feed_item = false;

$this->debug("$this->category -- $feed_title -- $title", 1);

// $link = preg_replace("/&amp;/", "&", $link);

$this->track_age($this->html2text($feed_title), $this->html2text($title), $this->html2text($desc), $link, $img, $this->category);

$this->article_desc[$this->index][]   = "$desc";
$this->article_link[$this->index][]   = "$link" ;
$this->article_titles[$this->index][] = "$title";
$this->article_img[$this->index][]    = "$img";


$this->feed_titles[$this->index] = $feed_title;
/*
if (!in_array($feed_title, $this->feed_titles) ) {
$this->index++;
}
*/

}

function category_filter ($i) {
// this sucks ... too slow
$refreshed_total = $this->refreshed_total;
$this->monitor() ;
$this->refreshed_total = $refreshed_total;
$this->selected_category = $this->categories[$i] ;

  if( is_array($this->cache[$this->selected_category]) ) {
  $this->article_titles = $this->cache[$this->selected_category]["article_titles"] ;
  $this->article_desc   = $this->cache[$this->selected_category]["article_desc"] ;
  $this->article_link   = $this->cache[$this->selected_category]["article_link"] ;
  $this->article_img    = $this->cache[$this->selected_category]["article_img"] ;
  $this->feed_titles    = $this->cache[$this->selected_category]["feed_titles"] ;
  }
  else {
  // $this->refresh_limit =10;
  $this->update_data() ;
  }

}

function cache ( $nuke=false) {

$this->cache_initialized = true;
if ($nuke) {
$i=0;
$this->cache = array();
  foreach($this->categories as $cat) {
  $i++;
}
}


$i=0;

  foreach($this->categories as $cat) {
  $this->category_filter($i);
  $i++;
  }

}

function age($file) {
$time = time();

  if ( file_exists($file) ) {
  $age = $time - filemtime($file) ;
  }
  else {
  $age = 0;
  }

return $age;
}

function track_age($feed, $title, $desc, $link, $img='', $cat='') {
$title = trim($title);
// $title = trim(str_replace("\n", "", $title));
// $desc = trim(str_replace("\n", "", $desc));

if ($cat && !$this->cat_urls[$link]) {
$this->cat_urls[$link] = $cat;
}

if ( preg_match("/gmail/", $feed) ) {
$desc = base64_decode($desc);
}

if (!$title) { return; }
$title_cache = $this->root ."/seen/" . md5($title);
$age = $this->age($title_cache) ;


    if (! file_exists($title_cache) ) {
    $age = 0;
    touch($title_cache);
    }
    elseif( $age < (60 * 60) ) {
    }
    $this->new['titles']["$feed -> $title"] = $age;
    $this->new['desc']["$feed -> $title"] = $desc;
    $this->new['link']["$feed -> $title"] = $link;
    $this->new['img']["$feed -> $title"] = $img;

  }

function parse_domain ($str, $already_got_it='') {

if ($already_got_it) {
return $already_got_it;
}

  if (preg_match("/https?\:\/\/([^\/]+)/i", $str, $matches) )  {
    $domain = $matches[1] ;
  }

return $domain;

}















function phrase_finder($data, $max_count=5) {
// $all_words = preg_split("/\s+/", $data); 

$data = $this->html2text($data);
foreach( file($this->root . "/phrase_stop") as $line) {
  foreach( explode(",", $line) as $word ) {
  $this->pstopwords[trim(strtolower($word))]++;
  }
}

foreach( file("/qbin/stopwords") as $word ) {
$this->stopwords[trim(strtolower($word))]++;
}
foreach( file( $this->root . "/stopwords") as $word) {
$this->stopwords[trim(strtolower($word))]++;
}

$three_words = array();
  for($index=0;$index<sizeof($this->feed_titles);$index++) {
    $tmp_three_words = array();
  $all_words = preg_split("/[^'a-z0-9A-Z]+/", strtolower($this->article_titles[$index] ." ". join(" ", $this->article_desc[$index]) ) ); 
    for($i=0;$i<sizeof($all_words);$i++) {
    if (strlen($all_words[$i]) > 20) { $all_words[$i] = '';  }
    if (strlen($all_words[$i +1]) > 20) { $all_words[$i+1] = '';  }
    if (strlen($all_words[$i +2]) > 20) { $all_words[$i+2] = '';  }
    if (strlen($all_words[$i +3]) > 20) { $all_words[$i+3] = '';  }
$candidate = trim($all_words[$i + 0] ." ". $all_words[$i +1] ." ". $all_words[$i + 2]. " " . $all_words[$i +3]) ;
$candidate2 = trim($all_words[$i + 0] ." ". $all_words[$i +1] ." ". $all_words[$i + 2]) ;
// $candidate1 = trim($all_words[$i]) ;
// $candidate = trim($all_words[$i + 0] ." ". $all_words[$i +1] ." ". $all_words[$i + 2]) ;
//$candidate2 = trim($all_words[$i + 0] ." ". $all_words[$i +1] ) ;
    if ( preg_match("/http/", $candidate ) ) { continue; }
    if ( preg_match("/youtube/", $candidate ) ) { continue; }
    if ( preg_match("/facebook/", $candidate ) ) { continue; }
    if ( preg_match("/watch/", $candidate ) ) { continue; }
    if ( preg_match("/author/", $candidate ) ) { continue; }
    if ( preg_match("/university/", $candidate ) ) { continue; }
    if ( preg_match("/twitter/", $candidate ) ) { continue; }
    if ( preg_match("/article/", $candidate ) ) { continue; }
    if ( preg_match("/rt com/", $candidate ) ) { continue; }
    if ( preg_match("/conversation/", $candidate ) ) { continue; }
    if ( preg_match("/null/", $candidate ) ) { continue; }
    if ( preg_match("/false/", $candidate ) ) { continue; }
    if ( preg_match("/likecount/", $candidate ) ) { continue; }
    if ( preg_match("/akamaihd/", $candidate ) ) { continue; }
    if ( preg_match("/div/", $candidate ) ) { continue; }
    if ( preg_match("/offset/", $candidate ) ) { continue; }
    if ( preg_match("/type user/", $candidate ) ) { continue; }
    if ( preg_match("/logtyping/", $candidate ) ) { continue; }
    if ( preg_match("/actorforpost/", $candidate ) ) { continue; }
    if ( preg_match("/likesentences/", $candidate ) ) { continue; }
    if ( preg_match("/collapseaddcomment/", $candidate ) ) { continue; }
    if ( preg_match("/showaddcomment/", $candidate ) ) { continue; }
    if ( preg_match("/shared a link/", $candidate ) ) { continue; }
    if ( preg_match("/comment/", $candidate ) ) { continue; }
    if ( preg_match("/nbsp/", $candidate ) ) { continue; }
    $tmp_three_words[ $candidate ]++;
    $tmp_three_words[ $candidate2 ]++;
    if (!$this->stopwords[$candidate1]) { 
      if (strlen($candidate1) > 1) { 
      $tmp_three_words[ $candidate1 ]+= 0.5;
      }
    }
    // $tmp_three_words[ $candidate2 ]++;
    // $tmp_three_words[ trim($all_words[$i + 0] ." ". $all_words[$i +1] ) ]++;
    }
  arsort($tmp_three_words, SORT_NUMERIC);
    $just_one =0;
    foreach($tmp_three_words as $word => $value) {
    if ($just_one > 10) { continue; }
    if ($this->pstopwords[$word]) { continue; }
    $three_words[$word]++;
    $just_one++;
    }
  }


$just_five = 1;
 arsort($this->words, SORT_NUMERIC);

// asort($this->words, SORT_NUMERIC);
foreach($three_words as $word => $value) {
if ($value < 2) { continue; }
//if (strlen($word) > 20) { continue; }
if ($just_five > $max_count) { continue; }
// if ($value < 10) { continue; }
// print "$word - $value <br>\n";

// $this->top[] = $word;
$top[] = $word;
$top5 .= "$word - $value\n";
$just_five++;
}

return $top5;

return join(", ", $top);


}

function text_histogram ($data, $max_count=5) {
$top = array();
$raw = $data;
$data = preg_replace("/<[^>]+>/", "", $data);
$data = preg_replace("/&#[^;]+;/", "", $data);
$this->words = array();
if (sizeof($this->stopwords) < 1) {
foreach( file("/qbin/stopwords") as $word ) {
$this->stopwords[trim(strtolower($word))]++;
}
foreach( file($this->root . "/stopwords") as $word) {
$this->stopwords[trim(strtolower($word))]++;
}
foreach( file( $this->root . "/histo.last") as $line) {
  foreach( explode(",", $line) as $word ) {
  //$this->stopwords[trim(strtolower($word))]++;
  }
}

}

$data = preg_replace("/\s+/", " ", $data);
$data = preg_replace("/\n/", " ", $data);
$data = preg_replace("/\r/", " ", $data);

$phrases = array();

  if (
     // capitalized - Four words ... writer on crack
       // !preg_match("/([A-Z][a-z]+\s+[A-Z][a-z]+\s+[A-Z][a-z]+\s+[A-Z][a-z]+)/", $data)
  1

       && (
      // capitalized - Three Word Phrases
      // preg_match_all("/([A-Z][a-z]+\s+[A-Z][a-z]+\s+[A-Z][a-z]+)/", $data, $matches)
      // capitalized - Two Word Phrases
       // || preg_match("/([A-Z][a-z]+\s+[A-Z][a-z]+)/", $data, $phrases)
       preg_match_all("/>([A-Z][a-z]+\s[A-Z][a-z]+)</", $raw, $matches)
      // || preg_match_all("/([A-Z][a-z]+\s[A-Z][a-z]+)/", $data, $matches)

       )
      ) {


    foreach ($matches as $phrases) {
    foreach ($phrases as $phrase) {
    // $data = preg_replace( $phrases, "", $data);
    $this->add_word($phrase, 2);
    }
    }

  }

$split_words = explode(" ", $data);

$split_words = explode(" ", $data);

  if (sizeof($split_words) > 3) {
    foreach ($split_words as $word) {
    $this->add_word($word);
    }
  }
  else {
    $this->add_word($data);
  }


$just_five = 1;
 arsort($this->words, SORT_NUMERIC);

// asort($this->words, SORT_NUMERIC);
foreach($this->words as $word => $value) {
if (strlen($word) > 20) { continue; }
if ($just_five > $max_count) { continue; }
// if ($value < 10) { continue; }
// print "$word - $value <br>\n";

// $this->top[] = $word;
$top[] = $word;
$top5 .= "$word - $value\n";
$just_five++;
}

return $top5;

return join(", ", $top);
}

function add_word ($str, $weight=1) {
$str = preg_replace("/[^a-z0-9 ]+/i", "", $str);
$str = trim(strtolower($str));
if (is_numeric($str) ) { return; }
if (strlen($str) == 1) { return; }
if (!$str) { return; }
if ($this->stopwords[$str]) {
return;
}

$this->words[$str]+= $weight;
}


function adjust_poll_interval ($poll_interval) {


$wobble = rand(1,5);

$one_hour  = (60 * 60) + $wobble;
$one_day   = $one_hour * 24;
$one_week = $one_day * 7;
$one_month = $one_day * 30;


/*
if ( 
preg_match("/^stock/i", $url) 
|| preg_match("/^stock/i", $url) 
) {
$poll_interval = 60 * 60 * 2;

$this->response_new = true;
// $this->response_good = true;
}

if (!$poll_interval) {
$poll_interval = $one_hour;
}

print "
respnose_new=$this->response_new
respnose_good=$this->response_good
";
sleep(10);
*/


// good ---------------------------------------------------------------------------
if ($this->response_new && $this->response_good) {
  if ($poll_interval >= $one_month) {
  $new_poll_interval = $one_week;
  }
  elseif ($poll_interval >= $one_week) {
  $new_poll_interval = $one_day;
  }
  elseif ($poll_interval >= $one_day) {
  $new_poll_interval = $one_hour;
  $new_poll_interval = $one_hour;
  }
  else {
  // leave it alone
  }

  if (intval($new_poll_interval) <= $one_hour  ) {
  $new_poll_interval = $one_hour;
  $this->debug("\tnew data, poll_interval maxed out , leaving at $new_poll_interval", 2);
  }
  else {
  $this->debug("\tnew data, poll_interval adj faster from $poll_interval to $new_poll_interval", 2);
  }

}
elseif($this->response_good) {
$new_poll_interval = ($poll_interval + $wobble) * 2;

  if (intval($poll_interval) > ($one_month * 2) ) {
  $new_poll_interval = $one_month;
  $this->debug("\tno change, poll_interval adj at botom leaving at $new_poll_interval", 2);
  }
  else {
  $this->debug("\tno change, poll_interval adj slower from $poll_interval to $new_poll_interval", 2);
  }

}
// bad ---------------------------------------------------------------------------
else {
$new_poll_interval = ($poll_interval + $wobble) * 2;

  if (intval($poll_interval) > ($one_month * 2) ) {
  $poll_interval = $one_month;
  $this->debug("feed error, poll_interval adj at bottom leaving at $new_poll_interval", 2);
  }
  else {
  $this->debug("feed error, poll_interval adj slower from $poll_interval to $new_poll_interval", 2);
  }

}

$new_poll_interval = ceil($new_poll_interval);
return $new_poll_interval;
}



}


?>
