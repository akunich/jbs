<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('modules/Authorisation.mod','libs/HTTP.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
/**
 * Gets PHP info if requested.
 */
if(IsSet($_GET['PHPINFO'])){
	#-------------------------------------------------------------------------------
	PhpInfo();
	#-------------------------------------------------------------------------------
	Exit;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Base')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Домашняя страница');
#-------------------------------------------------------------------------------
$__MESSAGES = &$GLOBALS['__MESSAGES'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(File_Exists(SPrintF('%s/DEBUG.OUT',SYSTEM_PATH)))
	$__MESSAGES[] = 'Биллинговая система работает в режиме отладки, с отображением лога ошибок на экран. Этот режим должен использоваться только при разработке. Отключите его, удалив файл DEBUG.OUT в корневой папке системы.';
#-------------------------------------------------------------------------------
if(IS_DEBUG)
	$__MESSAGES[] = 'Биллинговая система работает в режиме отладки. Отключите данный режим после устанения проблем, удалив файл DEBUG в корневой папке системы.';    
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// отчёты о ошибках на почту
if(File_Exists($File = SPrintF('%s/REPORTS',SYSTEM_PATH))){
	#-------------------------------------------------------------------------------
	$Emails = @File_Get_Contents($File);
	#-------------------------------------------------------------------------------
	if($Emails)
		$__MESSAGES[] = SPrintF('Включена отправка сообщений о ошибках на почтовые адреса: %s',Implode(',',Explode("\n",Trim($Emails))));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Count = DB_Count('Profiles',Array('ID'=>100));
if(Is_Error($Count))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!$Count){
	#-------------------------------------------------------------------------------
	$__MESSAGES[] = 'Профиль исполнителя не найден. Возможность формирования договоров не доступна.';
	#-------------------------------------------------------------------------------
	$DOM->AddAttribs('Body',Array('onload'=>"ShowWindow('/ProfileEdit',{TemplatesIDs:'Juridical,Individual'});"));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!CacheManager::isEnabled())
	$__MESSAGES[] = 'Система кеширования недоступна. Биллинг может работать быстрее используя ресурсы оперативной памяти. Убедитесь что memcached запущен и модуль для PHP установлен.';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Result = DB_Query('SHOW ENGINES');
if(Is_Error($Result))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Engines = MySQL::Result($Result);
if(Is_Error($Engines))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
foreach($Engines as $Engine){
	#-------------------------------------------------------------------------------
	if($Engine['Engine'] == 'InnoDB'){
		#-------------------------------------------------------------------------------
		if($Engine['Support'] != 'YES' && $Engine['Support'] != 'DEFAULT'){
			#-------------------------------------------------------------------------------
			$__MESSAGES[] = 'MySQL собран без поддержки InnoDB, или возможность использования InnoDB в MySQL отключена. Пожалуйста, исправьте возникшую проблему, т.к. биллинговая система не может использовать транзации и поддержку ссылочной целостности, что может привести к потерям данных.';
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IsInitDB = DB_Select('Config','Value',Array('UNIQ','Where'=>"`Param` = 'IsInitDB'"));
#-------------------------------------------------------------------------------
switch(ValueOf($IsInitDB)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	# No more...
	break;
case 'array':
	#-------------------------------------------------------------------------------
	if($IsInitDB['Value'] != 'TRUE'){
		#-------------------------------------------------------------------------------
		$Answer = HTTP_Send('/Patches',Array('Address'=>HOST_ID,'Host'=>HOST_ID,'Port'=>@$_SERVER['SERVER_PORT']));
		if(Is_Error($Answer))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Answer = Explode("\n",$Answer['Body']);
		#-------------------------------------------------------------------------------
		$Result = $Answer[Count($Answer)-1];
		#-------------------------------------------------------------------------------
		$__MESSAGES[] = ($Result != '[OK]'?SPrintF('Ошибка структурирования базы данных (%s)',$Result):'Обнаружен первый запуск системы. Произведено структурирование базы данных.');
		#-------------------------------------------------------------------------------
		# исправляем юзеров
		$Comp = Comp_Load('Tasks/RecoveryUsers',NULL,FALSE);
		#-------------------------------------------------------------------------------
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!Function_Exists('Tidy_Repair_String'))
	$__MESSAGES[] = 'Модуль для php - tidy не установлен. Возможность редактирования HTML страниц может работать не правильно. Пожалуйста, установите tidy: требуемые пакеты php-tidy.';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($disable_functions = Ini_Get('disable_functions'))
	$__MESSAGES[] = SPrintF('Внимание! В PHP выключены следюущие функции: <U>%s</U>. Возможно данные функции потребуются для работы системы. Найдите в файле php.ini опцию <U>disable_functions</U> и установите для нее пустое значение.',$disable_functions);
#-----------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Count($__MESSAGES)){
	#-------------------------------------------------------------------------------
	$Rows = Array();
	#-------------------------------------------------------------------------------
	foreach($GLOBALS['__MESSAGES'] as $Error)
		$Rows[] = Array(new Tag('TD',Array('class'=>'Standard','style'=>'background-color:#FFCCCC;'),$Error));
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Tables/Extended',$Rows,Array('width'=>400),'Сообщения системы');
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$DOM->AddChild('Into',$Comp);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Widgets','Administrator','100%',400);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Comp);
#-------------------------------------------------------------------------------
$Out = $DOM->Build();
#-------------------------------------------------------------------------------
if(Is_Error($Out))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Out;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
