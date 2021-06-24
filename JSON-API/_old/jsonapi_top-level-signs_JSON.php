<?php
$offset=0;
$url="http://special.library.unlv.edu/jsonapi/node/dc_object?filter[nnn][condition][path]=field_digital_id&filter[nnn][condition][operator]=STARTS_WITH&filter[nnn][condition][value]=NNN&filter[parent][condition][operator]=IS%20NULL&filter[parent][condition][path]=field_member_of&page[limit]=50&page[offset]=".$offset;
$data=file_get_contents($url);
$tempData=json_decode($data)->data;
$dataArray = $tempData;

while (sizeof($tempData)>0) {
$offset=$offset+50;
echo $offset;
$url="http://special.library.unlv.edu/jsonapi/node/dc_object?filter[nnn][condition][path]=field_digital_id&filter[nnn][condition][operator]=STARTS_WITH&filter[nnn][condition][value]=NNN&filter[parent][condition][operator]=IS%20NULL&filter[parent][condition][path]=field_member_of&page[limit]=50&page[offset]=".$offset;
$data=file_get_contents($url);
$tempData=json_decode($data)->data;
$dataArray = array_merge($dataArray,$tempData);
}
echo "Saving...";
$finalJson = json_encode($dataArray);
file_put_contents("nnn.json", $finalJson);

?>
