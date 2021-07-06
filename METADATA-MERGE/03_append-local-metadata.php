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

$input_path = "INPUT/UNR_metadata_2021-07-06.csv";
$input_data = fopen($input_path,"r");
$first_row = fgetcsv($input_data,0,$separator = "|");
$csvKeys=[];
$csvLines=[];

$keys = array_values($first_row);
  foreach ($keys as $index=>$key) {
    if ($key==NULL) { $key = "BLANK".$index;}
    $key = preg_replace("/[^A-Za-z0-9]/", '', $key);
    $key = substr($key, 0, 20);
    array_push($csvKeys, $key);
  }

#print_r($csvKeys);

while (($row = fgetcsv($input_data,0,$separator = "|")) != FALSE) {
    $csvLine=[];
    foreach ($row as $index=>$r) {
        $csvLine[$csvKeys[$index]]=$r;
    }
    array_push($csvLines,$csvLine);
}

#print_r($csvLines);

fclose($input_data);
 ?>
