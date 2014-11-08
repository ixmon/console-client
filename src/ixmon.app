#!/usr/bin/php  
<?
// ini_set("display_errors", "On");
// ini_set("error_reporting", E_ERROR);

date_default_timezone_set("America/New_York");
sanity_check();
wrap_in_xterm();
@ini_set("memory_limit", -1);

$ncurses = new ncurses();
$ncurses->splash_dialog("Loading data ... please wait",3 , 2);


/*
if (file_exists("/home/darkstar/.information_monitor/news.cache") ) {
$monitor = unserialize( file_get_contents( "/home/darkstar/.information_monitor/news.cache") );
$monitor->last_update["feed-update"] = time();
}
else {
}
*/

$monitor = new monitor();

if (file_exists($monitor->root . "/ixmon.cache") ) {
$monitor = unserialize( file_get_contents( $monitor->root . "/ixmon.cache") );
$monitor->last_update["feed-update"] = time();
}
else {
$monitor->init();
}

$debug = $monitor->global_histo;

$needs_refresh = true;

$ncurses->bind_key(array(K_KEY, NCURSES_KEY_UP),     "arrow_up"   );
$ncurses->bind_key(array(J_KEY, NCURSES_KEY_DOWN),   "arrow_down" );
$ncurses->bind_key(array(H_KEY, LEFT_KEY),           "arrow_left" );
$ncurses->bind_key(array(L_KEY, TAB_KEY, RIGHT_KEY), "arrow_right");
$ncurses->bind_key(array(Q_KEY),                     "quit"       );

// $ncurses->bind_key(      V_KEY,                      "youtube_stt");
$ncurses->bind_key(      ESCAPE_KEY,                   "show_help");
$ncurses->bind_key(      E_KEY,                      "edit_config");
$ncurses->bind_key(      ENTER_KEY,                  "handle_url" );
$ncurses->bind_key(      FSLASH_KEY,                 "search_func");


while(1){
$date = Date("l jS \of F Y h:i:s A");

/*
if ( intval($GLOBALS['tick']) > 50 ) {
$GLOBALS['tick'] = 0;
}
$swirl = '';

for($i=0;$i<50;$i++) {

  if (intval($GLOBALS['tick']) == $i) {
  $swirl .= "_-";
  }
  else {
  $swirl .= "__";
  }

}
$GLOBALS['tick']++;
*/

$color = 6;
$ncurses->app_title("$date $swirl", $color, false);
$color = 5;

$ncurses->widget_count = 0;
$ncurses->next['x'] = 0;
$ncurses->next['y'] = 1;

$ncurses->draw_menu('Categories', '7%' , '50%', category_menu_handler(), '', '', $color );
$ncurses->draw_menu('Feeds', '17%' , '50%', feed_menu_handler(), '', '', $color );
$ncurses->draw_menu ('Items', '72%' , '50%', items_menu_handler(), '', '', $color );

$ncurses->draw_textarea('Excerpt', '45%', '50%', excerpt_textarea_handler(),'','', '', $color) ;
$ncurses->draw_textarea ('Debug', '55%', '50%', $debug, '','', "no_break", $color) ;


/*
$ncurses->draw_menu('Categories', '7%' , '50%', category_menu_handler() );
$ncurses->draw_menu('Feeds', '17%' , '50%', feed_menu_handler() );
$ncurses->draw_menu ('Items', '39%' , '50%', items_menu_handler() );

$ncurses->draw_textarea('Excerpt', '33%', '50%', excerpt_textarea_handler()  ) ;
$ncurses->draw_textarea ('Debug', '100%', '50%', $debug, '','', "no_break"  ) ;
*/

ncurses_move(0,$ncurses->windows['main']['width']-1); // toss cursor out of sight.
$debug .= "\n" . time() ."\n";

$ncurses->trigger('Categories', 'Feeds');
$ncurses->trigger('Feeds', 'Items');
$ncurses->trigger('Items', 'Excerpt');

if ($y != "-1" || $needs_refresh) {
$ncurses->refresh();
$needs_refresh = false;
}
else {
$needs_refresh = $monitor->sparse_update();
$debug = $monitor->debug;
}

ncurses_timeout(100);
$y = ncurses_getch();

  if ($ncurses->has_callback($y) ) {
  $ncurses->callback($y);
  }
  else{
  if ($y != "-1") {
  $GLOBALS["monitor"]->debug("The '$y' key was pressed ...", 1);
  }

}


}//end main while





function debug_append($data) {
$GLOBALS["debug"] = substr("$data\n" . $GLOBALS["debug"], 0, 10000) ."\n";  
return $data;
}

