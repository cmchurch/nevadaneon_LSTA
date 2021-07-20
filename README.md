# nevadaneon_LSTA

LSTA-funded project to build Drupal front-end application for Islandora collection on Nevada Neon housed at UNLV

Christopher Church, PHD

## ----DESCRIPTION--------

### HARVEST
00.harvest.php\
* THIS SCRIPT EXECUTES THE FULL CHAIN FOR DATAMUNGING
   * 1. GRAB DATA FROM JSON API ENDPOINT AT UNLV
   * 2. TURN JSON INTO CSV TO READY FOR IMPORT INTO DRUPAL VIA FEEDS
   * 3. APPEND METADATA FROM UNR

### JSON API
01_jsonapi_all-signs-includes_JSON.php\
* THIS SCRIPT USES THE JSON API ENDPOINT AT THE UNLV SPECIAL COLLECTIONS TO COLLECT ALL INFORMATION ON NEON SIGNS
   * IT GRABS THE DCNODES, THE RELATED MEDIA NODES, AND THE RELATED FILE NODES AND STORES THEM IN THREE FILES (dc-nodes.json, media-nodes.json, files.json) IN JSON-OUTPUT
   * THIS SCRIPT ESSENTIALLY CACHES THE JSON RESULTS LOCALLY SO THEY CAN BE PROCESSED WITHOUT REPEAT CALLS TO THE JSON API ENDPOINT

02_json-to-csv.php
* THIS SCRIPT PROCESSES THE THREE JSON FILES CACHED FROM SCRIPT "01" ABOVE AND TRANSFORMS THE DATA INTO A CSV FILE, STORED IN CSV-OUTPUT DIRECTORY, THAT CAN BE PULLED INTO THE DRUPAL CMS USING THE FIELDS MODULE\
   * THE THREE IMAGE URL FIELDS (service, thumbnail, and original) ARE NOT ATOMIZED/NORMALIZED. THEY CONTAIN A COMMA DELIMITED LIST OF URLS OF ALL MEDIA ASSOCIATED WITH EACH DCNODE (aka sign), COLLAPSING THE RELATIONSHIPS FROM UNLV'S ISLANDORA
   * IN FEEDS, THE TAMPER MODULE WILL EXPLODE THE NON-ATOMIZED DATA IN THESE FIELDS TO POPULATE THE RELEVANT URL FIELDS
   * FEEDS WILL NEED A CONTENT TYPE WITH FIELDS ONTO WHICH THE CSV FIELDS CAN BE MAPPED

### METADATA MERGE
03_append-local-metadata.php
* THIS SCRIPT PERFORMS A DATA UNION ON THE DID (digital id) OF THE SIGNS FROM THE UNLV COLLECTION
   * EXTENSIBLE METADATA HELD BY UNR IS APPENDED TO THE DATA PULLED FROM THE JSON API
   * ALLOWS THE ADDITION OF EXTENSIBLE METADATA AS PART OF DATA FLOW, SO THAT BOTH THE UNLV COLLECTION LAYER AND THE UNR METADATA LAYERS CAN BE UPDATED AND REMERGED AS NECESSARY

05_grab-FAST-metadata.php
   * THIS SCRIPT GETS THE UNIQUE POSSIBLE VALUES FOR THE FAST SUBJECTS THAT CAN BE USED TO POPULATE DRUPAL TAXONOMY VOCABULARY
      * EACH IS A PAIR: TERM--URL
      * URL SHOULD BE CREATED AS A TAXONOMY FIELD FOR THE FAST SUBJECT VOCABULARY

### FIND LAT LON
FIND-LATLON/04_get-lat-lon.php
* THIS SCRIPT USES THE GOOGLE MAPS GEOCODE API TO GET LATITUDES AND LONGITUDES FOR THE NEON SIGNS CONTAINED IN THE UNR EXTENSIBLE METADATA TABLE
   * USES THE ADDRESS FIELD AND IF IT'S POPULATED GETS THE LAT/LON FROM GOOGLE API GEOCODE
   * STORES LAT/LON IF THE ROW DOESN'T ALREADY HAVE DATA


## ----NOTES TO SELF------

### *ISSUES TO RESOLVE*
1. ~~(RESOLVED) The images associated with each dc_content node do not distinguish between Thumbnails, Full-sized Derivatives, and Originals (only distinction is in filename and path and not the metadata itself) -> when pulling images out of the JSON API to use in application layer, some are large and some a small thumbnails~~
2. ~~(RESOLVED) Currently caching a local copy of the JSON API results pulled through custom PHP -> needs to use multiple API calls to get the URIs of the files associated with each dc_content node in the NNN collection -> while I can pull these as includes, a secondary step associating them on UUID would still be necessary~~
    * ~~PULLED THESE AS INCLUDES AND THEN PROCESSED UNR SERVER-SIDE, CALLS DOWN FROM THOUSANDS TO 10s~~
3. ~~(RESOLVED) Need to figure out best way to serve images (do I want to cache and serve locally, or just do it cross-domain)~~
    * ~~DECISION: store only URLs to the resources at UNLV and keep UNR app layer asset light~~
