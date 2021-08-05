<?PHP
/*
DESCRIPTION
 THIS SCRIPT PROCESSES THE METADATA CONTAINED IN THE DESCRIPTION FIELD FOR THE ITEMS IN THE PHO PREFIX (YESCO COLLECTION), BUILDING A METADATA TABLE FOR 03_append-local-metadata
 IT SKIPS ALL CHILDREN NODES AND ONLY DOES THE PARENTS
 IT PROCESSES THE DATE RANGES INTO A COMMA SEPARATED RANGE

CREDITS
 CHRISTOPHER M. CHURCH, PHD
 UNIVERSITY OF NEVADA
 LSTA GRANT, 2021
 NORTHERN NEVADA NEON PROJECT

DATE LAST UPDATED
 07-22-2021
*/

#-----------------------------------------BEGIN MAIN--------------------------------------------

#SET PATHS
$input_path = __DIR__ . "/../JSON-API/JSON-OUTPUT/dc-nodes.json";
$output_path = __DIR__ . "/OUTPUT/pho-meta.csv";

$crawl_url = "http://special.library.unlv.edu/jsonapi/node/dc_object?include=field_subject&filter[nnn][condition][path]=field_digital_id&filter[nnn][condition][operator]=STARTS_WITH&filter[nnn][condition][value]=";


#LOAD DC NODES FROM JSON
$dcNodes = json_decode(file_get_contents($input_path));
$rows = []; #initalize empty row array


#ITERATE OVER THE DC NODES AND PULL THE RELEVANT INFO OUT OF THE DESCRIPTION FIELD
foreach ($dcNodes as $index=>$node) {
  $id = $node->attributes->field_digital_id;
  $id_prefix = substr($id,0,3);                         #get prefix to check if we are on a 'neo' node
  $isHyphenated = preg_match("/\-/",$id);               #get T/F BOOL on whether id is hyphenated and thus a child
  if ($id_prefix!='pho'||$isHyphenated==1) {continue;}  #if we're not on a parent 'neo' node, then skip ahead

  #GET METADATA
  $row = initRow();                                     #initialize a blank row to be populated with Metadata
  $row['did-unr'] = $id;                                #set the did (will later be used for the metadata join in 03_append-local-metadata.php)
  $body = getBody($node);                               #get the unprocessed metadata in the description of the dc node
  $row['unr-desc']=$body;                               #we plan to overwrite this below, but we have a fallback if the REGEX match fails
  $row['date-img']=$node->attributes->field_edtf_date[0];
  $url=$crawl_url.$id;
  print "Getting JSON API data for $id    :";
  $data=file_get_contents($url); #GET the JSON API results from the ENDPOINT AS A JSON OBJECT
  $jsonObj=json_decode($data);
  $subjectObj = $jsonObj->included;
  $subjects = [];
  foreach ($subjectObj as $item) {
    array_push($subjects,$item->attributes->name);
  }
  $row['subj-fast']= join($subjects,",");
  print $row['subj-fast']."\n";

  /*See if it contains the keywords lit or unlit, day or night*/
  $litTags=[];                                          #initialize a blank array for the tags on whether the sign is lit or unlit
  $timeTags=[];                                         #initialize a blank array for the tags on whether the sign is day or night
  $intExtTags=[];
  $desc = $row['unr-desc'];                             #where we are going to look to see if someone described the sign as lit or unlit (full body of metadata would be too much, grabbing "day" from names and such)
  #DO REGEX MATCHES AND APPEND RESULTS TO RELEVANT ARRAY
  if (preg_match("/(?<=[^n])[l|L]it/",$desc)) {array_push($litTags,"lit");}   #match "lit" only if not preceded by "n" (which would be 'unlit')
  if (preg_match("/[u|U]nlit/",$desc)) {array_push($litTags,"unlit");}
  if (preg_match("/[D|d]ay/", $desc)) {array_push($timeTags,'day');}
  if (preg_match("/[N|n]ight/",$desc)) {array_push($timeTags,'night');}
  if (preg_match("/[D|d]awn/", $desc)) {array_push($timeTags,'dawn');}
  if (preg_match("/[D|d]usk/",$desc)) {array_push($timeTags,'dusk');}
  if (preg_match("/[E|e]xterior/",$desc)) {array_push($intExtTags,'exterior');}
  if (preg_match("/[I|i]nterior/",$desc)) {array_push($intExtTags,'interior');}
  #UPDATE ROW INFO WITH APPROPRIATE TAGS
  $row['lit-unlit']=join($litTags,",");
  $row['time-day']=join($timeTags,",");
  $row['int-ext']=join($intExtTags,",");

  /*clean up the date data*/
  $date_pattern = "/\d\d\d\d/";                         #we only want the year, so #### (4 numbers) is the pattern
  preg_match_all($date_pattern,$row['date-img'],$match); #get all years
  if(isset($match[0])) {
    $dates=$match[0];
    $length=sizeof($dates);
    if ($length>1) {
      $dates = range($dates[0],$dates[$length-1], $step=1); #build range between start and end date
    }
    $row['date-img']=join($dates,",");

  }

  #ADD ROW TO THE ARRAY OF ROWS
  $rows[$id]=$row;                                    #we assign an index by id to ensure that each row is unique per Digital ID
}

#OUTPUT THE RESULTS
makeCSV($output_path,$rows);

#-----------------------------------------END OF MAIN--------------------------------------------


#------------------------------------------FUNCTIONS---------------------------------------------
/*FUNCTIONS*/
/*---------*/
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
#----------------------------------------END FUNCTIONS------------------------------------------

?>
