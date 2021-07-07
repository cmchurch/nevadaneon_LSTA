<?PHP
/*
DESCRIPTION
 THIS SCRIPT GETS THE LAT LON FROM GAZETTEER FOR ADDRESSES WHERE AVAILABLE

CREDITS
 CHRISTOPHER M. CHURCH, PHD
 UNIVERSITY OF NEVADA
 LSTA GRANT, 2021
 NORTHERN NEVADA NEON PROJECT

DATE LAST UPDATED
 07-07-2021
*/


#************************MAIN***************************
print "\nSTART - GETTING LAT LON.\n";
$output_path = "OUTPUT/output.csv"; #where we will save the CSV

$UNR_metadata = fetchCSV(__DIR__."/INPUT/input.csv",'did'); #grab the csv

foreach ($UNR_metadata as $item) {

  if (isset($item['site-address'])) {
    $address = strip_tags($item['site-address']);
    get_lat_lon($address);
  }
}

#makeCSV($output_path,$combined);  #export the combined array as a CSV
print "END.\n";
#************************MFUNCTIONS**********************

function fetchCSV($input_path,$_UID_KEY) {
/*This function fetches as CSV at the $input_path, stores all the rows in an associative array with the key provided as an argument pulled from each entry*/
  $input_data = fopen($input_path,"r");
  $first_row = fgetcsv($input_data,0,$separator = "|");
  $csvKeys=[];
  $csvLines=[];

  $keys = array_values($first_row);
    foreach ($keys as $index=>$key) {
      if ($key==NULL) { $key = "BLANK".$index;}
      $key = preg_replace("/[^A-Za-z0-9]\-/", '', $key);
      $key = substr($key, 0, 20);
      array_push($csvKeys, $key);
    }

  #print_r($csvKeys);

  while (($row = fgetcsv($input_data,0,$separator = "|")) != FALSE) {
      $csvLine=[];
      foreach ($row as $index=>$r) {
          $csvLine[$csvKeys[$index]]=$r;
      }
      $csvLines[$csvLine[$_UID_KEY]]=$csvLine;
  }
  fclose($input_data);
  return$csvLines;
}

function makeCSV($_output_path,$finalNodeArray) {
#This function exports the provided array as a CSV to the output path
  $output = fopen($_output_path, "w");  #open an a file to output as csv
  $initArrayKey=array_key_first($finalNodeArray);
  fputcsv($output,array_keys($finalNodeArray[$initArrayKey]),'|'); #output headers to first line of CSV file
  foreach ($finalNodeArray as $line) {
    fputcsv($output,$line,'|');
  }
  fclose($output); #close the output file
  print "CSV exported to $_output_path\n";
}

function get_lat_lon($_address) {
  $url = "http://maps.google.com/maps/api/geocode/json?address=".urlencode($_address);

  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $responseJson = curl_exec($ch);
  curl_close($ch);

  $response = json_decode($responseJson);

  if ($response->status == 'OK') {
      $latitude = $response->results[0]->geometry->location->lat;
      $longitude = $response->results[0]->geometry->location->lng;

      echo 'Latitude: ' . $latitude;
      echo '<br />';
      echo 'Longitude: ' . $longitude;
  } else {
      echo $response->status;
      var_dump($response);
  }
}

 ?>
