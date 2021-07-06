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
while (($row = fgetcsv($input_data,0,$separator = "|")) != FALSE) {
  var_dump($row);
}

fclose($input_data);
 ?>
