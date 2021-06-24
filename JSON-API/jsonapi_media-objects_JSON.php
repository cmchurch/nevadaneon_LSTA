<?php
#DESCRIPTION
#DOWNLOAD JSON OF MEDIA OBJECTS ASSOCIATED WITH DC NODES IN CACHE FROM UNLV ISLANDORA'S JSON-API TO USE IN LOCAL CACHE AND CMS IMPORT

#CREDITS
#CHRISTOPHER M. CHURCH, PHD
#UNIVERSITY OF NEVADA
#LSTA GRANT, 2021
#NORTHERN NEVADA NEON PROJECT

#DATE LAST UPDATED
#06-24-2021

echo "DOWNLOADING JSON OBJECT FROM ENDPOINT AT UNLV FOR MEDIA CONTENT NODES RELATED TO DC CONTENT NODES IN CACHE\n\n";

   #INIT VARIABLES
	#cache path
         $input_path = "JSON-OUTPUT/dc_content-signs.json";
	#page offset for the JSON API endpoint	
	 $offset=0;	
	#the URL of the ENDPOINT (media) plus filters
	 $crawl_url="http://special.library.unlv.edu/jsonapi/media/image/?filter[field_media_of.id][value]=";
	#initialize an empty array to which we can append the data results from each call of the JSON API	
	 $dataArray = [];


   #LOAD CACHED NEON NODES (DC CONTENT) and ITERATE TO DOWNLOAD RELATED MEDIA NODES
	$dc_data=json_decode(file_get_contents($input_path));
	$total_nodes=count($dc_data);
	foreach ($dc_data as $key=>$datum) {
 		$url=$crawl_url.$datum->id;
 		$tempData=json_decode(file_get_contents($url))->data;
		$dataArray = array_merge($dataArray,$tempData); #ADD ONLY THE DATA ELEMENT TO AN ARRAY -> this is an array_merge because each media node has 2 items in an array (Thumbnail and Service Image)
         	$progress = $key/$total_nodes*100;
		echo "PROGRESS: $progress %     \r";
	}

#SAVE THE COLLECTED JSON DATA TO FILE IN OUTPUT DIRECTORY
	echo "\nSaving...\n"; #let the user know we're done iterating over the JSON API endpoint
	$finalJson = json_encode($dataArray); #encode the DATA results as a JSON OBJECT
	file_put_contents("JSON-OUTPUT/media-nodes_signs.json", $finalJson); #Save object to output directory to be used in cache for CMS
	echo "Saved JSON to OUTPUT DIRECTORY\n"; #let the user know we've finished
?>
