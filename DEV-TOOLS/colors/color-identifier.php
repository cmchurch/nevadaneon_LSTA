<?php
#uses the color extractor library
#https://github.com/thephpleague/color-extractor
#load libraries installed with composer

/* ------------------------------------------------INCLUDES------------------------------------------------------------*/
require '/home/chris/vendor/autoload.php';

#load color extractor related tools
use League\ColorExtractor\Color;
use League\ColorExtractor\ColorExtractor;
use League\ColorExtractor\Palette;

/* ------------------------------------------------MAIN----------------------------------------------------------------*/
#get commandline arguments
if (isset($argv[1])) { $grouping = $argv[1];} else {$grouping = NULL;}

if ($grouping == "nogroup") {print "RUNNING WITH GROUPING DISABLED.\n\n";} else { print "RUNNING WITH GROUPING ENABLED.\n\n";}

#get the CSV of color names, groups, hex codes, and rgb values and then build a useable array
$colorNamesCSV = fetchCSV(__DIR__ . "/color-names.csv",'machine-name');    #change to 'color-group' to use the user-created color mappings
$colorNames = getRGB($colorNamesCSV);
printColorsHTML($colorNamesCSV);    #use to create a viewer file for all the colors loaded into the program. to make it easier to see how the computer sees

#the test files we're planning to use
/*
#TEST FILES
$files = [__DIR__ . "/test-images/sutro.png",
          __DIR__ . "/test-images/green.png",
          __DIR__ . "/test-images/test2.png",
          __DIR__ . "/test-images/good-night.jpg",
          __DIR__ . "/test-images/sacramento.jpg",
          __DIR__ . "/test-images/red_neon.jpg",
          __DIR__ . "/test-images/flamingos.jpg",
          __DIR__ . "/test-images/camperland.jpg",];
*/

/*get the metadata list and image urls*/
$metadata = fetchCSV(__DIR__ . "/../../METADATA-MERGE/OUTPUT/import.csv",'did');    #change to 'color-group' to use the user-created color mappings

foreach ($metadata as $key=>$item) {
  $timeDayTags = explode(',',$item['time-day']);
  if (in_array('night',$timeDayTags)||in_array('dusk',$timeDayTags)) {   # we only want night or dusk images so we can see the neon
    $thumbnails = explode(",",$item['thumbnail']);
    $files[$key] = $thumbnails;
 }
}


#iterate over files and for each one get the main colors
$testCount = 0;
$fileCount = count($files);
$index = 0;
foreach ($files as $key=>$file) {
  $index++;
  print $key . "\n*************  " . number_format($index/$fileCount*100,2) . "% completed. \n";
  $html[$key] = "<h1>$key</h1>"; #build html so we can verify the results
  foreach ($file as $url) {
    print $url . "\n";
    $colors = getColors($url,$colorNames,$colorNamesCSV,$grouping );
    if ($colors=="skip") {print "\nFILE SKIPPED. Attempting next file.\n"; continue;}
    if ($colors=="quit") {print "\nSaving file and exiting.\n\n"; writeFiles($html,$colorTags); exit;}
    foreach ($colors as $color=>$count) {
      $colorTags[$key][$color] = TRUE; #we'll trace the existence of a color for each record by using a BOOL and then grabbing the key from the associative array
    }
    $colorsString = join(array_keys($colors),","); #get the colors as a string for each photo
    print "    COLORS: $colorsString \n";
    $html[$key] = $html[$key] . "<p><img src='$url'><br><span class='colors'>$colorsString</span></p>"; #continue building html to verify results by hand
  }
  #if ($testCount > 2) {break;}
  $testCount++;
}

writeFiles($html,$colorTags);
#program's finished
print "\n***END***\n";
exit;

/* ------------------------------------------------FUNCTIONS-------------------------------------------------------------*/

function writeFiles($_html,$_colorTags) {
  toHTML($_html); #export an HTML file that we can use to verify results
  toCSV($_colorTags); #export the CSV that we can merge with the metadata table
}

