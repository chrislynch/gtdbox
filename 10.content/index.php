<?php
$file = '/home/chris/Dropbox/Apps/gtdbox/todo.txt';
$todotxt = file_get_contents($file);
$todos = todotxt_to_array($todotxt);

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
                foreach($todos['priority'] as $priority){
                    print "<h1>Priority $priority</h1><table>";
                    foreach($todos['todos'] as $todo){
                        if ($todo['priority'] == $priority){
                            print " <tr>
                                        <td>{$todo['priority']}</td>
                                        <td>" . str_ireplace('-', '&nbsp;', $todo['created']) ."</td>
                                        <td>{$todo['task']}</td>
                                    </tr>";    
                        }
                    }
                    print "</table>";
                }  
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
            if (substr($line,0,1) == ' ' || substr($line,0,1) == "\t") {
                $previousItem = count($todos) - 1;
                $todos[$previousItem]['note'][] = $line;
            } else {
                $todo = newtodoarray();
                $words = explode(' ',$line);
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
                            case '@': $todo['context'][] = $word; break;
                            case '+': $todo['project'][] = $word; break;
                            // Hashtag extension, seen elsewhere
                            case '#': $todo['hashtag'][] = $word; break;
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
                                } 
                        }
                    }
                    $todo['task'] .= $word . ' ';
                }
                // Tidy up the task string
                $todo['task'] = trim($todo['task']);
                // Make a "line" ready to print
                $todo['line'] = '(' . $todo['priority'] . ') ' . $todo['created'] . ' ' . $todo['task'];
                $todos[] = $todo;    
            }
            
        }
    }
    // Update each line with its notes, ready for printing
    foreach($todos as &$todo){
        foreach($todo['note'] as $note){
            $todo['line'] .= "\n$note";
        }
    }
    
    // Parse through the return and collect a list of Contexts, WaitingOns, Projects, and Hashtags
    $return = array('todos'=>array(),'projects'=>array(),'contexts'=>array(),'waitingons'=>array(),'hashtags'=>array());
    $return['todos'] = $todos;
    foreach($return['todos'] as $todo){
        $return['priority'][$todo['priority']] = $todo['priority'];
        $return['project'] = array_merge($return['project'],$todo['project']);
        $return['context'] = array_merge($return['context'],$todo['context']);
        $return['waitingon'] = array_merge($return['waitingon'],$todo['waitingon']);
        $return['hashtag'] = array_merge($return['hashtag'],$todo['hashtag']);
    }
    // Eliminate duplicate values
    $return['project'] = array_unique($return['project']);
    $return['context'] = array_unique($return['context']);
    $return['waitingon'] = array_unique($return['waitingon']);
    $return['hashtag'] = array_unique($return['hashtag']);
    // Sort the key arrays
    sort($return['priority']);
    sort($return['project']);
    sort($return['context']);
    sort($return['waitingon']);
    sort($return['hashtag']);
    
    return $return;
}

function newtodoarray(){
    return array('priority' => 'C','created' =>  date('Y-m-d'),
                 'contexts' => array(), 'projects' => array(), 
                 'hashtags' => array(), 'waitingon' => array(),
                 'task' => '',
                 'extensions' => array(),
                 'value' => 0,
                 'notes' => array());
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