function arrow_up () {
$GLOBALS['ncurses']->move_up();

    if($GLOBALS['ncurses']->active_window == 'Categories') {
    $GLOBALS['monitor']->category_filter($GLOBALS['ncurses']->windows[$GLOBALS['ncurses']->active_window]['abs_x']);
    }


debug_append(update_debug_pane());
// $debug = update_debug_pane();

}

function arrow_down () {

$limit = '';
$ncurses = $GLOBALS['ncurses'];
$monitor = $GLOBALS['monitor'];

    if($ncurses->active_window == 'Feeds') {
    $limit = sizeof($monitor->feed_titles);
    $index = $ncurses->windows['Feeds']['abs_x'] + 1;
    

    $feed_name = $monitor->cache[$monitor->selected_category]["feed_titles"][$index];
    // $feed_url = $monitor->active_urls[ $index ];
    $feed_url = $monitor->cache[$monitor->selected_category]["feed_urls"][$index];

    $cache = $monitor->root ."/cache/" . md5($feed_url);
    debug_append("\n------------\n");
    debug_append("F is $index");
    debug_append("Feed is $feed_name");
    debug_append("Feed url is $feed_url");
    debug_append("cache is $cache");
    debug_append("poll interval is " . $monitor->get_poll_interval($cache ) );
    debug_append("Last updated " . date('l jS \of F Y h:i:s A',  filemtime($cache)) );
    debug_append("\n------------\n");
    }
    elseif($ncurses->active_window == 'Items') {
    $limit = sizeof($monitor->article_titles[$ncurses->windows['Feeds']['abs_x'] +1]);
    }
    elseif($ncurses->active_window == 'Excerpt') {
    $limit = 100;
    }

  $ncurses->move_down($limit);
    if($ncurses->active_window == 'Categories') {
    $monitor->category_filter($ncurses->windows[$ncurses->active_window]['abs_x']);
    }
debug_append(update_debug_pane());

}

function show_help () {

$help =<<<EOT

 -==== IXMON console help ===-

  Keyboard shortcuts

  h or left arrow  = move left
  l or right arrow = move right
  j or down  arrow = move down
  k or up    arrow = move up

  ENTER            = in the excerpt pane loads the item URL in browser
  ENTER            = in the feed pane refreshes the current feed

  In the Excerpt pane, hitting enter loads the item URL in browser

  q                = quit 
  ESC              = show this help screen
  e                = edit your ixmon config (~/.ixmon/config)

 -=== Editing ~/.ixmon/config ===-

  # line is a comment
  --name indicates a category called "name"

  anything else is read as an RSS url OR an ixmon specific url such as:

  search://something
  weather://some_city/some_state
  coin://bitcoin

  A youtube, twitter, vine, or instagram URL will be parsed as RSS too

         For more info, check out http://Ixmon.com

EOT;


$GLOBALS["monitor"]->write( $GLOBALS["monitor"]->root . "/help", $help);

exec("xterm -geometry 280x74 +sb -b 0 -w 0 -fg white -bg black -fa '-xos4-monospace-bold-r-normal--10-120-72-72-c-60-iso10646-1' -fs 11 -e  vim ~/.ixmon/help");
}

function edit_config () {
exec("xterm -geometry 280x74 +sb -b 0 -w 0 -fg white -bg black -fa '-xos4-monospace-bold-r-normal--10-120-72-72-c-60-iso10646-1' -fs 11 -e  vim ~/.ixmon/config");
}

function youtube_stt() {
$ncurses = $GLOBALS['ncurses'];
$monitor = $GLOBALS['monitor'];

$w1_sel = $ncurses->windows['Feeds']['abs_x'];
$w2_sel = $ncurses->windows['Items']['abs_x'];
$w3_sel = $ncurses->windows['Excerpt']['abs_x'];


$url = trim($monitor->article_link[$w1_sel][$w2_sel ]);



// this is interesting, but needs some work, returns subtitles from youtube
// exec("xterm -geometry 280x74 -b 0 -w 0 -fg white -bg black -fa '-xos4-monospace-bold-r-normal--10-120-72-72-c-60-iso10646-1' -fs 9 -e '/qbin/printtube \"$url\" | vim - '");


}

