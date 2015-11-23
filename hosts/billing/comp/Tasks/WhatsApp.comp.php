<?php
#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task','Mobile','Message','ID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
Debug(SPrintF('[comp/Tasks/WhatsApp]: отправка WhatsApp сообщения для (%u)',$Mobile));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
if(!$Config['Notifies']['Methods']['WhatsApp']['IsActive']){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/WhatsApp]: уведомления через WhatsApp отключены'));
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = $Mobile;
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('classes/WhatsApp/whatsprot.class.php','libs/Server.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Settings = SelectServerSettingsByTemplate('WhatsApp');
#-------------------------------------------------------------------------------
switch(ValueOf($Settings)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	$GLOBALS['TaskReturnInfo'] = 'server with template: WhatsApp, params: IsActive, IsDefault not found';
	#-------------------------------------------------------------------------------
	if(IsSet($GLOBALS['IsCron']))
		return 3600;
	#-------------------------------------------------------------------------------
	return $Settings;
	#-------------------------------------------------------------------------------
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# кастыли, поскольку класс хочет директорию для базы внутри своей директории
$wadata = SPrintF('%s/hosts/billing/system/classes/WhatsApp/wadata',SYSTEM_PATH);
#-------------------------------------------------------------------------------
if(!Is_Link($wadata) && Is_Dir($wadata)){
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	function RemoveDir($Path){
		#-------------------------------------------------------------------------------
		$Objs = Glob(SPrintF('%s/*',$Path));
		#-------------------------------------------------------------------------------
		if($Objs){
			#-------------------------------------------------------------------------------
			foreach($Objs as $Obj){
				#-------------------------------------------------------------------------------
				Is_Dir($Obj) ? RemoveDir($Obj) : @UnLink($Obj);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		@RmDir($Path);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	RemoveDir($wadata);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$DataFolder = SPrintF('%s/hosts/%s/tmp/WhatsApp',SYSTEM_PATH,HOST_ID);
#-------------------------------------------------------------------------------
$LogFile = SPrintF('%s/WhatsApp.%s.log',$DataFolder,Date('Y-m-d'));
#-------------------------------------------------------------------------------
if(!Is_Dir(SPrintF('%s/logs',$DataFolder)))
	if(!MkDir(SPrintF('%s/logs',$DataFolder),0750,true))
		return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!Is_Link($wadata))
	if(!SymLink($DataFolder,$wadata))
		return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Links = &Links();
#-------------------------------------------------------------------------------
$LinkID = Md5('WhatsAppClient');
#-------------------------------------------------------------------------------
if(!IsSet($Links[$LinkID])){
	#-------------------------------------------------------------------------------
	$Links[$LinkID] = NULL;
	#-------------------------------------------------------------------------------
	$WhatsAppClient = &$Links[$LinkID];
	#-------------------------------------------------------------------------------
	$WhatsAppClient = new WhatsProt($Settings['Login'], $Settings['Params']['Sender'],FALSE,TRUE,$DataFolder);
	if(Is_Error($WhatsAppClient))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$WhatsAppClient->connect();
	$WhatsAppClient->loginWithPassword($Settings['Password']);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$WhatsAppClient = &$Links[$LinkID];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# переводы строк
#$Message = Str_Replace("\r","",$Message);
#$Message = Str_Replace("\n","\n\r",$Message);
#-------------------------------------------------------------------------------
$IsMessage = $WhatsAppClient->sendMessage($Mobile,$Message);
if(Is_Error($IsMessage)){
	#-------------------------------------------------------------------------------
	UnSet($Links[$LinkID]);
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/WhatsApp]: error sending message, see error file: %s',$LogFile));
	#-------------------------------------------------------------------------------
	return 3600;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Config['Notifies']['Methods']['WhatsApp']['IsEvent'])
	return TRUE;
#-------------------------------------------------------------------------------
$Event = Comp_Load('Events/EventInsert',Array('UserID'=>$ID,'Text'=>SPrintF('Сообщение для (%u) через службу WhatsApp отправлено',$Mobile)));
#-------------------------------------------------------------------------------
if(!$Event)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>