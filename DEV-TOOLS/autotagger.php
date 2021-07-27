<?PHP
/*SCRIPT TO PULL OUT KEY WORDS AND THEN POTENTIALLY GENERATE AUTOTAGS*/



$UNLV_metadata = fetchCSV(__DIR__ . "/../METADATA-MERGE/OUTPUT/import.csv",'did');
$stopwords = getStopwords(__DIR__ . "/stopwords.txt");
$freq_list = [];

$pattern = "/[^a-z\s]/";
foreach ($UNLV_metadata as $row) {
  $id = $row['did'];
  $id_prefix = substr($id,0,3);
  if ($id_prefix!='neo') {continue;}
  $title = $row['title'];
  $body = $row['unr-desc'];
  $string = $title ." ". $body;
  $string = strtolower(strip_tags(str_replace('<', ' <',$string)));
  $string = preg_replace($pattern," ",$string);
  $string = preg_replace("/\s+/"," ",$string);
  $tokens = explode(" ",$string);
  foreach ($tokens as $token) {
    if (in_array($token, $stopwords)) {continue;}
    if (isset($freq_list[$token])) {$freq_list[$token]++;}
    else {$freq_list[$token]=1;}
  }

asort($freq_list,);
print_r($freq_list);

}

#print_r($UNLV_metadata);

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

function getStopwords($input_path) {
  $file = fopen($input_path,"r");
  $text = fread($file,filesize($input_path));
  return explode("\n",$text);
}

?>
