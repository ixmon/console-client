<?php
class ncurses {

  function ncurses() {
  $this->windows = array();
  $this->widget_count = 0;
  $this->sel_window = 0;

  $this->ptr = array(0,0,0,0);
  $this->widget_padding = 5;
  $this->input_mode = 'chr';

  define("ESCAPE_KEY",     27);
  define("ENTER_KEY",      13);
  define("TAB_KEY",         9);
  define("LEFT_KEY",      260);
  define("RIGHT_KEY",     261);
  define("E_KEY",         101);
  define("V_KEY",         118);
  define("Q_KEY",         113);
  define("J_KEY",         106);
  define("K_KEY",         107);
  define("H_KEY",         104);
  define("L_KEY",         108);
  define("FSLASH_KEY",     47);
  define("BACKSPACE_KEY", 263);


$ncurses_session = ncurses_init();
ncurses_noecho();


    if (ncurses_has_colors()) {
    ncurses_start_color();
    ncurses_init_pair(1, NCURSES_COLOR_RED, NCURSES_COLOR_BLACK);
    ncurses_init_pair(2, NCURSES_COLOR_GREEN, NCURSES_COLOR_BLACK);
    ncurses_init_pair(3, NCURSES_COLOR_YELLOW, NCURSES_COLOR_BLACK);
    ncurses_init_pair(4, NCURSES_COLOR_BLUE, NCURSES_COLOR_BLACK);
    ncurses_init_pair(5, NCURSES_COLOR_CYAN, NCURSES_COLOR_BLACK);
    ncurses_init_pair(6, NCURSES_COLOR_MAGENTA, NCURSES_COLOR_BLACK);
    ncurses_init_pair(7, NCURSES_COLOR_WHITE, NCURSES_COLOR_BLACK);
    ncurses_init_pair(8, NCURSES_COLOR_BLACK, NCURSES_COLOR_BLACK);
    ncurses_color_set(8);
    }

  
  $this->new_window('main', 0,0,0,0);

  }

  function has_callback ($name) {
  $function = $this->callbacks[$name];
    if (function_exists($function) ) {
    return true;
    }
  return false;
  }

  function callback ($name) {
  $function = $this->callbacks[$name];
    if (function_exists($function) ) {
    $function();
    }
   
  }

  function bind_key ($key, $func) {

    if (is_array($key) ) {
      foreach ($key as $k) {
      $this->callbacks[$k] = $func;
      }
    }
    else {
    $this->callbacks[$key] = $func;
    }

  }

function refresh () {

/*
$pad = '';
for($y=0;$y<$this->windows['main']['height'];$y++) {
  for($x=0;$x<$this->windows['main']['width'];$x++) {
  $pad .= "#";
  }
  $pad .= "\n";
}
*/

// ncurses_move(-1,-1); // toss cursor out of sight.
ncurses_wrefresh($this->windows['main']['obj']);



  foreach ($this->windows as $name => $ar) {
  if ($name == "main") {continue;} 
  ncurses_wrefresh($this->windows[$name]['obj']); 
  }

}

