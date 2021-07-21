<?php
/*
/*
DESCRIPTION
 THIS SCRIPT COPIES THE OUTPUT FROM 03_append-local-metadata.php TO THE FEEDS DIRECTORY IN DRUPAL FOR IMPORT

CREDITS
 CHRISTOPHER M. CHURCH, PHD
 UNIVERSITY OF NEVADA
 LSTA GRANT, 2021
 NORTHERN NEVADA NEON PROJECT

DATE LAST UPDATED
 07-21-2021
*/

*/

$source = __DIR__ . "/OUTPUT/import.csv";
$destination ="/var/www/neon/sites/default/files/feeds/import.csv";

if (copy($source,$destination)) {print "COPIED $source TO $destination";} else {print "FAILED TO UPDATE FEEDS";}
print "\n";
 ?>
