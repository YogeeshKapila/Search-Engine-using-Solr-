<?php

include 'SpellCorrector.php';
include 'simple_html_dom.php';

ini_set('memory_limit', -1);
ini_set('display_errors',1);

$limit = 10;
$query = isset($_REQUEST['q']) ? trim($_REQUEST['q']) : false;
              $isSpellCheck = true;
if(isset($_REQUEST['spellcheck']))
            { 
            }
$results = false;
if ($query) {
    require_once('/home/yogi/IRHW4/solr-php-client-master/Apache/Solr/Service.php');
    //require_once('SpellCorrector.php');
    $solr = new Apache_Solr_Service('localhost', 8983, '/solr/test/');

    $splitQueries = explode(" ", $query);
    $corrector = "";

    if(isset($_REQUEST['spellcheck']))
        { 
         $spell = false;
     }
     else
     {
        $spell = true;
     }

    foreach ($splitQueries as $splitValue) {
        
        if($spell)
        {
         $corrector .= SpellCorrector::correct($splitValue);
         $corrector .= " ";
        }

    }
    
    if ($corrector == $query) {
        $corrector = "";
    }

    if (get_magic_quotes_gpc() == 1) {
        $query = stripslashes($query);
    }

    $additionalparams=[];
    if(array_key_exists("algoselect", $_REQUEST) && $_REQUEST["algoselect"]=="pagerank") {
        $additionalparams['sort']="pagerankfile desc";
    }
        
    if ($corrector!== '' && strtolower(trim($corrector)) !== strtolower(trim($query))) {
    $results = $solr->search($corrector, 0, $limit,$additionalparams);
   }
   else
   {
    $results = $solr->search($query, 0, $limit,$additionalparams);
   }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PHP Solr Client</title>
    <link rel="stylesheet" href="jquery-ui.css">
    <script src="jquery-1.12.4.js"></script>
    <script src="jquery-ui.js"></script>
</head>
<body>
    <form accept-charset="utf-8" method="get">
        <label for="q">Search:</label>
        <input id="q" name="q" type="text" value="<?php echo htmlspecialchars($query, ENT_QUOTES, 'utf-8'); ?>"/>
        <input type="submit"/>
        <br>

        <input type="radio" name="algoselect" value="lucene"  <?php if ((!isset($_REQUEST['algoselect'])) || isset($_REQUEST['algoselect']) && $_REQUEST['algoselect'] == 'lucene')  echo ' checked="checked"';?> >SEARCH USING LUCENE
  
     
    </form>

        
        <?php
 echo "<br>";
  $url;
  if ($results)
  {
   $total = (int) $results->response->numFound;
   $start = min(1, $total);
   $end = min($limit, $total);
?>

  <?php 

    if ($corrector!== '' && strtolower(trim($corrector)) !== strtolower(trim($query))) {
          $isSpellCheck = true;
            //echo 'a'.$corrector.'a';
            echo '<p>Showing Results for :<a href="http://localhost/main1.php?q='.$corrector.'"><i>'.$corrector.'</i></a></p>';
            if(isset($_REQUEST['spellcheck']))
            { 
              $isSpellCheck = false;
            }
            if($isSpellCheck){
              echo '<p>Did you mean:<a href="http://localhost/main1.php?spellcheck=false&&q='.$query.'">'.$query.'</a></p>';
            }
            //<a href="http://localhost/main.php?q='.$corrector.'>'.$corrector.'</a>

    }
    ?>
    <div><h2 style="color: green">Results <?php echo $start; ?> - <?php echo $end;?> of <?php echo $total; ?>:</h2></div>
  <?php
   $snippet="";
  // $que = array();
   //array_push($que, $_REQUEST['q']);
   $individualterms = array();
   $individualterms = explode(" ", trim($_REQUEST['q']));
   $presented;
   //$check = array_merge($que,$individualterms);
   $directory= '/var/www/html/NBCNewsDownloadData/';
   //$files = array_diff(scandir($directory), array('.', '..'));
   echo "<br>";  
    foreach ($results->response->docs as $x)
    {
      $presented = false;
      if(is_array($x->title))
      echo "<strong><a href='".$x->og_url."'>".$x->title[0]."</a></strong><br>";
      else
        echo "<strong><a href='".$x->og_url."'>".$x->title."</a></strong><br>";
      echo "<a style='color:red' href='".$x->og_url."'>".$x->og_url."</a><br>";
      echo $x->id."<br>";
      echo $x->description;
      echo "<br>";
      if($spell)
      {
        $_REQUEST['q'] = $corrector;
      }
      
      if(!is_array($x->title) && strpos(strtolower($x->title), " ".strtolower(trim($_REQUEST['q']))." ")){
        $snippet = $x->title;
        $presented = true;
      }
      elseif (strpos(strtolower($x->description), " ".strtolower(trim($_REQUEST['q']))." ")){
        $snippet = $x->description;
        $presented = true;
      }
      
      elseif (strpos(strtolower($x->og_title), " ".strtolower(trim($_REQUEST['q']))." ")){
        $snippet = $x->og_title;
        $presented = true;
      }
      
      elseif (strpos(strtolower($x->og_description), " ".strtolower(trim($_REQUEST['q']))." ")){
        $snippet = $x->og_description;
        $presented = true;
      }

      else
      {   
          $html = file_get_html($x->id); 
          $bodytag = $html->find('body');
          foreach ($bodytag as $bodyElement) {
            $paragraph = $bodyElement->find('p');

            if($presented){
                break;
            }

            foreach ($paragraph as $lines) {
                $linesArray = explode(". ", $lines->plaintext);
                foreach ($linesArray as $line) {
                    if(strpos(strtolower($line), " ".strtolower(trim($_REQUEST['q']))." "))
                       { 
                        $position = strpos(strtolower($line), " ".strtolower(trim($_REQUEST['q']))." ");
                        if($position>70)$snippet = substr($line,$position-70,156);
                        else $snippet=$line;
                        $presented = true;
                        break;
                    }
                }
                
            }

          }         
      }

      if($presented)
       {
        
        $pattern = "/ ".trim($_REQUEST['q'])." /i";
        $replacement = "<b> ".trim($_REQUEST['q'])." </b>";
        echo "<strong><u>Snippet: </u></strong>".preg_replace($pattern, $replacement, $snippet);
        echo "<br><br><br>";
        continue;
    }

      if(!$presented)
      {
        foreach ($individualterms as $i) {
            $z = false;
            if(strpos(strtolower($x->title), " ".strtolower($i)." ")) {
        $z = true;
        $snippet = $x->title;
        $pattern = "/ ".$i." /i";
        $replacement = "<b> ".$i." </b>";
        echo "<strong><u>Snippet: </u></strong>".preg_replace($pattern, $replacement, $snippet);
        echo "<br><br><br>";
        break;
      }
      elseif (strpos(strtolower($x->description), " ".strtolower($i)." ")){
        $z = true;
        $snippet = $x->description;
        $presented = true;
        $pattern = "/ ".$i." /i";
        $replacement = "<b> ".$i." </b>";
        echo "<strong><u>Snippet: </u></strong>".preg_replace($pattern, $replacement, $snippet);
        echo "<br><br><br>";
        break;
      }
      
      elseif (strpos(strtolower($x->og_title), " ".strtolower($i)." ")){
        $z = true;
        $snippet = $x->og_title;
        $presented = true;
        $pattern = "/ ".$i." /i";
        $replacement = "<b> ".$i." </b>";
        echo "<strong><u>Snippet: </u></strong>".preg_replace($pattern, $replacement, $snippet);
        echo "<br><br><br>";
        break;
      }
      
      elseif (strpos(strtolower($x->og_description), " ".strtolower($i)." ")){
        $z = true;
        $snippet = $x->og_description;
        $presented = true;
        $pattern = "/ ".$i." /i";
        $replacement = "<b> ".$i." </b>";
        echo "<strong><u>Snippet: </u></strong>".preg_replace($pattern, $replacement, $snippet);
        echo "<br><br><br>";
        break;
      }

      else
      {   
          $html = file_get_html($x->id); 
          $bodytag = $html->find('body');
          foreach ($bodytag as $bodyElement) {
            $paragraph = $bodyElement->find('p');
            foreach ($paragraph as $lines) {
                if($presented){
                    break;
                }
                $linesArray = explode(". ", $lines->plaintext);
                foreach ($linesArray as $line) {
                    if(strpos(strtolower($line), " ".strtolower($i)." "))
                       { 
                        $z = true;
                        $position = strpos(strtolower($line), " ".strtolower($i)." ");
                        //if($position>70)
                        $snippet = $line;
                        $presented = true;
                        $pattern = "/ ".$i." /i";
                        $replacement = "<b> ".$i." </b>";
                        echo "<strong><u>Snippet: </u></strong>".preg_replace($pattern, $replacement, $snippet);
                        echo "<br><br><br>";
                        break;
                    }
                }
                
            }

          }

            if($presented){
                    break;
                }

      }

    }
 }

       
  }     

}

?>


    <script>
        $(function() {
            $("#q").autocomplete({
                source : function(request, response) {
                    var lastword = $("#q").val().toLowerCase().split(" ").pop(-1).trim();
                    var URL = "http://localhost:8983/solr/test/suggest?q=" + lastword + "&wt=json";
                    $.ajax({
                        
                                }
                                return prefix + value.term;
                            });
                            response(suggestions.slice(0, 7));
                        },
                        dataType : 'jsonp',
                        jsonp : 'json.wrf'
                    });
                },
                minLength : 1
            });
        });
        
    </script>
</body>
</html>

