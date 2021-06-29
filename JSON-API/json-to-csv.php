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
    if (!empty($node->attributes->citation)) {
      return $node->attributes->body->value;
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

$mediaTypes =['0db579ad-a810-45bb-acd1-002bf314b50f'  =>  'thumbnail',#media use UUID taxonomy term for thumbnail files
              '7bb44572-2b5c-4c28-b9f3-423c578455a8'  =>  'service',  #media use UUID taxonomy term for service files
              '5111671c-1010-4d63-9a53-5001aefc836a'  =>  'original'  #media use UUID taxonomy term for original files
            ];

#define function for grabbing attributes, using local variables so they reset with each get_called_class

foreach ($dcNodes as $key=>$node){

  #show progress to user
  $progress = $key/$total_dcNodes*100;
  echo "PROGRESS: $progress %     \r";

  #get the key attributes and relationships for each dc_node in JSON
  $dcNode_parent=$node->relationships->field_member_of->data;

  #if we haven't skipped the current node then continue grabbing attributes from the JSON
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
  foreach ($mediaNodes as $mediaKey=>$mediaNode)
    {
      $media = ['parent_id'   =>    $mediaNode->relationships->field_media_of->data->id,      #this is the UUID of the dc node to which the current media node belongs
                'file_id'     =>    $mediaNode->relationships->field_media_image->data->id,   #file id of the associated file to the current media node
                'usage'       =>    $mediaNode->relationships->field_media_use->data[0]->id   #this is the UUID of the taxonomy term attached to media node SEE SET VARIABLES ABOVE
      ];

      if ($media['parent_id']==$dcNode['uuid']) {  #it looks like some don't have service files
        $type = $mediaTypes[$media['usage']];
        $fileGrab = getFile($media['file_id'],$fileNodes);
        $dcNode[$type] = $fileGrab[0];
        $fileKey = $fileGrab[1];
        unset($mediaNodes[$mediaKey]);
        unset($fileNodes[$fileKey]);

      }
    }

    #check to see if this is a child node; if it is, use the UUID to update what's already in the finalNodeArray -> PROBLEM: this only works if the children come after the parents in the JSON always (otherwise it'll get overwritten)
    if (!empty($dcNode_parent)) {
        #it is a child, so update the file URLs with the results from ABOVE
        $updateUUID=$dcNode_parent[0]->id;

        #update service image
        $tempURLS=$finalNodeArray[$updateUUID]['service'];      #NOTE: make these proper functions!
        $tempURLtoADD=$dcNode['service'];
        if (isset($tempURLtoADD)) {
          $finalNodeArray[$updateUUID]['service']=$tempURLS.",".$tempURLtoADD;
        }

        #update thumbnail
        $tempURLS=$finalNodeArray[$updateUUID]['thumbnail'];
        $tempURLtoADD=$dcNode['thumbnail'];
        if (isset($tempURLtoADD)) {
          $finalNodeArray[$updateUUID]['thumbnail']=$tempURLS.",".$tempURLtoADD;
        }

        #update original
        $tempURLS=$finalNodeArray[$updateUUID]['original'];
        $tempURLtoADD=$dcNode['original'];
        if (isset($tempURLtoADD)) {
          $finalNodeArray[$updateUUID]['original']=$tempURLS.",".$tempURLtoADD;
        }
    }
    else {
      #it is a parent, so add it to the final Array as an entirely new object
      $finalNodeArray[$dcNode['uuid']]=$dcNode;

    }


  #add the current DC Node to the final array using the UUID as the key


  #only test on a handful during development
  #if ($counter==100) { break;}
  $counter++;

}

#we've finished, now time to output results
$output = fopen("CSV-OUTPUT/import.csv", "w");  #open an a file to output as csv
fputcsv($output,array_keys($dcNode),'|'); #output headers to first line of CSV file
foreach ($finalNodeArray as $line) {
  fputcsv($output,$line,'|');
}
fclose($output); #close the output file
echo "\nEND\n";    #let the user know we are done

?>
