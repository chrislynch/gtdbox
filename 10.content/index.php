<?php
$file = '/home/chris/Dropbox/Apps/gtdbox/todo.txt';
$todotxt = file_get_contents($file);
$tasks = todotxt_to_array($todotxt);
print '<pre>' . print_r($tasks,TRUE) . '</pre>';
// file_put_contents($file, $todotxt)

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

function todotxt_to_array($todotxt){
    /*
     * Parse a todo.txt string and create a structured array of all the tasks within
     */
    $todos = array();
    $lines = explode("\n",$todotxt);
    foreach($lines as $line){
        $line = rtrim($line);
        if (strlen($line) > 0){
            $todo = newtodoarray();
            $words = explode(' ',$words);
            $firstword = TRUE;
            foreach($words as $word){
                $letters = str_split($word);
                if ($firstword){
                    if ($letters[0] == '('){
                        // The word is the priority
                        // We *should* hit a creation date after this, so stay in first word mode
                        $todo['priority'] = $letters[1];
                        unset($word);
                    } else {
                        if(is_numeric($letters[0])){
                            // This should be the creation date then
                            // Creation date comes *after* priority so we can come out of first word mode now.
                            $todo['created'] = $word;
                            unset($word);
                            $firstword = FALSE;
                        } else {
                            // This is something else. We can exit first word mode
                            $firstword = FALSE;
                        }
                    }
                } 
                if (!$firstword && isset($word)){
                    // We are out of first word mode and have a word to process.
                    switch($letters[0]){
                        // todo.txt standard items
                        case '@': $todo['contexts'][] = $word; break;
                        case '+': $todo['projects'][] = $word; break;
                        // Hashtag extension, seen elsewhere
                        case '#': $todo['hashtags'][] = $word; break;
                        // My extensions - Value and Waiting On
                        case '_': $todo['waitingon'][] = $word; break;
                        case '£': case '$': $todo['value'] = strval(substr($word,1)); break;
                        default : 
                            if (strstr($word,':')){
                                // This might be an extension, like due:XXXX
                                // If there is a space after the :, this breaks the magic
                                $worddata = explode(':',$word);
                                if (ltrim($word[1] == $word[1])){
                                    $key = array_shift($worddata);
                                    $todo['extensions'][$key] = implode(':',$worddata);
                                } else {
                                    $todo['task'] .= $word . ' ';
                                }
                            } else {
                                $todo['task'] .= $word . ' ';
                            }
                    }
                }
            }
            $todos[] = $todo;
        }
    }
    
    return $todos;
}

function newtodoarray(){
    return array('priority' => 'c','created' => time(),
                 'contexts' => array(), 'projects' => array(), 
                 'hashtags' => array(), 'waitingon' => array(),
                 'task' => '',
                 'extensions' => array(),
                 'value' => 0);
}

function array_to_todotxt($todoarray){
    /*
     * Transform a structured array of todo items into a string,
     * normally for writing back to a file.
     */
}

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