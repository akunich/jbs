<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$Backup = (boolean) @$Args['Backup'];
$Force  = (boolean) @$Args['Force'];
#-------------------------------------------------------------------------------
Header('Content-type: text/plain; charset=utf-8');
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$DBConnection = $Config['DBConnection'];
#-------------------------------------------------------------------------------
if($Backup && !Preg_Match('/^Windows/',Php_UName('s'))){
  #-----------------------------------------------------------------------------
  echo "-- Резервное копирование базы данных\n\n";
  #-----------------------------------------------------------------------------
  $Tmp = System_Element('tmp');
  if(Is_Error($Tmp))
    return SPrintF("---\n%s\n---\n",Implode("\n",Array_Slice($__SYSLOG,Count($__SYSLOG)-20)));
  #-----------------------------------------------------------------------------
  $Folder = SPrintF('%s/db',$Tmp);
  #-----------------------------------------------------------------------------
  if(!File_Exists($Folder)){
    #---------------------------------------------------------------------------
    if(!@MkDir($Folder,0777,TRUE))
      return ERROR | @Trigger_Error(500);
  }
  #-----------------------------------------------------------------------------
  $DbName = $DBConnection['DbName'];
  #-----------------------------------------------------------------------------
  $Command = SPrintF('find %s -name "%s*.gz" -type f -mtime +2 -exec rm -f {} \;',$Folder,$DbName);
  #-----------------------------------------------------------------------------
  $Log = Array();
  #-----------------------------------------------------------------------------
  if(Exec($Command,$Log)){
    #---------------------------------------------------------------------------
    echo SPrintF("ERROR: ошибка очистки старых резервных копий:\n%s\n",Implode("\n",$Log));
    #---------------------------------------------------------------------------
    if(!$Force)
      return;
  }
  #-----------------------------------------------------------------------------
  $Command = 'cd %s;mysqldump --host=%s --port=%u --user=%s --password=%s --quote-names -r %s %s 2>&1;gzip %s';
  #-----------------------------------------------------------------------------
  $File = SPrintF('%s.sql',UniqID(SPrintF('%s_',$DbName)));
  #-----------------------------------------------------------------------------
  $Command = SPrintF($Command,$Folder,$DBConnection['Server'],$DBConnection['Port'],$DBConnection['User'],$DBConnection['Password'],$File,$DbName,$File);
  #-----------------------------------------------------------------------------
  $Log = Array();
  #-----------------------------------------------------------------------------
  $File = SPrintF('%s/%s.gz',$Folder,$File);
  #-----------------------------------------------------------------------------
  if(Exec($Command,$Log)){
    #---------------------------------------------------------------------------
    echo SPrintF("ERROR: ошибка создания резервной копии базы данных:\n%s\n",Implode("\n",$Log));
    #---------------------------------------------------------------------------
    if(!$Force)
      return;
  }
  #-----------------------------------------------------------------------------
  if(!File_Exists($File)){
    #---------------------------------------------------------------------------
    echo "ERROR: файл резервной копии не был создан\n";
    #---------------------------------------------------------------------------
    if(!$Force)
      return;
  }
  #-----------------------------------------------------------------------------
  $Size = @FileSize($File);
  #-----------------------------------------------------------------------------
  if($Size < 1024){
    #---------------------------------------------------------------------------
    echo "ERROR: файл резервной копии поврежден\n";
    #---------------------------------------------------------------------------
    if(!$Force)
      return;
  }else
    echo SPrintF("Файл (%s) резервной копии имеет размер %u Кб.\n",$File,$Size/1024);
  #-----------------------------------------------------------------------------
  echo "\n\n";
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IsUpdate = DB_Update('Tasks',Array('IsActive'=>TRUE,'IsExecuted'=>FALSE),Array('Where'=>'`TypeID` = "RecoveryProfiles"'));
if(Is_Error($IsUpdate))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$HostsIDs = Array_Reverse($GLOBALS['HOST_CONF']['HostsIDs']);
#-------------------------------------------------------------------------------
echo "\n\n-- Патчи базы данных\n\n";
#-------------------------------------------------------------------------------
foreach($HostsIDs as $HostID){
  #-----------------------------------------------------------------------------
  $Folder = SPrintF('%s/patches/%s/db',SYSTEM_PATH,$HostID);
  #-----------------------------------------------------------------------------
  if(!File_Exists($Folder))
    continue;
  #-----------------------------------------------------------------------------
  $PatchesIDs = IO_Scan($Folder);
  if(Is_Error($PatchesIDs))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  if(!Count($PatchesIDs))
    continue;
  #-----------------------------------------------------------------------------
  Sort($PatchesIDs);
  #-----------------------------------------------------------------------------
  $LastPatchDB = DB_Select('Config','Value',Array('UNIQ','Where'=>SPrintF("`Param` = 'LastPatchDB' AND `HostID` = '%s'",$HostID)));
  #-----------------------------------------------------------------------------
  switch(ValueOf($LastPatchDB)){
    case 'error':
      return ERROR | @Trigger_Error(500);
    case 'exception':
      #-------------------------------------------------------------------------
      $LastPatchDB = IntVal(End($PatchesIDs));
      #-------------------------------------------------------------------------
      echo SPrintF("Установка максимального патча базы данных (%u) хоста (%s)\n",$LastPatchDB,$HostID);
      #-------------------------------------------------------------------------
      $InInsert = DB_Insert('Config',Array('HostID'=>$HostID,'Param'=>'LastPatchDB','Value'=>$LastPatchDB));
      if(Is_Error($InInsert))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
    break;
    case 'array':
      #-------------------------------------------------------------------------
      $LastPatchDB = IntVal($LastPatchDB['Value']);
      #-------------------------------------------------------------------------
      echo SPrintF("Последний патч базы данных хоста (%s) (%u)\n",$HostID,$LastPatchDB);
      #-------------------------------------------------------------------------
      foreach($PatchesIDs as $PatchID){
        #-----------------------------------------------------------------------
        $PatchInt = IntVal($PatchID);
        #-----------------------------------------------------------------------
        if($PatchInt > $LastPatchDB){
          #---------------------------------------------------------------------
          $Patch = IO_Read(SPrintF('%s/%s',$Folder,$PatchID));
          if(Is_Error($Patch))
            return ERROR | @Trigger_Error(500);
          #---------------------------------------------------------------------
          echo SPrintF("Применение патча базы данных хоста (%s)\n---\n%s\n---\n",$HostID,$Patch);
          #---------------------------------------------------------------------
          $Patch = Explode('-- SEPARATOR',$Patch);
          #---------------------------------------------------------------------
          foreach($Patch as $Query){
            #-------------------------------------------------------------------
            $IsQuery = DB_Query($Query);
            if(Is_Error($IsQuery)){
              #-----------------------------------------------------------------
              $Link = &Link_Get('DB');
              #-----------------------------------------------------------------
              echo SPrintF("ERROR: ошибка применения патча (%s)\n",$Link->GetError());
              #-----------------------------------------------------------------
              if(!$Force)
                return;
            }
          }
          #---------------------------------------------------------------------
          $IsUpdate = DB_Update('Config',Array('Value'=>$PatchInt),Array('Where'=>SPrintF("`Param` = 'LastPatchDB' AND `HostID` = '%s'",$HostID)));
          if(Is_Error($IsUpdate))
            return ERROR | @Trigger_Error(500);
        }
      }
    break;
    default:
      return ERROR | @Trigger_Error(101);
  }
}
#-------------------------------------------------------------------------------
echo "\n\n-- Патчи файлов\n\n";
#-------------------------------------------------------------------------------
foreach($HostsIDs as $HostID){
  #-----------------------------------------------------------------------------
  $Folder = SPrintF('%s/patches/%s/files',SYSTEM_PATH,$HostID);
  #-----------------------------------------------------------------------------
  if(!File_Exists($Folder))
    continue;
  #-----------------------------------------------------------------------------
  $PatchesIDs = IO_Scan($Folder);
  if(Is_Error($PatchesIDs))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  if(!Count($PatchesIDs))
    continue;
  #-----------------------------------------------------------------------------
  Sort($PatchesIDs);
  #-----------------------------------------------------------------------------
  $File = SPrintF('%s/hosts/%s/.LastPatchFiles',SYSTEM_PATH,$HostID);
  #-----------------------------------------------------------------------------
  if(!File_Exists($File)){
    #---------------------------------------------------------------------------
    $LastPatchFiles = IntVal(End($PatchesIDs)); 
    #---------------------------------------------------------------------------
    $IsWrite = IO_Write($File,(string)$LastPatchFiles,TRUE);
    if(Is_Error($IsWrite))
      return ERROR | @Trigger_Error(500);
  }else{
    #---------------------------------------------------------------------------
    $LastPatchFiles = IO_Read($File);
    if(Is_Error($LastPatchFiles))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $LastPatchFiles = IntVal($LastPatchFiles);
    #---------------------------------------------------------------------------
    echo SPrintF("Последний патч файлов хоста (%s) (%u)\n",$HostID,$LastPatchFiles);
    #---------------------------------------------------------------------------
    foreach($PatchesIDs as $PatchID){
      #-------------------------------------------------------------------------
      $PatchInt = IntVal($PatchID);
      #-------------------------------------------------------------------------
      if($PatchInt > $LastPatchFiles){
        #-----------------------------------------------------------------------
        echo SPrintF("Применение патча файлов хоста (%s) (%s)\n",$HostID,$PatchID);
        #-----------------------------------------------------------------------
        if(!$Force){
          #---------------------------------------------------------------------
          $IsLoad = Load(SPrintF('%s/%s',$Folder,$PatchID));
          #---------------------------------------------------------------------
          if(Is_Error($IsLoad)){
            #-------------------------------------------------------------------
            echo "Ошибка применения патча\n";
            #-------------------------------------------------------------------
            $__SYSLOG = &$GLOBALS['__SYSLOG'];
            #-------------------------------------------------------------------
            echo SPrintF("---\n%s\n---\n",Implode("\n",Array_Slice($__SYSLOG,Count($__SYSLOG)-20)));
            #-------------------------------------------------------------------
            if(!$Force)
              return;
          }
        }
        #-----------------------------------------------------------------------
        $IsWrite = IO_Write($File,(string)$PatchInt,TRUE);
        if(Is_Error($IsWrite))
          return ERROR | @Trigger_Error(500);
      }
    }
  }
}
#-------------------------------------------------------------------------------
echo "\n\n-- Перезагрузка базы данных\n\n";
#-------------------------------------------------------------------------------
foreach($HostsIDs as $HostID){
  #-----------------------------------------------------------------------------
  $Path = SprintF('%s/db/%s/permissions.sql',SYSTEM_PATH,$HostID);
  #-----------------------------------------------------------------------------
  if(File_Exists($Path)){
    #---------------------------------------------------------------------------
    echo SPrintF("Перезагрузка прав для хоста (%s)\n",$HostID);
    #---------------------------------------------------------------------------
    $Command = 'mysql --host=%s --port=%u --user=%s --password=%s %s 2>&1 < %s';
    #---------------------------------------------------------------------------
    $Command = SPrintF($Command,$DBConnection['Server'],$DBConnection['Port'],$DBConnection['User'],$DBConnection['Password'],$DBConnection['DbName'],$Path);
    #---------------------------------------------------------------------------
    $Log = Array();
    #---------------------------------------------------------------------------
    if(Exec($Command,$Log))
      return SPrintF("ERROR: ошибка перезагрузки прав:\n%s",Implode("\n",$Log));
  }
}
#-------------------------------------------------------------------------------
foreach($HostsIDs as $HostID){
  #-----------------------------------------------------------------------------
  $Path = SprintF('%s/db/%s/views.sql',SYSTEM_PATH,$HostID);
  #-----------------------------------------------------------------------------
  if(File_Exists($Path)){
    #---------------------------------------------------------------------------
    echo SPrintF("Перезагрузка представлений для хоста (%s)\n",$HostID);
    #---------------------------------------------------------------------------
    $Command = 'mysql --host=%s --port=%u --user=%s --password=%s %s 2>&1 < %s';
    #---------------------------------------------------------------------------
    $Command = SPrintF($Command,$DBConnection['Server'],$DBConnection['Port'],$DBConnection['User'],$DBConnection['Password'],$DBConnection['DbName'],$Path);
    #-------------------------------------------------------------------------
    $Log = Array();
    #-------------------------------------------------------------------------
    if(Exec($Command,$Log))
      return SPrintF("ERROR: ошибка перезагрузки представлений:\n%s",Implode("\n",$Log));
  }
}
#-------------------------------------------------------------------------------
foreach($HostsIDs as $HostID){
  #-----------------------------------------------------------------------------
  $Path = SprintF('%s/db/%s/triggers.sql',SYSTEM_PATH,$HostID);
  #-----------------------------------------------------------------------------
  if(File_Exists($Path)){
    #---------------------------------------------------------------------------
    echo SPrintF("Перезагрузка триггеров для хоста (%s)\n",$HostID);
    #---------------------------------------------------------------------------
    $Command = 'mysql --host=%s --port=%u --user=%s --password=%s %s 2>&1 < %s';
    #---------------------------------------------------------------------------
    $Command = SPrintF($Command,$DBConnection['Server'],$DBConnection['Port'],$DBConnection['User'],$DBConnection['Password'],$DBConnection['DbName'],$Path);
    #---------------------------------------------------------------------------
    $Log = Array();
    #---------------------------------------------------------------------------
    if(Exec($Command,$Log))
      return SPrintF("ERROR: ошибка перезагрузки триггеров:\n%s",Implode("\n",$Log));
  }
}
#-------------------------------------------------------------------------------
foreach($HostsIDs as $HostID){
  #-----------------------------------------------------------------------------
  $Path = SprintF('%s/db/%s/functions.sql',SYSTEM_PATH,$HostID);
  #-----------------------------------------------------------------------------
  if(File_Exists($Path)){
    #---------------------------------------------------------------------------
    echo SPrintF("Перезагрузка функций для хоста (%s)\n",$HostID);
    #---------------------------------------------------------------------------
    $Command = 'mysql --host=%s --port=%u --user=%s --password=%s %s 2>&1 < %s';
    #---------------------------------------------------------------------------
    $Command = SPrintF($Command,$DBConnection['Server'],$DBConnection['Port'],$DBConnection['User'],$DBConnection['Password'],$DBConnection['DbName'],$Path);
    #---------------------------------------------------------------------------
    $Log = Array();
    #---------------------------------------------------------------------------
    if(Exec($Command,$Log))
      return SPrintF("ERROR: ошибка перезагрузки функций:\n%s",Implode("\n",$Log));
  }
}
#-------------------------------------------------------------------------------
$IsUpdate = DB_Update('Config',Array('Value'=>'TRUE'),Array('Where'=>"`Param` = 'IsInitDB'"));
if(Is_Error($IsUpdate))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$IsFlush = CacheManager::flush();
if(!$IsFlush)
  @Trigger_Error(500);
#-------------------------------------------------------------------------------
return '[OK]';
#-------------------------------------------------------------------------------


?>
