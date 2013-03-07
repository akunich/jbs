<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('classes/Server.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

#return 60;

$Config = Config();
$Settings = $Config['Tasks']['Types']['HostingCPUUsage'];



$HostingServers = DB_Select('HostingServers',Array('ID','Address'));
#-------------------------------------------------------------------------------
switch(ValueOf($HostingServers)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	# No more...
	break;
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
foreach($HostingServers as $HostingServer){
	#-------------------------------------------------------------------------------
	$Server = new Server();
	#-------------------------------------------------------------------------------
	$IsSelected = $Server->Select((integer)$HostingServer['ID']);
	#-------------------------------------------------------------------------------
	switch(ValueOf($IsSelected)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'true':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	# достаём за период
	$TFilter = SPrintF('%s - %s',date('Y-m-d',time() - $Settings['PeriodToLock']*24*3600),date('Y-m-d',time() - 24*3600));
	$BUsages = Call_User_Func_Array(Array($Server,'GetCPUUsage'),Array($TFilter));
	#-------------------------------------------------------------------------------
	switch(ValueOf($BUsages)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return $BUsages;
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#Debug(SPrintF('[comp/Tasks/HostingCPUUsage]: BUsage = %s',print_r($BUsages,true)));
	#-------------------------------------------------------------------------------
	# достаём за вчера
	$TFilter = SPrintF('%s - %s',date('Y-m-d',time() - 24*3600),date('Y-m-d',time() - 24*3600));
	$SUsages = Call_User_Func_Array(Array($Server,'GetCPUUsage'),Array($TFilter));
	#-------------------------------------------------------------------------------
	switch(ValueOf($SUsages)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return $SUsages;
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#Debug(SPrintF('[comp/Tasks/HostingCPUUsage]: SUsage = %s',print_r($SUsages,true)));
	#-------------------------------------------------------------------------------
	# достаём юзеров из биллинга, и их лимиты


break;


}



return 60;




?>
