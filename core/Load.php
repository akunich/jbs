<?php
/**
 * Joonte Billing Core.
 *
 * Configure system environment and handle all users requests.
 * 
 * @author vvelikodny
 */
#-------------------------------------------------------------------------------
$GLOBALS['__MESSAGES'] = Array();
#-------------------------------------------------------------------------------
# счётчики для отладки
$GLOBALS['__COUNTER_MYSQL'] = 0;
$GLOBALS['__TIME_MYSQL'] = 0;
$GLOBALS['__COUNTER_COMPS'] = 0;
#-------------------------------------------------------------------------------
if(!Ini_Get('date.timezone'))
  @Ini_Set('date.timezone','Europe/Moscow');
#-------------------------------------------------------------------------------
List($Micro,$Seconds) = Explode(' ',MicroTime());
#-------------------------------------------------------------------------------
if(!Define('START_TIME',(float)$Micro + (float)$Seconds))
  Exit('[JBs core]: не удалось определить константу (START_TIME)');
#-------------------------------------------------------------------------------
UnSet($Micro,$Seconds);
#-------------------------------------------------------------------------------
Header('X-Powered-By: Joonte Billing System (http://www.joonte.com)');
Header('Cache-Control: no-cache, must-revalidate');
Header('Content-Type: text/html; charset=utf-8');
#-------------------------------------------------------------------------------
if(!Error_Reporting(E_ALL)) # Уровень перехвата ошибок полный
  Exit('[JBs core]: не удалось установить уровень перехвата ошибок');
#-------------------------------------------------------------------------------
Ignore_User_Abort(TRUE); # Если пользователь закрыл соединение выполнение продолжиться

/**
 * Defines system constants.
 */

if(!Define('VERSION', '##VERSION##'))
  Exit('[JBs core]: не удалось определить константу (VERSION)');

/**
 * Defines SYSTEM_PATH constant.
 */
if(!Define('SYSTEM_PATH',DirName(DirName(__FILE__))))
  Exit('[JBs core]: не удалось определить константу (SYSTEM_PATH)');

/**
 * Defines IS_DEBUG constant. Debug mode is enabled if IS_DEBUG is set.
 */
if(!Define('IS_DEBUG',File_Exists(SPrintF('%s/DEBUG',SYSTEM_PATH)))) {
    Exit('[JBs core]: не удалось определить константу (IS_DEBUG)');
}

/** Define shorthand directory separator constant. */
if (!Defined('DS')) {
    Define('DS', DIRECTORY_SEPARATOR);
}
 
/** Defines Smatry internal plugins. */
if (!Defined('SMARTY_SYSPLUGINS_DIR')) {
    Define('SMARTY_SYSPLUGINS_DIR', SPrintF('%s/others/root/smarty/sysplugins/', SYSTEM_PATH));
} 

if (!Defined('SMARTY_PLUGINS_DIR')) {
    Define('SMARTY_PLUGINS_DIR', SPrintF('%s/others/root/smarty/plugins/', SYSTEM_PATH));
}

/**
 * Browsers tokens.
 */
$BrowsersIDs = Array('MSIE','Konqueror','Firefox','Opera','UnKnown');

foreach($BrowsersIDs as $BrowserID){
  //----------------------------------------------------------------------------
  if(Preg_Match(SPrintF('/%s/is',$BrowserID),@$_SERVER['HTTP_USER_AGENT']))
    break;
}
#-------------------------------------------------------------------------------
if(!Define('BROWSER_ID',$BrowserID))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
UnSet($BrowsersIDs,$BrowserID);
#-------------------------------------------------------------------------------
if(!Define('XML_HTTP_REQUEST',IsSet($_GET['XMLHttpRequest']) || IsSet($_POST['XMLHttpRequest'])))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$HostID = StrToLower(@$_SERVER['HTTP_HOST']);
#-------------------------------------------------------------------------------
if(Preg_Match('/^www\.(.+)$/',$HostID,$Mathces))
  $HostID = Next($Mathces);