  function splash_dialog($message, $graphic=1, $color=7) {

$hourglass = <<<TXT
         _.-"""-._
    _.-""         ""-._
  :"-.               .-":
  '"-_"-._       _.-".-"'
    ||T+._"-._.-"_.-"|
    ||:   "-.|.-" : ||
    || .   ' :|  .  ||
    ||  .   '|| .   ||
    ||   ';.:||'    ||
    ||    '::||     ||
    ||      )||     ||
    ||     ':||     ||
    ||   .' :||.    ||
    ||  ' . :||.'   ||
    ||.'-  .:|| -'._||
  .-'": .::::||:. : "'-.
  :"-.'::::::||::'  .-":
   "-."-._"--:"  .-".-"
      "-._"-._.-".-"
          "-.|.-"

TXT;

$skull_big = <<<TXT
                             ...----....
                         ..-:"''         ''"-..
                      .-'                      '-.
                    .'              .     .       '.
                  .'   .          .    .      .    .''.
                .'  .    .       .   .   .     .   . ..:.
              .' .   . .  .       .   .   ..  .   . ....::.
             ..   .   .      .  .    .     .  ..  . ....:IA.
            .:  .   .    .    .  .  .    .. .  .. .. ....:IA.
           .: .   .   ..   .    .     . . .. . ... ....:.:VHA.
           '..  .  .. .   .       .  . .. . .. . .....:.::IHHB.
          .:. .  . .  . .   .  .  . . . ...:.:... .......:HIHMM.
         .:.... .   . ."::"'.. .   .  . .:.:.:II;,. .. ..:IHIMMA
         ':.:..  ..::IHHHHHI::. . .  ...:.::::.,,,. . ....VIMMHM
        .:::I. .AHHHHHHHHHHAI::. .:...,:IIHHHHHHMMMHHL:. . VMMMM
       .:.:V.:IVHHHHHHHMHMHHH::..:" .:HIHHHHHHHHHHHHHMHHA. .VMMM.
       :..V.:IVHHHHHMMHHHHHHHB... . .:VPHHMHHHMMHHHHHHHHHAI.:VMMI
       ::V..:VIHHHHHHMMMHHHHHH. .   .I":IIMHHMMHHHHHHHHHHHAPI:WMM
       ::". .:.HHHHHHHHMMHHHHHI.  . .:..I:MHMMHHHHHHHHHMHV:':H:WM
       :: . :.::IIHHHHHHMMHHHHV  .ABA.:.:IMHMHMMMHMHHHHV:'. .IHWW
       '.  ..:..:.:IHHHHHMMHV" .AVMHMA.:.'VHMMMMHHHHHV:' .  :IHWV
        :.  .:...:".:.:TPP"   .AVMMHMMA.:. "VMMHHHP.:... .. :IVAI
       .:.   '... .:"'   .   ..HMMMHMMMA::. ."VHHI:::....  .:IHW'
       ...  .  . ..:IIPPIH: ..HMMMI.MMMV:I:.  .:ILLH:.. ...:I:IM
     : .   .'"' .:.V". .. .  :HMMM:IMMMI::I. ..:HHIIPPHI::'.P:HM.
     :.  .  .  .. ..:.. .    :AMMM IMMMM..:...:IV":T::I::.".:IHIMA
     'V:.. .. . .. .  .  .   'VMMV..VMMV :....:V:.:..:....::IHHHMH
       "IHH:.II:.. .:. .  . . . " :HB"" . . ..PI:.::.:::..:IHHMMV"
        :IP""HHII:.  .  .    . . .'V:. . . ..:IH:.:.::IHIHHMMMMM"
        :V:. VIMA:I..  .     .  . .. . .  .:.I:I:..:IHHHHMMHHMMM
        :"VI:.VWMA::. .:      .   .. .:. ..:.I::.:IVHHHMMMHMMMMI
        :."VIIHHMMA:.  .   .   .:  .:.. . .:.II:I:AMMMMMMHMMMMMI
        :..VIHIHMMMI...::.,:.,:!"I:!"I!"I!"V:AI:VAMMMMMMHMMMMMM'
        ':.:HIHIMHHA:"!!"I.:AXXXVVXXXXXXXA:."HPHIMMMMHHMHMMMMMV
          V:H:I:MA:W'I :AXXXIXII:IIIISSSSSSXXA.I.VMMMHMHMMMMMM
            'I::IVA ASSSSXSSSSBBSBMBSSSSSSBBMMMBS.VVMMHIMM'"'
             I:: VPAIMSSSSSSSSSBSSSMMBSSSBBMMMMXXI:MMHIMMI
            .I::. "H:XIIXBBMMMMMMMMMMMMMMMMMBXIXXMMPHIIMM'
            :::I.  ':XSSXXIIIIXSSBMBSSXXXIIIXXSMMAMI:.IMM
            :::I:.  .VSSSSSISISISSSBII:ISSSSBMMB:MI:..:MM
            ::.I:.  ':"SSSSSSSISISSXIIXSSSSBMMB:AHI:..MMM.
            ::.I:. . ..:"BBSSSSSSSSSSSSBBBMMMB:AHHI::.HMMI
            :..::.  . ..::":BBBBBSSBBBMMMB:MMMMHHII::IHHMI
            ':.I:... ....:IHHHHHMMMMMMMMMMMMMMMHHIIIIHMMV"
              "V:. ..:...:.IHHHMMMMMMMMMMMMMMMMHHHMHHMHP'
               ':. .:::.:.::III::IHHHHMMMMMHMHMMHHHHM"
                 "::....::.:::..:..::IIIIIHHHHMMMHHMV"
                   "::.::.. .. .  ...:::IIHHMMMMHMV"
                     "V::... . .I::IHHMMV"'
                       '"VHVHHHAHHHHMMV:"'
TXT;

$skull = <<<TXT
|________________________+~___________~_________/ _______________\_|
|_____/ ________________ \____________/ _________/_________________|
|______/________________\______________/___________________________|
|_____________________________________________ ___/\_._____________|
| __ __~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~____)__//\__\___________/|
| () []~~~~~~~~~~~~~~~\HHH/~~~~\HH/~~____/_______________________/:|
|IWWIWWI~~~~~~~~~~~~~~~~~~~~~~~~~~~~(_________________________/*:x:|
|IWWIWWI~_~_~_~_~_~_~_~_~_~_~_~  .--. ~_~/______________/'x'$'*'x'$|
| WW WW ,== __________________ .'    '. ___________/'$'*'x'$'*'x'$'|
| WW WW =='==. ______________ /  ~~~~  \ ______/*'x'$'*'x'$'*'x'$'*|
| JL JL =.'=.'===.__________ ( __    __ ) 'x'$'*'x'$'*'x'$'*'x'$'*'|
|}\(}\(}\:=.'=.'''==. *'x'$ /|<o->  <o->|\ $'*'x'$'*'x'$'*'x'$'*'x'|
|(}\(}\(}\(}=.'=.''''==. ` ( |    ^^    | ) $`*`x`$`*`x`$`*`x`$`*`x|
|\}(\)(\)(\)(=.''=.'''''= _ ) \   __   / / x`$`*`x`$`*`x`$`*`x`$`*`|
|\(}\(}\(}\(}\(=.''=.''' /##\  \_(__)_/ / *`x`$`*`x`$`*`x`$`*`x`$`*|
|(\)(\)(\)(\)(\)\='''== /####) )#\__// ( $`*`x`$`*`x`$`*`x`$`*`x`$`|
|}(\)(\)(\)(\)(\)(\='' /####( |##| |#\  \  `*`x}$`*`x}$`*`x}$`*`x}$|
|(}\(}\(}\(}\(}\(}\(' (#####| |##(_/##\_/\ .  :x:$:*:x:$:*:x:$:*:x:|
|\}(\)(\)(\)(\)(\)(\)( \####(_)###########\ '=   $:*:x:$:*:x:$:*:x:|
|\(}\(}\(}\(}\(}\(}\(}\( \#################) ''==.  x'$'*'x'$'*'x'$|
|(\)(\)(\)(\)(\)(\)(\)(\) \/##############/ ''''''==.  *'x'$'*'x'$'|
|}(\)(\)(\)(\)(\)(\)(\)(\ /############\#/ ='''''''''==.  $'*'x'$'*|
|(}\(}\(}\(}\(}\(}\(}\(} /#############)  ''=.''''''''''==.  x'$'*'|
|\}(\)(\)(\)(\)(\)(\)(\ /#############/ '''''==.'''''''''''==.   'x|
|\(}\(}\(}\(}\(}\(}\(} /#############/ =''''''''=.''''''''''''===. |
|(\)(\)(\)(\)(\)(\)(\ /#############/ \}=.''''''''=.''''''''''''''=|
|}(\)(\)(\)(\)(\)(\) (#############/ }(\)(=.''''''''=.'''''''''''''|
|(}\(}\(}\(}\(}\(}\(} \###########( \(}\(}\(=.''''''''==.''''''''''|
|\}(\)(\)(\)(\)(\)(\)( \###########\ \}(\)(\)(=.'''''''''==.'''''''|
|\(}\(}\(}\(}\(}\(}\(}\ \###########\ (}\(}\(}\(=.''''''''''===.'''|
|(\)(\)(\)(\)(\)(\)(\)(\ \###########\ }(\)(\)(\)(='''''''''''''===|
+------------------------------------------------------------------+
TXT;

$scream = <<<TXT
________________________+~___________~_________/ _______________\___________\_
_____/ ________________ \____________/ _________/_____________________________
______/________________\______________/_______________________________________
_____________________________________________ ___/\_.____________.____________
 __ __~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~____)__//\__\____________\__________/
 () []~~~~~~~~~~~~~~~\HHH/~~~~\HH/~~____/_______________________/___________/:
IWWIWWI~~~~~~~~~~~~~~~~~~~~~~~~~~~~(____________________________________/*:x:
IWWIWWI~_~_~_~_~_~_~_~_~_~_~_~  .--. ~_~/______________/'x'$'*'x'x$*'x'$'*'x'$
 WW WW ,== __________________ .'    '. ___________/'$'*'x'$'*'x'$$''x'$'*'x'$'
 WW WW =='==. ______________ /  ~~~~  \ ______/*'x'$'*'x'$'*'x'$''*x'$'*'x'$'*
 JL JL =.'=.'===.__________ ( __    __ ) 'x'$'*'x'$'*'x'$'*'x'$'**''$'*'x'$'*'
}\(}\(}\:=.'=.'''==. *'x'$ /|<o->  <o->|\ $'*'x'$'*'x'$'*'x'$'*'xx''*'x'$'*'x'
(}\(}\(}\(}=.'=.''''==. ` ( |    ^^    | ) $`*`x`$`*`x`$`*`x`$`*``x$`*`x`$`*`x
\}(\)(\)(\)(=.''=.'''''= _ ) \   __   / / x`$`*`x`$`*`x`$`*`x`$`**``$`*`x`$`*`
\(}\(}\(}\(}\(=.''=.''' /##\  \_(__)_/ / *`x`$`*`x`$`*`x`$`*`x`$``*x`$`*`x`$`*
(\)(\)(\)(\)(\)\='''== /####) )#\__// ( $`*`x`$`*`x`$`*`x`$`*`x`$$``x`$`*`x`$`
}(\)(\)(\)(\)(\)(\='' /####( |##| |#\  \  `*`x}$`*`x}$`*`x}$`*`x}}$*`x}$`*`x}$
(}\(}\(}\(}\(}\(}\(' (#####| |##(_/##\_/\ .  :x:$:*:x:$:*:x:$:*:xx::*:x:$:*:x:
\}(\)(\)(\)(\)(\)(\)( \####(_)###########\ '=   $:*:x:$:*:x:$:*:xx::*:x:$:*:x:
\(}\(}\(}\(}\(}\(}\(}\( \#################) ''==.  x'$'*'x'$'*'x''$*'x'$'*'x'$
(\)(\)(\)(\)(\)(\)(\)(\) \/##############/ ''''''==.  *'x'$'*'x'$  'x'$'*'x'$'
}(\)(\)(\)(\)(\)(\)(\)(\ /############\#/ ='''''''''==.  $'*'x'$'==  $'*'x'$'*
(}\(}\(}\(}\(}\(}\(}\(} /#############)  ''=.''''''''''==.  x'$'*''==.  x'$'*'
\}(\)(\)(\)(\)(\)(\)(\ /#############/ '''''==.'''''''''''==.   ''''''==.   'x
\(}\(}\(}\(}\(}\(}\(} /#############/ =''''''''=.''''''''''''===.''''''''===. 
(\)(\)(\)(\)(\)(\)(\ /#############/ \}=.''''''''=.'''''''''''''=='''''''''''=
}(\)(\)(\)(\)(\)(\) (#############/ }(\)(=.''''''''=.''''''''''''.''''''''''''
(}\(}\(}\(}\(}\(}\(} \###########( \(}\(}\(=.''''''''==.''''''''''''==.'''''''
\}(\)(\)(\)(\)(\)(\)( \###########\ \}(\)(\)(=.'''''''''==.'''''''''''''==.'''
\(}\(}\(}\(}\(}\(}\(}\ \###########\ (}\(}\(}\(=.''''''''''===.''''''''''''===
(\)(\)(\)(\)(\)(\)(\)(\ \###########\ }(\)(\)(\)(='''''''''''''==''''''''''''=
TXT;

if ($graphic == 1) {
$graphic = $scream;
}
elseif ($graphic == 2) {
$graphic = $skull_big;
}
elseif ($graphic == 3) {
$graphic = $hourglass;
}
else {
//$graphic = "";
}

$mid_x = floor($this->windows['main']['width'] / 2) - 22;
$mid_y = floor($this->windows['main']['height'] / 2) - 17;
$mid_x = 1;
$mid_y = 4;
  
$box_width = strlen($message) + 50;
// ncurses_color_set(6);
  // $this->new_window('splash', 10,9,10,10);

$hborder = "";
while (strlen($hborder) <  $box_width+2 ) {
$hborder.= "_";
}

ncurses_color_set($color);
// ncurses_wborder($this->windows['splash']['obj'], 0,0, 0,0, 0,0, 0,0); // border it

  // ncurses_wcolor_set($this->windows['main']['obj'], NCURSES_COLOR_YELLOW);
  // ncurses_attron(NCURSES_A_REVERSE);
  ncurses_mvaddstr($mid_y - 1,$mid_x, substr("  $hborder", 0, strlen($message) +4) );
 ncurses_mvaddstr($mid_y,$mid_x, $this->title_short_str($message, $box_width +2, true));

$i=1;
foreach( explode("\n", $graphic) as $line) {
while (strlen($line) <  $box_width) {
$line .= " ";
}
  ncurses_mvaddstr($mid_y + $i,$mid_x, "| $line |");
$i++;
}



  ncurses_mvaddstr($mid_y + $i,$mid_x, "|$hborder|");
  // ncurses_mvaddstr(11,90, $message);
  // ncurses_mvaddstr(12,90, "_________________________");
  // ncurses_attroff(NCURSES_A_REVERSE);
  // ncurses_mvaddstr(12,90,  `cat ascii2`);
  // ncurses_wcolor_set($this->windows['splash']['obj'], NCURSES_COLOR_BLACK);
  // ncurses_attron(NCURSES_A_REVERSE);
  ncurses_mvaddstr(1, 1, "-");
  ncurses_refresh(); 
ncurses_color_set(5);
  }

  function app_title ($title, $color=1, $underline=true) {

// list ($x_pos, $y_pos) = $this->layout($this->windows['main']['width'] , $height, $x_pos, $y_pos);

    ncurses_color_set($color);
  // ncurses_attron(NCURSES_A_REVERSE);
  // ncurses_mvaddstr($y_pos, $x_pos, $this->title_short_str($title, $this->windows['main']['width'] , true));
  $title = $this->title_short_str($title, $this->windows['main']['width'], $underline, 'app-title', 'right') ;
  ncurses_mvaddstr(0, 0, $title);
  // ncurses_mvwaddstr($this->windows['main']['obj'], 0, 1, $this->title_short_str($title, $this->windows['main']['width'] , true));
  // ncurses_attroff(NCURSES_A_REVERSE);
    ncurses_color_set(5);
  }

  function new_window($name, $width, $height, $x_pos, $y_pos) {

  // $this->window_index[$this->widget_count] = $name;

  $window = ncurses_newwin($height, $width, $y_pos, $x_pos); 
  // ncurses_getmaxyx(&$window, $height, $width);
  // Fatal error: Call-time pass-by-reference has been removed 
  ncurses_getmaxyx($window, $height, $width);
  $this->windows[$name]['obj']    = $window;
  $this->windows[$name]['height'] = $height;
  $this->windows[$name]['width']  = $width ;


if ( !$this->windows[$name]['abs_x']) {
// hmmm
$this->windows[$title]['abs_x'] = 0;
}

  if ($name == 'main') {
  ncurses_border(0,0, 0,0, 0,0, 0,0);
  ncurses_color_set(5);
  $this->windows[$name]['width']--;
  }
  else {
  $this->window_index[] = $name;
  }

  return $window;
  }


function layout ($width, $height, $x_pos=0, $y_pos=0, $padding) {

$nudge_right = $width + $padding; 
$nudge_down = $height ;

if ($this->next['x'] <= $this->windows['main']['width'] ) {
$x_pos = $this->next['x'];
$y_pos = $this->next['y'];
$this->next['x'] += $nudge_right;
$this->next['y'] = $this->next['y'];
// $this->tallest['y'] = $nudge_down;
}
elseif($this->next['x'] > $this->windows['main']['width'] ) {

for($i=0;$i<$this->windows['main']['width']; $i++ ) {
$pad .= "_";
}
$pad = substr($pad, 0, $this->windows['main']['width']);

$this->next['y']--; // because the Information Monitor title has a artificial +1 under it

$x_pos = 0;
$y_pos = $this->next['y'] + $nudge_down;
$this->next['y'] += $nudge_down;
$this->next['x'] = $nudge_right;


  // ncurses_color_set(5);
// ncurses_mvaddstr($this->next['y']-1,0, $pad);
  // ncurses_color_set(5);

}


return array($x_pos, $y_pos);


if (!$x_pos) {
$x_pos = 1;
}
  if ($this->next['x']) {
  $x_pos = $this->next['x'];
  }

if (!$y_pos) {
$y_pos = 1;
}
  if ($this->next['y']) {
  $y_pos = $this->next['y'];
  }


// print "$width, $height -> $x_pos $y_pos\n"; sleep(1);
return array($x_pos, $y_pos);
}


function draw_menu ($title, $width, $height, $options, $x_pos='', $y_pos='', $color=1) {

if ( preg_match("/(\d+)%/", $width, $matches ) ) {
$width = floor($this->windows['main']['width'] * $matches[1] * 0.01);
}

if ( preg_match("/(\d+)%/", $height, $matches ) ) {
$height= floor($this->windows['main']['height'] * $matches[1] * 0.01);
}

$this->windows[$title]['limit']     = sizeof($options) ;
$this->windows[$title]['page_size'] =  $height - 4;
$this->windows[$title]['page_count'] = ceil( sizeof($options) / ($height - 4) );

list ($x_pos, $y_pos) = $this->layout($width, $height, $x_pos, $y_pos, $this->widget_padding);

$window = $this->new_window($title, $width + ( $this->widget_padding * 2) , $height, $x_pos, $y_pos );

$child_window_name = $this->windows[$title]['child'];
$last_window_name = $this->windows[$title]['parent'];
$last_window = $this->windows[$last_window_name]['obj'];

$window_index = $this->widget_count;
$this->widget_count++;

  // ncurses_color_set(3);
// ncurses_wcolor_set($window, NCURSES_COLOR_CYAN);

// eureka ... second call is much faster, no blinking
// ncurses_mvaddstr($y_pos, $x_pos, $this->title_short_str($title, $width, false, 'menu'));

ncurses_wcolor_set($window, $color);
ncurses_mvwaddstr($this->windows[$title]['obj'], 0, 1, $this->title_short_str($title, $width, true, 'default'));
  // ncurses_color_set(5);

ncurses_wcolor_set($window, NCURSES_COLOR_WHITE);
// ncurses_wborder($window, 0,0, 0,0, 0,0, 0,0); // border it
ncurses_wcolor_set($window, NCURSES_COLOR_BLACK);

$b =0;
$selected[$window_index] = $this->windows[$title]['abs_x'];
$selected[$window_index + 1] = $this->windows[$child_window_name]['abs_x'];

for($a=intval($this->windows[$title]['page_start']);$b<$this->windows[$title]['page_size'];$a++){

$out = trim($options[$a]);

  if($this->windows[$title]['abs_x'] == intval($a)   ){ 

  ncurses_wattron($window,NCURSES_A_REVERSE);
  if($this->sel_window == $window_index ) {
  ncurses_wcolor_set($window, NCURSES_COLOR_GREEN);
  }
  else {
  ncurses_wcolor_set($window, NCURSES_COLOR_MAGENTA);
  }
  ncurses_mvwaddstr ($window, 1+$b, 1, short_str($out, $width, true));
  ncurses_wcolor_set($window, NCURSES_COLOR_BLACK);
  ncurses_wattroff($window,NCURSES_A_REVERSE);

if ($window_index > 0 && $last_window) {
ncurses_wcolor_set($last_window, NCURSES_COLOR_MAGENTA);
ncurses_mvwaddstr ($last_window, 1+$b, $this->windows[$last_window_name]['width'] -  ( $this->widget_padding * 2) , "---");
ncurses_wcolor_set($last_window, NCURSES_COLOR_BLACK);
}

    $pad = "";
    for($x=0;$x< ( $width- strlen(short_str($out, $width, true))); $x++ ) {
      $pad .= "-";
    }
  ncurses_wcolor_set($window, NCURSES_COLOR_MAGENTA);
      ncurses_mvwaddstr ($window, 1+$b, strlen(short_str($out, $width, true))+1, $pad);

  ncurses_wcolor_set($window, NCURSES_COLOR_BLACK);



  }
  else{
  ncurses_mvwaddstr ($window, 1+$b, 1, short_str($out, $width));

    if (
          ($b > $selected[$window_index + 1] && $b <= $selected[$window_index])
          || ( $b < $selected[$window_index + 1] && $b >= $selected[$window_index])
          ) {
  // connector
    ncurses_wcolor_set($window, NCURSES_COLOR_MAGENTA);
      ncurses_mvwaddstr ($window, 1+$b, $width, "|");
    ncurses_wcolor_set($window, NCURSES_COLOR_BLACK);
    }



  }

$b++;
}


}


function draw_textarea ($title, $width, $height, $text, $x_pos='', $y_pos='', $no_break=false, $color=1 ) {

// $title = "فیلتر شکن $title فیلتر شکن";

if ( preg_match("/(\d+)%/", $width, $matches ) ) {
$width = floor($this->windows['main']['width'] * $matches[1] * 0.01);
}

if ( preg_match("/(\d+)%/", $height, $matches ) ) {
$height= floor($this->windows['main']['height'] * $matches[1] * 0.01);
}

list ($x_pos, $y_pos) = $this->layout($width, $height, $x_pos, $y_pos, 0);


$window = $this->new_window($title, $width + ( $this->widget_padding * 2) , $height, $x_pos, $y_pos );
// $window = $this->new_window($title, $width, $height, $x_pos, $y_pos );
// $last_window = $this->window_index[$this->widget_count -1];

$last_window_name = $this->windows[$title]['parent'];
$last_window = $this->windows[$last_window_name]['obj'];

$window_index = $this->widget_count;
$this->widget_count++;


  ncurses_wcolor_set($window, $color);

// if ($window_index == 3) {
# if the window takes up most of the screen add a red border
// if ( $this->windows['main']['width']  - $width  < $width ) {
if (0 && $this->next['y'] > 0 ) {
  // ncurses_color_set($color);
// ncurses_mvaddstr($y_pos , $x_pos, $this->title_short_str($title, $width, true, 'default'));
ncurses_mvwaddstr($window, 0, 1, $this->title_short_str($title, $width, true, 'default'));
}
else {
  // ncurses_color_set($color);
// ncurses_mvaddstr($y_pos , $x_pos, $this->title_short_str($title, $width, false, 'default'));
ncurses_mvwaddstr($this->windows[$title]['obj'], 0, 0, $this->title_short_str($title, $width, true, 'default'));
}

if ($no_break) {
  ncurses_wcolor_set($window, $this->random_color() );
}
else {
$text = line_break($text, $width);
}
$x =1;
$text = ltrim($text);
$lines = explode("\n", $text) ;

$this->windows[$title]['limit'] = sizeof($lines);
$this->windows[$title]['page_size'] =  $height - 4;
$this->windows[$title]['page_count'] = ceil( sizeof($options) / ($height - 4) );



for($i=intval($this->windows[$title]['page_start']);$x<$this->windows[$title]['page_size'];$i++){
$line = $lines[$i];


  ncurses_color_set($color);
  ncurses_wcolor_set($window, $color);
  // really $x is the y coordinate
    ncurses_mvwaddstr ($window, $x, 0,  "|");
   // ncurses_mvaddch ( $window, $x, 1,  NCURSES_ACS_VLINE);

/*
  ncurses_color_set($color);
  ncurses_move($y_pos + $x, $x_pos );
  // ncurses_vline ( NCURSES_ACS_VLINE, $height -4 );
   ncurses_addch (   NCURSES_ACS_VLINE);
  ncurses_move($y_pos, $x_pos);
*/



  // works ncurses_wvline ( $window , NCURSES_ACS_VLINE, 20 );
  // ncurses_mvvline ( $x, 0, NCURSES_ACS_VLINE);
  // ncurses_mvvline ( int $y , int $x , int $attrchar , int $n )
  // ncurses_mvwaddstr ($window, $width, 0,  "|");
  ncurses_wcolor_set($window, 7);
  ncurses_color_set(7);




if (  $this->get_active_window() == $title && $this->windows[$title]['abs_x'] == $i ) {
if ( !strlen($line)) { $line = " "; }
  ncurses_wcolor_set($window, NCURSES_COLOR_GREEN);
  ncurses_wattron($window,NCURSES_A_REVERSE);
  ncurses_mvwaddstr ($window, $x+1, 1,  $line );
  ncurses_wattroff($window,NCURSES_A_REVERSE);
  ncurses_wcolor_set($window, NCURSES_COLOR_BLACK);
}
elseif ( preg_match("/https?/", $line) ) {

  ncurses_wcolor_set($window, NCURSES_COLOR_RED);
  ncurses_mvwaddstr ($window, $x+1, 1,  $line );
  ncurses_wcolor_set($window, NCURSES_COLOR_BLACK);


}
else {
  ncurses_mvwaddstr ($window, $x+1, 1,  $line );
}

$x++;
}



}

function random_color() {

$colors = array(
NCURSES_COLOR_RED,
NCURSES_COLOR_GREEN,
NCURSES_COLOR_YELLOW,
// NCURSES_COLOR_BLUE,
NCURSES_COLOR_CYAN,
NCURSES_COLOR_MAGENTA,
NCURSES_COLOR_BLACK, // BLACK and WHITE are actually flipped
// NCURSES_COLOR_WHITE, 
);

return $colors[rand(0, sizeof($colors)-1) ]; 
}

function get_active_window() {

if (! $this->active_window ) {

$this->active_window = $this->window_index[$this->sel_window];
}

return $this->active_window;

}

function move_right () {

$this->sel_window++;
  if ($this->sel_window > $this->widget_count) {
  $this->sel_window = 0;
  }

$this->active_window = $this->window_index[$this->sel_window];
}


function move_left () {

$this->sel_window--;

  if ($this->sel_window < 0) {
  $this->sel_window = 0;
  }

$this->active_window = $this->window_index[$this->sel_window];

}

function move_up () {

// exp
$window_name = $this->get_active_window();
$child_window_name = $this->windows[$window_name]['child'];
  if ( $this->windows[$window_name]['abs_x'] <= 0 ) {
  $this->windows[$window_name]['abs_x'] = 0;
  $this->windows[$window_name]['rel_x'] = 0;
  $this->windows[$window_name]['page']  = 0;
  $this->windows[$window_name]['page_start'] =0;
  return;
  }
  elseif ( $this->windows[$window_name]['rel_x']  == 0 ) {
  $this->windows[$window_name]['page_start']--;
$this->windows[$window_name]['abs_x']--;
  // child menu set to first element
  $this->windows[$child_window_name]['abs_x'] = 0;
  $this->windows[$child_window_name]['rel_x'] = 0;

  }
  else {
  // scroll the widget 1 item up
  $this->windows[$window_name]['abs_x']--;
  $this->windows[$window_name]['rel_x']--;
  // child menu set to first element
  $this->windows[$child_window_name]['abs_x'] = 0;
  $this->windows[$child_window_name]['rel_x'] = 0;
  }
//

return;
}



function move_down ($limit) {

// exp
$window_name = $this->get_active_window();
$child_window_name = $this->windows[$window_name]['child'];
$parent_window_name = $this->windows[$window_name]['parent'];


  if ( $this->windows[$window_name]['abs_x'] >= $this->windows[$window_name]['limit'] ) {
  return $selected;
  }
  elseif ( $this->windows[$window_name]['rel_x'] >= $this->windows[$window_name]['page_size'] - 1) {
  // scroll the widget 1 page down
  // $this->windows[$window_name]['page_start'] = $this->windows[$window_name]['abs_x'];
  $this->windows[$window_name]['page_start']++;
  $this->windows[$window_name]['abs_x']++;
  // child menu set to first element
  $this->windows[$child_window_name]['abs_x'] = 0;
  $this->windows[$child_window_name]['rel_x'] = 0;
  }
  else {
  // scroll the widget 1 item down
  $this->windows[$window_name]['abs_x']++;
  $this->windows[$window_name]['rel_x']++;
  // child menu set to first element
  $this->windows[$child_window_name]['abs_x'] = 0;
  $this->windows[$child_window_name]['rel_x'] = 0;

  }
//
// return $selected;

return;


}

function trigger ($parent_window, $child_window) {
$this->windows[$parent_window]['child'] = $child_window;
$this->windows[$child_window]['parent'] = $parent_window;
// $this->windows[$parent_window]['children'][$child_window] = 'refresh';
}

function title_short_str ($str, $limit, $pad, $style='default', $h_align='left' ) {


$str = substr($str,0,$limit-3);
if ($style == 'default') {
$str = strtoupper("_/ $str \_");
}
elseif($style == 'app-title') {
$str = strtoupper("__--| $str |--__");
}
else {
$str = strtoupper("[ $str ]");
}

if ($pad) {
$padding = "_";
}
else {
$padding = " ";
}
  if ($h_align == "right") {
  while (strlen($str) + strlen($line) < $limit) {
    $line .= $padding;
    }
  $str = $line . $str;
  }
  else {
    while (strlen($str) < $limit) {
    $str .= $padding;
    }
  }

return $str;
}



function end () {
ncurses_end();
}

// end class ncurses
}

?>
