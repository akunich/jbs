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
$UsersIDs   =   (array) @$Args['UsersIDs'];
$MethodsIDs =   (array) @$Args['MethodsIDs'];
$Logic      =  (string) @$Args['Logic'];
$FromID     = (integer) @$Args['FromID'];
$Theme      =  (string) @$Args['Theme'];
$Message    =  (string) @$Args['Message'];
$FiltersIDs =   (array) @$Args['FiltersIDs'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Array = Array();
#-------------------------------------------------------------------------------
foreach($UsersIDs as $UserID)
  $Array[] = (integer)$UserID;
#-------------------------------------------------------------------------------
$UsersIDs = $Array;
#-------------------------------------------------------------------------------
if(!Count($MethodsIDs))
  return new gException('METHODS_NOT_SELECTED','Методы рассылки не выбраны');
#-------------------------------------------------------------------------------
$Dispatches = Array();
#-------------------------------------------------------------------------------
foreach($FiltersIDs as $FilterID){
  #-----------------------------------------------------------------------------
  $FilterID = Explode('|',$FilterID);
  #-----------------------------------------------------------------------------
  $DispatchID = Current($FilterID);
  #-----------------------------------------------------------------------------
  if(!IsSet($Dispatches[$DispatchID])){
    #---------------------------------------------------------------------------
    $Comp = Comp_Load(SPrintF('Dispatch/%s',$DispatchID),TRUE);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Dispatches[$DispatchID] = $Comp;
  }
  #-----------------------------------------------------------------------------
  $Dispatch = $Dispatches[$DispatchID];
  #-----------------------------------------------------------------------------
  if($Dispatch){
    #---------------------------------------------------------------------------
    $FilterID = Next($FilterID);
    #---------------------------------------------------------------------------
    if(IsSet($Dispatch[$FilterID])){
      #-------------------------------------------------------------------------
      $Filter = $Dispatch[$FilterID];
      #-------------------------------------------------------------------------
      $UsersIDs = Array_Merge($UsersIDs,$Filter['UsersIDs']);
    }
  }
}
#-------------------------------------------------------------------------------
switch($Logic){
  case 'AND':
    #---------------------------------------------------------------------------
    $Matches = Array_Count_Values($UsersIDs);
    #---------------------------------------------------------------------------
    $UsersIDs = Array();
    #---------------------------------------------------------------------------
    foreach($Matches as $UserID=>$Match){
      #-------------------------------------------------------------------------
      if($Match == Count($FiltersIDs))
        $UsersIDs[] = $UserID;
    }
  break;
  case 'OR':
    Array_Unique($UsersIDs);
  break;
  default:
    return new gException('WRONG_LOGIC','Не верный способ объединения фильтров');
}
#-------------------------------------------------------------------------------
if(!Count($UsersIDs))
  return new gException('FILTERS_USERS_NOT_FOUND','С использованием фильтров ни один из пользователей для рассылки сообщений не найден');
#-------------------------------------------------------------------------------
$Users = DB_Select('Users','ID',Array('Where'=>SPrintF('`ID` IN (%s)',Implode(',',$UsersIDs))));
#-------------------------------------------------------------------------------
switch(ValueOf($Users)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return new gException('USERS_NOT_FOUND','Пользователи для рассылки уведомлений не найдены');
  break;
  case 'array':
    #---------------------------------------------------------------------------
    $Count = DB_Count('Users',Array('ID'=>$FromID));
    if(Is_Error($Count))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    if(!$Count)
      return new gException('SENDER_NOT_FOUND','Отправитель сообщения не найден');
    #---------------------------------------------------------------------------
    if(!$Theme)
      return new gException('THEME_IS_EMPTY','Введите тему сообщения');
    #---------------------------------------------------------------------------
    if(!$Message)
      return new gException('MESSAGE_IS_EMPTY','Введите сообщение');
    #---------------------------------------------------------------------------
    $Config = &Config();
    #---------------------------------------------------------------------------
    $Methods = &$Config['Notifies']['Methods'];
    #---------------------------------------------------------------------------
    foreach(Array_Keys($Methods) as $MethodID)
      $Methods[$MethodID]['IsActive'] = In_Array($MethodID,$MethodsIDs);
    #---------------------------------------------------------------------------
    $Count = 0;
    #---------------------------------------------------------------------------
    $Replace = Array('Theme'=>$Theme,'Message'=>$Message);
    #--------------------------------------------------------------------------
    $SendTo = Array();
    foreach($Users as $User){
      $SendTo[] = $User['ID'];
    }
    /*
    foreach($Users as $User){
      $msg = new DispatchMsg($Replace, (integer)$User['ID'], $FromID);
      $IsSend = NotificationManager::sendMsg($msg);
      #-------------------------------------------------------------------------
      switch(ValueOf($IsSend)){
        case 'error':
          return ERROR | @Trigger_Error(500);
        case 'exception':
          # No more...
        break;
        case 'true':
          $Count++;
        break;
        default:
          return ERROR | @Trigger_Error(101);
      }
    }
    #---------------------------------------------------------------------------
    if(!$Count)
      return new gException('USERS_NOT_NOTIFIES','Ни один из пользователей не был оповещен');
    */
    #UnSet($UsersIDs);
    $Params = Array(Implode(',',$SendTo),$Theme,$Message);
    #---------------------------------------------------------------------------
    #---------------------------------------------------------------------------
    $IsAdd = Comp_Load('www/Administrator/API/TaskEdit',Array('UserID'=>$FromID,'TypeID'=>'Dispatch','Params'=>$Params));
    #---------------------------------------------------------------------------
    switch(ValueOf($IsAdd)){
    case 'error':
      return ERROR | @Trigger_Error(500);
    case 'exception':
      return ERROR | @Trigger_Error(400);
    case 'array':
      # No more...
      break;
    default:
      return ERROR | @Trigger_Error(101);
    }
    #---------------------------------------------------------------------------
    #---------------------------------------------------------------------------
    return Array('Status'=>'Ok','Users'=>SizeOf($Users));
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
