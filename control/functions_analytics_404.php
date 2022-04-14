<?php 
   require_once(ROOT_DIR.'/control/basicFunctions.php');
   require_once(ROOT_DIR.'/model/getDataFromGoogle.php');
   require_once(ROOT_DIR.'/model/curlQuery.php');
   
   $jsonListSites = ROOT_DIR.'/data/sites.json';
   $listSites = json_decode(file_get_contents($jsonListSites),true);

   function getNotFoundList($site,$verifiHttpStatus=false){
        global $listSites;
        $siteData = $listSites[$site];
        $view_id  = $siteData['metroId'];
        $urlBase = $siteData['websiteDomain'];
        $expandDetails = getNotFound($view_id);
        $resultData = array();
        foreach ($expandDetails as $id => $report) {
               $currentData = array();
               $currentData['title_filter'] =$report[1];
               $currentData['site_id'] = $site;
               $currentData['url']  =$report[0];
               $currentData['complete-url'] = $urlBase.$currentData['url'];
               $currentData['views']=$report[2];
               if($verifiHttpStatus){
                   $currentData['http-status'] = httpResponses($completeUrl);
               }
               array_push($resultData,$currentData);
        }
        return $resultData;
   }
?>