<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$ConfigPath = SPrintF('%s/hosts/%s/config/Config.xml',SYSTEM_PATH,HOST_ID);
#-------------------------------------------------------------------------------
if(File_Exists($ConfigPath)){
	#-------------------------------------------------------------------------------
	$File = IO_Read($ConfigPath);
	if(Is_Error($File))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$XML = String_XML_Parse($File);
	if(Is_Exception($XML))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Config = $XML->ToArray();
	#-------------------------------------------------------------------------------
	$Config = $Config['XML'];
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Config = Array();
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$GC = $Config['Tasks']['Types']['GC'];
#-------------------------------------------------------------------------------
$Items = Array('TableTasksStoryPeriod','TableServersUpTimeStoryPeriod','TableRequestLogStoryPeriod');
#-------------------------------------------------------------------------------
foreach($Items as $Item){
	#-------------------------------------------------------------------------------
	if(IsSet($GC[$Item])){
		#-------------------------------------------------------------------------------
		$Value = $GC[$Item];
		Debug(SPrintF('[patches/billing/files/1000063.php]: %s = %s',$Item,$Value));
		#-------------------------------------------------------------------------------
		UnSet($GC[$Item]);
		#-------------------------------------------------------------------------------
		if(!IsSet($GC['CleanTablesSettings']) || !Is_Array($GC['CleanTablesSettings'])){
			$GC['CleanTablesSettings'] = Array($Item=>$Value);
		}else{
			$GC['CleanTablesSettings'][$Item] = $Value;
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Config['Tasks']['Types']['GC'] = $GC;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$File = IO_Write($ConfigPath,To_XML_String($Config),TRUE);
if(Is_Error($File))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$IsFlush = CacheManager::flush();
if(!$IsFlush)
	@Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
?>
