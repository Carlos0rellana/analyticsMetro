<?php

function getResultListFromArc($queryArray) {
  $curl = curl_init();
  curl_setopt_array($curl,$queryArray);
  $response = curl_exec($curl);
  curl_close($curl);
  return $response;
}

function makeArrayQuerie($apiQueryUrl,$key=false){
  require ROOT_DIR.'/config/dataConection.php';
  if(!$key){$key=$pass;}
  return array(
    CURLOPT_URL => $apiUrl.$apiQueryUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
    CURLOPT_HTTPHEADER => array('Authorization: Bearer '.$key),
  );
}

function getSitesList(){
  return getResultListFromArc(makeArrayQuerie('site/v3/website/%0A'));
}

function getSearchList($site,$start,$end=false,$canonical_website=false,$from=0){
  $canonical='';
  if(!$end){$end=$start;}
  if($canonical_website===true){$canonical='+AND+canonical_website:'.$site;}
  $query='content/v4/search/published?website='.$site.'&q=publish_date:%5B'.$start.'+TO+'.$end.'%5D'.$canonical.'&_sourceInclude=publish_date,credits,canonical_website,revision,planning&size=100&from='.$from;
  return getResultListFromArc(makeArrayQuerie($query));
}

function getSearchListArticles($site,$start,$end=false,$from=0){
  if(!$end){$end=$start;}
  $query='content/v4/search/published?website='.$site.'&q=publish_date:%5B'.$start.'+TO+'.$end.'%5D+AND+canonical_website:'.$site.'&_sourceInclude=_id,type,headlines.basic,publish_date,credits,canonical_website,label,websites,additional_properties,taxonomy.primary_section&size=100&from='.$from;
  return getResultListFromArc(makeArrayQuerie($query));
}  
?>