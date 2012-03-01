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
$ServiceID      = (integer) @$Args['ServiceID'];
$ServiceFieldID = (integer) @$Args['ServiceFieldID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
  return ERROR | @Trigger_Error(500);
#------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'ServiceFieldEditForm','onsubmit'=>'return false;'));
#-------------------------------------------------------------------------------
if($ServiceFieldID){
  #-----------------------------------------------------------------------------
  $ServiceField = DB_Select('ServicesFields','*',Array('UNIQ','ID'=>$ServiceFieldID));
  #-----------------------------------------------------------------------------
  switch(ValueOf($ServiceField)){
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
}else{
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'name'  => 'ServiceID',
      'type'  => 'hidden',
      'value' => $ServiceID
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Form->AddChild($Comp);
  #-----------------------------------------------------------------------------
  $ServiceField = Array(
    #---------------------------------------------------------------------------
    'Name'        => 'Цвет',
    'Prompt'      => 'Указывает на цвет',
    'TypeID'      => 'Select',
    'Options'     => "Black=Черный=10.00\nWhite=Белый=20.00",
    'ValidatorID' => 'Default',
    'Default'     => 'Black',
    'IsDuty'      => TRUE,
    'IsKey'       => TRUE,
    'SortID'      => 10
  );
}
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Standard')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/Administrator/ServiceFieldEdit.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
$DOM->Delete('Title');
#-------------------------------------------------------------------------------
$Table = Array();
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'Name',
    'size'  => 20,
    'value' => $ServiceField['Name']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Название',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/TextArea',
  Array(
    'name' => 'Prompt',
    'rows' => 3,
    'cols' => 25
  ),
  $ServiceField['Prompt']
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Подсказка',$Comp);
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Fields = $Config['Services']['Fields'];
#-------------------------------------------------------------------------------
$Types = $Fields['Types'];
#-------------------------------------------------------------------------------
$Options = Array();
#-------------------------------------------------------------------------------
$Script = Array('var Disabled = {};');
#-------------------------------------------------------------------------------
foreach($Types as $TypeID=>$Type){
  #-----------------------------------------------------------------------------
  $Options[$TypeID] = $Type['Name'];
  #-----------------------------------------------------------------------------
  $Script[] = SPrintF("Disabled['%s'] = %s;",$TypeID,JSON_Encode($Type['Disabled']));
}
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',new Tag('SCRIPT',Array('force-escape'=>'yes'),Implode("\n",$Script)));
#-------------------------------------------------------------------------------
$DOM->AddAttribs('Body',Array('onload'=>'DisabledUpdate();'));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'TypeID','onchange'=>'DisabledUpdate();'),$Options,$ServiceField['TypeID']);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Тип',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/TextArea',
  Array(
    'name' => 'Options',
    'rows' => 5,
    'cols' => 25
  ),
  $ServiceField['Options']
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Список выбора',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'Default',
    'size'  => 15,
    'value' => $ServiceField['Default']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Значение по умолчанию',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'checkbox',
    'name'  => 'IsDuty',
    'value' => 'yes'
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($ServiceField['IsDuty'])
  $Comp->AddAttribs(Array('checked'=>'true'));
#-------------------------------------------------------------------------------
$Table[] = Array('Обязательное поле',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'checkbox',
    'name'  => 'IsKey',
    'value' => 'yes'
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($ServiceField['IsKey'])
  $Comp->AddAttribs(Array('checked'=>'true'));
#-------------------------------------------------------------------------------
$Table[] = Array('Ключевое поле',$Comp);
#-------------------------------------------------------------------------------
$Validators = $Fields['Validators'];
#-------------------------------------------------------------------------------
$Options = Array();
#-------------------------------------------------------------------------------
foreach($Validators as $ValidatorID=>$Validator)
  $Options[$ValidatorID] = $Validator['Name'];
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'ValidatorID'),$Options,$ServiceField['ValidatorID']);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Валидатор',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'name'  => 'SortID',
    'size'  => 5,
    'value' => $ServiceField['SortID']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Порядок сортировки',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'    => 'button',
    'onclick' => 'GetURL(document.referrer);',
    'value'   => 'Отмена'
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Div = new Tag('DIV',Array('align'=>'right'),$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'    => 'button',
    'onclick' => 'ServiceFieldEdit();',
    'value'   => ($ServiceFieldID?'Сохранить':'Добавить')
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Div->AddChild($Comp);
#-------------------------------------------------------------------------------
$Table[] = $Div;
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form->AddChild($Comp);
#-------------------------------------------------------------------------------
if($ServiceFieldID){
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'name'  => 'ServiceFieldID',
      'type'  => 'hidden',
      'value' => $ServiceFieldID
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Form->AddChild($Comp);
}
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Form);
#-------------------------------------------------------------------------------
$Out = $DOM->Build();
#-------------------------------------------------------------------------------
if(Is_Error($Out))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return $Out;
#-------------------------------------------------------------------------------

?>
