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

### FIND LAT LON
FIND-LATLON/04_get-lat-lon.php
* THIS SCRIPT USES THE GOOGLE MAPS GEOCODE API TO GET LATITUDES AND LONGITUDES FOR THE NEON SIGNS CONTAINED IN THE UNR EXTENSIBLE METADATA TABLE
   * USES THE ADDRESS FIELD AND IF IT'S POPULATED GETS THE LAT/LON FROM GOOGLE API GEOCODE
   * STORES LAT/LON IF THE ROW DOESN'T ALREADY HAVE DATA


## ----NOTES TO SELF------

### *ISSUES TO RESOLVE*
1. (RESOLVED) The images associated with each dc_content node do not distinguish between Thumbnails, Full-sized Derivatives, and Originals (only distinction is in filename and path and not the metadata itself) -> when pulling images out of the JSON API to use in application layer, some are large and some a small thumbnails
2. (RESOLVED) Currently caching a local copy of the JSON API results pulled through custom PHP -> needs to use multiple API calls to get the URIs of the files associated with each dc_content node in the NNN collection -> while I can pull these as includes, a secondary step associating them on UUID would still be necessary
    * PULLED THESE AS INCLUDES AND THEN PROCESSED UNR SERVER-SIDE, CALLS DOWN FROM THOUSANDS TO 10s
3. (RESOLVED) Need to figure out best way to serve images (do I want to cache and serve locally, or just do it cross-domain)
    * DECISION: store only URLs to the resources at UNLV and keep UNR app layer asset light
4. (RESOLVED) METADATA MERGE #THE MERGE RECURSIVE doesn't work because it's not making a properly formed CSV -> need to go line by line so that empty keys still get a blank value, or fix it in code after the merge (after line 182, we have the wrong number of lines, causing import to fail
5. 07-08-21: Got the metadata merge working, and I've successfully imported a test batch and got it to allow sorting on whether it's "lit" or "unlit." There are two things, though, that might cause an issue:
    *     5a. If an NNN item has children that are different than the parent (i.e. parent node is lit, but children are unlit), this could cause an issue with filtering
    *     5b. The PHO items don't have that metadata
    * The first of these is a bigger issue, because it goes back to how we want to handle the child nodes (which only happens in the NNN collection). Right now, I'm folding the children into the parent, but maybe we should keep them as separate?
