<?php
#load libraries installed with composer
require '/home/chris/vendor/autoload.php';

#get the tools we need from the nlp-tools library
use NlpTools\FeatureFactories\DataAsFeatures;
use NlpTools\Tokenizers\WhitespaceTokenizer;
use NlpTools\Documents\TokensDocument;
use NlpTools\Documents\TrainingSet;
use NlpTools\Models\Lda;
use NlpTools\Utils\StopWords;

#INITS
$metadata = fetchCSV(__DIR__ . "/../../METADATA-MERGE/OUTPUT/import.csv",'did');
$docs = getTitleDesc($metadata);

#get stopwords and add extended list
$standard_stopwords = getStopwords(__DIR__ . "/../autotag/stopwords.txt");
$extended_stopwords =  getStopwords(__DIR__ . "/../autotag/expanded-stopwords.txt");
$stopwords = new StopWords (array_merge($standard_stopwords,$extended_stopwords));


#tokenize and apply stopwords
$tok = new WhitespaceTokenizer();
$tset = new TrainingSet();
foreach ($docs as $f) {
    $d = new TokensDocument($tok->tokenize($f));
    $d->applyTransformation($stopwords);
    $tset->addDocument('', $d);
}

$lda = new Lda(
    new DataAsFeatures(), // a feature factory to transform the document data
    5, // the number of topics we want
    1, // the dirichlet prior assumed for the per document topic distribution
    1  // the dirichlet prior assumed for the per word topic distribution
);

// run the sampler 50 times
$lda->train($tset,50);


print_r(
    // $lda->getPhi(10)
    // just the 10 largest probabilities
    $lda->getWordsPerTopicsProbabilities(10)
);



#-------------------------------------------------FUNCTIONS-------------------------------------------------
function fetchCSV($input_path,$_UID_KEY) {
/*This function fetches as CSV at the $input_path, stores all the rows in an associative array with the key provided as an argument pulled from each entry*/
  $input_data = fopen($input_path,"r");
  $first_row = fgetcsv($input_data,0,$separator = "|");
  $csvKeys=[];
  $csvLines=[];

  $keys = array_values($first_row);
    foreach ($keys as $index=>$key) {
      if ($key==NULL) { $key = "BLANK".$index;}
      $key = preg_replace("/[^A-Za-z0-9]-/", '', $key);
      $key = substr($key, 0, 20);
      array_push($csvKeys, $key);
    }

  #print_r($csvKeys);

  while (($row = fgetcsv($input_data,0,$separator = "|")) != FALSE) {
      $csvLine=[];
      foreach ($row as $index=>$r) {
          #if ($csvKeys[$index]=='lit-unlit') {$r = preg_replace("/,\s+/", ',', $r);} #NOTE: moving to TAMPER MODULE, check to see if it is one of the multifields, and if so, remove the space after comma -> maybe use TAMPER module after this script instead
          $csvLine[$csvKeys[$index]]=$r;    #here is the actual value for each field in CSV
      }
      $csvLines[$csvLine[$_UID_KEY]]=$csvLine;
  }
  fclose($input_data);
  return$csvLines;
}

function getTitleDesc($_metadata) {
  $_docs=[];
  foreach ($_metadata as $_row) {
    $_text = join([$_row['title'],$_row['unr-desc']]," ");
    $_text = strtolower(strip_tags(str_replace('<', ' <',$_text)));
    $_text = preg_replace("/[^a-z\s]/"," ",$_text);
    $_text = preg_replace("/\s+/"," ",$_text);
    array_push($_docs,$_text);
  }
  return $_docs;
}

function getStopwords($input_path) {
  $file = fopen($input_path,"r");
  $text = fread($file,filesize($input_path));
  return explode("\n",$text);
}


?>
