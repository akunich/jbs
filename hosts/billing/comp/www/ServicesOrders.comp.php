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
$ServiceID = (integer) @$Args['ServiceID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
  return ERROR | @Trigger_Error(500);
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
$DOM->AddAttribs('MenuLeft',Array('args'=>'User/Services'));
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Заказы на услуги');
#-------------------------------------------------------------------------------
if($ServiceID){
  #-----------------------------------------------------------------------------
  $Service = DB_Select('Services',Array('Item','IsActive'),Array('UNIQ','ID'=>$ServiceID));
  #-----------------------------------------------------------------------------
  switch(ValueOf($Service)){
    case 'error':
      return ERROR | @Trigger_Error(500);
    case 'exception':
      return ERROR | @Trigger_Error(400);
    break;
    case 'array':
      #-------------------------------------------------------------------------
      $DOM->AddText('Title',SPrintF('Услуги → %s',$Service['Item']),TRUE);
      #-------------------------------------------------------------------------
      if($Service['IsActive']){
        #-----------------------------------------------------------------------
        $Comp1 = Comp_Load('Buttons/Standard',Array('onclick'=>SPrintF("ShowWindow('/ServiceOrder',{ServiceID:%u});",$ServiceID)),'Новый заказ','Add.gif');
        if(Is_Error($Comp1))
          return ERROR | @Trigger_Error(500);
        #-----------------------------------------------------------------------
        $Comp2 = Comp_Load('Buttons/Standard',Array('onclick'=>"ShowWindow('/Clause',{ClauseID:'/Help/Services/Paying'});"),'Оплатить (продлить) заказ','Pay.gif');
        if(Is_Error($Comp2))
          return ERROR | @Trigger_Error(500);
        #----------------------------------------------------------------------
        $Comp = Comp_Load('Buttons/Panel',Array('Comp'=>$Comp1,'Name'=>'Новый заказ'),Array('Comp'=>$Comp2,'Name'=>'Оплатить (продлить) заказ'));
        if(Is_Error($Comp))
          return ERROR | @Trigger_Error(500);
        #-----------------------------------------------------------------------
        $DOM->AddChild('Into',$Comp);
      }
      #-------------------------------------------------------------------------
      $Template = Array('Source'=>Array('Conditions'=>Array('Where'=>Array(UniqID()=>SPrintF('`ServiceID` = %u',$ServiceID)))));
      #-------------------------------------------------------------------------
      $Comp = Comp_Load('Tables/Super','ServicesOrders[User]',$Template);
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $DOM->AddChild('Into',$Comp);
    break;
    default:
      return ERROR | @Trigger_Error(101);
  }
}else{
  #-----------------------------------------------------------------------------
  $Service = DB_Select('Services',Array('ID','Code'),Array('UNIQ','Where'=>"`IsActive` = 'yes' AND `IsHidden` = 'no'",'SortOn'=>'SortID','Limits'=>Array('Start'=>0,'Length'=>1)));
  #-----------------------------------------------------------------------------
  switch(ValueOf($Service)){
    case 'error':
      return ERROR | @Trigger_Error(500);
    case 'exception':
      #-------------------------------------------------------------------------
      $Comp = Comp_Load('Information','Активные услуги не найдены. Пожалуйста, по всем вопросам обращайтесь в центр поддержки.','Notice');
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $DOM->AddChild('Into',$Comp);
    break;
    case 'array':
      #-------------------------------------------------------------------------
      $Code = $Service['Code'];
      # added by lissyara, see JBS-176
      if($Code == "Domains"){$Code = "Domain";}
      #-------------------------------------------------------------------------
      Header(SPrintF('Location: /%s',($Code != 'Default'?SPrintF('%sOrders',$Code):SPrintF('ServicesOrders?ServiceID=%s',$Service['ID']))));
      #-------------------------------------------------------------------------
      return NULL;
    default:
      return ERROR | @Trigger_Error(101);
  }
}
#-------------------------------------------------------------------------------
$Out = $DOM->Build();
#-------------------------------------------------------------------------------
if(Is_Error($Out))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return $Out;
#-------------------------------------------------------------------------------

?>
