<?php


#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php','libs/Http.php')))
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
$DOM->AddAttribs('MenuLeft',Array('args'=>'Administrator/AddIns'));
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Дополнения → Обслуживание системы → Обновление системы');
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY');
#-------------------------------------------------------------------------------
$Img = new Tag('IMG',Array('alt'=>'-','width'=>12,'height'=>10,'src'=>'SRC:{Images/ArrowRight.gif}'));
#-------------------------------------------------------------------------------
$Comp1 = Comp_Load(
  'Form/Input',
  Array(
    'type'    => 'button',
    'onclick' => "form.action = '/Update';form.Commit.value = 0;form.submit();",
    'value'   => '+'
  )
);
if(Is_Error($Comp1))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Comp2 = Comp_Load(
  'Form/Input',
  Array(
    'type'    => 'button',
    'onclick' => "form.action = '/Update';form.Commit.value = 1;form.submit();",
    'value'   => '+'
  )
);
if(Is_Error($Comp2))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Comp3 = Comp_Load(
  'Form/Input',
  Array(
    'type'    => 'button',
    'onclick' => "form.action = '/Patches';form.submit();",
    'value'   => '+'
  )
);
if(Is_Error($Comp3))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Checkbox = Comp_Load(
  'Form/Input',
  Array(
    'type'    => 'checkbox',
    'name'    => 'Backup',
    'checked' => 'yes',
    'value'   => 'yes'
  )
);
if(Is_Error($Checkbox))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Backup = new Tag('NOBODY',new Tag('DIV',Array('class'=>'Standard'),'Структурировать базу данных'),$Checkbox,new Tag('SPAN',Array('style'=>'font-size:11px;'),'сделать резервную копию базы'));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Buttons/Panel',Array('Comp'=>$Comp1,'Name'=>'Проверить наличие обновлений'),$Img,Array('Comp'=>$Comp2,'Name'=>'Применить обновления'),$Img,Array('Comp'=>$Comp3,'Name'=>$Backup));
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'UpdateForm','action'=>'/Update','target'=>'Update','method'=>'POST'),$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type' => 'hidden',
    'name' => 'Commit'
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form->AddChild($Comp);
#-------------------------------------------------------------------------------
$NoBody->AddChild($Form);
#-------------------------------------------------------------------------------
$NoBody->AddChild(new Tag('IFRAME',Array('name'=>'Update','width'=>'650','style'=>'height:240;'),'Загрузка...'));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tab','Administrator/Billing',$NoBody);
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
