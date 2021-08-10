<?php
/*
DESCRIPTION
https://www.oclc.org/developer/develop/web-services/fast-api/assign-fast.en.html

CREDITS
 CHRISTOPHER M. CHURCH, PHD
 UNIVERSITY OF NEVADA
 LSTA GRANT, 2021
 NORTHERN NEVADA NEON PROJECT

DATE LAST UPDATED
 08-05-2021
*/

#-----------------------------------------BEGIN MAIN--------------------------------------------
$metadata = fetchCSV(__DIR__ . "/../../METADATA-MERGE/OUTPUT/pho-meta.csv",'did-unr');
$output_path = __DIR__ . "/OUTPUT/fixed-fast_pho.csv";

$data = getFAST($metadata);

print_r($data);

#OUTPUT THE RESULTS
#makeCSV($output_path,$data);

#-----------------------------------------END OF MAIN--------------------------------------------


#------------------------------------------FUNCTIONS---------------------------------------------
/*FUNCTIONS*/
/*---------*/
function getFAST($_metadata) {
  $possibleSubjects = [];
  $crawl_url_start = "http://fast.oclc.org/searchfast/fastsuggest?&query=";
  $crawl_url_tail = "&queryIndex=suggestall&queryReturn=idroot";
  $total = sizeof($_metadata);
  $count = 0;
  foreach ($_metadata as $key=>$item) {
    $count++;
    print number_format($count/$total*100,1) . "% complete.        \r";
    $fastSUBJs = explode(",",$item['subj-fast']);
    foreach ($fastSUBJs as $s)    {
      if (!in_array($s,array_keys($possibleSubjects))) {
        $query = urlencode($s);
        $url = $crawl_url_start . $query . $crawl_url_tail;
        $d=file_get_contents($url);
        $data=json_decode($d);
        try {
          if (isset($data->response->docs[0]->idroot)) { $idroot = $data->response->docs[0]->idroot; }
          else { throw new Exception;}
        }
        catch (Exception $ex) {
          print "\t\t\t\tCOULD NOT GET ID FOR $s                                 \r";
        }
        #print $idroot;
        $FASTurl = "http://id.worldcat.org/fast/" . substr($idroot,3);
        $possibleSubjects[$s] = $FASTurl;
      }
    }
  }
  return $possibleSubjects;
}

function initRow() {
  #create an empty row
  return   [ 'did-unr'        =>    NULL,
             'unr-file-name'  =>    NULL,
             'unr-title'      =>    NULL,
             'unr-desc'       =>    NULL,
             'date-inst'      =>    NULL,
             'site-name'      =>    NULL,
             'site-address'   =>    NULL,
             'lat-lon'        =>    NULL,
             'sign-manf'      =>    NULL,
             'sign-owner'     =>    NULL,
             'lit-unlit'      =>    NULL,
             'time-day'       =>    NULL,
             'subj-fast'      =>    NULL,
             'print-pub'      =>    NULL,
             'format'         =>    NULL,
             'dcmi'           =>    NULL,
             'prov'           =>    NULL,
             'proj-name'      =>    NULL,
             'unr-cit'        =>    NULL,
             'dig-pub'        =>    NULL,
             'contrib'        =>    NULL,
             'int-ext'        =>    NULL,
             'proj-name'      =>    NULL,
             'staff-notes'    =>    NULL,
             'date-img'       =>    NULL,
             'proj-name'      =>    NULL,
             'title-code'     =>    NULL,
             'title-full'     =>    NULL
  ];
}


function getbody($_node)
{
    if (!empty($_node->attributes->body)) {
      return $_node->attributes->body->value;
    }
    else {
      return NULL;
    }
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
#----------------------------------------END FUNCTIONS------------------------------------------

?>
