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

$input_path = "JSON-OUTPUT/media-nodes.json";
$mediaNodes = json_decode(file_get_contents($input_path));
$input_path = "JSON-OUTPUT/dc-nodes.json";
$dcNodes = json_decode(file_get_contents($input_path));
$input_path = "JSON-OUTPUT/files.json";
$fileNodes = json_decode(file_get_contents($input_path));

$counter=1; #so we don't run the entire thing while testing

foreach ($dcNodes as $node){
  $dcNode_uuid=$node->id;
  $dcNode_title=$node->attributes->title;
  $dcNode_body=$node->attributes->body;
  $dcNode_parent=$node->relationships->field_member_of->data;
  if (empty($dcNode_parent)) {$dcNode_parent="NULL";} else {$dcNode_parent=$dcNode_parent[0]->id;}
  $rowArray=[$dcNode_uuid,$dcNode_title,$dcNode_parent];
  $rowText = implode(",",$rowArray)."\n";
  print $rowText;
  if ($counter==10) { break;}
  $counter++;
}

echo "END\n";
?>
