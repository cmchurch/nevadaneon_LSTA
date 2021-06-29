<?PHP
#DESCRIPTION
#TRANSFORM JSON OBJECTS INTO CSV FOR CMS IMPORT

#CREDITS
#CHRISTOPHER M. CHURCH, PHD
#UNIVERSITY OF NEVADA
#LSTA GRANT, 2021
#NORTHERN NEVADA NEON PROJECT

#DATE LAST UPDATED
#06-28-2021

#***********************************
#*************FUNCTIONS*************
#***********************************

function getlatlon($node)
{
  if (!empty($node->attributes->field_topic_location)) {
    $latitude=$node->attributes->field_topic_location[0]->lat;
    $longitude=$node->attributes->field_topic_location[0]->lng;
  }
  else {
    $latitude=NULL;
    $longitude=NULL;
  }
  return [$latitude,$longitude];
}

#***********************************
#***************MAIN****************
#***********************************

echo "\n\nTRANSFORMING JSON OBJECT INTO CSV FOR CMS IMPORT\n";
echo "*************************************************\n\n";
#READ IN THE JSONS FROM CACHE
$input_path = "JSON-OUTPUT/media-nodes.json";
$mediaNodes = json_decode(file_get_contents($input_path));
$input_path = "JSON-OUTPUT/dc-nodes.json";
$dcNodes = json_decode(file_get_contents($input_path));
$input_path = "JSON-OUTPUT/files.json";
$fileNodes = json_decode(file_get_contents($input_path));

#open an a file to output as csv
$output = fopen("CSV-OUTPUT/import.csv", "w");

#set variables
$counter=1; #so we don't run the entire thing while testing
#$mediaUse_taxonomy_uuid="7bb44572-2b5c-4c28-b9f3-423c578455a8"; #media use UUID taxonomy term for service files
$mediaUse_taxonomy_uuid="0db579ad-a810-45bb-acd1-002bf314b50f"; #media use UUID taxonomy term for thumbnail files


#define function for grabbing attributes, using local variables so they reset with each get_called_class

foreach ($dcNodes as $node){


  #get the key attributes and relationships for each dc_node in JSON
  $dcNode_parent=$node->relationships->field_member_of->data;
  #check to see if this is a child node; if it is, skip it
  if (!empty($dcNode_parent)) {continue;}
  #if we haven't skipped the current node then continue grabbing attributes from the JSON
  $dcNode = ['did'      =>    $node->attributes->field_digital_id,
             'uuid'     =>    $node->id,
             'url'      =>    str_replace("http://n2t.net","http://special.library.unlv.edu",$node->attributes->field_archival_resource_key->uri),
             'title'    =>    $node->attributes->title,
             'body'     =>    $node->attributes->body->value,
             'citation' =>    $node->attributes->field_citation->value,
             'lat'      =>    getlatlon($node)[0],
             'lon'      =>    getlatlon($node)[1]

  ];
  echo $dcNode['body']."\n";

  $dcNode_digital_id=$node->attributes->field_digital_id;
  $dcNode_uuid=$node->id; #get the uuid of the current dc node
  $dcNode_url=$node->attributes->field_archival_resource_key->uri; #get the url of the current dc node
  $dcNode_url=str_replace("http://n2t.net","http://special.library.unlv.edu",$dcNode_url); #change the hostname because the UNLV db stores a link to the EZID record
  $dcNode_title=$node->attributes->title;
  $dcNode_body=$node->attributes->body->value;
  $dcNode_citation=$node->attributes->field_citation->value;
  if (!empty($node->attributes->field_topic_location)) {
    $dcNode_latitude=$node->attributes->field_topic_location[0]->lat;
    $dcNode_longitude=$node->attributes->field_topic_location[0]->lng;
  }
  else {
    $dcNode_latitude=NULL;
    $dcNode_longitude=NULL;
  }
  #iterate over mediaNodes for the current dc_node and find the associated files
  foreach ($mediaNodes as $mediaNode)
    {
      $mediaParentID = $mediaNode->relationships->field_media_of->data->id; #this is the UUID of the dc node to which the current media node belongs
      $mediaFileID = $mediaNode->relationships->field_media_image->data->id; #this is the file id of the associated file to the current media node
      $mediaUse = $mediaNode->relationships->field_media_use->data[0]->id; #this is the UUID of the taxonomy term attached to media node SEE SET VARIABLES ABOVE
      if ($mediaParentID==$dcNode_uuid&&$mediaUse==$mediaUse_taxonomy_uuid) {  #it looks like some don't have service files
        foreach ($fileNodes as $fileNode) {
          $fileId = $fileNode->id;
          if ($fileId==$mediaFileID) {
            $dcNode_fileURL = "http://special.library.unlv.edu".$fileNode->attributes->uri->url;
            break;
          }
        }
        break;
      }
    }
  #if (empty($dcNode_parent)) {$dcNode_parent="NULL";} else {$dcNode_parent=$dcNode_parent[0]->id;}

  #build the row of the CSV as an array
  $rowArray=[$dcNode_digital_id,
             $dcNode_uuid,
             $dcNode_title,
             $dcNode_url,
             $dcNode_latitude,
             $dcNode_longitude,
             $dcNode_body,
             $dcNode_fileURL,
             $dcNode_citation];
  #combine row elements into text using a delimiter
  $rowText = implode("|",$rowArray)."\n";

  #write the row to the csv file
  fwrite($output,$rowText);

  #only test on a handful during development
  if ($counter==10) { break;}
  $counter++;

}

#we've finished
fclose($output); #close the output file
echo "END\n";    #let the user know we are done

?>