#-------------------------------------------------------------------------------
if(Preg_Match('/^(.+)\:[0-9]+$/',$HostID,$Mathces))
  $HostID = Next($Mathces);
#-------------------------------------------------------------------------------
if(!Define('HOST_ID',$HostID))
  Exit('[JBs core]: не удалось определить константу (HOST_ID)');
//------------------------------------------------------------------------------
if(!Define('UNIQ_ID',Md5(HOST_ID)))
  Exit('[JBs core]: не удалось определить константу (UNIQ_ID)');
#-------------------------------------------------------------------------------
if(!Define('ERROR',0xABCDEF))
  Exit('[JBs core]: не удалось определить константу (ERROR)');
#-------------------------------------------------------------------------------
UnSet($HostID,$Mathces);
#-------------------------------------------------------------------------------
if(!Define('FUNCTION_INIT',Base64_Decode('aWYoSXNTZXQoJF9fYXJnc190eXBlcykpew0KICBmb3IoJGk9MDskaTxDb3VudCgkX19hcmdzX18pOyRpKyspew0KICAgICRfX2FyZ190eXBlID0gKCRpIDwgQ291bnQoJF9fYXJnc190eXBlcyk/JF9fYXJnc190eXBlc1skaV06JF9fYXJnc190eXBlc1tDb3VudCgkX19hcmdzX3R5cGVzKS0xXSk7DQogICAgaWYoJF9fYXJnX3R5cGUgPT0gJyonKQ0KICAgICAgY29udGludWU7DQogICAgJF9fdHlwZSA9IEdldFR5cGUoJF9fYXJnc19fWyRpXSk7DQogICAgaWYoIUluX0FycmF5KCRfX3R5cGUsRXhwbG9kZSgnLCcsJF9fYXJnX3R5cGUpKSl7DQogICAgICBEZWJ1ZyhQcmludF9SKCRfX2FyZ3NfXyxUUlVFKSk7DQogICAgICBUcmlnZ2VyX0Vycm9yKFNQcmludEYoJ1tGVU5DVElPTl9JTklUXTog0L/QsNGA0LDQvNC10YLRgCAoJXMpINC/0YDQuNC90Y/RgiAoJXMpINC+0LbQuNC00LDQu9GB0Y8gKCVzKScsJGksJF9fdHlwZSwkX19hcmdfdHlwZSkpOw0KICAgIH0NCiAgfQ0KfQ==')))
  Exit('[JBs core]: не удалось определить константу (FUNCTION_INIT)');
#******************************************************************************#
# УСТАНОВКА ПАРАМЕТРОВ PHP
#******************************************************************************#
if(Mb_Internal_Encoding('UTF-8') === FALSE)
  $GLOBALS['__MESSAGES'][] = 'Не удалось установить кодировку UTF-8 (mb_internal_encoding)';
#-------------------------------------------------------------------------------
if($Inis = @Parse_Ini_File(SPrintF('%s/core/php.ini',SYSTEM_PATH),TRUE)){
  #-----------------------------------------------------------------------------
  foreach(Array_Keys($Inis) as $IniID){
    #---------------------------------------------------------------------------
    $Ini = $Inis[$IniID];
    #---------------------------------------------------------------------------
    if($Inis[$IniID] != (integer)Ini_Get($IniID))
      $GLOBALS['__MESSAGES'][] = SPrintF('[JBs core]: ошибка php.ini, требуется %s=%s',$IniID,$Ini);
  }
  #-----------------------------------------------------------------------------
  UnSet($Inis,$IniID,$Ini);
}
#******************************************************************************#
# БАЗОВАЯ ФУНКЦИЯ ЗАГРУЗКИ
#******************************************************************************#
function Load($__FILE__){ require($__FILE__);  }
#******************************************************************************#

