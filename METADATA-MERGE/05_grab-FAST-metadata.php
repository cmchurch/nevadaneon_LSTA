<?PHP
/*
DESCRIPTION
 THIS SCRIPT GRABS THE FAST METADATA AND GENERATES A CSV OF UNIQUE TERMS WITH THEIR URLS FOR IMPORT INTO DRUPAL

CREDITS
 CHRISTOPHER M. CHURCH, PHD
 UNIVERSITY OF NEVADA
 LSTA GRANT, 2021
 NORTHERN NEVADA NEON PROJECT

DATE LAST UPDATED
 07-09-2021
*/


#************************MAIN***************************
print "\nTHIS SCRIPT GRABS THE FAST METADATA AND GENERATES A CSV OF UNIQUE TERMS WITH THEIR URLS FOR IMPORT INTO DRUPAL\n";
print "\nSTART.\n";
$output_path = __DIR__ . "/OUTPUT/FAST.csv"; #where we will save the CSV
$metadata = fetchCSV(__DIR__ . "/OUTPUT/import.csv",'did'); #grab the final CSV that will be imported to DRUPAL (this is why we are pulling from an OUTPUT directory) digital indentifier (did) necssary for assigning rows to index
$output_array = []; #initialize an empty array to which we can append each FAST item for export as CSV

foreach ($metadata as $item) { #iterate over the rows grabbed from CSV
  $subjFAST_string = $item['subj-fast']; #get just the column entry for subj-fast
  if (empty($subjFAST_string)) {continue;} #if it's blank, skip the rest
  $subjFASTpair_array = explode(",",$subjFAST_string); #explode the array on commas to get a string of "FAST SUBJ -- URL" for each datum contained in the multivalue field
  foreach ($subjFASTpair_array as $subjPair){ #iterate over each ITEM in the array from the previous step
      $subjFAST_array = explode(" -- ",$subjPair); #explode the array on " -- " to get a pair of "FAST SUBJ" and "URL" as array elements for each datum contained in the multivalue field
      $subjFAST['term'] = trim($subjFAST_array[0]); #trim whitespace
      $subjFAST['url'] = trim($subjFAST_array[1]);
      $output_array[$subjFAST['term']] = $subjFAST; #just keep overwriting the duplicates using term name as index, so we don't end up with multiples
  }
}

makeCSV($output_path,$output_array);  #export the combined array as a CSV
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
