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

function getbody($node)
{
    if (!empty($node->attributes->body)) {
      return $node->attributes->body->value;
    }
    else {
      return NULL;
    }
}

function getcitation($node)
{
    if (!empty($node->attributes->field_citation)) {
      return $node->attributes->field_citation->value;
    }
    else {
      return NULL;
    }
}

function getFile($file_uuid, $fileNodesToSearch) {
  foreach ($fileNodesToSearch as $fileKey=>$fileNode) {
    $fileId = $fileNode->id;
    if ($fileId==$file_uuid) {
      $result = ["http://special.library.unlv.edu".$fileNode->attributes->uri->url,$fileKey];
      return $result;
    }
  }
}

function mediaNodesIterate($_mediaNodes, $_fileNodes, $dcNode,$mediaTypes) {
  foreach ($_mediaNodes as $mediaKey=>$mediaNode)
    {
      #print_r($mediaNode->relationships->field_media_of->data->id);
      $media = ['parent_id'   =>    $mediaNode->relationships->field_media_of->data->id,      #this is the UUID of the dc node to which the current media node belongs
                'file_id'     =>    $mediaNode->relationships->field_media_image->data->id,   #file id of the associated file to the current media node
                'usage'       =>    $mediaNode->relationships->field_media_use->data[0]->id   #this is the UUID of the taxonomy term attached to media node SEE SET VARIABLES ABOVE
      ];

      if ($media['parent_id']==$dcNode['uuid']) {  #it looks like some don't have service files
        $type = $mediaTypes[$media['usage']];
        $fileGrab = getFile($media['file_id'],$_fileNodes);
        $dcNode[$type] = $fileGrab[0];
        $fileKey = $fileGrab[1];
        unset($_mediaNodes[$mediaKey]);
        unset($_fileNodes[$fileKey]);
      }
    }
    return [$_mediaNodes,$_fileNodes,$dcNode];
}

#***********************************
#***************MAIN****************
#***********************************

echo "\n\nTRANSFORMING JSON OBJECT INTO CSV FOR CMS IMPORT\n";
echo "*************************************************\n\nSTART\n";
#READ IN THE JSONS FROM CACHE
$input_path = "JSON-OUTPUT/media-nodes.json";
$mediaNodes = json_decode(file_get_contents($input_path));
$input_path = "JSON-OUTPUT/dc-nodes.json";
$dcNodes = json_decode(file_get_contents($input_path));
$total_dcNodes = count($dcNodes);
$input_path = "JSON-OUTPUT/files.json";
$fileNodes = json_decode(file_get_contents($input_path));

#set variables
$counter=1; #so we don't run the entire thing while testing
$csvLines =[]; #this is where we will store all CSV lines before writing them
$finalNodeArray=[];
$childNodeArray=[];

$mediaTypes =['0db579ad-a810-45bb-acd1-002bf314b50f'  =>  'thumbnail',#media use UUID taxonomy term for thumbnail files
              '7bb44572-2b5c-4c28-b9f3-423c578455a8'  =>  'service',  #media use UUID taxonomy term for service files
              '5111671c-1010-4d63-9a53-5001aefc836a'  =>  'original'  #media use UUID taxonomy term for original files
            ];

#define function for grabbing attributes, using local variables so they reset with each get_called_class

foreach ($dcNodes as $key=>$node){

  #show progress to user
  $progress = $key/$total_dcNodes*100;
  echo "PROGRESS: $progress %     \r";

  #get the information on whether this is a child or a prent node (it's a child if it is a member of another node)
  $dcNode_parent=$node->relationships->field_member_of->data;

  #get all the relevant data we need from the JSON for the current node in the foreach loop
  $dcNode = ['did'      =>    $node->attributes->field_digital_id,
             'uuid'     =>    $node->id,
             'url'      =>    str_replace("http://n2t.net","http://special.library.unlv.edu",$node->attributes->field_archival_resource_key->uri),
             'title'    =>    $node->attributes->title,
             'body'     =>    getbody($node),
             'citation' =>    getcitation($node),
             'lat'      =>    getlatlon($node)[0],
             'lon'      =>    getlatlon($node)[1],
             'thumbnail'=>    NULL,
             'service'  =>    NULL,
             'original' =>    NULL

  ];

  #iterate over mediaNodes for the current dc_node and find the associated files
  $iterateMediaResults = mediaNodesIterate($mediaNodes, $fileNodes, $dcNode,$mediaTypes);
  $mediaNodes = $iterateMediaResults[0];
  $fileNodes = $iterateMediaResults[1];
  $dcNode = $iterateMediaResults[2];


  #check to see if this is a child node; if it is, use the UUID to update what's already in the finalNodeArray -> PROBLEM: this only works if the children come after the parents in the JSON always (otherwise it'll get overwritten)
  if (!empty($dcNode_parent)) {
    $dcNode['parent']=$dcNode_parent[0]->id;
    $childNodeArray[$dcNode['uuid']]=$dcNode;
  }
  else {
    #it is a parent, so add it to the final Array as an entirely new object
    #add the current DC Node to the final array using the UUID as the key
    $finalNodeArray[$dcNode['uuid']]=$dcNode;
  }


  #only test on a handful during development
  #if ($counter==10) { break;}
  $counter++;

}

#NOW ITERATE OVER THE CHILD NODES AND ADD THEIR DATA TO THE PARENT (urls for the files) - Doing this second makes the program work even if the children are out of order (i.e. don't follow parent in JSON), because the parent is instantiated in an array first before the child references it by a UUID index
foreach ($childNodeArray as $child) {
  #it is a child, so update the file URLs with the results from ABOVE
  $updateUUID=$child['parent'];

  foreach (array_values($mediaTypes) as $type) {

    #update each image type based on what's in the child
    $tempURLS=$finalNodeArray[$updateUUID][$type];      #get what's already in the finalArray for URLS to the files for service
    $tempURLtoADD=$child[$type];                       #get the url from the current child in the iteration to add to the list from previous line
    if (isset($tempURLtoADD)) {                             #check to make sure we have something to add, or else we end up with extraneous commas
      if (isset($tempURLS)) {
        $finalNodeArray[$updateUUID][$type]=$tempURLS.",".$tempURLtoADD;
      }
      else {
        $finalNodeArray[$updateUUID][$type]=$tempURLtoADD; #set for initial to avoid leading comma
      }
    }
  }
}


#***********************************
#**************OUTPUT***************
#***********************************

#we've finished, now time to output results
$output = fopen("CSV-OUTPUT/import.csv", "w");  #open an a file to output as csv
fputcsv($output,array_keys($dcNode),'|'); #output headers to first line of CSV file
foreach ($finalNodeArray as $line) {
  fputcsv($output,$line,'|');
}
fclose($output); #close the output file
echo "\nEND\n";    #let the user know we are done

?>