function handle_url () {
$ncurses = $GLOBALS['ncurses'];
$monitor = $GLOBALS['monitor'];

$w1_sel = $ncurses->windows['Feeds']['abs_x'];
$w2_sel = $ncurses->windows['Items']['abs_x'];
$w3_sel = $ncurses->windows['Excerpt']['abs_x'];



$ncurses->splash_dialog("Loading data ... please wait");
    if ( $ncurses->get_active_window() == "Feeds" ) {
    $monitor->delete($ncurses->windows['Feeds']['abs_x'] );
$cat = $monitor->selected_category;

/*
    $monitor->monitor();
  $monitor->selected_category = $cat;
    $monitor->update_data();
*/
// $GLOBALS["monitor"]->monitor();
$GLOBALS["monitor"]->selected_category = $cat;
$GLOBALS["monitor"]->cache("nuke");

    $debug = $monitor->debug;

$ncurses->refresh();
    }
    elseif ( $ncurses->get_active_window() == "Excerpt" ) {
    $url = trim($monitor->article_link[$w1_sel][$w2_sel ]);
    // exec("xterm -bg black -fg white -e links '$url'  &");
      if ( !preg_match("/https?/i", $url) && file_exists($url) ) {
      exec("xterm -fg white -bg black -geometry 180x50 -b 0 -fs 18 -e 'vim $url'  ");
      }
      else{
      exec("firefox '$url'  &");
      }
    }
    elseif ( $ncurses->get_active_window() == "Debug" ) {
    
    $histo = explode("\n", $debug);
    list($ncurses->input_str, $undef) = explode(" - ", $histo[$ncurses->windows['Debug']['abs_x']]) ;

  $monitor->monitor() ;
  $monitor->search_term = $ncurses->input_str;
$ncurses->splash_dialog("searching all feeds for $ncurses->input_str...", 3, 1);
  $monitor->refresh_limit =0;
  $monitor->cache[$monitor->selected_category] = '';
  $monitor->cache("empty");
    $monitor->update_data() ;
  $debug = "search for: $ncurses->input_str";
  $ncurses->input_str = '';


    }


}

function quit () {
$ncurses = $GLOBALS['ncurses'];
$monitor = $GLOBALS['monitor'];

      $ncurses->end();
        exit;

}

function arrow_left () {
$ncurses = $GLOBALS['ncurses'];
$monitor = $GLOBALS['monitor'];

$ncurses->move_left();

if ($monitor->selected_category && $monitor->selected_category && !in_array( $monitor->selected_category, array( "Everything", "Unsorted"))  ) {
$debug = compute_histo($ncurses, $monitor, $debug) ;
}
else {
$debug = $monitor->global_histo;
}
}


function arrow_right () {
$ncurses = $GLOBALS['ncurses'];
$monitor = $GLOBALS['monitor'];

$ncurses->move_right();

if ($monitor->selected_category && $monitor->selected_category && !in_array( $monitor->selected_category, array( "Everything", "Unsorted"))  ) {
$debug = compute_histo($ncurses, $monitor, $debug) ;
}
else {
$debug = $monitor->global_histo;
}

// $debug = update_debug_pane();

}

function search_func () {
// don't do anything ... 
$ncurses = $GLOBALS['ncurses'];
$monitor = $GLOBALS['monitor'];

$ncurses->input_mode = "str";
$ncurses->input_str = '';
$GLOBALS['debug'] = "search for: $ncurses->input_str";


}

function update_debug_pane () {

$w1_sel = $GLOBALS["ncurses"]->windows['Feeds']['abs_x'];
$w2_sel = $GLOBALS["ncurses"]->windows['Items']['abs_x'];
$w3_sel = $GLOBALS["ncurses"]->windows['Excerpt']['abs_x'];


$GLOBALS["monitor"]->last_update['ascii-update'] = time();

if (
$GLOBALS["ncurses"]->get_active_window() == "Items" 
) {


    $chunk = trim($GLOBALS["monitor"]->article_desc[$w1_sel][$w2_sel ]);
    $chunk .= "\n" . trim($GLOBALS["monitor"]->article_link[$w1_sel][$w2_sel ]);
    $img_url = $GLOBALS["monitor"]->article_img[$w1_sel][$w2_sel ];
$title = trim($GLOBALS["monitor"]->article_titles[$w1_sel][$w2_sel ]);



    if ($img_url ) {
    }


    if ($img_url) {
    $img_file   = $GLOBALS["monitor"]->root ."/images/". md5($img_url . " image") ;
    $ascii_file = $GLOBALS["monitor"]->root ."/images/". md5($img_url . " image ascii") ;


      if (file_exists($ascii_file)  ) {
      $GLOBALS["monitor"]->ascii_job = array();
      return  file_get_contents($ascii_file);
      }
      else {
      $GLOBALS["monitor"]->debug("detected img_file=$img_file, img_url=$img_url, scheduling retrieval... ", 1);
      $GLOBALS["monitor"]->delayed_ascii_get($img_url, $img_file, $ascii_file); 
        for($i=0;$i<55;$i++) {
        $clear .= "\n";
        }
      return "$title\n$image_url\n";
      }

    }
}

return $debug;
}


