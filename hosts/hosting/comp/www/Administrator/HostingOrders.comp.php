<?php


#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
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
$DOM->AddAttribs('MenuLeft',Array('args'=>'Administrator/Services'));
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Услуги → Хостинг → Заказы');
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY');
#-------------------------------------------------------------------------------
$Comp1 = Comp_Load('Buttons/Standard',Array('onclick'=>"ShowWindow('/Administrator/HostingOrderEdit');"),'Добавить новый заказ на хостинг','Add.gif');
if(Is_Error($Comp1))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Comp2 = Comp_Load('Buttons/Standard',Array('onclick'=>"ShowWindow('/Administrator/HostingCompensation');"),'Произвести компенсацию времени заказам клиентов','Add.gif');
if(Is_Error($Comp2))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Buttons/Panel',Array('Comp'=>$Comp1,'Name'=>'Добавить заказ'),Array('Comp'=>$Comp2,'Name'=>'Компенсация времени'));
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody->AddChild($Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Super','HostingOrders');
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody->AddChild($Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tab','Administrator/Hosting',$NoBody);
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
return $Out;
#-------------------------------------------------------------------------------

?>