4. ~~(RESOLVED) METADATA MERGE #THE MERGE RECURSIVE doesn't work because it's not making a properly formed CSV -> need to go line by line so that empty keys still get a blank value, or fix it in code after the merge (after line 182, we have the wrong number of lines, causing import to fail~~
5. 07-08-21: Got the metadata merge working, and I've successfully imported a test batch and got it to allow sorting on whether it's "lit" or "unlit." There are two things, though, that might cause an issue:
    *     5a. If an NNN item has children that are different than the parent (i.e. parent node is lit, but children are unlit), this could cause an issue with filtering
    *     5b. The PHO items don't have that metadata
    * The first of these is a bigger issue, because it goes back to how we want to handle the child nodes (which only happens in the NNN collection). Right now, I'm folding the children into the parent, but maybe we should keep them as separate?
        * maybe the way to solve this would be to display a mosaic of available thumbnails for each NNN item on the signs field?
        * maybe something like this would work, built into the VIEW:https://medium.com/@axel/mosaic-layouts-with-css-grid-d13f4e3ed2ae
            * would need to be responsive
            * needs to handle variable numbers of Thumbnails
            * mark these images as decorative for screen readers, since they are just a preview and don't add new INFORMATION
            * this would give the viewer a preview that would make sense if he/she searches for unlit signs (the unlit ones would show up in thumbnail mosaic), because right now it only shows the first image of the parent and its children, which may or may not be what matched in the search of the metadata for lit/unlit or interior/exterior
6. ~~(RESOLVED) 07-09-21: need to build a one-off script to pull out the FAST info and URLS to populate a Drupal vocabulary so the metadata import will working~~
    * ~~PSEUDOCODE:~~
        * ~~1) Explode on COMMA~~
        * ~~2) FOREACH pair of FAST name and url, explode on -- ~~
        * ~~3) TRIM leading and trailing whitespace~~
        * ~~4) REMOVE duplicates in array~~
        * ~~5) PUT into csv~~
    * ~~THEN, import into a FAST vocabulary in DRUPAL using csv~~
    * ~~write code now, but will need to be run once all possible FAST entries have been inputted~~
    * ~~WORKING - made list of TERMS (added them to Drupal project docs)~~
7. 07-19-21: MATERIALS STILL TO GRAB (COLLECTIONS)
   * Southern Nevada Neon Survey Records (collection) with previx 'neo'  -- EXAMPLE: http://special.library.unlv.edu/ark%3A/62930/d1b853r4n
       * **ISSUE** The 'neo' materials have children like the 'nnn' materials, but the children don't have the resource type associated with them -> not all materials in the 'neo' collection are photographs of the signs -> some are pdfs and word docs -> since the children do not have the mimetype or resource type, then filtering will be difficult
           * see http://special.library.unlv.edu/ark:/62930/d1tb0zs8h for instance, which is the child of http://special.library.unlv.edu/ark:/62930/d1vm4349q -> only the parent has the mimetype / resource type information, while the children include photos as well as a pdf
           * it seems that only photographs have service images, whereas the doc, pdf children only have thumbnails and no service images
           * going to import and see what happens
           * RESULTS: the 'neo' items only grabbed the thumbnail images (they do not have service images or originals like the other collections) -> it seems it's there in the json, but it's not getting it when converting to CSV (are non-images breaking it?) - here's the error:

PHP Notice:  Undefined index: e3411af5-d56a-4690-a896-9af3167b855f in /home/chris/Desktop/NevadaNeon/NorthernNevadaNeon/JSON-API/02_json-to-csv.php on line 184
PHP Notice:  Trying to access array offset on value of type null in /home/chris/Desktop/NevadaNeon/NorthernNevadaNeon/JSON-API/02_json-to-csv.php on line 184
PHP Notice:  Undefined index: service in /home/chris/Desktop/NevadaNeon/NorthernNevadaNeon/JSON-API/02_json-to-csv.php on line 184
PHP Notice:  Undefined index: original in /home/chris/Desktop/NevadaNeon/NorthernNevadaNeon/JSON-API/02_json-to-csv.php on line 184
PHP Notice:  Undefined index: d2db8ad5-7f59-4202-b258-d32a85a726db in /home/chris/Desktop/NevadaNeon/NorthernNevadaNeon/JSON-API/02_json-to-csv.php on line 184
PHP Notice:  Trying to access array offset on value of type null in /home/chris/Desktop/NevadaNeon/NorthernNevadaNeon/JSON-API/02_json-to-csv.php on line 184
PHP Notice:  Undefined index: service in /home/chris/Desktop/NevadaNeon/NorthernNevadaNeon/JSON-API/02_json-to-csv.php on line 184
PHP Notice:  Undefined index: original in /home/chris/Desktop/NevadaNeon/NorthernNevadaNeon/JSON-API/02_json-to-csv.php on line 184

   * Dreaming the Skyline (can't find in Islandora as a collection; it seems to be a Digital Project with many records from YESCO corporate records) - see http://special.library.unlv.edu/search?keys=neon&f%5B0%5D=digital_project%3ADreaming%20the%20Skyline%3A%20Resort%20Architecture%20and%20the%20New%20Urban%20Space
   * **Note:** Neon in Nevada Photograph Collection with pho prefix grabs a lot of duplicates from the NNN collection (maybe exclude?)
   * Instead of pulling from collections, maybe pull from Neon Survey digital project by uuid (2038 records) - though would need to filter by Resource Type (image only)
