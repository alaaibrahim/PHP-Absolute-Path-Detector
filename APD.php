<?php
function &getPHPFiles($dir) {
    $Directory = new RecursiveDirectoryIterator($dir);
    $Iterator = new RecursiveIteratorIterator($Directory);
    $Regex = new RegexIterator($Iterator, '/^.+\.(php|inc)$/i', RecursiveRegexIterator::GET_MATCH);
    $files = array();
    foreach ($Regex as $file) {
        $files[] = $file[0];
    }
    return $files;
}

if (!isset($argv[1])) {
    echo "Usage: ${argv[0]} path/to/dir".PHP_EOL;
    exit;
}
$files_to_parse = getPHPFiles($argv[1]);
foreach($files_to_parse as $file) {
  //echo "Parsing $file ....".PHP_EOL;
  ParsePHPFile($file);
}



function ParsePHPFile($file,$prepend_out='') {
  $contents = file_get_contents($file);
  $contents = strip_comments($contents);
  if (preg_match_all("/<\\?(php)?(.*?)(\\?>|$$)/s",$contents,$matchs)) {
    foreach($matchs[2] as $match) {
      $match = strip_comments($match);
      $results = lookForPaths($match);
      if ($results) {
        for ($i = 0; $i < count($results[0]);$i ++) {
          $file_param = get_file_path($results[1][$i],$results[2][$i]);
          if (is_absolute_file($file_param)) {
            echo $prepend_out."$file : " . trim($results[0][$i]).PHP_EOL;
          }
        }
      }
    }
  }
}


function strip_comments($content) {
  $content = preg_replace('|[^:]//.*|','',$content);
  $content = preg_replace('|/\\*.*?\\*/|s','',$content);
  return $content;
}
function is_absolute_file($file) {
  if (preg_match('/^(\'|\")\\//',trim($file),$matchs)) {
    return true;
  }
}

function get_file_path($func,$params) {
  // Path is always the first Param
  preg_match("/^(\\(|\\s)*(.*?)(,|;|$|\\))/",$params,$matchs);
  return $matchs[2];
}
function lookForPaths($block) {
  if (preg_match_all("/(fopen|file_get_contents|file_put_contents|include|include_once|require|require_once)((\s|\\().*?);/s",$block,$matchs)) {
    return $matchs;
  }
  return false;
}
