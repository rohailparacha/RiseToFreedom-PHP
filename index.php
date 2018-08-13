<?php
require './streak/init.php';
require_once('./novak/Infusionsoft/infusionsoft.php');
$streak_key = '1e5915f16a134716ab66b1d7540f00d7';
$streak = \Streak\Streak::setApiKey($streak_key);
$pipeline	= new \Streak\StreakPipeline;
$stage	= new \Streak\StreakStage;
$contact	= new \Streak\StreakContact;
$box	= new \Streak\StreakBox;
$field	= new \Streak\StreakField;

//Infusion soft key and app 
$appName = 'ce184.infusionsoft.com';
$apiKey = '79c288ec74bca6c7c33dee312090d21c';
$app = new Infusionsoft_App($appName, $apiKey);
//Add the Infusionsoft App to the AppPool class
Infusionsoft_AppPool::addApp($app);

//get pipeline
$pipelinename = 'Kickstart - KateMcShea.com Pipeline';
$pipelineKey= getPipelineKey($pipeline, $pipelinename);

if(isset($_POST['action']) )
    {
        if($_POST['action']=='newbuyer'){
            //get stage
            $stagename='K1 Scheduled';
            $stageKey = getStageKey($stage, $stagename, $pipelineKey); 
            $contactId = $_POST['contactId'];
            //check if passed coach exists in the contacts
            
            $coachemail= getCoach($contactId);   
            
            if(isset($_POST['email']))
                $boxName= $_POST['email'];
            else 
                $boxName = 'Email Not Found';
            
            $boxKey = createBox($box,$boxName, $stageKey, $pipelineKey, $coachemail) ;
            //add boxKey back to infusionsoft
            updateInfusionsoft($contactId , $boxKey);            
            $orderId = getLatestOrder($contactId);
            $arr= getOrderDetails($orderId);
            //Add product name, product price and date of purchase
            $productName=$arr["name"]; ; 
            $productDate=$arr["date"];
            $cart=$arr["cart"]; 
            updateField($field, $boxKey, getFieldId($field, $pipelineKey, "Product Of Purchase"), $productName  );
            updateField($field, $boxKey, getFieldId($field, $pipelineKey, "Date Of Purchase"), $productDate );
            updateField($field, $boxKey, getFieldId($field, $pipelineKey, "Amount of Purchase"), $cart );
        }

        else if($_POST['action']=='k1complete')
        
        {
            $boxkey = $_POST['boxkey'];
            $stagename='K1 Complete';
            $stageKey = getStageKey($stage, $stagename, $pipelineKey);        
            $params	= array('stageKey' => $stageKey);
            $data	= $box->editBox($boxkey, $params);
            $stop_date = new DateTime();
            $stop_date->modify('+1 day');
            updateField($field, $boxkey, getFieldId($field, $pipelineKey, "K1 Complete Date"), dateConvert($stop_date->format('Y-m-d')));  
            //applytag
        }

        else if($_POST['action']=='k2scheduled')
        {
            $stop_date = new DateTime();
            $stop_date->modify('+2 day');
            $boxkey = $_POST['boxkey'];
            updateField($field, $boxkey, getFieldId($field, $pipelineKey, "K2 Scheduled Date"), dateConvert($stop_date->format('Y-m-d')));   
            
            
        }

        else if($_POST['action']=='k2complete')
        {
             $stop_date = new DateTime();
            $stop_date->modify('+3 day');
            $boxkey = $_POST['boxkey'];
            updateField($field, $boxkey, getFieldId($field, $pipelineKey, "K2 Complete Date"), dateConvert($stop_date->format('Y-m-d')));  
            
        }

        else if($_POST['action']=='salecompleted')
        {
                
        }

    }




function getOrderDetails($orderId)
{
    $orderItems = Infusionsoft_DataService::query(new Infusionsoft_OrderItem(), array('OrderId' => $orderId));
    $cartValue='';
    $att = 'ItemName';

    $orderDetails = array();
    if(count($orderItems)>0)
    {
    $prodFirstPurchase = $orderItems[0]->$att;
    $date = dateConvert(date("Y-m-d"));

    foreach($orderItems as $orderItem) {
        // Each $orderItem will be an Infusionsoft_OrderItem object
        $att = 'ItemName';
        $cartValue= $cartValue.$orderItem->$att." ";
        $att = 'PPU';
        $cartValue = $cartValue.$orderItem->$att." ";
        $att = 'Qty';
        $cartValue=$cartValue.$orderItem->$att."p ";
        $cartValue=$cartValue.",";        
    }    
    $orderDetails["name"] = $prodFirstPurchase;
    $orderDetails["cart"] = $cartValue;
    $orderDetails["date"] = $date;
    }
    
    return $orderDetails;
}

