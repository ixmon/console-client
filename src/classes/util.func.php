<?php

function short_str ($str, $limit, $space_pad=false) {

$limit--;
$limit--;
$limit--;

if (strlen($str) > $limit) {
  $str = substr($str, 0, $limit - 3);
  $str .= "...";
  }

if ($space_pad) {
// $str = substr($str, 0, $limit - 3);

/*
while (strlen($str) < $limit ) {
$str .= " ";
  }
// $str .="|";
*/
}

return $str;
}

function line_break ($str, $limit) {

$length = 0;
$ret = '';
$line = '';
$buffer = array();
$newline = "__newline__";
$str = preg_replace("/\r|\n/", " $newline ", $str);
$words = preg_split("/\s+/", trim($str));

for($i=0;$i<sizeof($words);$i++) {
$word = $words[$i] . " ";

  if ($words[$i] == $newline) {
  $word = '';
  $line .= "\n";
  }

  if ( preg_match("/https?/i", $word) && strlen("$line$word") > $limit)  {
  $line .= "\n";
  $word .= "\n";
  }

$length = strlen($line) + strlen($word);

  
  if (  $length < $limit) {
  $line .= $word;
  }
  else {
  $ret .= "$line\n";
  $line = $word;
  }

}
$ret .= $line;

return $ret;
}



?>
