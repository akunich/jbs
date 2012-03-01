<?php


#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('classes/DOM.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Main')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Обсуждения');
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Buttons/Standard',Array('onclick'=>"ShowWindow('/EdeskEdit');"),'Новое обсуждение','Add.gif');
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Panel = Comp_Load('Buttons/Panel',Array('Comp'=>$Comp,'Name'=>'Новое обсуждение'));
if(Is_Error($Panel))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY',$Panel);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Super','Edesks');
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody->AddChild($Comp);
#-------------------------------------------------------------------------------
$NoBody->AddChild($Panel);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$NoBody);
#-------------------------------------------------------------------------------
$Out = $DOM->Build();
#-------------------------------------------------------------------------------
if(Is_Error($Out))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return $Out;
#-------------------------------------------------------------------------------

?>
