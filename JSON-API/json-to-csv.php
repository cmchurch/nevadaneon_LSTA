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

echo "\n\nTRANSFORMING JSON OBJECT INTO CSV FOR CMS IMPORT\n";
echo "*************************************************\n\n";
#READ IN THE JSONS FROM CACHE
$input_path = "JSON-OUTPUT/media-nodes.json";
$mediaNodes = json_decode(file_get_contents($input_path));
$input_path = "JSON-OUTPUT/dc-nodes.json";
$dcNodes = json_decode(file_get_contents($input_path));
$input_path = "JSON-OUTPUT/files.json";
$fileNodes = json_decode(file_get_contents($input_path));

#set variables
$counter=1; #so we don't run the entire thing while testing
#$mediaUse_taxonomy_uuid="7bb44572-2b5c-4c28-b9f3-423c578455a8"; #media use UUID taxonomy term for service files
$mediaUse_taxonomy_uuid="0db579ad-a810-45bb-acd1-002bf314b50f"; #media use UUID taxonomy term for service files

foreach ($dcNodes as $node){
  #get the key attributes and relationships for each dc_node in JSON
  $dcNode_uuid=$node->id;
  $dcNode_title=$node->attributes->title;
  $dcNode_body=$node->attributes->body;
  $dcNode_parent=$node->relationships->field_member_of->data;
  #check to see if this is a child node; if it is, skip it
  if (!empty($dcNode_parent)) {continue;}
  foreach ($mediaNodes as $mediaNode)
    {
      $mediaParentID = $mediaNode->relationships->field_media_of->data->id;
      $mediaFileID = $mediaNode->relationships->field_media_image->data->id;
      $mediaUse = $mediaNode->relationships->field_media_use->data[0]->id;
      if ($mediaParentID==$dcNode_uuid&&$mediaUse==$mediaUse_taxonomy_uuid) {  #it looks like some don't have service files
        foreach ($fileNodes as $fileNode) {
          $fileId = $fileNode->id;
          if ($fileId==$mediaFileID) {
            $dcNode_fileURL = $fileNode->attributes->uri->url;
            break;
          }
        }
        break;
      }
    }
  #if (empty($dcNode_parent)) {$dcNode_parent="NULL";} else {$dcNode_parent=$dcNode_parent[0]->id;}

  #build the row of the CSV as an array
  $rowArray=[$dcNode_uuid,$dcNode_title,$dcNode_fileURL];
  #combine row elements into text using a delimiter
  $rowText = implode(",",$rowArray)."\n";

  #see what we made
  print $rowText;

  #only test on a handful during development
  if ($counter==10) { break;}
  $counter++;

}

#we've finished
echo "END\n";
?>
