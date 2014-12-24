<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('DNSmanagerOrder');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$ExecuteDate = Comp_Load('DNSmanagerOrders/SearchExecuteTime');
if(Is_Error($ExecuteDate))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IsAdd = Comp_Load('www/Administrator/API/TaskEdit',Array('UserID'=>$DNSmanagerOrder['UserID'],'TypeID'=>'DNSmanagerSuspend','ExecuteDate'=>$ExecuteDate,'Params'=>Array($DNSmanagerOrder['ID'])));
#-------------------------------------------------------------------------------
switch(ValueOf($IsAdd)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	return TRUE;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
