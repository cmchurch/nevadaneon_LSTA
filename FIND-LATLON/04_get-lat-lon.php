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
$output_path = __DIR__ . "/OUTPUT/output.csv"; #where we will save the CSV

$UNR_metadata = fetchCSV(__DIR__."/INPUT/input.csv",'did'); #grab the csv
$count = 0;
foreach ($UNR_metadata as $item) {

  if (!empty($item['site-address'])&&empty($item['lat-lon'])) {    #only call GOOGLE MAP API if this row has an address but does not yet have a lat-lon pair
    $did = $item['did'];                                           #grab the 'did' of the item to update the main associative array
    $address = strip_tags($item['site-address']);                  #get address and strip out HTML to pass to API endpoint
    $UNR_metadata[$did]['lat-lon'] = get_lat_lon($address);        #get geocoded lat-lon and store them in the main associative array
  }
}

makeCSV($output_path,$UNR_metadata);  #export the combined array as a CSV
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
#this function calls the GOOGLE API to grab the latitude and longitude for each row
  $api_key = trim(file_get_contents(__DIR__."/API-KEY/api-key.txt"));         #get the API key from HD (not shared on Github)
  $url = "https://maps.google.com/maps/api/geocode/json?address=".urlencode($_address)."&key=".$api_key; #build API endpoint call
  $responseJson = file_get_contents($url); #get geocode resutls from endpoint as JSON
  $response = json_decode($responseJson);  #decode JSON to use

  if ($response->status == 'OK') {         #if we got a 200 OK from endpoint, store the results; IF NOT, return NULL
      $latitude = $response->results[0]->geometry->location->lat;
      $longitude = $response->results[0]->geometry->location->lng;
      return $latitude.", ".$longitude;
  } else {
      return NULL;
  }

}

 ?>
