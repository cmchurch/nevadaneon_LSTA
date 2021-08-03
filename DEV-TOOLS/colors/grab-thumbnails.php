<?PHP

$metadata = fetchCSV(__DIR__ . "/../../METADATA-MERGE/OUTPUT/import.csv",'did');
$output = __DIR__ . "/tmp/";

$total = count($metadata);
$iterate_count = 0;

foreach ($metadata as $key=>$item) {
  $timeDayTags = explode(',',$item['time-day']);
  $iterate_count++;
  if (in_array('night',$timeDayTags)||in_array('dusk',$timeDayTags)) {   # we only want night or dusk images so we can see the neon

    $urls = explode(",",$item['thumbnail']);
    $did = $item['did'];
    print $did . "     " . number_format($iterate_count/$total*100,2) . "% downloaded. \n";
    foreach ($urls as $index=>$url) {
      $filename = join([$did,$index],"-") . ".jpg";
      $file_location = $output . $filename;
      if (file_exists($file_location)) {continue;} #don't download it again if we already have it
      $file = getFile($url);
      if ($file=="skip")     { continue;}
      elseif ($file=="quit") { exit;    }
      else {

        file_put_contents($file_location, $file);
        print "     $filename output to $output \n";
      }
    }
  }
}

exit;


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

function getFile($url) {
  $success = FALSE;
  $fail = FALSE;
  while ($success!=TRUE&&$fail!=TRUE) {
     $file = file_get_contents($url);
     if ($file===FALSE) {
       echo "\nCould not access current URL\n";
       $input = readline('Would you like to try again, skip, or quit and save (T/S/Q)? ');
       if ($input=='S'||$input=='s') {$fail = TRUE; echo "\nCURRENT VALUE FAILED!\n"; return "skip";}
       if ($input=='Q'||$input=='q') {$fail = TRUE; echo "\nCURRENT VALUE FAILED!\n"; return "quit";}
     } else {  $success = TRUE;}
   }
  return $file;
}
?>
