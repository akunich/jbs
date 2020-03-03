<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Args');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Null($Args))
	if(Is_Error(System_Load('modules/Authorisation.mod')))
		return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Args = IsSet($Args)?$Args:Args();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$TaskID = (integer) @$Args['TaskID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Task = DB_Select('Tasks',Array('ID','CreateDate','UserID','TypeID','Params','Errors','Result','ExecuteDate'),Array('UNIQ','ID'=>$TaskID));
#-------------------------------------------------------------------------------
switch(ValueOf($Task)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return new gException('TASK_NOT_FOUND','Задание не найдено');
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Tmp = System_Element('tmp');
if(Is_Error($Tmp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$IsWrite = IO_Write(SPrintF('%s/TaskLastExecute.txt',$Tmp),Date('YmdHis'),TRUE);
if(Is_Error($IsWrite))
	return ERROR | @Trigger_Error('[TaskExecute_ERROR]: не удалось записать данные в маркерный файл');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# удаляем файл с данными о выполняемом задании, если он не удалился после его выполнения
$TaskNowRunning = SPrintF('%s/TaskNowRunning.txt',$Tmp);
#-------------------------------------------------------------------------------
if(File_Exists($TaskNowRunning))
	if(!UnLink($TaskNowRunning))
		return ERROR | @Trigger_Error(SPrintF('[comp/www/Administrator/API/TaskExecute]: ошибка при удалении файла (%s)',$TaskNowRunning));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$TaskID = $Task['ID'];
#-------------------------------------------------------------------------------
$Free = DB_Query(SPrintF("SELECT IS_FREE_LOCK('Tasks%s') as `IsFree`",$TaskID));
if(Is_Error($Free))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Rows = MySQL::Result($Free);
if(Is_Error($Rows))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(Count($Rows) < 1)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Row = Current($Rows);
#-------------------------------------------------------------------------------
if(!$Row['IsFree'])
	return new gException('TASK_ALREADY_EXECUTING','Задание уже выполняется');
#-------------------------------------------------------------------------------
$Lock = DB_Query(SPrintF("SELECT GET_LOCK('Tasks%s',5) as `IsLocked`",$TaskID));
if(Is_Error($Lock))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Rows = MySQL::Result($Lock);
if(Is_Error($Rows))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(Count($Rows) < 1)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Row = Current($Rows);
#-------------------------------------------------------------------------------
if(!$Row['IsLocked'])
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$UTask = Array();
#-------------------------------------------------------------------------------
$Params = (array)$Task['Params'];
#-------------------------------------------------------------------------------
Array_UnShift($Params,$Task);
#-------------------------------------------------------------------------------
Array_UnShift($Params,$Path = SPrintF('Tasks/%s',$Task['TypeID']));
#-------------------------------------------------------------------------------
if(Is_Error(System_Element(SPrintF('comp/%s.comp.php',$Path)))){
	#-------------------------------------------------------------------------------
	$FreeLock = DB_Query(SPrintF("SELECT RELEASE_LOCK('Tasks%s')",$TaskID));
	if(Is_Error($FreeLock))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	return new gException('TASK_HANDLER_NOT_APPOINTED','Заданию не назначен обработчик');
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#--------------------------------TRANSACTION------------------------------------
if(Is_Error(DB_Transaction($TransactionID = UniqID('TaskExecute'))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IsWrite = IO_Write($TaskNowRunning,$TaskID,TRUE);
if(Is_Error($IsWrite))
	return ERROR | @Trigger_Error('[TaskExecute_ERROR]: не удалось записать данные о выполняемом задании');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$__SYSLOG = &$GLOBALS['__SYSLOG'];
#-------------------------------------------------------------------------------
$Index = Count($__SYSLOG);
#-------------------------------------------------------------------------------
$Result = Call_User_Func_Array('Comp_Load',$Params);
#-------------------------------------------------------------------------------
$Log = Implode("\n",Array_Slice($__SYSLOG,$Index));
#-------------------------------------------------------------------------------
#Debug(SPrintF('[comp/www/Administrator/API/TaskExecute]: Result = %s',print_r($Result,true)));
switch(ValueOf($Result)){
case 'error':
	#-------------------------------------------------------------------------------
	if(Is_Error(DB_Roll($TransactionID)))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$UTask['Errors'] = $Task['Errors'] + 1;
	#-------------------------------------------------------------------------------
	$UTask['Result'] = SPrintF("%s\n\n%s",Mb_Convert_Encoding($Task['Result'],'UTF-8'),$Log);
	#-------------------------------------------------------------------------------
	$Number = Comp_Load('Formats/Task/Number',$Task['ID']);
	if(Is_Error($Number))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Event = Array('UserID'=>$Task['UserID'],'PriorityID'=>'Error','Text'=>SPrintF('Задание №%s [%s] вернуло ошибку выполнения',$Number,$Task['TypeID']));
	$Event = Comp_Load('Events/EventInsert',$Event);
	if(!$Event)
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
case 'exception':
	#-------------------------------------------------------------------------------
	if(Is_Error(DB_Roll($TransactionID)))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$UTask['Result']   = $Log;
	#-------------------------------------------------------------------------------
	if(!$UTask['Result'])
		$UTask['Result'] = print_r($Result,true);
	#-------------------------------------------------------------------------------
	$UTask['IsActive'] = FALSE;
	#-------------------------------------------------------------------------------
	$Number = Comp_Load('Formats/Task/Number',$Task['ID']);
	if(Is_Error($Number))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Event = Array('UserID'=>$Task['UserID'],'PriorityID'=>'Error','Text'=>SPrintF('Задание №%s [%s] не может быть выполнено в автоматическом режиме',$Number,$Task['TypeID']));
	$Event = Comp_Load('Events/EventInsert',$Event);
	if(!$Event)
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
case 'true':
	#-------------------------------------------------------------------------------
	$UTask['Result']     = '';
	#-------------------------------------------------------------------------------
	$UTask['IsExecuted'] = TRUE;
	#-------------------------------------------------------------------------------
	if(Is_Error(DB_Commit($TransactionID)))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
case 'integer':
	#-------------------------------------------------------------------------------
	if($Result < Time() && $Result > Time() - 365*24*60*60){
		#-------------------------------------------------------------------------------
		# вариант, когда вылезло достаточно старое время, но это явно не сдвиг
		$UTask['ExecuteDate'] = $Result + 24*60*60;
		#-------------------------------------------------------------------------------
	}elseif($Result < Time()){
		#-------------------------------------------------------------------------------
		# сдвиг времени
		$ExecuteDate = $Task['ExecuteDate'];
		#-------------------------------------------------------------------------------
		if($ExecuteDate < Time())
			$ExecuteDate += Round((Time() - $ExecuteDate)/$Result + 1)*$Result;
		#-------------------------------------------------------------------------------
		$UTask['ExecuteDate'] = $ExecuteDate;
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		# явно указанное время запуска
		$UTask['ExecuteDate'] = $Result;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/www/Administrator/API/TaskExecute]: Task.TypeID = %s; UTask.ExecuteDate = %s',$Task['TypeID'],date('Y-m-d G:i:s',$UTask['ExecuteDate'])));
	#-------------------------------------------------------------------------------
	if(Is_Error(DB_Commit($TransactionID)))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/www/Administrator/API/TaskExecute]: Result = %s',print_r($Result,true)));
	#-------------------------------------------------------------------------------
	return ERROR | @Trigger_Error(101);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# execute additional task, if need
if(IsSet($GLOBALS['TaskReturnArray'])){
	#-------------------------------------------------------------------------------
	$CompName = $GLOBALS['TaskReturnArray']['CompName'];
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/www/Administrator/API/TaskExecute]: TaskReturnArray задана, возможно необходимо выполнить: %s',$CompName));
	#-------------------------------------------------------------------------------
	# задаём массив с пометками - выполнена ли дополнительная задача
	if(!IsSet($Task['Params']['AdditionalTaskExecuted']) || !Is_Array($Task['Params']['AdditionalTaskExecuted']))
		$Task['Params']['AdditionalTaskExecuted'] = Array();
	#-------------------------------------------------------------------------------
	$AdditionalTaskExecuted = $Task['Params']['AdditionalTaskExecuted'];
	#-------------------------------------------------------------------------------
	# выполнено или нет?
	if(!IsSet($AdditionalTaskExecuted[$CompName]) || $AdditionalTaskExecuted[$CompName] != 'yes'){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/www/Administrator/API/TaskExecute]: TaskReturnArray => выполнение задачи: %s',$CompName));
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load($CompName,$GLOBALS['TaskReturnArray']['CompParameters']);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		# set in task parameters: additional task is executed
		$Task['Params']['AdditionalTaskExecuted'][$CompName] = 'yes';
		#-------------------------------------------------------------------------------
		$IsUpdate = DB_Update('Tasks',Array('Params'=>$Task['Params']),Array('ID'=>$Task['ID']));
		if(Is_Error($IsUpdate))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	UnSet($GLOBALS['TaskReturnArray']);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IsUpdate = DB_Update('Tasks',$UTask,Array('ID'=>$Task['ID']));
if(Is_Error($IsUpdate))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$FreeLock = DB_Query(SPrintF("SELECT RELEASE_LOCK('Tasks%s')",$TaskID));
if(Is_Error($FreeLock))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(File_Exists($TaskNowRunning))
	if(!UnLink($TaskNowRunning))
		return ERROR | @Trigger_Error(SPrintF('[comp/www/Administrator/API/TaskExecute]: ошибка при удалении файла, после выполнения задания (%s)',$TaskNowRunning));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
?>
