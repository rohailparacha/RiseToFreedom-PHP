<?php
require_once('./novak/Infusionsoft/infusionsoft.php');
//Infusion soft key and app 
$appName = 'ce184.infusionsoft.com';
$apiKey = '79c288ec74bca6c7c33dee312090d21c';
$app = new Infusionsoft_App($appName, $apiKey);
//Add the Infusionsoft App to the AppPool class
Infusionsoft_AppPool::addApp($app);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // fetch RAW input
    $json = file_get_contents('php://input');

    // decode json
    $object = json_decode($json);

    // expecting valid json
    if (json_last_error() !== JSON_ERROR_NONE) {
        die(header('HTTP/1.0 415 Unsupported Media Type'));
    }

    //
    $my_file = 'file.txt';
    $handle = fopen($my_file, 'a') or die('Cannot open file:  '.$my_file);
    fwrite($handle, print_r($object,true));
    
    //add notes
    $att="updatedFieldKeys";
    $fieldkeys= $object[0]->$att;
    $att = "fields";
    $note = $object[0]->$att;
    $att = "boxKey";
    $boxkey  = $object[0]->$att;
    if(in_array("1013",$fieldkeys))
    {
        $att2="1013";
        if(trim($note->$att2)!="")
            addNotesToINF($note->$att2, $boxkey);
    }
    else if(in_array("1012",$fieldkeys))
    {
        $att2="1012";
        if(trim($note->$att2)!="")
          {
              if(trim($note->$att2)==9003)
                applyTag($boxkey,10764);
              else if(trim($note->$att2)==9001)
                applyTag($boxkey,10762);
          }
    }
        
}

function addNotesToINF($note, $boxkey)
{
    $infusionSoftUserSearch = Infusionsoft_DataService::query( new Infusionsoft_Contact(), array( '_StreakBoxKey' => $boxkey ) );
    $infusionSoftUser = array_shift( $infusionSoftUserSearch );
    $contactAction = new Infusionsoft_ContactAction();    
    $contactAction->ContactId = $infusionSoftUser->Id;
    $contactAction->CompletionDate = date('Ymj\TG:i:s');
    $contactAction->ActionDescription = 'Notes From Streak';
    $contactAction->CreationNotes = $note;
    $contactAction->save();
}

function applyTag($boxkey, $tagId)
{   
    $infusionSoftUserSearch = Infusionsoft_DataService::query( new Infusionsoft_Contact(), array( '_StreakBoxKey' => $boxkey ) );
    $infusionSoftUser = array_shift( $infusionSoftUserSearch );
    $contactId = $infusionSoftUser->Id;    
    Infusionsoft_ContactService::addToGroup($contactId, $tagId);
}


?>

