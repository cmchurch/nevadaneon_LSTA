<?PHP
/*QUICKLY GENERATE AN HTML PAGE SO I CAN SEE THE IMAGES AND INFO TO FILL IN METADATA*/

$UNLV_metadata = fetchCSV(__DIR__ . "/../JSON-API/CSV-OUTPUT/import.csv",'did');

$html_output = fopen("viewer.html","w");

$opening_tags = "
<head>
<style>
img {max-width:100%;}
</style>
</head>
<body>";
fwrite($html_output,$opening_tags);

foreach ($UNLV_metadata as $row) {
  $id = $row['did'];
  $id_prefix = substr($id,0,3);
  if ($id_prefix!='sky') {continue;}
  $title = $row['title'];
  $body = $row['body'];
  $string = "<div>
                <h1 class='title'>$title</h1>
                <p class='id'>$id</p>
                <p class='body'>$body</p>
                ";
 #print_r($row);
 $urls = explode(",",$row['service']);
 foreach ($urls as $url) {
   $string = $string . "<img src='$url'>";
 }
  $string = $string . "</div>";

  fwrite($html_output,$string);
}



fclose($html_output);
/*-----------------*/
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

?>
