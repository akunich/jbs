<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Params','Type');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
#-------------------------------------------------------------------------------
$jParams = Comp_Load('Formats/Explode/JSON',$Params);
if(Is_Error($jParams))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#Debug('[comp/Formats/Task/Params]: ' . print_r($jParams,true));
#-------------------------------------------------------------------------------
if($Type == 'Theme'){
	#-------------------------------------------------------------------------------
	$Out = Comp_Load('Formats/String',$jParams['Theme'],20);
	if(Is_Error($Out))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	return $Out;
	#-------------------------------------------------------------------------------
}elseif($Type == 'Message'){
	#-------------------------------------------------------------------------------
	$Out = Comp_Load('Formats/String',$jParams['Message'],20);
	if(Is_Error($Out))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	return $Out;
	#-------------------------------------------------------------------------------
}elseif($Type == 'SendToIDs'){
	#-------------------------------------------------------------------------------
	$Out = SizeOf(Explode(',',$jParams['SendToIDs'])) + SizeOf(Explode(',',$jParams['SendedIDs'])) - 1;
	#-------------------------------------------------------------------------------
	return SPrintF(' %s ',$Out);
	#-------------------------------------------------------------------------------
}elseif($Type == 'SendedIDs'){
	#-------------------------------------------------------------------------------
	if(IsSet($jParams['SendedIDs'])){
		return SPrintF(' %s ',SizeOf(Explode(',',$jParams['SendedIDs'])));
	}else{
		return ' 0 ';
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $jParams;
#-------------------------------------------------------------------------------

?>
