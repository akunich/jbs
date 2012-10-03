<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$EdesksIDs  = (array) @$Args['RowsIDs'];
#-------------------------------------------------------------------------------
if(Count($EdesksIDs) < 1)
  return new gException('EDESKS_NOT_SELECTED','Тикеты для закрытия выбраны');
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Array = Array();
#-------------------------------------------------------------------------------
foreach($EdesksIDs as $EdesksID)
  $Array[] = (integer)$EdesksID;
#-------------------------------------------------------------------------------
$Edesks = DB_Select('EdesksOwners',Array('ID','UserID','StatusID','Flags'),Array('Where'=>SPrintF('`ID` IN (%s)',Implode(',',$Array))));
#-------------------------------------------------------------------------------
switch(ValueOf($Edesks)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return ERROR | @Trigger_Error(400);
  case 'array':
    #---------------------------------------------------------------------------
    $__USER = $GLOBALS['__USER'];
    #---------------------------------------------------------------------------
    #---------------------------------------------------------------------------
    foreach($Edesks as $Edesk){
      #-------------------------------------------------------------------------
      $IsPermission = Permission_Check('EdeskClose',(integer)$__USER['ID'],(integer)$Edesk['UserID']);
      #-------------------------------------------------------------------------
      switch(ValueOf($IsPermission)){
        case 'error':
          return ERROR | @Trigger_Error(500);
        case 'exception':
          return ERROR | @Trigger_Error(400);
        case 'false':
          return ERROR | @Trigger_Error(700);
        case 'true':
	  #---------------------------------------------------------------------
          $Number = Comp_Load('Formats/Edesk/Number',$Edesk['ID']);
          if(Is_Error($Number))
            return ERROR | @Trigger_Error(500);
          #---------------------------------------------------------------------
          if($Edesk['Flags'] == 'DenyClose')
            return new gException('DENY_CLOSE_TICKET',SPrintF('Тикет #%s запрещено закрывать',$Number));
          #---------------------------------------------------------------------
          if($Edesk['StatusID'] == 'Closed')
            return new gException('TICKET_CLOSED',SPrintF('Тикет #%s уже закрыт',$Number));
          #---------------------------------------------------------------------
          $Comp = Comp_Load('www/API/StatusSet',Array('ModeID'=>'Edesks','StatusID'=>'Closed','RowsIDs'=>$Edesk['ID']));
          #---------------------------------------------------------------------
          switch(ValueOf($Comp)){
            case 'error':
              return ERROR | @Trigger_Error(500);
            case 'exception':
              #return $StatusSet;
	      return ERROR | @Trigger_Error(400);
            case 'array':
              # No more...
            break;
            default:
              return ERROR | @Trigger_Error(101);
          }
        break;
        default:
          return ERROR | @Trigger_Error(101);
      }
    }
    #---------------------------------------------------------------------------
    if(Is_Error(DB_Commit($TransactionID)))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    return Array('Status'=>'Ok');
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
