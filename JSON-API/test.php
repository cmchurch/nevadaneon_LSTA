<?php
$url="http://special.library.unlv.edu/jsonapi/node/dc_object?filter[prefix][condition][path]=field_digital_id&filter[prefix][condition][operator]=STARTS_WITH&filter[prefix][condition][value]=NNN&page[limit]=50&&page[offset]=650";
$data=file_get_contents($url);
$tempData=json_decode($data)->data;
print_r($tempData);
echo sizeof($tempData);
?>
