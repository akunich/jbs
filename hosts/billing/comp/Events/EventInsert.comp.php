<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Args');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
#-------------------------------------------------------------------------------
$Text		= (IsSet($Args['Text'])?$Args['Text']:'текст события не задан');
$UserID		= (IsSet($Args['UserID'])?$Args['UserID']:1);
$IsReaded	= (IsSet($Args['IsReaded'])?$Args['IsReaded']:TRUE);
$PriorityID	= (IsSet($Args['PriorityID'])?$Args['PriorityID']:'System');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$UserIDs = Array();
#-------------------------------------------------------------------------------
if(Is_Array($UserID)){
	#-------------------------------------------------------------------------------
	$UserIDs   = $UserID;
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$UserIDs[] = $UserID;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
foreach($UserIDs as $UserID){
	#-------------------------------------------------------------------------------
	$Event = Array	(
				'UserID'	=> (integer)$UserID,
				'Text'		=>  (string)$Text,
				'PriorityID'	=>  (string)$PriorityID,
				'IsReaded'	=> (boolean)$IsReaded
			);
	#-------------------------------------------------------------------------------
	$IsInsert = DB_Insert('Events',$Event);
	if(Is_Error($IsInsert))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
