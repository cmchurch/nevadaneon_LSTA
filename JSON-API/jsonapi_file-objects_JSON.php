<?php
#DESCRIPTION
#DOWNLOAD JSON OF FILE OBJECTS ASSOCIATED WITH DC NODES IN CACHE FROM UNLV ISLANDORA'S JSON-API TO USE IN LOCAL CACHE AND CMS IMPORT

#CREDITS
#CHRISTOPHER M. CHURCH, PHD
#UNIVERSITY OF NEVADA
#LSTA GRANT, 2021
#NORTHERN NEVADA NEON PROJECT

#DATE LAST UPDATED
#06-24-2021

echo "DOWNLOADING JSON OBJECT FROM ENDPOINT AT UNLV FOR FILE OBJECTS RELATED TO MEDIA CONTENT NODES IN CACHE\n\n";

   #INIT VARIABLES
	#cache path
         $input_path = "JSON-OUTPUT/media-nodes_signs.json";
	#page offset for the JSON API endpoint	
	 $offset=0;	
	#the URL of the ENDPOINT (dc_content) plus filters
	 $crawl_url="http://special.library.unlv.edu/jsonapi/file/file/";
	#initialize an empty array to which we can append the data results from each call of the JSON API	
	 $dataArray = [];


   #LOAD CACHED MEDIA NODES (media) AND ITERATE TO GET FILE OBJECTS RELATED TO THE MEDIA NODE
	$media_data=json_decode(file_get_contents($input_path));
	$total_nodes=count($media_data);
	foreach ($media_data as $key=>$datum) {
 		$url=$crawl_url.$datum->relationships->field_media_image->data->id;		
		$tempData=json_decode(file_get_contents($url))->data;
		array_push($dataArray,$tempData); #ADD ONLY THE DATA ELEMENT TO AN ARRAY -> This is a push because there is no array inside the data element of a file object -> it's just 1 item (as opposed to 2 for each media node)
         	$progress = $key/$total_nodes*100;
		echo "PROGRESS: $progress %     \r";
	}

#SAVE THE COLLECTED JSON DATA TO FILE IN OUTPUT DIRECTORY
	echo "\nSaving...\n"; #let the user know we're done iterating over the JSON API endpoint
	$finalJson = json_encode($dataArray); #encode the DATA results as a JSON OBJECT
	file_put_contents("JSON-OUTPUT/file-nodes_signs.json", $finalJson); #Save object to output directory to be used in cache for CMS
	echo "Saved JSON to OUTPUT DIRECTORY\n"; #let the user know we've finished
?>
