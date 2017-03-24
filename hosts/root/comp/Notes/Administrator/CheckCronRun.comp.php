<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru  **/
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Result = Array();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Interface']['Administrator']['Notes']['Tasks'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Settings['ShowUnExecuted'])
	return $Result;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Marker = SPrintF('%s/hosts/%s/tmp/TaskLastExecute.txt',SYSTEM_PATH,HOST_ID);
#-------------------------------------------------------------------------------
if(Is_Readable($Marker)){
	#-------------------------------------------------------------------------------
	$LastExecuted = File_Get_Contents($Marker);
	#-------------------------------------------------------------------------------
	$LastExecuted = StrToTime($LastExecuted);
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Notes/Administrator/CheckCronRun]: LastExecuted = %s',Date('Y-m-d H:i:s',$LastExecuted)));
	#-------------------------------------------------------------------------------
	if($LastExecuted < (Time() - $Settings['CronDownTime']))
		$Array = Array('Message'=>SPrintF('Последнее задание было выполнено <B>%s в %s</B>',Date('Y-m-d',$LastExecuted),Date('H:i:s',$LastExecuted)));
}else{
	#-------------------------------------------------------------------------------
	$Array = Array('Message'=>SPrintF('Планировщик ни разу не запускался в штатном режиме, или, отсутствует доступ к файлу <BR /><B>%s</B>',$Marker));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(IsSet($Array)){
	#-------------------------------------------------------------------------------
	$NoBody = new Tag('NOBODY');
	#-------------------------------------------------------------------------------
	$NoBody->AddHTML(TemplateReplace('Notes.Administrator.CheckCronRun',$Array));
	#-------------------------------------------------------------------------------
	$Result[] = $NoBody;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Result;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
?>