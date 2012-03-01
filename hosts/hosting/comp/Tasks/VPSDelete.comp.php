<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task','VPSOrderID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('classes/VPSServer.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$VPSOrder = DB_Select('VPSOrdersOwners',Array('ID','UserID','ServerID','Login','Domain','(SELECT `IsReselling` FROM `VPSSchemes` WHERE `VPSSchemes`.`ID` = `VPSOrdersOwners`.`SchemeID`) as `IsReselling`','(SELECT `Name` FROM `VPSSchemes` WHERE `VPSSchemes`.`ID` = `VPSOrdersOwners`.`SchemeID`) as `SchemeName`'),Array('UNIQ','ID'=>$VPSOrderID));
#-------------------------------------------------------------------------------
switch(ValueOf($VPSOrder)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return ERROR | @Trigger_Error(400);
  case 'array':
    #---------------------------------------------------------------------------
    $VPSServer = new VPSServer();
    #---------------------------------------------------------------------------
    $IsSelected = $VPSServer->Select((integer)$VPSOrder['ServerID']);
    #---------------------------------------------------------------------------
    switch(ValueOf($IsSelected)){
      case 'error':
        return ERROR | @Trigger_Error(500);
      case 'exception':
        return ERROR | @Trigger_Error(400);
      case 'true':
        #-----------------------------------------------------------------------
        $IsDelete = $VPSServer->Delete($VPSOrder['Login'],$VPSOrder['IsReselling']);
        #-----------------------------------------------------------------------
        switch(ValueOf($IsDelete)){
          case 'error':
            return ERROR | @Trigger_Error(500);
          case 'exception':
            return $IsDelete;
          case 'true':
            #-------------------------------------------------------------------
	    $Event = Array(
	    			'UserID'	=> $VPSOrder['UserID'],
				'PriorityID'	=> 'Billing',
				'Text'		=> SPrintF('Заказ VPS (%s), тариф (%s) успешно удален с сервера (%s)',$VPSOrder['Login'],$VPSOrder['SchemeName'],$VPSServer->Settings['Address'])
	                  );
            $Event = Comp_Load('Events/EventInsert',$Event);
            if(!$Event)
              return ERROR | @Trigger_Error(500);
            #-------------------------------------------------------------------
	    $GLOBALS['TaskReturnInfo'] = Array($VPSServer->Settings['Address'],$VPSOrder['Login'],$VPSOrder['SchemeName']);
            #-------------------------------------------------------------------
            return TRUE;
          default:
            return ERROR | @Trigger_Error(101);
        }
      default:
        return ERROR | @Trigger_Error(101);
    }
  default:
    return ERROR | @Trigger_Error(101);

}
#-------------------------------------------------------------------------------

?>
