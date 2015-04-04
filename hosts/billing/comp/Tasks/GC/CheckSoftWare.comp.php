<?php

#-------------------------------------------------------------------------------
/** @author Alex keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Params');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Tasks']['Types']['GC']['CheckSoftWareSettings'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Settings['IsActive'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Messages = Array();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверяем необходимые для работы модули
$Extensions = Array('gd','json','libxml','mbstring','mysql','openssl','xml','zlib','imap','mailparse');
#-------------------------------------------------------------------------------
foreach($Extensions as $Extension){
	#-------------------------------------------------------------------------------
	if(!Extension_Loaded($Extension)){
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Tasks/GC/CheckSoftWare]: Extensions not found: %s',$Extension));
		#-------------------------------------------------------------------------------
		if($Settings['IsEvent'])
			$Messages[] = SPrintF('Не найден модуль php, требуемый для работы: %s.',$Extension);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверяем параметры php
if(Ini_Get('safe_mode'))
	if($Settings['IsEvent'])
		$Messages[] = SPrintF('Необходимо выключить безопасный режим в PHP, т.к. это существенно ограничивает возможности PHP интерпретатора. Найдите в файле %s опцию "safe_mode" и установите ее значение в 0.',PHP_INI_PATH);
#-------------------------------------------------------------------------------
if(Ini_Get('disable_functions'))
	if($Settings['IsEvent'])
		$Messages[] = SPrintF('Внимание! В PHP выключены следюущие функции: "%s". Возможно данные функции потребуются для работы системы. Найдите в файле %s опцию "disable_functions" и установите для нее пустое значение.',$disable_functions,PHP_INI_PATH);
#-------------------------------------------------------------------------------
if(Ini_Get('open_basedir'))
	if($Settings['IsEvent'])
		$Messages[] = SPrintF('Включено ограничение open_basedir. Если необходимые для работы приложения не будут найдены, необходимо закомментировать опцию "open_basedir" в файле %s, или в конфигурации виртуалхоста apache',PHP_INI_PATH);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# проверяем наличие утилиты htmldoc
$Result = Exec('htmldoc --version 2>&1');
#-------------------------------------------------------------------------------
if(!Preg_Match('/not\sfound/',$Result)){
	#-------------------------------------------------------------------------------
	if(Preg_Match('/[0-9]+\.[0-9]+\.[0-9]/',$Result,$HtmlDoc)){
		#-------------------------------------------------------------------------------
		$HtmlDoc = Current($HtmlDoc);
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Tasks/GC/CheckSoftWare]: htmldoc version: %s',$HtmlDoc));
		#-------------------------------------------------------------------------------
		if(FloatVal($HtmlDoc) < 1.8)
			if($Settings['IsEvent'])
				$Messages[] = 'Несовместимая версия htmldoc. Требуется версия htmldoc 1.8+';
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		$Messages[] = 'Не удалось определить версию htmldoc. Попробуйте, выполнить следующу команду "htmldoc --version".';
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Messages[] = 'Приложение htmldoc не найдено.';
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# развешиваем сообщения
if(SizeOf($Events) > 0){
	foreach($Messages as $Message){
		#-------------------------------------------------------------------------------
		$Event = Array(
				'UserID'        => 100,
				'PriorityID'    => 'Error',
				'Text'          => SPrintF('%s Биллинговая система может работать с ошибками, или часть функционала будет недоступна.',$Message),
				'IsReaded'      => FALSE
				);
		#-------------------------------------------------------------------------------
		$Event = Comp_Load('Events/EventInsert',$Event);
		#-------------------------------------------------------------------------------
		if(!$Event)
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
