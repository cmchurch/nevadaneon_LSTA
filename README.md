# nevadaneon_LSTA

LSTA-funded project to build Drupal front-end application for Islandora collection on Nevada Neon housed at UNLV

Christopher Church, PHD


----NOTES TO SELF------

*ISSUES TO RESOLVE*
1. The images associated with each dc_content node do not distinguish between Thumbnails, Full-sized Derivatives, and Originals (only distinction is in filename and path and not the metadata itself) -> when pulling images out of the JSON API to use in application layer, some are large and some a small thumbnails
2. Currently caching a local copy of the JSON API results pulled through custom PHP -> needs to use multiple API calls to get the URIs of the files associated with each dc_content node in the NNN collection -> while I can pull these as includes, a secondary step associating them on UUID would still be necessary
3. Need to figure out best way to serve images (do I want to cache and serve locally, or just do it cross-domain) 
