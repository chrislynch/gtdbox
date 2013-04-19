<?php
$file = '/home/chris/Dropbox/Apps/gtdbox/todo.txt';
$todotxt = file_get_contents($file);
file_put_contents($file, $todotxt)

?>

<html>
    <head>
        <title>gtdBox</title>
        <link rel="stylesheet" href="css.css">
    </head>
    <body>
        <div id="header">
            <form action="?action=add">
                <input type="text" name="todo">
                <input type="submit" value="Add To Do">
            </form>
        </div>
        <div id="todotxt">
        <?php
            print formattodotxt($todotxt);
        ?>
        </div>
        <div id="tools">
            <h2>Tools</h2>
            
            <h3>Search</h3>
            <?php 
                if (isset($_REQUEST['project']) OR isset($_REQUEST['context']) OR isset($_REQUEST['hashtag'])){
                    print "<a href='?'>Clear Search</a><br>";
                }
            ?>
            <h4>Projects</h4>
            <?php printwordcloud(wordcloud($todotxt,'+'),'project'); ?>
            <h4>Contexts</h4>
            <?php printwordcloud(wordcloud($todotxt,'@'),'context'); ?>
            <h4>Hashtags</h4>
            <?php printwordcloud(wordcloud($todotxt,'#'),'hashtag'); ?>
        </div>
        <div id="footer"></div>
    </body>
</html>

<?php

function formattodotxt($todotxt){
    $return = '';
    $lines = explode("\n",$todotxt);
    $linecount = 0;
    $firstline = FALSE;
    foreach($lines as $line){
        $returnline = '';
        $words = explode(' ',$line);
        $wordcount = 0;
        foreach($words as $word){
            $word = trim($word);
            switch (substr($word,0,1)){
                case '+':
                    $returnline .= "<span class='project'><a href='?project=$word'>$word</a></span> ";
                    break;
                case '@':
                    $returnline .= "<span class='context'><a href='?context=$word'>$word</a></span> ";
                    break;
                case '#':
                    $returnline .= "<span class='hashtag'><a href='?hashtag=$word'>$word</a></span> ";
                    break;
                case '(':
                    if($wordcount == 0){
                        $formattedword = str_ireplace('(','',$word);
                        $formattedword = str_ireplace(')','',$formattedword);
                        $returnline .= "<span class='priority priority-$formattedword'><a href='?priority=$word'>$word</a></span> ";
                    } else {
                        $returnline .= $word . ' ';
                    }
                    break;
                default:
                    $returnline .= $word . ' ';
            }
            $wordcount += 1;
        }
        
        if($returnline !== ' '){
            $returnlineOK = TRUE;
            if(isset($_REQUEST['project'])){ if(!(strstr($returnline,$_REQUEST['project']))){ $returnlineOK = FALSE; }}
            if(isset($_REQUEST['context'])){ if(!(strstr($returnline,$_REQUEST['context']))){ $returnlineOK = FALSE; }}
            if(isset($_REQUEST['hashtag'])){ if(!(strstr($returnline,$_REQUEST['hashtag']))){ $returnlineOK = FALSE; }}

            if($returnlineOK){
                $firstline = TRUE;
                $return .= $returnline . "&nbsp;&nbsp;<a href='?action=complete&todo=$linecount'>[X]</a><br>";
            }    
        } else {
            if($firstline){
                $return .= $returnline . '<br>';
            }
        }
        $linecount++;
    }
    return $return;
}

function wordcloud($words,$find){
    $words = str_ireplace("\n", ' ', $words);
    $words = explode(' ',$words);
    $cloud = array();
    foreach($words as $word){
        if(strlen($word) > 1){
            if(substr($word,0,1) == $find){
                $word = trim(substr($word,1));
                $cloud[$word] += 1;
            }    
        }
    }
    ksort($cloud);
    return $cloud;
}

function printwordcloud($wordcloud,$variableName){
    foreach($wordcloud as $word=>$score){
        print "<a href='?$variableName=$word'>$word</a> "; 
    }
}

?>