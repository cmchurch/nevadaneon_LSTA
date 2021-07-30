<?php
#uses the color extractor library
#https://github.com/thephpleague/color-extractor
#load libraries installed with composer
require '/home/chris/vendor/autoload.php';

use League\ColorExtractor\Color;
use League\ColorExtractor\ColorExtractor;
use League\ColorExtractor\Palette;


$colorNamesCSV = fetchCSV(__DIR__ . "/color-names.csv",'machine-name');    #change to 'color-group' to use the user-created color mappings
$colorNames = getRGB($colorNamesCSV);
printColorsCSV($colorNamesCSV);    #use to create a viewer file for all the colors loaded into the program


$files = [__DIR__ . "/test-images/sutro.png",
          __DIR__ . "/test-images/green.png",
          __DIR__ . "/test-images/test2.png",
          __DIR__ . "/test-images/good-night.jpg",
          __DIR__ . "/test-images/sacramento.jpg"];

foreach ($files as $file) {
  print "\n" . $file . "\n";
  getColors($file,$colorNames);
}


print "\n***END***\n";

/*FUNCTIONS-*/
function getColors($file, $_colorNames) {

  $palette = Palette::fromFilename($file);

  $colorNameCount = [];

  // $palette is an iterator on colors sorted by pixel count
  foreach($palette as $color => $count) {
      // colors are represented by integers
     $hex = Color::fromIntToHex($color); #, ': ', $count, "\n";
     $name = getcolorname($hex,$_colorNames);
     if (!isset($colorNameCount[$name])) {$colorNameCount[$name]=1;} else {$colorNameCount[$name]++;}
     #echo $hex, " ", $color,"\n";
  }

  // it offers some helpers too
  $topFive = $palette->getMostUsedColors(5);

  $colorCount = count($palette);


  arsort($colorNameCount);
  $count = 0;
  $avoid = ["black","brown","gray","white",""];
  foreach ($colorNameCount as $key=>$value) {
    if (!in_array($key,$avoid)) {
      print $key . " " . $value ."\n";
      $count++;
      if ($count>5) {break;}
    }
  }
}


function getcolorname($_hex,$_colorNames) {
  $distances = array();
  $val = html2rgb($_hex);
  foreach ($_colorNames as $name => $c) {
      $distances[$name] = distancel2($c, $val);
  }

  $mincolor = "";
  $minval = pow(2, 30); /*big value*/
  foreach ($distances as $k => $v) {
      if ($v < $minval) {
          $minval = $v;
          $mincolor = $k;
      }
  }
  return $mincolor;
}


function fetchCSV($input_path,$_UID_KEY) {
/*This function fetches as CSV at the $input_path, stores all the rows in an associative array with the key provided as an argument pulled from each entry*/
  $input_data = fopen($input_path,"r");
  $first_row = fgetcsv($input_data,0,$separator = "|");
  $csvKeys=[];
  $csvLines=[];

  $keys = array_values($first_row);
    foreach ($keys as $index=>$key) {
      if ($key==NULL) { $key = "BLANK".$index;}
      $key = preg_replace("/[^A-Za-z0-9]-/", '', $key);
      $key = substr($key, 0, 20);
      array_push($csvKeys, $key);
    }

  #print_r($csvKeys);

  while (($row = fgetcsv($input_data,0,$separator = "|")) != FALSE) {
      $csvLine=[];
      foreach ($row as $index=>$r) {
          #if ($csvKeys[$index]=='lit-unlit') {$r = preg_replace("/,\s+/", ',', $r);} #NOTE: moving to TAMPER MODULE, check to see if it is one of the multifields, and if so, remove the space after comma -> maybe use TAMPER module after this script instead
          $csvLine[$csvKeys[$index]]=$r;    #here is the actual value for each field in CSV
      }
      $csvLines[$csvLine[$_UID_KEY]]=$csvLine;
  }
  fclose($input_data);
  return$csvLines;
}

function getRGB($_colorNamesCSV) {
  $_colorNames = [];
  foreach ($_colorNamesCSV as $key=>$item){
    $_colorNames[$key] = [$item['r'],$item['g'],$item['b']];
  }
  return $_colorNames;
}

function html2rgb($color)
{
    if ($color[0] == '#')
        $color = substr($color, 1);

    if (strlen($color) == 6)
        list($r, $g, $b) = array($color[0].$color[1],
                                 $color[2].$color[3],
                                 $color[4].$color[5]);
    elseif (strlen($color) == 3)
        list($r, $g, $b) = array($color[0].$color[0],
            $color[1].$color[1], $color[2].$color[2]);
    else
        return false;

    $r = hexdec($r); $g = hexdec($g); $b = hexdec($b);

    return array($r, $g, $b);
}

function distancel2(array $color1, array $color2) {
    return sqrt(pow($color1[0] - $color2[0], 2) +
        pow($color1[1] - $color2[1], 2) +
        pow($color1[2] - $color2[2], 2));
}

function printColorsCSV($colors) {
  $html_output = fopen("color-viewer.html","w");
    $opening_tags = "
    <html>
    <head>
    </head>
    <body>";
    fwrite($html_output,$opening_tags);
    foreach ($colors as $color) {
        $hex=$color['hex'];
        $colorName=$color['machine-name'];
        $colorGroup=$color['color-group'];
        $string = "<h1 style='color:$hex'>$colorName ($colorGroup)</h1>";
        fwrite($html_output,$string);
        }
        $closing_tags="</body></html>";
        fwrite($html_output,$closing_tags);
        fclose($html_output);
}
?>
