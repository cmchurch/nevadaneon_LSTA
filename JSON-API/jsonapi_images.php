<?php


$parent_data=json_decode(file_get_contents("nnn.json"));


foreach ($parent_data as $datum) {
 $media_url="http://special.library.unlv.edu/jsonapi/media/image/?filter[field_media_of.id][value]=".$datum->id;
 $media_json=json_decode(file_get_contents($media_url));
 
 foreach ($media_json->data as $media_element) {
	$uid_file = $media_element->relationships->field_media_image->data->id;
 	$file_url="http://special.library.unlv.edu/jsonapi/file/file/".$uid_file;
 	$file_json=json_decode(file_get_contents($file_url));
	echo $file_json->data->attributes->uri->url.",";
 }
echo "\n";
}



?>
