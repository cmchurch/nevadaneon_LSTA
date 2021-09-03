<?php
$input_path = __DIR__ . "/JSON-OUTPUT/dc-nodes.json";
$dcNodes = json_decode(file_get_contents($input_path));
foreach ($dcNodes as $node) {
  $id = $node->attributes->field_digital_id;
  $id_prefix = substr($id,0,3);
  if ($id_prefix!='NNN') {continue;}  
  print $id ."\n";
}
?>