function LoadComp($__FILE__){
  //----------------------------------------------------------------------------
  $__args__ = Array_Slice(Func_Get_Args(),2);
  //----------------------------------------------------------------------------
  Debug(SPrintF("Load file: '%s'",$__FILE__));
  $GLOBALS['__COUNTER_COMPS']++;
  //----------------------------------------------------------------------------
  # get file
  $FileContent = File($__FILE__);
  # delete last string
  UnSet($FileContent[(SizeOf($FileContent) - 1)]);
  # delete first string
  UnSet($FileContent[0]);
  # create text from array
  $FileContent = Implode("\n", $FileContent);
  # get result
  $CompResult = Eval($FileContent);
/*  if($CompResult === FALSE) {
    Debug($__FILE__);
    return ERROR | @Trigger_Error(1000);
  }*/
  //----------------------------------------------------------------------------
  return $CompResult;
}
#******************************************************************************#
# СИСТЕМНЫЙ ЛОГ
#******************************************************************************#
$GLOBALS['__SYSLOG'] = Array();

/**
 * Puts debug messages to system log and debug.log file.
 * 
 * @param $message System message.
 */
function Debug($message) {
    $__SYSLOG = &$GLOBALS['__SYSLOG'];

    if (Count($__SYSLOG) > 500) {
        Array_Splice($__SYSLOG, 0, 50);
    }

    $date = Date('H:i:s');

    List($micro, $seconds) = Explode(' ',MicroTime());

    if(isset($_SERVER["REMOTE_PORT"])){$r_port = $_SERVER["REMOTE_PORT"];}else{$r_port = "console";}

    $message = SPrintF('[%s.%02u][%s] %s', $date, $micro * 100, $r_port, $message);

    $__SYSLOG[] = $message;

    if (IS_DEBUG) {
    	umask(0077);
        @File_Put_Contents(SPrintF('%s/debug.log', SYSTEM_PATH), SPrintF("%s\n", $message), FILE_APPEND);
    }
}
#-------------------------------------------------------------------------------
function Report($Theme,$ReportID = ''){
  #-----------------------------------------------------------------------------
  if(File_Exists($File = SPrintF('%s/REPORTS',SYSTEM_PATH))){
    #---------------------------------------------------------------------------
    if(!$ReportID)
      $ReportID = UniqID('ID');
    #---------------------------------------------------------------------------
    $Email = @File_Get_Contents($File);
    if($Email)
      @Mail($Email,$ReportID,Implode("\n",$GLOBALS['__SYSLOG']));
  }
}
#-------------------------------------------------------------------------------
Debug(SPrintF('[JBs core]: запуск системы (%s)',Date('d.n.y')));
Debug(SPrintF('[JBs core]: тип интерфейса сервера (%s)',PHP_SAPI_Name()));
Debug(SPrintF('[JBs core]: IP-адрес сервера (%s)',IsSet($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:'127.0.0.1'));
Debug(SPrintF('[JBs core]: версия PHP интерпретатора (%s)',PhpVersion()));
Debug(SPrintF('[JBs core]: операционная система (%s)',Php_Uname()));
#-------------------------------------------------------------------------------
if(Function_Exists('posix_getpwuid')){
  #-----------------------------------------------------------------------------
  $USER = Posix_GetPWUID(Posix_GetUID());
  #-----------------------------------------------------------------------------
  Debug(SPrintF('[JBs core]: система запущена от имени пользователя (%s)',$USER['name']));
}
#-------------------------------------------------------------------------------
Debug(SPrintF('[JBs core]: осуществлен запрос с адреса (%s)',$_SERVER['REMOTE_ADDR']));
Debug(SPrintF('[JBs core]: REQUEST_URI=(%s)',@$_SERVER['REQUEST_URI']));
#******************************************************************************#
# ПОДСИСТЕМА ОТЛАДКИ
#******************************************************************************#
$__ERR_CODE = 100;
#-------------------------------------------------------------------------------
function __Error_Handler__($Number,$Error,$File,$Line){
  #-----------------------------------------------------------------------------
  $Message = SPrintF('[%s]-%s в линии %s файла %s',$Number,$Error,$Line,$File);
  #-----------------------------------------------------------------------------
  $__ERR_CODE = &$GLOBALS['__ERR_CODE'];
  #-----------------------------------------------------------------------------
  if((integer)$Error && $__ERR_CODE == 100)
    $__ERR_CODE = $Error;
  #-----------------------------------------------------------------------------
  Debug(SPrintF('[!] %s',$Message));
  #Debug(SPrintF('[!] %s',debug_print_backtrace()));
  #-----------------------------------------------------------------------------
  #Error_Reporting(E_ALL);
  if(Error_Reporting()){
    #---------------------------------------------------------------------------
    $JBsErrorID = SPrintF('%s[%s]',HOST_ID,Md5(Implode(':',Array($Number,$Error,$Line,$File))));
    #---------------------------------------------------------------------------
    $__SYSLOG = &$GLOBALS['__SYSLOG'];
    #---------------------------------------------------------------------------
    $Log = Implode("\n",$__SYSLOG);
    #---------------------------------------------------------------------------
    $Debugger = @FsockOpen('127.0.0.1',9000,$nError,$sError,0);
    if(Is_Resource($Debugger)){
      #-------------------------------------------------------------------------
      if(!@Fwrite($Debugger,$Log))
        Debug('[__Error_Handler__]: не удалось отправить лог в отладчик');
      #-------------------------------------------------------------------------
      FClose($Debugger);
    }else{
      #-------------------------------------------------------------------------
      Report($JBsErrorID,$JBsErrorID);
      #-------------------------------------------------------------------------
      foreach(Array(SYSTEM_PATH,'/tmp') as $Folder){
        #-----------------------------------------------------------------------
        $Path = SPrintF('%s/jbs-errors.log',$Folder);
        #-----------------------------------------------------------------------
        if(File_Exists($Path)){
          #---------------------------------------------------------------------
          if(FileSize($Path) > 1024*1024)
            UnLink($Path);
        }
        #-----------------------------------------------------------------------
	umask(0077);
	#-----------------------------------------------------------------------
        if(!@File_Put_Contents($Path,SPrintF("%s\n\n%s\n\n",$JBsErrorID,$Log),FILE_APPEND)){
          #---------------------------------------------------------------------
          Debug(SPrintF('[__Error_Handler__]: не удалось осуществить запись ошибки в системный лог (%s)',$Path));
          #---------------------------------------------------------------------
          continue;
        }
        #-----------------------------------------------------------------------
        break;
      }
    }
    #---------------------------------------------------------------------------
    if(File_Exists(SPrintF('%s/DEBUG',SYSTEM_PATH)))
      Exit($Log);
    else{
      #-------------------------------------------------------------------------
      $Errors = Array(
        #-----------------------------------------------------------------------
        100 => 'Ошибка выполнения',
        101 => 'Неизвестный результат',
        201 => 'Неверные параметры',
        400 => 'Ошибка данных',
        500 => 'Системная ошибка',
        600 => 'Ошибка политики безопасности',
        700 => 'Нарушение политики прав'
      );
#-------------------------------------------------------------------------------
$Result = <<<EOD
<HTML>
 <HEAD>
  <TITLE>%s</TITLE>
  <META http-equiv="Content-Type" content="text/html" charset="utf-8" />
  <STYLE>
   body{
     background-color: #F9F9F9;
     font-family:      Verdana;
     font-size:        12px;
   }
   h1{
     color:       #A65300;
     font-size:   25px;
     font-weight: normal;
     margin:      15px;
     text-align:  left;
   }
   p{
     border: 1px solid #B7B7FF;
     background-color: #FFFFD7;
     margin:           10px;
     padding:          5px;
   }
  </STYLE>
 </HEAD>
 <BODY>
  <H1>%s</H1>
  <P>
   <SPAN>Приносим свои извинения.</SPAN>
   <BR />
   <SPAN>В ближайшее время мы постараемся исправить возникшую проблему.</SPAN>
   <BR />
   <SPAN>Идентификтор ошибки: </SPAN><B>%s</B>
  </P>
  <DIV style="font-size:10px;margin:10px;">
   <SPAN>Joonte Software 2007-2010</SPAN>
   <A href="http://www.joonte.com">http://www.joonte.com</A>
  </DIV>
 </BODY>
</HTML>
EOD;
#-------------------------------------------------------------------------------
      $String = SPrintf('%s (%s)',$Errors[$__ERR_CODE],$__ERR_CODE);
      #-------------------------------------------------------------------------
      @Header(SPrintF('JBs-ErrorID: %s',$JBsErrorID));
      #-------------------------------------------------------------------------
      if(IsSet($_POST['XMLHttpRequest'])){
        #-----------------------------------------------------------------------
        $Answer = Array('Error'=>Array('CodeID'=>$__ERR_CODE,'String'=>$String),'Status'=>'Error');
        #-----------------------------------------------------------------------
        Exit(JSON_Encode($Answer));
      }else
        Exit(SPrintF($Result,$String,$String,$JBsErrorID));
    }
  }
}
#-------------------------------------------------------------------------------
if(Set_Error_Handler('__Error_Handler__') === FALSE)
  Exit('Не удалось установить перехват ошибок');
#******************************************************************************#
# НАСТРОЙКА СРЕДЫ ВЫПОЛНЕНИЯ
#******************************************************************************#
$PATH = (IsSet($_ENV['PATH'])?$_ENV['PATH']:'');
#-------------------------------------------------------------------------------
$PATH = SPrintF('%s:/usr/local/bin:/usr/local/sbin:/usr/bin:/usr/sbin:/bin:/sbin:%s:%s:%s',$PATH,SYSTEM_PATH,DirName(SYSTEM_PATH),DirName(DirName(SYSTEM_PATH)));
#-------------------------------------------------------------------------------
if(!PutENV(SPrintF('PATH=%s',$PATH)))
  $GLOBALS['__MESSAGES'][] = '[JBs core]: не удалось установить переменную окружения PATH';
#-------------------------------------------------------------------------------
Debug(SPrintF('[JBs core]: PATH=(%s)',$PATH));
#-------------------------------------------------------------------------------
UnSet($PATH);
#******************************************************************************#
# ЗАГРУЗКА ХОСТА
#******************************************************************************#
$HOST_CONF = @Parse_Ini_File($Path = SPrintF('%s/hosts/%s/host.ini',SYSTEM_PATH,HOST_ID));
if(!$HOST_CONF)
  Exit(SPrintF('[JBs core]: ошибка загрузки конфигурации хоста (%s)',$Path));
#-------------------------------------------------------------------------------
$HOST_CONF['HostsIDs'] = Explode(',',$HOST_CONF['HostsIDs']);
#-------------------------------------------------------------------------------
UnSet($Path);
/******************************************************************************/
# ЗАГРУЗКА БИБЛИОТЕК И КЛАССОВ
/******************************************************************************/
# Предзагрузка
/******************************************************************************/
Debug('[JBs core]: загрузка автозагружаемых классов и библиотек');
#-------------------------------------------------------------------------------
$HostsIDs = $GLOBALS['HOST_CONF']['HostsIDs'];
#-------------------------------------------------------------------------------
foreach(Array('libs','classes') as $Folder){
  #-----------------------------------------------------------------------------
  foreach($HostsIDs as $HostID){
    #---------------------------------------------------------------------------
    $Path = SPrintF('%s/hosts/%s/system/%s/auto',SYSTEM_PATH,$HostID,$Folder);
    #---------------------------------------------------------------------------
    if(!File_Exists($Path))
      continue;
    #---------------------------------------------------------------------------
    $Resource = OpenDir($Path);
    #---------------------------------------------------------------------------
    while($File = ReadDir($Resource)){
      #-------------------------------------------------------------------------
      if(($File != '.') && ($File != '..') && ($File != '.svn') && ($File != 'plugins') && ($File != 'sysplugins')){
        #-----------------------------------------------------------------------
        $File = SPrintF('%s/%s',$Path,$File);
        #-----------------------------------------------------------------------
        Debug(SPrintF('[JBs core]: загружается системный компонент (%s)',$File));
        #-----------------------------------------------------------------------
        if(Load($File) === ERROR)
          return ERROR | Trigger_Error('[JBs core]: не удалось загрузить элемент ядра');
      }
    }
    #---------------------------------------------------------------------------
    CloseDir($Resource);
  }
}
#-------------------------------------------------------------------------------
UnSet($Folder,$HostsIDs,$HostID,$Path,$Resource,$File);

/**
 * Custom class loader.
 *
 * @param <type> $class Class name for load.
 */
function JoonteAutoLoad($class) {
  #-----------------------------------------------------------------------------
  $ClassPath = System_Element('system/classes/'.$class.'.class.php');
  #-----------------------------------------------------------------------------
  if (Is_Error($ClassPath)) {
      throw new Exception("Coudn't load class: ".$ClassPath);
  }

  include_once($ClassPath);
}

spl_autoload_register('JoonteAutoLoad');

/**
 * Request processing.
 */
$__URI = $_SERVER['REQUEST_URI'];
#-------------------------------------------------------------------------------
$Index = StrPos($__URI,'?');
#-------------------------------------------------------------------------------
if(Is_Int($Index))
  $__URI = SubStr($__URI,0,$Index);
#-------------------------------------------------------------------------------
UnSet($Index);
#-------------------------------------------------------------------------------
Debug(SPrintF('[JBs core]: внешний запрос сформирован как (__URI=%s)',$__URI));

/**
 * Custom shutdown function.
 */
function __ShutDown_Function__(){
  #-----------------------------------------------------------------------------
  #-----------------------------------------------------------------------------
  # added by lissyara 2011-10-12 in 16:15 MSK, for JBS-173
  #Debug("[JBs core]:" . print_r($GLOBALS, true));
  List($Micro,$Seconds) = Explode(' ',MicroTime());
  $WorkTimeTmp = (float)$Micro + (float)$Seconds - START_TIME;
  $UserData = Array(
    #-------------------------------------------------------------
    'CreateDate'	=> Time(),
    'UserID'		=> IsSet($GLOBALS['__USER'])			?$GLOBALS['__USER']['ID']:10,
    'REMOTE_ADDR'	=> IsSet($GLOBALS['_SERVER']['REMOTE_ADDR'])	?$GLOBALS['_SERVER']['REMOTE_ADDR']:'',
    'REQUEST_URI'	=> IsSet($GLOBALS['_SERVER']['REQUEST_URI'])	?$GLOBALS['_SERVER']['REQUEST_URI']:'',
    'HTTP_REFERER'	=> IsSet($GLOBALS['_SERVER']['HTTP_REFERER'])	?$GLOBALS['_SERVER']['HTTP_REFERER']:'',
    'HTTP_USER_AGENT'	=> IsSet($GLOBALS['_SERVER']['HTTP_USER_AGENT'])?$GLOBALS['_SERVER']['HTTP_USER_AGENT']:'',
    'WORK_TIME'		=> $WorkTimeTmp,
    'TIME_MYSQL'	=> $GLOBALS['__TIME_MYSQL'],
    'COUNTER_MYSQL'	=> $GLOBALS['__COUNTER_MYSQL'],
    'COUNTER_COMPS'	=> $GLOBALS['__COUNTER_COMPS']
  );
  #---------------------------------------------------------------
/*  if($GLOBALS['_SERVER']['REQUEST_URI'] != '/API/Events'){
    $IsInsert = DB_Insert('RequestLog',$UserData);
    if(Is_Error($IsInsert))
      return ERROR | @Trigger_Error(500);
  }*/
  #-----------------------------------------------------------------------------
  #-----------------------------------------------------------------------------
  List($Micro,$Seconds) = Explode(' ',MicroTime());
  #-----------------------------------------------------------------------------
  if(!Define('WORK_TIME',((float)$Micro + (float)$Seconds) - START_TIME))
    Exit('[JBs core]: не удалось определить константу (WORK_TIME)');
  #-----------------------------------------------------------------------------
  Debug(SPrintF('[JBs core]: система работала: %s',WORK_TIME));
  # added by lissyara, 2011-10-11 in 15:27 MSK
  Debug('[JBs core]: время работы MySQL: ' . $GLOBALS['__TIME_MYSQL'] . " [" . Round($GLOBALS['__TIME_MYSQL'] / WORK_TIME * 100, 2) . "%]");
  Debug('[JBs core]: запросов к MySQL: ' . $GLOBALS['__COUNTER_MYSQL']);
  Debug('[JBs core]: загружено компонентов: ' . $GLOBALS['__COUNTER_COMPS']);

  Debug('');
  Debug('');
  Debug('');
  Debug('');
  #-----------------------------------------------------------------------------
  if(IS_DEBUG && WORK_TIME > 100){
    #---------------------------------------------------------------------------
    $Debugger = @FsockOpen('127.0.0.1',9000,$nError,$sError,0);
    if(Is_Resource($Debugger)){
      #-------------------------------------------------------------------------
      @Fwrite($Debugger,Implode("\n",$GLOBALS['__SYSLOG']));
      #-------------------------------------------------------------------------
      FClose($Debugger);
    }
  }
}

/**
 * Register custom shutdown function.
 */
Register_ShutDown_Function('__ShutDown_Function__');

/**
 * Load modules
 */
$Loaded = Array();
#-------------------------------------------------------------------------------
$HostsIDs = $GLOBALS['HOST_CONF']['HostsIDs'];
#-------------------------------------------------------------------------------
foreach($HostsIDs as $HostID){
  #-----------------------------------------------------------------------------
  $Path = SPrintF('%s/hosts/%s/system/modules/auto',SYSTEM_PATH,$HostID);
  #-----------------------------------------------------------------------------
  if(!File_Exists($Path))
    continue;
  #-----------------------------------------------------------------------------
  $Folder = OpenDir($Path);
  if(!$Folder)
    return ERROR | Trigger_Error('[JBs core]: не возможно открыть папку модулей');
  #-----------------------------------------------------------------------------
  while($File = ReadDir($Folder)){
    #---------------------------------------------------------------------------
    if(($File != '.') && ($File != '..') && ($File != '.svn')){
      #-------------------------------------------------------------------------
      if(In_Array($File,$Loaded))
        continue;
      #-------------------------------------------------------------------------
      $Module = SPrintF('%s/%s',$Path,$File);
      #-------------------------------------------------------------------------
      Debug(SPrintF('[JBs core]: загружается модуль (%s)',$Module));
      #-------------------------------------------------------------------------
      if(Load($Module) === ERROR)
        return ERROR | Trigger_Error('[JBs core]: не удалось загрузить модуль');
      #-------------------------------------------------------------------------
      $Loaded[] = $File;
    }
  }
  
  CloseDir($Folder);
}

/**
 *  Configure Smarty template engine.
 */
$smarty  = JSmarty::get();

// Sets template paths.
$templatePaths = Array();

foreach($HostsIDs as $HostID) {
    $templatePaths[] = SPrintF('%s/hosts/%s/templates', SYSTEM_PATH, $HostID);
}

$smarty->setTemplateDir($templatePaths);

$smarty->setCompileDir(SPrintF('%s/hosts/%s/tmp/template_c', SYSTEM_PATH, HOST_ID));
$smarty->setCacheDir(SPrintF('%s/hosts/%s/tmp/cache', SYSTEM_PATH, HOST_ID));
$smarty->setConfigDir(SPrintF('%s/others/root/smarty/configs', SYSTEM_PATH));

//$GLOBALS['smarty']=$smarty;

UnSet($Loaded,$HostsIDs,$HostID,$Path,$Folder,$File,$Module);
/**
 * Start main module.
 */
$HostsIDs = $GLOBALS['HOST_CONF']['HostsIDs'];
#-------------------------------------------------------------------------------
foreach($HostsIDs as $HostID) {
  #-----------------------------------------------------------------------------
  $Path = SPrintF('%s/hosts/%s/system/modules/Main.php', SYSTEM_PATH, $HostID);
  #-----------------------------------------------------------------------------
  if (File_Exists($Path)) {
    if(Load($Path) === ERROR) {
      return ERROR | Trigger_Error('[JBs core]: не удалось загрузить базовый модуль');
    }
    
    break;
  }
}
UnSet($HostsIDs,$HostID,$Path);
?>
