<?php
    function conection($query,$local=false){
        require ROOT_DIR.'/config/dataConection.php';
        $conn = mysqli_connect($servername,$username,$password,$database);
        if($local){$conn = mysqli_connect($servernameLocal,$usernameLocal,$passwordLocal,$databaseLocal);}
        $msj = array();
        if (!$conn) {
            die("Connection failed: " . mysqli_connect_error());
        }
        if(mysqli_query($conn,$query)) {
            $msj['success'] = mysqli_fetch_all(mysqli_query($conn,$query),MYSQLI_ASSOC);
        } else {
            $msj['error'] = $query . "<br>" . mysqli_error($conn);
        }
        mysqli_close($conn);
        return $msj;
    }

    function conectionCustom($query,$local=false){
        require ROOT_DIR.'/config/dataConection.php';
        $mysqli = new mysqli($servername,$username,$password,$database);
        if($local){$mysqli = new mysqli($servernameLocal,$usernameLocal,$passwordLocal,$databaseLocal);}
        /* check connection */
        if (mysqli_connect_errno()) {
            printf("Error de conexión: %s\n", mysqli_connect_error());
            exit();
        }
        $msj = array();
        $mysqli->query($query);
        if($mysqli) {
            $msj['success'] = $mysqli->insert_id;
        } else {
            $msj['error'] = $query . "<br>" . mysqli_error($conn);
        }
        $mysqli->close();
        return $msj;
    }

    function saveAarticleData($articleData){
        if(is_array($articleData)){
            if(
                array_key_exists('id',$articleData) &&
                array_key_exists('site',$articleData) &&
                array_key_exists('title',$articleData) &&
                array_key_exists('url',$articleData) &&
                array_key_exists('main_category',$articleData) &&
                array_key_exists('author',$articleData) &&
                array_key_exists('primary_site',$articleData) &&
                array_key_exists('date_publish',$articleData) &&
                array_key_exists('type',$articleData)){
                    require ROOT_DIR.'/config/dataConection.php';
                    $conn = mysqli_connect($servername, $username, $password, $database);
                    $date = date('Y-m-d H:i:s', strtotime($articleData['date_publish']));
                    $title = $conn->real_escape_string($articleData['title']);
                    $category = $conn->real_escape_string($articleData['main_category']);
                    $author = $conn->real_escape_string($articleData['author']);
                    $query = "INSERT INTO article (id,site,title,url,main_category,author,primary_site,date_publish,type) VALUES ('".$articleData['id']."','".$articleData['site']."','".$title."','".$articleData['url']."','".$category."','".$author."','".$articleData['primary_site']."','".$date."','".$articleData['type']."')";
                    if(!$conn){
                        die("Connection failed: " . mysqli_connect_error());
                    }
                    if (mysqli_query($conn, $query)) {
                        $msj['success'] = "New record created successfully";
                    } else {
                        $msj['error']['query'] = $query;
                        $msj['error']['mysqlError'] = mysqli_error($conn); 
                    }
                    mysqli_close($conn);
                    return $msj;
            }else{
                $error['error']='faltan(';
                if(!array_key_exists('id',$articleData)){$error.='id , ';}
                if(!array_key_exists('site',$articleData)){$error.='site , ';}
                if(!array_key_exists('title',$articleData)){$error.='title , ';}
                if(!array_key_exists('url',$articleData)){$error.='url , ';}
                if(!array_key_exists('main_category',$articleData)){$error.='main_category , ';}
                if(!array_key_exists('author',$articleData)){$error.='author , ';}
                if(!array_key_exists('primary_site',$articleData)){$error.='primary_site , ';}
                if(!array_key_exists('date_publish',$articleData)){$error.='date_publish , ';}
                if(!array_key_exists('type',$articleData)){$error.='type , ';}
                return $error.=')';
            }
            
        }else{
            return array('error'=>'no se ingresa un array de artículo');
        }
           
    }
    function checkArticlesByDate($idSite,$date){
        $sql = "select  id, site, title, url, main_category, author, primary_site, date_publish, type  from article where date_publish  between '".$date." 00:00:00' and '".$date." 23:59:59' and site='".$idSite."' order by date_publish asc;";
        return conection($sql);
    }

    function insertGoogleDataArticles($analytics){
        require_once(ROOT_DIR.'/objects/source.php');
        require_once(ROOT_DIR.'/objects/analytics.php');
        $sql = 'insert into analytics (id_article,site,date,page_view) values ("'.$analytics->getIdArticle().'","'.$analytics->getSite().'","'.$analytics->getDate().'","'.$analytics->getPageView().'");';
        $tempCurrent = conectionCustom($sql);
        $sourceSet = array();
        $currentId = array_key_exists('success',$tempCurrent)? $tempCurrent['success']:false;
        $sourceList = $analytics->getSourceDetails();
         
        echo('================================\n');
        echo($tempCurrent);
        echo('================================\n');
        
        if($currentId!==false && count($sourceList)>0){
            $sourceList = $analytics->getSourceDetails();
            foreach($sourceList as $key => $value){
                $sqlForSources = 'insert into traffic_source (id_analytics,source,page_view,user) values ("'.$currentId.'","'.$value->getSource().'","'.$value->getPageView().'","'.$value->getUser().'");';    
                $sourceSet[$key]=conectionCustom($sqlForSources);
            }
        }
        
        return(array($tempCurrent,$sourceSet));
    }

    //insert into analytics (id_article,site,date,page_view) values ("ZNCROCOKMRCGNKPOIZVNIMMFSM","novamulher","2022-01-30","0");
?>

