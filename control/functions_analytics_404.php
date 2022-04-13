<?php 
   require_once(ROOT_DIR.'/control/basicFunctions.php');
   require_once(ROOT_DIR.'/model/getDataFromGoogle.php');
   require_once(ROOT_DIR.'/model/curlQuery.php');
   
   $jsonListSites = ROOT_DIR.'/data/sites.json';
   $listSites = json_decode(file_get_contents($jsonListSites),true);

   function getNotFoundList($site,$start,$end=false,$verifiHttpStatus=false){
        global $listSites;
        $siteData = $listSites[$site];
        $view_id  = $siteData['metroId'];
        $urlBase = $siteData['websiteDomain'];
        if($start!==false){$end=$start;}
        $expandDetails = getNotFound($view_id,$start,$end);
        $resultData = array();
        foreach ($expandDetails as $id => $report) {
            $detailsList = $report->getData()->getRows();
            //print_r($report);
            foreach ($detailsList as $key => $objectArticle) {
               $currentData = array();
               $currentData['title_filter'] =$objectArticle['dimensions'][0];
               $currentData['site_id'] = $site;
               $currentData['url']  =$objectArticle['dimensions'][1];
               $completeUrl = $urlBase.$currentData['url'];
               $detailReport = $objectArticle->getMetrics()[0];
               $currentData['complete-url'] = $completeUrl;
               $currentData['views']=$detailReport->getValues()[0];
               if($verifiHttpStatus){
                   $currentData['http-status'] = httpResponses($completeUrl);
               }
               
               array_push($resultData,$currentData);
            }
        }
        return $resultData;
   }
?>