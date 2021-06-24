<?php
#INIT VARIABLES
	$offset=0;
	$collection_prefixes = ["NNN"]; #also available "pho" and "sky" (both have much more than just neon, so need a way to filter it)
	$crawl_url="http://special.library.unlv.edu/jsonapi/node/dc_object?filter[prefix][condition][path]=field_digital_id&filter[prefix][condition][operator]=STARTS_WITH&filter[prefix][condition][value]=";
	$dataArray = [];

#HIT JSON ENDPOINT WITH FILTER FOR EACH PREFIX IN "field_digital_id" OF NODE DC_CONTENT OBJECT
#THIS WILL COLLECT BOTH PARENTS AND CHILDREN
	foreach ($collection_prefixes as $prefix) {
		echo "\nGETTING COLLECTION $prefix \n";
		$tempData=["INIT"];
		while (sizeof($tempData)>0) {
			$state = sizeof($tempData);
			$url=$crawl_url.$prefix."&page[limit]=50&page[offset]=".$offset;
			$data=file_get_contents($url);
			$tempData=json_decode($data)->data;
			$dataArray = array_merge($dataArray,$tempData);
			$offset=$offset+50;
			#SHOW STATUS DURING CRAWL			
			echo "OFFSET: $offset          \r";
		}

		echo "\nDONE!\n";
	}

#SAVE THE COLLECTED JSON DATA TO FILE IN OUTPUT DIRECTORY
	echo "\nSaving...";
	$finalJson = json_encode($dataArray);
	file_put_contents("JSON-OUTPUT/dc_content-signs.json", $finalJson);
	echo "Saved JSON to OUTPUT DIRECTORY\n";
?>
