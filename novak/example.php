<?php
// Include the SDK
require_once('Infusionsoft/infusionsoft.php');
$appName = 'ce184.infusionsoft.com';
$apiKey = '79c288ec74bca6c7c33dee312090d21c';

//Initiate the Infusionsoft_App with API credentials
$app = new Infusionsoft_App($appName, $apiKey);

//Add the Infusionsoft App to the AppPool class
Infusionsoft_AppPool::addApp($app);


// Create a new contact object
$contact = new Infusionsoft_Contact(385256);
$contact->_boxkey= 'hello';
$contact->save();
// //Read a field from the loaded Contact object
//var_dump($contact);
$customFields = Infusionsoft_CustomFieldService::getCustomFields(new Infusionsoft_Contact(385256));
$customFieldsAsArray = array();
foreach($customFields as $customField){
    $customFieldsAsArray[] = '_' . $customField->Name;
}
//var_dump($customFieldsAsArray);



?>