<?php

require("logging.php");

$ref=getenv('HTTP_REFERER');
$original = $_POST['original'];
$translation = $_POST['translation'];
$lang = $_POST['lang'];

header( 'Location: ' . $ref);


if(!isset($original) || !isset($translation) || !isset($lang))
{
    logger("Enter " . __FILE__ . " missing params: $original , $translation, $lang," .  $ref, 0);
    return;
}



try
{
    $db_name = dirname(__FILE__) . "/transposh.sqlite";
    
    // create new database (procedural interface)
    $db = new SQLiteDatabase($db_name);
    //logger("Opened database $db_name", 0);
    
    $db->query("INSERT OR REPLACE INTO phrases (original, translated, lang)
                VALUES ('" . $original . "','" . $translation . "','" . $lang . "')");
    
}
catch(Exception $exception)
{
    logger("error !!! " . $exception->getMessage(), 0);
}


?>