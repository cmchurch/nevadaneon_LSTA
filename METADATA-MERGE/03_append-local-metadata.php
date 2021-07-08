<?PHP
/*
DESCRIPTION
 THIS SCRIPT APPENDS LOCAL METADATA TO DATA PULLED FROM UNLV, USING DIGITAL ID (did) for JOIN

CREDITS
 CHRISTOPHER M. CHURCH, PHD
 UNIVERSITY OF NEVADA
 LSTA GRANT, 2021
 NORTHERN NEVADA NEON PROJECT

DATE LAST UPDATED
 07-06-2021
*/


#************************MAIN***************************
print "\nSTART.\n";
$output_path = __DIR__ . "/OUTPUT/import.csv"; #where we will save the CSV

$UNR_metadata = fetchCSV(__DIR__ . "/INPUT/input.csv",'did-unr'); #grab the data from UNR (change header to did-unr for recursive merge, otherwise it nests them if they have the same name)
$UNLV_metadata = fetchCSV(__DIR__ . "/../JSON-API/CSV-OUTPUT/import.csv",'did');             #grab the data from UNLV

#NOTE: MERGE RECURSIVE won't create keys for the NULL values if they don't exist in the second array, so this is handled in the makeCSV function (see below)
$combined = array_merge_recursive($UNLV_metadata,$UNR_metadata);                  #merge array on shared key 'did' vs 'did-unr'


makeCSV($output_path,$combined);  #export the combined array as a CSV
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
      $key = preg_replace("/[^A-Za-z0-9]-/", '', $key);
      $key = substr($key, 0, 20);
      array_push($csvKeys, $key);
    }

  #print_r($csvKeys);

  while (($row = fgetcsv($input_data,0,$separator = "|")) != FALSE) {
      $csvLine=[];
      foreach ($row as $index=>$r) {
          if ($csvKeys[$index]=='lit-unlit') {$r = preg_replace("/,\s+/", ',', $r);} #check to see if it is one of the multifields, and if so, remove the space after comma -> maybe use TAMPER module after this script instead
          $csvLine[$csvKeys[$index]]=$r;    #here is the actual value for each field in CSV
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
  $header_keys = array_keys($finalNodeArray[$initArrayKey]);
  fputcsv($output,$header_keys,'|'); #output headers to first line of CSV file
  foreach ($finalNodeArray as $line) {
    $temp_line = array_fill_keys($header_keys,NULL); #create a blank template of the line with all the keys set to NULL (NOTE: this ensures a well-formed CSV when there are NULLs from the RECURSIVE MERGE in main)
    $line_filledKeysWhereBlank = array_replace($temp_line,$line); #update the temp line of NULLS with values from the current line in the finalNodeArray
    fputcsv($output,$line_filledKeysWhereBlank,'|'); #output to file
  }
  fclose($output); #close the output file
  print "CSV exported to $_output_path\n";
}

 ?>
