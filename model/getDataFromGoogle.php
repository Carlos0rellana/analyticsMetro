<?php
require_once ROOT_DIR.'/vendor/autoload.php';

function initializeAnalytics(){
  $client = new Google_Client();
  $client->setApplicationName("CheckingAnalytics");
  $client->setAuthConfig(ROOT_DIR.'/config/publimetroChecking.json');
  $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
  $analytics = new Google_Service_AnalyticsReporting($client);
  return $analytics;
}

function getReport($view_id,$url,$start,$end=null){
  $analytics = initializeAnalytics();
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
  return $analytics->reports->batchGet( $body );
}


