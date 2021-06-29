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
  #check to see if this is a child node; if it is, skip it
  if (!empty($dcNode_parent)) {continue;}
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
        foreach ($fileNodes as $fileKey=>$fileNode) {
          $fileId = $fileNode->id;
          if ($fileId==$media['file_id']) {
            $dcNode[$type] = "http://special.library.unlv.edu".$fileNode->attributes->uri->url;
            $dcNode_fileURL = "http://special.library.unlv.edu".$fileNode->attributes->uri->url;
            unset($mediaNodes[$mediaKey]);
            unset($fileNodes[$fileKey]);
          }
        }
      }
    }

  #write the row to the csv file using a delimiter
  #fputcsv($output,$dcNode,'|');
  array_push($csvLines,$dcNode);

  #only test on a handful during development
  #if ($counter==10) { break;}
  $counter++;

}

#we've finished, now time to output results
$output = fopen("CSV-OUTPUT/import.csv", "w");  #open an a file to output as csv
fputcsv($output,array_keys($csvLines[0]),'|'); #output headers to first line of CSV file
foreach ($csvLines as $line) {
  fputcsv($output,$line,'|');
}
fclose($output); #close the output file
echo "\nEND\n";    #let the user know we are done

?>
