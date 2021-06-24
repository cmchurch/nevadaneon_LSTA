<?php
#INIT VARIABLES
$offset=0;
$collection_prefixes = ["NNN","pho","sky"];
$crawl_url="http://special.library.unlv.edu/jsonapi/node/dc_object?filter[prefix][condition][path]=field_digital_id&filter[prefix][condition][operator]=STARTS_WITH&filter[prefix][condition][value]=pho&page[limit]=50&page[offset]=";
$tempData="INIT";
$dataArray = [];

foreach ($collection_prefixes as $prefix) {
echo "GETTING COLLECTION $prefix;"

	while (sizeof($tempData)>0) {
		echo "OFFSET: $offset     \r";
		$url=$crawl_url.$offset;
		$data=file_get_contents($url);
		$tempData=json_decode($data)->data;
		$dataArray = array_merge($dataArray,$tempData);
		$offset=$offset+50;
	}

echo "DONE!";
}
echo "Saving...";
$finalJson = json_encode($dataArray);
file_put_contents("nnn.json", $finalJson);

?>
