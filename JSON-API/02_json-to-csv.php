<?PHP
/*
DESCRIPTION
 TRANSFORM JSON OBJECTS INTO CSV FOR CMS IMPORT

CREDITS
 CHRISTOPHER M. CHURCH, PHD
 UNIVERSITY OF NEVADA
 LSTA GRANT, 2021
 NORTHERN NEVADA NEON PROJECT

DATE LAST UPDATED
 06-30-2021

BASIC PSEUDOCODE
1. GET ALL JSON OBJECTS (DCNODES, MEDIA NODES, AND FILE NODES)
2. ITERATE OVER DCNODES IN MAIN FUNCTION USING FOREACH LOOP
3. IN FOREACH LOOP ITERATION, DO THE FOLLOWING:
    a) GET THE MAIN DATA FOR EACH DC NODE AND STORE IT IN AN ASSOCIATIVE ARRAY
    b) CALL mediaNodesIterate(), WHICH CALLS getFile() AND GRABS THE MEDIA NODES RELATED THROUGH field_media_of IN JSON
       AND GETS THE ASSOCIATED FILE URL FROM THE FILE NODE
    c) DETERMINE IF WE HAVE A CHILD OR PARENT NODE BASED ON field_member_of IN THE DCNODE JSON
    d) IF PARENT, ADD IT DIRECTLY TO THE FINAL NODE ARRAY THAT WILL BE EXPORTED TO CSV
       IF CHILD, ADD IT TO A TEMPORARY CHILD NODE ARRAY WITH PARENT INFO APPENDED
4. AFTER FOREACH LOOP ON DCNODES, ITERATE OVER CHILD NODE ARRAY TO UPDATE THE FINAL NODE ARRAY WITH THE FILE URLS USING UUID AS KEY TO JOIN THE TWO ASSOCIATIVE ARRAYS
5. EXPORT THE CSV WITH HEADERS

*/

#***********************************
#*************FUNCTIONS*************
#***********************************

function getlatlon($_node)
{
  if (!empty($_node->attributes->field_topic_location)) {
    $latitude=$_node->attributes->field_topic_location[0]->lat;
    $longitude=$_node->attributes->field_topic_location[0]->lng;
  }
  else {
    $latitude=NULL;
    $longitude=NULL;
  }
  return [$latitude,$longitude];
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

function getcitation($_node)
{
    if (!empty($_node->attributes->field_citation)) {
      return $_node->attributes->field_citation->value;
    }
    else {
      return NULL;
    }
}

function getFile($file_uuid, $_fileNodes) {
  foreach ($_fileNodes as $fileKey=>$fileNode) {
    $fileId = $fileNode->id;
    if ($fileId==$file_uuid) {
      $result = ["http://special.library.unlv.edu".$fileNode->attributes->uri->url,$fileKey];
      return $result;
    }
  }
}

function mediaNodesIterate($_mediaNodes, $_fileNodes, $_dcNode,$_mediaTypes) {
  foreach ($_mediaNodes as $mediaKey=>$mediaNode)
    {
      #print_r($mediaNode->relationships->field_media_of->data->id);
      $media = ['parent_id'   =>    $mediaNode->relationships->field_media_of->data->id,      #this is the UUID of the dc node to which the current media node belongs
                'file_id'     =>    $mediaNode->relationships->field_media_image->data->id,   #file id of the associated file to the current media node
                'usage'       =>    $mediaNode->relationships->field_media_use->data[0]->id   #this is the UUID of the taxonomy term attached to media node SEE SET VARIABLES ABOVE
      ];

      if ($media['parent_id']==$_dcNode['uuid']) {  #it looks like some don't have service files
        $type = $_mediaTypes[$media['usage']];
        $fileGrab = getFile($media['file_id'],$_fileNodes);
        $_dcNode[$type] = $fileGrab[0];
        $fileKey = $fileGrab[1];
        #if we unset the node, then if there is a duplicate, we get a blank -> duplicates can arise if the call for media_nodes to the JSON API results in the two associated media files falling across an OFFSET divide -> the included dc_node will then show up twice in the "included" JSON element, so if we unset it, then we will lose the file data for the second time it shows up
        #without unset commands, it is twice as slow, however
        #unset($_mediaNodes[$mediaKey]);
        #unset($_fileNodes[$fileKey]);
      }
    }
    return [$_mediaNodes,$_fileNodes,$_dcNode];
}


function getJSONAPI ($_uuid) {
  $url = "http://special.library.unlv.edu/jsonapi/node/dc_object/" . $_uuid;
  $data=file_get_contents($url); #GET the JSON API results from the ENDPOINT AS A JSON OBJECT
  $jsonObj=json_decode($data);
  $node=$jsonObj->data;
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
  print "FOUND MISSING PARENT, GRABBED " . $dcNode['did'] . " from web\n";
  return $dcNode;
}

#***********************************
#***************MAIN****************
#***********************************

echo "\n\nTRANSFORMING JSON OBJECT INTO CSV FOR CMS IMPORT\n";
echo "*************************************************\n\nSTART\n";
#READ IN THE JSONS FROM CACHE
$input_path = __DIR__ . "/JSON-OUTPUT/media-nodes.json";
$mediaNodes = json_decode(file_get_contents($input_path));
$input_path = __DIR__ . "/JSON-OUTPUT/dc-nodes.json";
$dcNodes = json_decode(file_get_contents($input_path));
$total_dcNodes = count($dcNodes);
$input_path = __DIR__ . "/JSON-OUTPUT/files.json";
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
  $dcNode_parent=$node->relationships->field_member_of->data;   #pull out the parent (not part of dcNode associative array -> we don't want it in final csv)

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
  $mediaNodes = $iterateMediaResults[0];  #inside mediaNodesIterate function it unsets the array element once it's been processed, so we have to update the master list so we don't iterate over items already processed
  $fileNodes = $iterateMediaResults[1];
  $dcNode = $iterateMediaResults[2];      #update the current dcNode we're working with in the foreach loop with the results from iterating over the mediaNodes

  #check to see if this is a child node; if it is, use the UUID to update what's already in the finalNodeArray -> PROBLEM: this only works if the children come after the parents in the JSON always (otherwise it'll get overwritten)
  #**note** does not work for the 'neo' items, because they can be a member of, so adding a second condition (did is not hyphenated) to verify we are dealing with a child node
  $isHyphenated = preg_match("/\-/",$dcNode['did']);
  if (!empty($dcNode_parent)&&$isHyphenated==1) {
    $dcNode['parent']=$dcNode_parent;
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
  $parents=$child['parent']; #there can be multiple parents in array for some collections
  foreach ($parents as $parent) { #iterate over the parents and see if one matches our parent list, if so break
    $parentFound = FALSE;
    if (isset($finalNodeArray[$parent->id])) {
      $updateUUID = $parent->id;
      $parentFound = TRUE;
      break;
    }
  }

  #this code catches child nodes and grabs the parent if the parent didn't exist (last batch of NNN items had imageless parents, which messed up code)
  if ($parentFound == FALSE) {
    $updateUUID = $child['parent'][0]->id;
    $finalNodeArray[$updateUUID] = getJSONAPI($updateUUID);
  }

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
$output = fopen(__DIR__ . "/CSV-OUTPUT/import.csv", "w");  #open an a file to output as csv
$initArrayKey=array_key_first($finalNodeArray);
fputcsv($output,array_keys($finalNodeArray[$initArrayKey]),'|'); #output headers to first line of CSV file
foreach ($finalNodeArray as $line) {
  fputcsv($output,$line,'|');
}
fclose($output); #close the output file
echo "\nEND\n";    #let the user know we are done

?>
