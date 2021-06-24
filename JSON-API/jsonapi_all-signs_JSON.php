<?php
#DESCRIPTION
#DOWNLOAD JSON OF DC_OBJECTS FROM UNLV ISLANDORA'S JSON-API TO USE IN LOCAL CACHE AND CMS IMPORT

#CREDITS
#CHRISTOPHER M. CHURCH, PHD
#UNIVERSITY OF NEVADA
#LSTA GRANT, 2021
#NORTHERN NEVADA NEON PROJECT

#DATE LAST UPDATED
#06-21-2021

echo "DOWNLOADING JSON OBJECT FROM ENDPOINT AT UNLV FOR DC_CONTENT NODES RELATED TO NEON";

   #INIT VARIABLES
	#page offset for the JSON API endpoint	
	 $offset=0;	
	#prefixes and collection identifiers -> first in associative array is prefix on the digital id and second is the UUID of the collection
	#also available "sky" (did not include "sky" because the collection includes much more than NEON)
	 $digital_id_prefixes = ["NNN"=>null,"pho"=>"1661de4d-e916-4108-b529-f3b15030c27b"]; #note: as of 6-2021, the NNN materials have not been assigned a collection, so it is left null
	#the URL of the ENDPOINT (dc_content) plus filters
	 $crawl_url="http://special.library.unlv.edu/jsonapi/node/dc_object?filter[prefix][condition][path]=field_digital_id&filter[prefix][condition][operator]=STARTS_WITH&filter[prefix][condition][value]=";
	#initialize an empty array to which we can append the data results from each call of the JSON API	
	 $dataArray = [];

   #HIT JSON ENDPOINT WITH FILTER FOR EACH PREFIX IN "field_digital_id" OF NODE DC_CONTENT OBJECT, AS WELL AS THE COLLECTION IDENTIFIER IF RELEVANT
   #THIS WILL COLLECT BOTH PARENTS AND CHILDREN
	foreach ($digital_id_prefixes as $prefix=>$collection) {
		echo "\nGETTING ID PREFIX $prefix FROM COLLECTION IF SPECIFIED \n";		#tell user we're working
		
		#INIT VARIABLES LOCAL TO FOREACH LOOP
		 $offset=0; #set the offset at 0 for current PREFIX=?COLLECTION
		 $tempData=["INIT"]; #PUT SOMETHING IN TEMPARRAY TO MAKE SURE THAT THE WHILE LOOP STARTS INITIALLY

		while (sizeof($tempData)>0) {	#continue looping as long as the JSON API does not return an empty data element in the results per offset
			$url=$crawl_url.$prefix."&page[limit]=50&page[offset]=".$offset; #built JSON API endpoint URL using prefix information
			if (isset($collection)) $url=$url."&filter[field_archival_collection.id]=".$collection; #add the collection UUID to URL filter if present in associative array
			$data=file_get_contents($url); #GET the JSON API results from the ENDPOINT AS A JSON OBJECT
			$tempData=json_decode($data)->data; #READ THE DATA ELEMENT INTO PHP FROM THE DECODED JSON OBJECT
			$dataArray = array_merge($dataArray,$tempData); #ADD ONLY THE DATA ELEMENT TO AN ARRAY
			$offset=$offset+50; #ITERATE THE OFFSET TO GET NEXT RESULTS			
			echo "OFFSET: $offset          \r"; #SHOW STATUS DURING CRAWL TO THE USER SO WE KNOW IT'S WORKING
		}
		echo "\nDONE!\n"; #FINISHED WITH CURRENT PREFIX=>COLLECTION COMBINATION
	}

#SAVE THE COLLECTED JSON DATA TO FILE IN OUTPUT DIRECTORY
	echo "\nSaving...\n"; #let the user know we're done iterating over the JSON API endpoint
	$finalJson = json_encode($dataArray); #encode the DATA results as a JSON OBJECT
	file_put_contents("JSON-OUTPUT/dc_content-signs.json", $finalJson); #Save object to output directory to be used in cache for CMS
	echo "Saved JSON to OUTPUT DIRECTORY\n"; #let the user know we've finished
?>