function sanity_check () {

$classes = array("ncurses", "monitor", "ix_spider");
$errors = '';

  foreach ($classes as $class) {
    if (!class_exists($class) ) {
    $errors .= "error loading $class\n";
    }
  }

  if ($errors) {
  die($errors);
  }

  if (!function_exists("ncurses_init") ) {
  $errors .= "Please install php-ncurses ... maybe 'pecl install ncurses' ?\n";

/*
Ubuntu 14.04 Trusty Tahr
apt-get install php5-dev libncursesw5-dev php-pear php5-curl
pecl install ncurses
*/

  }

  if ($errors) {
  die($errors);
  }

}


function wrap_in_xterm () {
  if (!isset($_SERVER["XTERM_RUNNING"])) {

  $prog = $GLOBALS['argv'][0];
  // "export XTERM_RUNNING=yes; xterm -geometry 280x74 -cr red -uc -ms black +sb -b 0 -w 0 -fg white -bg black -fa '-xos4-monospace-bold-r-normal--10-120-72-72-c-60-iso10646-1' -fs 9 -e '$prog'"
  system(
  "export XTERM_RUNNING=yes;LANG=en_US.UTF-8; xterm -u8 -geometry 280x74 -cr red -uc -ms black +sb -b 0 -w 0 -fg white -bg black -fa '-misc-fixed-medium-r-normal--18-120-100-100-c-90-iso10646-1' -fs 9 -e '$prog'"
  );
  exit;
  }
}

function category_menu_handler () {
$ncurses = $GLOBALS['ncurses'];
$monitor = $GLOBALS['monitor'];

return $monitor->categories;
}

function feed_menu_handler () {
$ncurses = $GLOBALS['ncurses'];
$monitor = $GLOBALS['monitor'];


return $monitor->feed_titles;

}

function items_menu_handler () {
$ncurses = $GLOBALS['ncurses'];
$monitor = $GLOBALS['monitor'];


$w1_sel = $ncurses->windows['Feeds']['abs_x'];
$w2_sel = $ncurses->windows['Items']['abs_x'];
$w3_sel = $ncurses->windows['Excerpt']['abs_x'];



return $monitor->article_titles[$w1_sel];

}


function excerpt_textarea_handler () {
$ncurses = $GLOBALS['ncurses'];
$monitor = $GLOBALS['monitor'];


$w1_sel = $ncurses->windows['Feeds']['abs_x'];
$w2_sel = $ncurses->windows['Items']['abs_x'];
$w3_sel = $ncurses->windows['Excerpt']['abs_x'];



return $monitor->article_desc[$w1_sel][$w2_sel ];

}

/*
this is the old search everything feature ... fix me

if ($ncurses->input_mode == "str") {
$needs_refresh = false;
$y = '';
  $ncurses->input_buf = ncurses_getch($ncurses->windows['main']['obj']);

  if ($ncurses->input_buf == ENTER_KEY) {
  $ncurses->input_mode = "chr";
  $ncurses->input_buf = '';

$ncurses->splash_dialog("searching all feeds for $ncurses->input_str...", 3, 1);

  $monitor->monitor() ;
  $monitor->search_term = $ncurses->input_str;
  $monitor->refresh_limit =0;
  $monitor->cache[$monitor->selected_category] = '';
  $monitor->cache("empty");
    $monitor->update_data() ;
  $debug = "search for: $ncurses->input_str";
  $ncurses->input_str = '';


  }
  elseif ($y == "-1") {
  }
  else {


    if ($ncurses->input_buf == BACKSPACE_KEY) {
    $ncurses->input_str = substr($ncurses->input_str, 0, -1);
    }
    else {
    if ( preg_match("/a-z0-9/i", $ncurses->input_buf ) ) {
    }
    $ncurses->input_str .= chr($ncurses->input_buf);
    }


  $debug = "search for: $ncurses->input_str";
  }
}
else {
ncurses_timeout(100);
$y = ncurses_getch($detail_window);
}
*/



function compute_histo ($ncurses, $monitor,  $debug) {


$w1_sel = $ncurses->windows['Feeds']['abs_x'];
$w2_sel = $ncurses->windows['Items']['abs_x'];
$w3_sel = $ncurses->windows['Excerpt']['abs_x'];



if ($ncurses->get_active_window() == "Excerpt") {
// $debug = $monitor->phrase_finder($monitor->article_desc[$w1_sel + 1][$w2_sel ], 10 );
$debug = $monitor->text_histogram($monitor->article_desc[$w1_sel][$w2_sel ], 10 );
}
elseif ($ncurses->get_active_window() == "Items") {
// $debug = $monitor->phrase_finder($monitor->article_desc[$w1_sel ][$w2_sel ], 10 );
$debug = $monitor->text_histogram( join(" ", $monitor->article_desc[$w1_sel]), 10 );
}
elseif ($ncurses->get_active_window() == "Feeds") {
$debug = $monitor->global_histo;
}
else {
$debug = $debug;
}

// return $ncurses->get_active_window();

return $debug;

}

?>

