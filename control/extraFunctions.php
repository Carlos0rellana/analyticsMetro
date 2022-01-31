<?php
    require_once(ROOT_DIR.'/model/conection-query.php');
    $data = json_decode(file_get_contents(ROOT_DIR.'/data/sites.json'), true);

    function getMonthResults($query_date,$site,$author=false){
        $firstDate = date('Y-m-01', strtotime($query_date));
        $lastDate  = date('Y-m-t', strtotime($firstDate));
        $searchAuthor = $author?"+AND+credits.by.name:".$author:'';
        $query="/content/v4/search/published?_sourceInclude=_id,headlines.basic,publish_date,credits,canonical_website,publish_date,type&q=publish_date:%5B".$firstDate."+TO+".$lastDate."%5D+AND+type:story".$searchAuthor."&website=".$site."&sort=publish_date:asc";
        $arrayResults = json_decode(getSearchListArticlesOpenQuery($query));
        return $arrayResults?$arrayResults->count:0;
    }

    function bucleMonth($year,$site){
        $results = array();
        for ($i=1; $i <= 12 ; $i++) { 
            $month = $i<10? '0'.$i:$i;
            $query_date = $year.'-'.$month;
            $currentCheck = array();
            $currentCheck['date'] = $query_date;
            $currentCheck['personare'] = getMonthResults($query_date,$site,'Personare');
            $currentCheck['europress'] = getMonthResults($query_date,$site,'Europa%20Press');
            $currentCheck['total'] = getMonthResults($query_date,$site);
            $currentCheck['diff'] = $currentCheck['total'] - ($currentCheck['europress'] + $currentCheck['personare']);
            array_push($results,$currentCheck);
        }
        return $results;
    }

    function makeHtmlTable($results,$site){
        $trResults = '';
        foreach ($results as $key => $value) {
            $trResults .= tableRowGenerator($value);
        }
        return tableDisplayHTML($trResults,$site); 
    }

    function tableDisplayHTML($trList,$site){
        $html =
        '
        <h1>'.$site.'</h1>
        <table>
          <thead>
            <tr>
              <th>Mes</th>
              <th>Articulos Sin Per/Eur</th>
              <th>Articulos Personare</th>
              <th>Articulos Euro Press</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            '.$trList.'
          </tbody>
        </table>';
        return $html;
    }

    function tableRowGenerator($queryResults){
        return 
        '<tr>
            <td>'.$queryResults['date'].'</td>
            <td>'.$queryResults['diff'].'</td>
            <td>'.$queryResults['personare'].'</td>
            <td>'.$queryResults['europress'].'</td>
            <td>'.$queryResults['total'].'</td>
        </tr>';
    }
?>