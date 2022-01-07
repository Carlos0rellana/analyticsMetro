<?php
    require_once(ROOT_DIR.'/model/conection-query.php');
    require_once(ROOT_DIR.'/model/conection-query-mysql.php');
    require_once(ROOT_DIR.'/control/basicFunctions.php');

    function typeOfArticle($value){
        switch ($value){
            case "story":
                $a='s';
                break;
            case "gallery":
                $a='g';
                break;
            case "video":
                $a='v';
                break;
            case "redirect":
                $a='r';
                break;
            case "collection":
                $a='c';
                break;
            default:
                $a='0';
        }
        return $a;
    }
    function dataIteration($websiteList,$mainWebsite,$currentSite=null){
        if(is_array($websiteList)){
            $website = array();
            $website['url'] = $websiteList['website_url'];
            $website['main_category'] = null;
            $website['site'] = $currentSite;
            $website['primary_site'] = 0;
            if(array_key_exists('website_section',$websiteList)){
                $website['main_category'] = $websiteList['website_section']['_id'];
                if($website['site'] === $mainWebsite){
                    $website['primary_site'] = 1;
                }
            }
            return $website;
        }
        return false;
    }
    function getAuthorFromArticles($type,$dataArticle){
        $currentAuthorName ='';
        switch ($type) {
            case "story":
                if(
                    array_key_exists('label',$dataArticle) &&
                    array_key_exists('data_layer_mail',$dataArticle['label']) &&
                    array_key_exists('text',$dataArticle['label']['data_layer_mail'])
                ){
                    $currentAuthorName = $dataArticle['label']['data_layer_mail']['text'];
                }elseif(
                    array_key_exists('credits',$dataArticle) &&
                    array_key_exists('by',$dataArticle['credits']) &&
                    count($dataArticle['credits']['by']) > 0
                ){
                    $qtyAuthors = count($dataArticle['credits']['by']);
                    foreach($dataArticle['credits']['by'] as $key => $authorItem){
                        if(
                            array_key_exists('type',$authorItem) &&
                            $authorItem['type'] === 'author'
                        ){
                            if(
                                array_key_exists('additional_properties',$authorItem) &&
                                array_key_exists('original',$authorItem['additional_properties']) &&
                                array_key_exists('byline',$authorItem['additional_properties']['original'])
                            ){
                                if($qtyAuthors>1){$currentAuthorName .= ',';}
                                $currentAuthorName .= $authorItem['additional_properties']['original']['byline'];
                            }elseif(array_key_exists('name',$authorItem)){
                                if($qtyAuthors>1){$currentAuthorName .= ',';}
                                $currentAuthorName .= $authorItem['name'];
                            }
                        }
                    }
                }
                break;
            case "gallery":
                if( 
                    array_key_exists('additional_properties',$dataArticle) && 
                    array_key_exists('owner',$dataArticle['additional_properties'])
                ){
                    $currentAuthorName = $dataArticle['additional_properties']['owner'];
                }elseif(
                    array_key_exists('credits',$dataArticle) && 
                    array_key_exists('by',$dataArticle['credits']) && 
                    count($dataArticle['credits']['by'])>0 &&
                    array_key_exists('byline',$dataArticle['credits']['by'][0])
                ){
                    $currentAuthorName = $dataArticle['credits']['by'][0]['byline'];
                }
                break;
            case "video":
                if( 
                    array_key_exists('additional_properties',$dataArticle) && 
                    array_key_exists('firstPublishedBy',$dataArticle['additional_properties']) &&
                    array_key_exists('email',$dataArticle['additional_properties']['firstPublishedBy'])
                ){
                    $currentAuthorName = $dataArticle['additional_properties']['firstPublishedBy']['email'];
                }
                break;
        }
        return $currentAuthorName;
    }
    function makeAarticle($findArticle,$site){
        if(is_array($findArticle)){
            $articlesListResults = array();
            $fixValue = array();
            $fixValue['id']=$findArticle['_id'];
            $fixValue['type']= typeOfArticle($findArticle['type']);
            $fixValue['title']=$findArticle['headlines']['basic'];
            $fixValue['date_publish']=$findArticle['publish_date'];
            $fixValue['author']=getAuthorFromArticles($findArticle['type'],$findArticle);
            foreach($findArticle['websites'] as $key => $sitesDetails){
                $tempValues = $fixValue + dataIteration($sitesDetails,$findArticle['canonical_website'],$key);
                array_push($articlesListResults,$tempValues);
            }
            return $articlesListResults;
        }
        return false;
    }
    function countAuthors($articleList){
        if(is_array($articleList)){
            $result = array();
            foreach($articleList as $key => $article){
                $authorList = $article['credits']['by'];
                if(is_array($authorList )){
                    foreach($authorList as $k => $author){
                        $authorData=array();
                        if(array_key_exists('_id',$author)){
                            $authorData['id']=$author['_id'];
                        }else{
                            $authorData['id']=$author['name'];
                        }
                        if(array_key_exists('name',$author)){
                            $authorData['name']=$author['name'];
                        }else{
                            $authorData['name']=$author['_id'];
                        }
                        $authorData['qty']=1;
                        $findValue=array_search($authorData['id'], array_column($result,'id'));
                        if($findValue===false){
                            array_push($result,$authorData);
                        }else{
                            $result[$findValue]['qty']=$result[$findValue]['qty']+1;
                        }
                    }
                }
            }
            return $result;
        }else{
            return 'No se ingreso un array';
        }
        
    }
    function simpleArticlesList($site,$start,$end,$from=0){
        $arcQuery = json_decode(getSearchListArticles($site,$start,$end,$from),true);
        if(is_array($arcQuery) && array_key_exists('content_elements',$arcQuery)){
            $articlesList = array();
            $articlesList['list'] = array();
            $articlesList['totalResults']=$arcQuery['count'];
            foreach($arcQuery['content_elements'] as $item){
                $tempArticle = makeAarticle($item,$site);
                if($tempArticle){
                    array_push($articlesList['list'],$tempArticle);
                }
            }
            return $articlesList;
        }
        return array("error"=>$arcQuery);
    }
    function bucleArticlesList($site,$start,$end){
        $result = simpleArticlesList($site,$start,$end,0);
        if(!array_key_exists('error',$result)){
            if(array_key_exists('totalResults',$result) && array_key_exists('list',$result)){
                if($result['totalResults']<=100){
                    $result['countArrayList']=count($result['list']);
                    return $result;
                }else{
                    $tempListArticles = $result['list'];
                    $iteration = intval($result['totalResults']/100);
                    for ($i=1; $i <= $iteration ; $i++) {
                        $next = $i * 100;
                        $tempResult = simpleArticlesList($site,$start,$end,$next);
                        $tempListArticles = array_merge($tempListArticles,$tempResult['list']);
                    }
                    $result['list'] = $tempListArticles;
                    $result['countArrayList']=count($result['list']);
                    return $result;
                }
            }
        }
        return $result;
    }
    function countResults($site,$start,$end,$exc){
        $arcQuery = json_decode(getSearchList($site,$start,$end,$exc),true);
        $result = array();
        $result['site']=$site;
        if($arcQuery['count']==0){
            $result['error']='No se encontraron articulos para '.$site;
            return $result;
        }
        $result['qty'] = $arcQuery['count'];
        $result['rank'] = countAuthors($arcQuery['content_elements']);
        return $result;
    }
    function bucleCounts($start,$end,$exc){
        $arcQuery = json_decode(getSitesList(),true);
        $result = array();
        if(!validateDate($start) && !validateDate($end) ){
            $result['error'] = 'Fecha incorrectas (YYYY-MM-DD).';
            return $result;
        }
        foreach($arcQuery as $item){
            array_push($result,countResults($item['_id'],$start,$end,$exc));
        }
        return $result;
    }
    function bucleSaveArticlesByDay($site,$date){
        $mounthList = bucleArticlesList($site,$date,$date)['list'];
        $returnValue = array();
        foreach($mounthList as $key => $articlesGroup){
            foreach($articlesGroup as $article){
                array_push($returnValue,saveAarticleData($article));
            }
        }
        return $returnValue;
    }
    function bucleSaveArticlesByMounth($site,$mounth,$year){
        $returnValue = array();
        $mounthFormat = $mounth<10 && substr($mounth,0,1)!=='0' ? '0'.$mounth:$mounth;
        $qtyDays = cal_days_in_month(CAL_GREGORIAN,$mounthFormat,$year);
        for ($i=1; $i <= $qtyDays; $i++) {
            $day = $i<10? '0'.$i:$i;
            $date = $year.'-'.$mounthFormat.'-'.$day;
            $mounthDay = bucleSaveArticlesByDay($site,$date);
            $returnValue[$date]=$mounthDay;
        }
        return $returnValue;
    }
    function bucleSaveArticlesByYear($site,$year){
        $returnValue = array();
        for ($i=1; $i <= 12; $i++) {
            $mounthDay = bucleSaveArticlesByMounth($site,$i,$year);
            $returnValue['mounth-'.$i]=$mounthDay;
        }
        return $returnValue;
    }
    function bucleSaveArticlesByYearAllSites($site,$year){
        $returnValue = array();
        $data = json_decode(file_get_contents(ROOT_DIR.'/data/sites.json'), true);
        foreach ($data as $key => $value) {
            if($value['status']===true){
                $yearOfArticles = bucleSaveArticlesByYear($value['id'],$year);
                $returnValue[$value['id']][$year]=$yearOfArticles;
            }
        }
        return $returnValue;
    }
    function autoBucleWithLimit($limitDate,$urlJsonLog = ROOT_DIR.'/logs/date-log.json',$listSitesJson = ROOT_DIR.'/data/sites.json',$start='- 1 days'){
        $urlLogsites = checkFileOrJsonCreate($urlJsonLog,array("years" => array()));
        $siteList = json_decode(file_get_contents($listSitesJson),true);
        $yearLogs = json_decode(file_get_contents($urlLogsites),true);
        if(count($yearLogs['years'])>0){
            $currentYear = array_key_last($yearLogs['years']);
            $currentMonth = array_key_last($yearLogs['years'][$currentYear]);
            $currentDay = array_key_last($yearLogs['years'][$currentYear][$currentMonth]);
            $currentDate = $currentYear.'-'.$currentMonth.'-'.$currentDay;
            $lastSiteCheck = array_key_last($siteList);
            foreach ($siteList as $key => $site){
                if($site['status']===true){
                    if(!in_array($key,$yearLogs['years'][$currentYear][$currentMonth][$currentDay])){
                        array_push($yearLogs['years'][$currentYear][$currentMonth][$currentDay],$key);
                        bucleSaveArticlesByDay($key,$currentDate);
                        writeFile($urlLogsites,json_encode($yearLogs));
                        return json_decode(file_get_contents($urlLogsites),true);
                    }
                }
                if($lastSiteCheck === $key){
                    $yesterday = date("Y-m-d",strtotime($currentDate.$start));
                    $limit = gmdate("Y-m-d",strtotime($limitDate));
                    if($yesterday >= $limit){
                        if(date("Y",strtotime($currentDate)) !== date("Y",strtotime($yesterday))){
                            $currentYear = date("Y",strtotime($yesterday));
                            $yearLogs['years'][$currentYear]=array();
                        }
                        if(date("m",strtotime($currentDate)) !== date("m",strtotime($yesterday))){
                            $currentMonth = date("m",strtotime($yesterday));
                            $yearLogs['years'][$currentYear][$currentMonth]=array();
                        }
                        $yearLogs['years'][$currentYear][$currentMonth][date('d',strtotime($yesterday))] = array();
                        writeFile($urlLogsites,json_encode($yearLogs));
                        return json_decode(file_get_contents($urlLogsites),true);
                    }
                }

            }
            return json_decode(file_get_contents($urlLogsites),true);
        }else{
            $currentDate = date('Y-m-d');
            $yearLogs['years'][date("Y")]=array();
            $yearLogs['years'][date("Y")][date("m")]=array();
            $yearLogs['years'][date("Y")][date("m")][date("d",strtotime($currentDate."- 2 days"))]=array();
            writeFile($urlLogsites,json_encode($yearLogs));
        }
        return count($yearLogs['years']);
    }
    function autoBucleYesterdayMode(){
        
        $currentDate = date("Y-m-d",strtotime(date('Y-m-d')."- 1 days"));
        $urlLogsites = checkFileOrJsonCreate(ROOT_DIR.'/data/'.$currentDate.'.json',array("years" => array()));
        $siteList = json_decode(file_get_contents(ROOT_DIR.'/data/sites.json'),true);
        $yearLogs = json_decode(file_get_contents($urlLogsites),true);

        if(count($yearLogs['years'])>0){
            $lastSiteCheck = array_key_last($siteList);
            foreach ($siteList as $key => $site){
                if($site['status']===true){
                    if(!in_array($key,$yearLogs['years'][$currentDate])){
                        array_push($yearLogs['years'][$currentDate],$key);
                        bucleSaveArticlesByDay($key,$currentDate);
                        writeFile($urlLogsites,json_encode($yearLogs));
                        return json_decode(file_get_contents($urlLogsites),true);
                    }
                }
            }
            return json_decode(file_get_contents($urlLogsites),true);
        }else{
            $yearLogs['years'][$currentDate]=array();
            writeFile($urlLogsites,json_encode($yearLogs));
        }
        return count($yearLogs['years']);
    }
    function autoBucleLastWeekMode(){
        $currentWeek = date("W",strtotime(date('Y-m-d')."- 4 days"));
        $endDate = date("Y-m-d",strtotime(date('Y-m-d')."- 9 days"));
        autoBucleWithLimit($endDate,ROOT_DIR.'/logs/w__'.$currentWeek.'.json',ROOT_DIR.'/data/week_sites.json');
        return 'End data count: '.$endDate;
    }
?>