<?php
   require_once(ROOT_DIR.'/objects/article.php');
   require_once(ROOT_DIR.'/objects/source.php');
   require_once(ROOT_DIR.'/objects/analytics.php');
   require_once(ROOT_DIR.'/control/basicFunctions.php');
   require_once(ROOT_DIR.'/model/getDataFromGoogle.php');
   
   $jsonListSites = ROOT_DIR.'/data/sites.json';
 
   $listSites = json_decode(file_get_contents($jsonListSites),true);
   $jsonLogFolder = ROOT_DIR.'/logs/analytics/dailyPoll';
   $googleLimitRequest=16000;//50000;
   $qtyLimitDayBack=10;

   //verifica cuantas request quedan por hacer
   //retorna un array con status (true or false) y otro con totalQty (sumatoria de fechas actuales)
   //la fecha es el nombre del directorio
   //folder url es la fecha de la consulta
   //los array deben tener una casilla con el nombre total y ahí indicar la sumatoria de todos los sitios
   function verifyQtyQueryByDay($folderUrl){
      global $googleLimitRequest;
      $fileList = array_diff(scandir($folderUrl), array('..', '.'));
      if(file_exists($folderUrl) && count($fileList)>0){
         $totalPoll = 0;
         foreach($fileList as $key => $value) {
            $decodeLog = json_decode(file_get_contents($folderUrl.'/'.$value),true);
            $totalPoll += $decodeLog['total'];
         }
         if($totalPoll<=$googleLimitRequest){
            return(array('status'=>true,'success'=> ($googleLimitRequest - $totalPoll)));
         }
         return(array('status'=>false,'error'=> 'Limite alcanzado, llevas ->'.$totalPoll.' de un total de: '.$googleLimitRequest.'.'));
      }
      return(array('status'=>true,'error'=>'No existe folder con la fecha ingresada.'));
   }

   //trae listado de articulos desde la base de datos de publimetro un día determinado
   function getArticleListFromPublimetroByDay($idSite,$date){
      $resultQuery = checkArticlesByDate($idSite,$date);
      $resultList = Array();
      if(array_key_exists('success',$resultQuery)){
         foreach ($resultQuery['success'] as $key => $value){
            $article = new article();
            $article->setId($value['id']);
            $article->setSite($value['site']);
            $article->setTitle($value['title']);
            $article->setUrl($value['url']);
            $article->setMainCategory($value['main_category']);
            $article->setAuthor($value['author']);
            $article->setPrimaySite($value['primary_site']);
            $article->setDatePublish($value['date_publish']);
            $article->setType($value['type']);
            array_push($resultList,$article);
         }
      }
      return $resultList;
   }

   // se debe ingresar un objeto de articulo
   // crear consulta y retornar dos objetos, uno: analytics (trae la info global) y source (trae detalles por source traffic)
   function googleGetInfo($objectArticle,$day){
      global $listSites;
      //el orden de los valores usuarios y page_views vienen determinados en el columnHeader de la respuesta de google, 
      // actualmente son user,page_view en ese orden, esto se repite en todas las respuestas desde la API
      $siteData = $listSites[$objectArticle->getSite()];
      $checkUrl = $siteData['relativeUrl']?'':$siteData['websiteDomain'];
      $view_id  = $siteData['metroId'];
      //se crea objeto para metricas
      $globalMetric = new analytics();
      $globalMetric->setBdId($objectArticle->getID($checkUrl.$objectArticle->getUrl()));
      $globalMetric->setIdArticle($objectArticle->getID());
      $globalMetric->setSite($objectArticle->getSite());
      $globalMetric->setDate($day);
      $expandDetails = getReport($view_id,$checkUrl.$objectArticle->getUrl(),$day);
      foreach ($expandDetails as $id => $report) {
         $dataSource = array();
         $detailsList = $report->getData()->getRows();
         $totalReports = $report->getData()->getTotals()[0];
         //$globalMetric->setUser($totalReports->getValues()[0]);
         $globalMetric->setPageView($totalReports->getValues()[1]);
         $dataSource = array();
         foreach ($detailsList as $key => $objectArticle) {
            $source = new Source();
            $source->setIdAnalytics($globalMetric->getBdId());
            $source->setSource($objectArticle->getDimensions()[0]);  
            $detailReport = $objectArticle->getMetrics()[0];
            $source->setUser($detailReport->getValues()[0]);        
            $source->setPageView($detailReport->getValues()[1]);
            array_push($dataSource,$source);
         }
         $globalMetric->setSourceDetails($dataSource);
      }
      return $globalMetric;
   }
   

   function createLogOneDayTenDaysAgo($day,$jsonName){
      global $qtyLimitDayBack,$listSites,$googleLimitRequest,$jsonLogFolder;
      $today = date('Y-m-d');
      $start = date('Y-m-d',strtotime($day));
      $dateOfChecks = array();
      $dateOfChecks['total'] = 0;
      $fileRoute=$jsonLogFolder.'/'.$today;
      $checkStatus = verifyQtyQueryByDay($fileRoute);
      if($checkStatus['status']){
         for ($i=0; $i <= $qtyLimitDayBack ; $i++) { 
            $currentDay = date('Y-m-d', strtotime($start.' -'.$i.' days'));
            $dateOfChecks[$start][$currentDay] = array();
            foreach ($listSites as $key => $value) {
               $resultArticleListByDay = getArticleListFromPublimetroByDay($key,$currentDay);
               $qtyQueries = count($resultArticleListByDay)>0?count($resultArticleListByDay):0;
               if(($checkStatus['success']+$qtyQueries)<=$googleLimitRequest){
                  if($qtyQueries>0){
                     $dateOfChecks[$start][$currentDay][$key]['qty'] = $qtyQueries;
                     $dateOfChecks[$start][$currentDay][$key]['details'] = $resultArticleListByDay;
                     $dateOfChecks['total'] += $qtyQueries;
                     if(array_key_exists('total',$dateOfChecks[$start][$currentDay])){
                        $dateOfChecks[$start][$currentDay]['total'] += $qtyQueries;
                     }else{
                        $dateOfChecks[$start][$currentDay]['total'] = $qtyQueries;
                     }
                     makeJsonLogByDate($fileRoute,$jsonName,$currentDay,$start,$dateOfChecks);
                  }
               }
            }
         }
      }
   }

   function searchOnGoogleOneDayTenDaysAgo($day,$jsonName){  
      global $qtyLimitDayBack,$qtyLimitDayBack,$listSites,$jsonLogFolder;
      $today = date('Y-m-d');
      $fileLogRoute=$jsonLogFolder.'/'.$today.'/'.$jsonName;
      if(file_exists($fileLogRoute)){
         $listOfArticlesToSearch = json_decode(file_get_contents($fileLogRoute),true);
         if(array_key_exists('total',$listOfArticlesToSearch) && $listOfArticlesToSearch['total']>0){
            $limitQueryBySeconds = 10;
            $count = 0;
            foreach($listOfArticlesToSearch as $key => $item){
               if($key !== 'total'){
                  foreach($item as $dateNews => $data){
                     foreach($data as $id => $value){
                        if($id !== 'total' && array_key_exists('details',$value)){
                           foreach($value['details'] as $idArticle => $article){
                              if(!$article['searchStatus']){
                                 $currentArticle = new article();
                                 $currentArticle->setSite($article['site']);
                                 $currentArticle->setId($article['id']);
                                 $currentArticle->setUrl($article['url']);
                                 $currentArticle->setSearchStatus($article['searchStatus']);
                                 if($count < $limitQueryBySeconds){
                                    $result = googleGetInfo($currentArticle,$day);
                                    $count++;
                                    if(is_object($result) || is_array($result)){
                                       $listOfArticlesToSearch[$key][$dateNews][$id]['details'][$idArticle]['searchStatus'] = true;
                                       print_r(insertGoogleDataArticles($result));
                                       checkFileOrJsonCreate($fileLogRoute,$listOfArticlesToSearch);
                                    }
                                 }else{
                                    return null;
                                 }
                              }
                           }
                        }
                     }
                  }
               }
            }
         }else{
            createLogOneDayTenDaysAgo($day,$jsonName);
         }
      }else{
         createLogOneDayTenDaysAgo($day,$jsonName);
      }
   }
   

   function iterationLogsWithRange($start,$end){
      global $jsonLogFolder;
      $today = date('Y-m-d');
      $startDate = date_create($start);
      $endDate = date_create($end);
      $qtyDays = ((array) date_diff($startDate,$endDate))['days'];
      for($count=0 ; $count <= $qtyDays ; $count++){
         if(verifyQtyQueryByDay($jsonLogFolder.'/'.$today)['status']){
            $currentDay = date('Y-m-d', strtotime($start.' -'.$count.' days'));
            createLogOneDayTenDaysAgo($currentDay,$currentDay.'.json');
         }
      }
      //echo(file_get_contents($jsonLogFolder.'/'.date('Y-m-d').'/historic.json'));
   }

   //recibe un array con una estructura que contiene fechas y dentro de ellas, las fechas consultadas en cada una 
   function makeJsonLogByDate($urlJson,$nameJson,$dateGroup,$dateSearch,$listSitesWithQty=null){
      $countBySite = array();
      $fileRoute = $urlJson.'/'.$nameJson;
      if(!file_exists($fileRoute)){
         createFolder($urlJson);
         foreach ($listSitesWithQty as $key => $value) {
            $countBySite[$dateGroup][$dateSearch][$key]['qty']=0;
         }
         $countBySite[$dateGroup][$dateSearch]['total']=0;
         $countBySite['total']=0;
      }else if(!array_key_exists('error',$listSitesWithQty)){
         $countBySite = $listSitesWithQty;
      }
      checkFileOrJsonCreate($fileRoute,$countBySite);
      return $countBySite;
   }
?>