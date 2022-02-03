<?php
   require_once(ROOT_DIR.'/objects/article.php');
   require_once(ROOT_DIR.'/objects/source.php');
   require_once(ROOT_DIR.'/objects/analytics.php');
   require_once(ROOT_DIR.'/control/basicFunctions.php');
   require_once(ROOT_DIR.'/model/getDataFromGoogle.php');

   require_once(ROOT_DIR.'/model/conection-query-mysql.php');
   
   $jsonListSites = ROOT_DIR.'/data/sites.json';
 
   $listSites = json_decode(file_get_contents($jsonListSites),true);
   $jsonLogFolder = ROOT_DIR.'/logs/analytics/dailyPoll';
   //se deja a 35000 para que abarque 3 días aprox (igualmente puede exeder ese monto hasta el momento el máximo obserbado es de 14000)
   $googleLimitRequest=32000;//50000;
   $qtyLimitDayBack=10;

   //verifica cuantas request quedan por hacer
   //retorna un array con status (true or false) y otro con totalQty (sumatoria de fechas actuales)
   //la fecha es el nombre del directorio
   //folder url es la fecha de la consulta
   //los array deben tener una casilla con el nombre total y ahí indicar la sumatoria de todos los sitios
   function verifyQtyQueryByDay($folderUrl){
      global $googleLimitRequest;
      $statusJson = $folderUrl.'/status.json';
      if(file_exists($statusJson)){
         $totalPoll = 0;
         $decodeLog = json_decode(file_get_contents($statusJson),true);
         foreach ($decodeLog as $key => $value) {
            $totalPoll += $value['qty'];
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

   //order Date array
   function date_sort($a, $b) {
      return strtotime($a) - strtotime($b);
   }
   function orderArrayDate($arr){
      usort($arr,"date_sort");
      return($arr);
   }
   
   // se debe ingresar el status.json
   // retorna el dia a buscar en la siguiente iteracion
   function dateOrderList($arr){
      $todayDates = array();
         foreach($arr as $key => $itemValue){
            array_push($todayDates,$key);
         }
         return orderArrayDate($todayDates);
   }

   function getNextDayTosearch($day,$dateFolder){
      global $jsonLogFolder, $googleLimitRequest;
      $yesterday = $jsonLogFolder.'/'.date('Y-m-d',strtotime($dateFolder.' -1 days'));
      $fileLogStatus = $jsonLogFolder.'/'.$dateFolder.'/status.json';
      $checkToday = json_decode(file_get_contents($fileLogStatus),true);
      $checkDate = dateOrderList($checkToday)[0];
      $next = true;
      $total = 0;
      foreach($checkToday as $value){
         $total += $value['qty'] - $value['progres'];
         if($value['ready']===false){$next = false;}
      }
      if(file_exists($yesterday.'/status.json')){
         echo('existe archivo de ayer<br>');
         $checkYesterday = json_decode(file_get_contents($yesterday.'/status.json'),true);
         $tempDate = dateOrderList($checkYesterday)[0];
         if(strtotime($tempDate) < strtotime($checkDate)){
            $checkDate = $tempDate;
         }
         foreach($checkYesterday as $key => $value){
            //print_r($value); 
            //print_r($value,'<hr/>');
            if($value['ready']===false){$next = false;}
            if($value['qty'] > $value['progres'] && !array_key_exists($key,$checkToday)){
               $checkToday[$key] = $value;
               if(!copy($yesterday.'/'.$key.'.json', $jsonLogFolder.'/'.$dateFolder.'/'.$key.'.json')){
                  echo("error al copiar $yesterday.'/'.$key.'.json'...\n");
                  return false;
               }
               checkFileOrJsonCreate($fileLogStatus,$checkToday);
               echo('Se graba exitosamente archivo incompleto del día anterior.');
               return $key;
            }
         }
      }
      if($next && $total < $googleLimitRequest){
         return(date('Y-m-d',strtotime($checkDate.' -1 days')));
      }
      return false;
   }

   function createLogOneDayTenDaysAgo($day,$jsonName,$today){
      global $qtyLimitDayBack,$listSites,$googleLimitRequest,$jsonLogFolder;
      $startTime = microtime(true);
      $start = date('Y-m-d',strtotime($day));
      $dateOfChecks = array();
      $dateOfChecks['total'] = 0;
      $fileRoute=$jsonLogFolder.'/'.$today;
      $fileLogStatus=$fileRoute.'/status.json';
      $checkStatus = verifyQtyQueryByDay($fileRoute);
      $tempStatus = array();
      
      if(file_exists($fileLogStatus)){
         $tempStatus = json_decode(file_get_contents($fileLogStatus),true);
      }
      if(!array_key_exists($day,$tempStatus)){
         $tempStatus[$day] = ['qty'=>0,'progres'=>0,'ready'=>false,'current'=>false];
      }
      
      if($checkStatus['status']){
         if(!$tempStatus[$day]['ready']){
            for ($i=0; $i <= $qtyLimitDayBack ; $i++) { 
               $currentDay = date('Y-m-d', strtotime($start.' -'.$i.' days'));
               $dateOfChecks[$start][$currentDay] = array();
               foreach ($listSites as $key => $value) {
                  $resultArticleListByDay = getArticleListFromPublimetroByDay($key,$currentDay);
                  $qtyQueries = count($resultArticleListByDay)>0?count($resultArticleListByDay):0;
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
               checkFileOrJsonCreate($fileLogStatus,$tempStatus);
            }
            $tempStatus[$day] = ['qty'=>$dateOfChecks['total'],'progres'=>0,'ready'=>true,'current'=>false];  
            checkFileOrJsonCreate($fileLogStatus,$tempStatus);
            $time_elapsed_secs = microtime(true) - $startTime;
            print_r(timeCount($time_elapsed_secs).' <= tiempo de ejecución despues del FOR.<br>');
            return false;
         }else{
            $dateSearch = getNextDayTosearch($day,$today);
            $testingCount=0;
            if($dateSearch!==false && $testingCount===0){
               echo('Día de busqueda =>'.$dateSearch.' <br> Carpeta donde busca =>'.$today.'<br> Contador =>'.$testingCount);
               print_r(createLogOneDayTenDaysAgo($dateSearch,$dateSearch.'.json',$today));
               echo('\n');
               $time_elapsed_secs = microtime(true) - $startTime;
               print_r(timeCount($time_elapsed_secs).' <= tiempo de ejecución.<br>');
               $testingCount++;
               return 'salida rara';
            }
         }
      }

      
   }

   function checkOrderAndCurrent($listDayAndStatus){
      $arr = array();
      foreach($listDayAndStatus as $k => $v){
         array_push($arr,$k);
      }

      
      function date_sort($a,$b) {
         return strtotime($a) - strtotime($b);
      }

      usort($arr, "date_sort");
      print_r($arr);
   }

   function automaticSearch(){

   }

   function searchOnGoogleOneDayTenDaysAgo($day,$jsonName,$today){
      global $qtyLimitDayBack,$qtyLimitDayBack,$listSites,$jsonLogFolder;
      $startTime = microtime(true);
      $fileRoute = $jsonLogFolder.'/'.$today;
      $fileLogRoute =$fileRoute.'/'.$jsonName;
      $fileLogStatus=$fileRoute.'/status.json';
      echo('<=========> ROUTE: '.$fileLogStatus.'<=========>\n');
      if(file_exists($fileLogRoute) && file_exists($fileLogStatus)){
         $listOfArticlesToSearch = json_decode(file_get_contents($fileLogRoute),true);
         $tempStatus = json_decode(file_get_contents($fileLogStatus),true);

         $time_elapsed_secs = microtime(true) - $startTime;
         
         if($tempStatus[$day]['ready']){
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
                                    if((is_object($result) || is_array($result)) && checkNotExistenceOfArticleAnalytics($result)){
                                       $listOfArticlesToSearch[$key][$dateNews][$id]['details'][$idArticle]['searchStatus'] = true;
                                       
                                       insertGoogleDataArticles($result);

                                       checkFileOrJsonCreate($fileLogRoute,$listOfArticlesToSearch);
                                       
                                       $tempStatus[$day]['progres'] += 1;
                                       $tempStatus[$day]['current']=true;  
                                       checkFileOrJsonCreate($fileLogStatus,$tempStatus);
                                    }
                                    $time_elapsed_secs = microtime(true) - $startTime;
                                    print_r($time_elapsed_secs.' <= tiempo de ejecución dentro del googleFOR.\n');
                                 }else{
                                    $time_elapsed_secs = microtime(true) - $startTime;
                                    print_r($time_elapsed_secs.' <= tiempo de ejecución.\n');
                                    return null;
                                 }
                              }
                           }
                        }
                     }
                  }
               }
            }
            if($tempStatus[$day]['progres'] >= $tempStatus[$day]['qty']){
               print_r('check status');
               $i = array_search($day,array_keys($tempStatus));
               $count=0;
               foreach ($tempStatus as $key => $value) {
                  if($count===$i  ){$tempStatus[$key]['current']=false;}
                  if($count===$i+1){$tempStatus[$key]['current']=true;}
                  $count++;
               }
               checkFileOrJsonCreate($fileLogStatus,$tempStatus);
            }
         }
      }
      $time_elapsed_secs = microtime(true) - $startTime;
      print_r($time_elapsed_secs.' <= tiempo de ejecución de funcion completa.\n');
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