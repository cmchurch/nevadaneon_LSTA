# nevadaneon_LSTA

LSTA-funded project to build Drupal front-end application for Islandora collection on Nevada Neon housed at UNLV

Christopher Church, PHD

## ----DESCRIPTION--------

01_jsonapi_all-signs-includes_JSON.php\
 THIS SCRIPT USES THE JSON API ENDPOINT AT THE UNLV SPECIAL COLLECTIONS TO COLLECT ALL INFORMATION ON NEON SIGNS \
  -> IT GRABS THE DCNODES, THE RELATED MEDIA NODES, AND THE RELATED FILE NODES AND STORES THEM IN THREE FILES (dc-nodes.json, media-nodes.json, files.json) IN JSON-OUTPUT\
  -> THIS SCRIPT ESSENTIALLY CACHES THE JSON RESULTS LOCALLY SO THEY CAN BE PROCESSED WITHOUT REPEAT CALLS TO THE JSON API ENDPOINT\

02_json-to-csv.php
 THIS SCRIPT PROCESSES THE THREE JSON FILES CACHED FROM SCRIPT "01" ABOVE AND TRANSFORMS THE DATA INTO A CSV FILE, STORED IN CSV-OUTPUT DIRECTORY, \
 THAT CAN BE PULLED INTO THE DRUPAL CMS USING THE FIELDS MODULE\
  -> THE THREE IMAGE URL FIELDS (service, thumbnail, and original) ARE NOT ATOMIZED/NORMALIZED. THEY CONTAIN A COMMA DELIMITED LIST OF URLS OF ALL \
     MEDIA ASSOCIATED WITH EACH DCNODE (aka sign), COLLAPSING THE RELATIONSHIPS FROM UNLV'S ISLANDORA\
  -> IN FEEDS, THE TAMPER MODULE WILL EXPLODE THE NON-ATOMIZED DATA IN THESE FIELDS TO POPULATE THE RELEVANT URL FIELDS\
  -> FEEDS WILL NEED A CONTENT TYPE WITH FIELDS ONTO WHICH THE CSV FIELDS CAN BE MAPPED\
\
\

## ----NOTES TO SELF------

### *ISSUES TO RESOLVE*
1. (RESOLVED) The images associated with each dc_content node do not distinguish between Thumbnails, Full-sized Derivatives, and Originals (only distinction is in filename and path and not the metadata itself) -> when pulling images out of the JSON API to use in application layer, some are large and some a small thumbnails
2. Currently caching a local copy of the JSON API results pulled through custom PHP -> needs to use multiple API calls to get the URIs of the files associated with each dc_content node in the NNN collection -> while I can pull these as includes, a secondary step associating them on UUID would still be necessary
3. Need to figure out best way to serve images (do I want to cache and serve locally, or just do it cross-domain) 
