<?php

$output = fopen("images-by-uuid_parent.csv", "w"); 
$parent_data=json_decode(file_get_contents("nnn.json"));

fwrite($output,"UUID\tIMAGES\n");
$total=count($parent_data);
foreach ($parent_data as $key=>$datum) {
 $media_url="http://special.library.unlv.edu/jsonapi/media/image/?filter[field_media_of.id][value]=".$datum->id;
 $media_json=json_decode(file_get_contents($media_url));
 fwrite($output,$datum->id."\t");
 $imagesArray=[];
 foreach ($media_json->data as $media_element) {
	$uid_file = $media_element->relationships->field_media_image->data->id;
 	$file_url="http://special.library.unlv.edu/jsonapi/file/file/".$uid_file;
 	$file_json=json_decode(file_get_contents($file_url));
	array_push($imagesArray, $file_json->data->attributes->uri->url);
 }
 fwrite($output,implode(",",$imagesArray)."\n");
 $progress = $key/$total*100;
  echo "PROGRESS: $progress %     \r";
}
fclose($output);


?>
