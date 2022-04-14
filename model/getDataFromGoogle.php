<?php
require_once ROOT_DIR.'/vendor/autoload.php';
require_once ROOT_DIR.'/model/curlQuery.php';

function initializeAnalytics(
    $json = ROOT_DIR.'/config/publimetroChecking.json',
    $name='CheckingAnalytics'
  ){
      $client = new Google_Client();
      $client->setApplicationName($name);
      $client->setAuthConfig($json);
      $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);  
      return $client;
}

function getReport($view_id,$url,$start,$end=null){
  $analytics = new Google_Service_AnalyticsReporting(initializeAnalytics());
  // Create the DateRange object.
  if($end === null){$end = $start;}
  $dateRange = new Google_Service_AnalyticsReporting_DateRange();
  $dateRange->setStartDate($start);
  $dateRange->setEndDate($end);

  // Create the Metrics object.
  //$sessions = new Google_Service_AnalyticsReporting_Metric();
  //$sessions->setExpression("ga:sessions");
  //$sessions->setAlias("Sesiones");
  $users = new Google_Service_AnalyticsReporting_Metric();
  $users->setExpression("ga:users");
  $users->setAlias("users");
  $pageviews = new Google_Service_AnalyticsReporting_Metric();
  $pageviews->setExpression("ga:pageviews");
  $pageviews->setAlias("page_views");

  // Create the segment dimension.
  $segmentSources = new Google_Service_AnalyticsReporting_Dimension();
  $segmentSources->setName("ga:source");
  $segmentPath    = new Google_Service_AnalyticsReporting_Dimension();
  $segmentPath->setName("ga:pagePath");

  //creacion de filtro
  // Create Dimension Filter.
  $dimensionFilter = new Google_Service_AnalyticsReporting_SegmentDimensionFilter();
  $dimensionFilter->setDimensionName("ga:pagePath");
  $dimensionFilter->setOperator("EXACT");
  $dimensionFilter->setExpressions(array($url));
  // Create the DimensionFilterClauses
  $dimensionFilterClause = new 
  Google_Service_AnalyticsReporting_DimensionFilterClause();
  $dimensionFilterClause->setFilters(array($dimensionFilter));
  

  // Create the ReportRequest object.
  $request = new Google_Service_AnalyticsReporting_ReportRequest();
  $request->setViewId($view_id);
  $request->setDateRanges($dateRange);
  $request->setDimensions(array($segmentSources,$segmentPath));
  $request->setDimensionFilterClauses(array($dimensionFilterClause));
  $request->setMetrics(array(/*$sessions,*/$users,$pageviews));

  $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
  $body->setReportRequests( array( $request) );
  return $analytics->reports->batchGet($body);
}

function getNotFound($view_id){
  $json = ROOT_DIR.'/config/analytics-404-realtime.json';
  $name='analytics-404-realtime';
  $analytics = new Google_Service_Analytics(initializeAnalytics($json,$name));
  $optParams = [
    'dimensions'=>'rt:pagePath,rt:pageTitle',
    'filters'=>'rt:pageTitle==404',
    'max-results'=>10000,
    'sort'=>'-rt:pageviews'
  ];

  $results = $analytics->data_realtime->get('ga:'.$view_id,'rt:pageviews',$optParams);
  return($results->getRows());
}