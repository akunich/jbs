<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('VPSOrder');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
switch($VPSOrder['StatusID']){
case 'Waiting':
	#-------------------------------------------------------------------------------
	# No more...
	break;
	#-------------------------------------------------------------------------------
case 'Deleted':
	#-------------------------------------------------------------------------------
	$Count = DB_Count('Tasks',Array('Where'=>Array(SPrintF('`UserID` = %u', $VPSOrder['UserID']),"`IsExecuted` = 'no'")));
	if(Is_Error($Count))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	if($Count)
		if(Time() - $VPSOrder['StatusDate'] < 600)
			return new gException('SYNCHRONIZATION_WAITING','Синхронизация по удалению заказа с сервера еще не произведена. Пожалуйста, повторите запрос через 10 минут.');
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return new gException('DELETE_DENIED','Удаление заказа не возможно');
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Services/Orders/OrdersHistory',Array('OrderID'=>$VPSOrder['OrderID'],'Parked'=>$VPSOrder['IP']));
switch(ValueOf($Comp)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return $Comp;
case 'array':
	return TRUE;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
