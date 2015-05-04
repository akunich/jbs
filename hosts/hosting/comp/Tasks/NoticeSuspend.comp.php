<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
$Settings = $Config['Tasks']['Types']['NoticeSuspend'];
#-------------------------------------------------------------------------------
# достаём время выполнения
$ExecuteTime = Comp_Load('Formats/Task/ExecuteTime',Array('ExecuteTime'=>$Settings['ExecuteTime'],'ExecuteDays'=>@$Settings['ExecuteDays'],'DefaultTime'=>MkTime(4,20,0,Date('n'),Date('j')+1,Date('Y'))));
if(Is_Error($ExecuteTime))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
# если неактивна, то через день запуск
if(!$Settings['IsActive'])
	return $ExecuteTime;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = Array();
#-------------------------------------------------------------------------------
$Where = Array('`Code` != "Default"','`IsHidden` = "no"');
#-------------------------------------------------------------------------------
$Services = DB_Select('Services',Array('ID','Code','Name'),Array('Where'=>$Where));
switch(ValueOf($Services)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'][] = 'no services for suspend notice';
	#-------------------------------------------------------------------------------
	return $ExecuteTime;
	#-------------------------------------------------------------------------------
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
foreach($Services as $Service){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/NoticeSuspend]: Service = %s',$Service['Code']));
	#-------------------------------------------------------------------------------
	#if($Service['Code'] != 'Domain')
	#	continue;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Columns = Array('*');
	#-------------------------------------------------------------------------------
	$Where = "`DaysRemainded` IN (1,5,10,15) AND `StatusID` = 'Active'";
	#-------------------------------------------------------------------------------
	if($Service['Code'] == 'Domain'){
		#-------------------------------------------------------------------------------
		$Columns[] = '(SELECT `Name` FROM `DomainSchemes` WHERE `DomainSchemes`.`ID` = `DomainOrdersOwners`.`SchemeID`) as `DomainZone`';
		#-------------------------------------------------------------------------------
		$Where = "`StatusID` = 'Active' AND CEIL((`ExpirationDate` - UNIX_TIMESTAMP())/86400) IN (1,5,10,15,30)";
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Orders = DB_Select(SPrintF('%sOrdersOwners',$Service['Code']),$Columns,Array('Where'=>$Where));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Orders)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		Debug(SPrintF('[comp/Tasks/NoticeSuspend]: для сервиса %s нет уведомлений о блокировке',$Service['Code']));
		continue 2;
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'][$Service['Code']] = Array(SizeOf($Orders));
	#-------------------------------------------------------------------------------
	foreach($Orders as $Order){
		#-------------------------------------------------------------------------------
		if($Service['Code'] == 'Domain'){
			#-------------------------------------------------------------------------------
			$ClassName = SPrintF('%sNoticeSuspendMsg',$Service['Code']);
			#-------------------------------------------------------------------------------
			$msg = new $ClassName($Order,(integer)$Order['UserID']);
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			$msg = new Message(SPrintF('%sNoticeSuspend',$Service['Code']),(integer)$Order['UserID'],Array(SPrintF('%sOrder',$Service['Code'])=>$Order));
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$IsSend = NotificationManager::sendMsg($msg);
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		switch(ValueOf($IsSend)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			# No more...
		case 'true':
			# No more...
			break;
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $ExecuteTime;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>