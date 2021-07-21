<?PHP
/*
DESCRIPTION
 THIS SCRIPT PROCESSES THE METADATA CONTAINED IN THE DESCRIPTION FIELD FOR THE ITEMS IN THE NEO COLLECTION, BUILDING A METADATA TABLE FOR 03_append-local-metadata
 IT SKIPS ALL CHILDREN NODES AND ONLY DOES THE PARENTS

CREDITS
 CHRISTOPHER M. CHURCH, PHD
 UNIVERSITY OF NEVADA
 LSTA GRANT, 2021
 NORTHERN NEVADA NEON PROJECT

DATE LAST UPDATED
 07-21-2021
*/

#-----------------------------------------BEGIN MAIN--------------------------------------------

#SET PATHS
$input_path = __DIR__ . "/../JSON-API/JSON-OUTPUT/dc-nodes.json";
$output_path = __DIR__ . "/OUTPUT/neo-meta.csv";

#LOAD DC NODES FROM JSON
$dcNodes = json_decode(file_get_contents($input_path));
$rows = []; #initalize empty row array

#REGEX PATTERNS TO EXTRACT DATA
$patterns = ["unr-desc"        =>    "/^.+?(?=\<br\>)/",                                      #unr-desc, get first line
             "site-name"       =>    "/(?<=Site name\:).+?(?=\<br\>)/",                       #site-name, get after "Site name:" to next <br> tag
             "site-address"    =>    "/(?<=Site address\:).+?(?=\<br\>)/",                    #Site address, get after "Site address:" to next <br> tag
             "date-inst"       =>    "/(?<=Sign\s\-\sdate of installation\:).+?(?=\<br\>)/",  #date of installation
             "sign-owner"      =>    "/(?<=Sign owner\:).+?(?=\<br\>)/",                      #sign owner, get after "Site owner:" to next <br> tag
             "date-img"        =>    "/(?<=Survey\s\-\sdate completed\:).+?(?=\<br\>)/",      #date of the image, coincides with date of survey
             "creator"         =>    "/(?<=Surveyor\:).+?(?=\<br\>)/"                         #surveyor, coincides with creator of the image
            ];

#ITERATE OVER THE DC NODES AND PULL THE RELEVANT INFO OUT OF THE EDSCRIPTION FIELD
foreach ($dcNodes as $index=>$node) {
  $id = $node->attributes->field_digital_id;
  $id_prefix = substr($id,0,3);                         #get prefix to check if we are on a 'neo' node
  $isHyphenated = preg_match("/\-/",$id);               #get T/F BOOL on whether id is hyphenated and thus a child
  if ($id_prefix!='neo'||$isHyphenated==1) {continue;}  #if we're not on a parent 'neo' node, then skip ahead

  #GET METADATA
  $row = initRow();                                     #initialize a blank row to be populated with Metadata
  $row['did-unr'] = $id;                                #set the did (will later be used for the metadata join in 03_append-local-metadata.php)
  $body = getBody($node);                               #get the unprocessed metadata in the description of the dc node
  $row['unr-desc']=$body;                               #we plan to overwrite this below, but we have a fallback if the REGEX match fails

  #iterate over the patterns to extract the relevant data
  foreach ($patterns as $key=>$pattern) {
      preg_match($pattern,$body,$match);
      if(isset($match[0])) {                            #only try to add the result of the REGEX match if it exists
        $row[$key] = trim($match[0]);                   # add the found REGEX to relevant key, trimming after the match in case someone forgot a space after the colon
      }
  }

  #get rid of the text "Information about the sign is available in the Southern Nevada Neon Survey Data Sheet." as well as the "Two surveys were..." if it exists in the description
  $string="/Information about the.+|Two surveys were.+./";
  $row['unr-desc'] = preg_replace($string,"",$row['unr-desc']);

  /*See if it contains the keywords lit or unlit, day or night*/
  $litTags=[];                                          #initialize a blank array for the tags on whether the sign is lit or unlit
  $timeTags=[];                                         #initialize a blank array for the tags on whether the sign is day or night
  $desc = $row['unr-desc'];                             #where we are going to look to see if someone described the sign as lit or unlit (full body of metadata would be too much, grabbing "day" from names and such)
  #DO REGEX MATCHES AND APPEND RESULTS TO RELEVANT ARRAY
  if (preg_match("/(?<=[^n])[l|L]it/",$desc)) {array_push($litTags,"lit");}   #match "lit" only if not preceded by "n" (which would be 'unlit')
  if (preg_match("/[u|U]nlit/",$desc)) {array_push($litTags,"unlit");}
  if (preg_match("/[D|d]ay/", $desc)) {array_push($timeTags,'day');}
  if (preg_match("/[N|n]ight/",$desc)) {array_push($timeTags,'night');}
  #UPDATE ROW INFO WITH APPROPRIATE TAGS
  $row['lit-unlit']=join($litTags,",");
  $row['time-day']=join($timeTags,",");

  /*clean up the date data - not the cleanest way to do this, but we need a single numeric value and much of the data is a qualitative string*/
  $date_pattern = "/\d\d\d\d/";                         #we only want the year, so #### (4 numbers) is the pattern
  preg_match($date_pattern,$row['date-img'],$match);
  if(isset($match[0])) {$row['date-img']=$match[0];}
  preg_match($date_pattern,$row['date-inst'],$match);
  if(isset($match[0])) {$row['date-inst']=$match[0];}

  #ADD ROW TO THE ARRAY OF ROWS
  array_push($rows,$row);
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