function toCSV($colorTags) {
  $header_keys = ["did","color-tags"];
  $output = fopen("color-tags.csv","w");
  fputcsv($output,$header_keys,'|'); #output headers to first line of CSV file
  foreach ($colorTags as $did=>$tags) {
    $line = [$did,join(array_keys($tags),",")];
    fputcsv($output,$line,'|'); #output headers to first line of CSV file
  }
  fclose($output);
}


function toHTML($_html) {
  $html_output = fopen("results.html","w");
  $opening_tags = "
  <html>
  <head>
  </head>
  <body>";
  fwrite($html_output,$opening_tags);
  foreach ($_html as $item) {
      fwrite($html_output,$item);
  }
  $closing_tags="</body></html>";
  fwrite($html_output,$closing_tags);
  fclose($html_output);

}

function getColors($file, $_colorNames,$_colorNamesCSV,$_grouping) {
#This function gets the colors from the image, turns them into HEX then rgb values, and then calls getcolorname, which compares them to the reference list of colors and groups using l2distance (Euclidian)
  $success = FALSE;
  $fail = FALSE;
  while ($success!=TRUE&&$fail!=TRUE)
   try {
     @$palette = Palette::fromFilename($file); #get the palette of all present colors by pixel stored as integers (@symbol hides warning since we have an exception handler)
     $success = TRUE;
   }
     catch (Exception $ex) {
       echo "\nCould not access current URL\n";
       $input = readline('Would you like to try again, skip, or quit and save (T/S/Q)? ');
       if ($input=='S'||$input=='s') {$fail = TRUE; echo "\nCURRENT VALUE FAILED!\n"; return "skip";}
       if ($input=='Q'||$input=='q') {$fail = TRUE; echo "\nCURRENT VALUE FAILED!\n"; return "quit";}
  }
  $colorNameCount = []; #init a blank array for counting the colors
  $arrayToReturn = [];  #init a final array we'll return once the function's done

  #go over each pixel in the palette, convert the integer value to HEX and then get the color name for each pixel
  foreach($palette as $color => $count) {
     #colors are represented by integers, so need to convert to HEX and then RGB
     $hex = Color::fromIntToHex($color);
     $name = getColorName($hex,$_colorNames);
     if ($_grouping == "nogroup") { $colorIndex = $name;} else {$colorIndex = $_colorNamesCSV[$name]['color-group']; }
     if (!isset($colorNameCount[$colorIndex])) {$colorNameCount[$colorIndex]=1;} else {$colorNameCount[$colorIndex]++;} #store in the associative array a count for how many of each color we've encountered
  }

  arsort($colorNameCount); #sort our count of the colors in descending order
  $iterate_count = 0; #init a count, because we only want the top colors
  $avoid = ["black","brown","gray","white",""]; #we want to avoid drab colors because they aren't lit neon!

  foreach ($colorNameCount as $key=>$value) {   #iterate over all the colors we've seen by their name
    if ($_grouping == "nogroup") {
      $check = in_array($_colorNamesCSV[$key]['color-group'],$avoid);
    }
    else {
      $check = in_array($key,$avoid);
    }
    if (!$check) {               #make sure it's not a color we want to avoid
    $arrayToReturn[$key] = $value;
      $iterate_count++;
      if ($iterate_count>3) {break;} #just get the top three colors
    }

  }
  return $arrayToReturn;
}

function getColorName($_hex,$_colorNames) {
  $distances = array();
  $val = htmlToRGB($_hex);
  foreach ($_colorNames as $name => $c) {
      $distances[$name] = L2distance($c, $val);
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

function htmlToRGB($color)
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

function L2distance($color1, $color2) {
    return sqrt(pow($color1[0] - $color2[0], 2) +
        pow($color1[1] - $color2[1], 2) +
        pow($color1[2] - $color2[2], 2));
}

function printColorsHTML($colors) {
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