//Function to get pipelinekey
function getPipelineKey($pipeline, $pipelinename)
{
    $data		= $pipeline->getAllPipelines();
    $item_object = json_decode($data,true);

    foreach($item_object as $obj)
    {
        if($obj['name']==$pipelinename)
            return $obj['pipelineKey'];
    }
    return '';
}

//Function to get stage key
function getStageKey($stage, $stagename, $pipelineKey)
{
    $data	= $stage->getAllStages($pipelineKey);
    //get stage K1 Scheduled
    foreach(json_decode($data,true) as $stages)
    {
        if($stages['name']==$stagename)
            return $stages['key'];
    }
    return '';
}

//function to get contact key
function getContactKey($coachemail)
{
    $teamkey	= 'agxzfm1haWxmb29nYWVyEQsSBFRlYW0YgICIv9nL0wgM';
	$params		= array(
						'emailAddresses'	=> array($coachemail),			
  					);
	$data		= $contact->contactExist($teamkey, $params, $getIfExisting = true);
    $coachKey= '';
    if (count($data)>0)
        {
                $obj= json_decode($data,true);
                return $obj['key'];
        }

    return '';
}

//function to create Box
function createBox($box, $name, $stageKey, $pipelineKey, $coachemail)
{
    if($coachemail!=null){
    $params	= array('name' => $name, 'stageKey' => $stageKey,  'assignedToSharingEntries' => '[{"email":"'.$coachemail.'"}]');
    }
    else{
    $params	= array('name' => $name, 'stageKey' => $stageKey);
    }
    
    $data	= $box->createBox($pipelineKey, $params);
    $object =  json_decode($data,true);
    return $object['boxKey'];
}

//Add Field Values
function updateField($field, $boxKey, $fieldKey, $value)
{
	$params = array('value' => $value);
	$data	= $field->editFieldValue($boxKey, $fieldKey, $params);
	print_r( json_decode($data) );
}

//convert to reqquired time/date format
function dateConvert($date)
{
    return strtotime($date) * 1000;
}

//update key in infusionsoft
function updateInfusionsoft($contactId, $boxKey)
{
    // Create a new contact object
    $contact = new Infusionsoft_Contact($contactId);
    $contact->_StreakBoxKey= $boxKey;
    $contact->save();
}

function getFieldId($field, $pipelineKey, $fieldName)
{
    $data	= $field->getAllFields($pipelineKey);
	  
    $fields =  json_decode($data,true) ;

    foreach ($fields as $field)
    {        
        if($field["name"]==$fieldName)
            return $field["key"];
    }
    return '';

}

function getLatestOrder($contactId)
{		
        $invoices = Infusionsoft_DataService::findByField(new Infusionsoft_Invoice(), 'ContactId', $contactId);        
        $time= '1900-01-01';
        foreach ($invoices as $invoice)
        {
            $arr = "DateCreated";
            $arr2 = "JobId";
            if(strtotime($invoice->$arr)>strtotime($time))
                {
                    $time = $invoice->$arr;
                    $jobId = $invoice->$arr2;
                }
        }
        return $jobId;
}

function getCoach($id)
{
    $infusionSoftUserSearch = Infusionsoft_DataService::query( new Infusionsoft_ContactAction(), array( 'ContactId' => $id ) );
    $att= "ActionType";
    foreach($infusionSoftUserSearch as $action)
    {
        if(trim($action->$att)=='Appointment')
        {
            $att='UserID';
            $userId = $action->$att;
        }
    }
    $infusionSoftUserSearch = Infusionsoft_DataService::query( new Infusionsoft_User(), array( 'Id' => $userId ) );
    $att= 'Email';
    return $infusionSoftUserSearch[0]->$att;
}

function applyTag($boxkey, $tagId)
{   
    $infusionSoftUserSearch = Infusionsoft_DataService::query( new Infusionsoft_Contact(), array( '_StreakBoxKey' => $boxkey ) );
    $contactId = $infusionSoftUser->Id;
    $infusionSoftUser = array_shift( $infusionSoftUserSearch );
    Infusionsoft_ContactService::addToGroup($contactId, $tagId);
}


?>

