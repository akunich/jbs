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
if(Is_Error($DOM->Load('Window')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Персональные данные');
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/UserPersonalDataChange.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
$Messages = Messages();
#-------------------------------------------------------------------------------
$Table = Array('Общая информация');
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'name'   => 'Name',
    'size'   => 25,
    'type'   => 'text',
    'prompt' => $Messages['Prompts']['User']['Name'],
    'value'  => $__USER['Name']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Ваше имя',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/TextArea',
  Array(
    'name' => 'Sign',
    'rows' => 3,
    'cols' => 30
  ),
  $__USER['Sign']
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Подпись',$Comp);
#-------------------------------------------------------------------------------
$Table[] = 'Ваши контактные данные';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'name'	=> 'Email',
    'size'	=> 25,
    'type'	=> 'text',
    'prompt'	=> 'Для смены почтового адреса, подтвердите текущий почтовый адрес, и обратитесь в "Центр поддержки", указав новый адрес, на который надо сменить текущий',
    'value'	=> $__USER['Email'],
    'readonly'	=> 'readonly',
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(CacheManager::isEnabled()){
  $Config = Config();
  #-------------------------------------------------------------------------------
  if($Config['Notifies']['Methods']['Email']['IsActive']){
    #-----------------------------------------------------------------------------
    if($__USER['EmailConfirmed'] > 0){
      $EmailConfirmed = Comp_Load('Formats/Date/Extended',$__USER['EmailConfirmed']);
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      $Prompt = "Ваш почтовый адрес был подтверждён: " . $EmailConfirmed;
    }else{
      $Prompt = "Нажмите для подтверждения вашего почтового адреса";
    }
    #-----------------------------------------------------------------------------
    $Comp = Comp_Load(
      'Form/Input',
      Array(
        'onclick' => 'EmailConfirm();',
        'type'    => 'button',
        'value'   => 'Подтвердить',
        'prompt'	=> $Prompt
      )
    );
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #-----------------------------------------------------------------------------
    $NoBody->AddChild($Comp);
  }
  #-----------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Table[] = Array('Электронный адрес',$NoBody);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'name'   => 'Mobile',
    'size'   => 25,
    'type'   => 'text',
    'prompt' => $Messages['Prompts']['Mobile'],
    'value'  => $__USER['Mobile']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY',$Comp);
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
if($Config['Notifies']['Methods']['SMS']['IsActive']){
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'onclick' => 'MobileConfirm();',
      'type'    => 'button',
      'value'   => 'Подтвердить'
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $NoBody->AddChild($Comp);
}
#-------------------------------------------------------------------------------
$Table[] = Array('Номер мобильного телефона',$NoBody);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'name'   => 'ICQ',
    'size'   => 25,
    'type'   => 'text',
    'prompt' => $Messages['Prompts']['ICQ'],
    'value'  => $__USER['ICQ']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY',$Comp);
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
if($Config['Notifies']['Methods']['ICQ']['IsActive']){
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'onclick' => 'ICQTest();',
      'type'    => 'button',
      'value'   => 'Тест',
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $NoBody->AddChild($Comp);
}
#-------------------------------------------------------------------------------
$Table[] = Array('ICQ-номер',$NoBody);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'name'   => 'JabberID',
    'size'   => 25,
    'type'   => 'text',
    'prompt' => $Messages['Prompts']['JabberID'],
    'value'  => $__USER['JabberID']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
$NoBody = new Tag('NOBODY',$Comp);
#-------------------------------------------------------------------------------
if($Config['Notifies']['Methods']['Jabber']['IsActive']){
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'onclick' => 'JabberTest();',
      'type'    => 'button',
      'value'   => 'Тест',
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  $NoBody->AddChild($Comp);
}
#-------------------------------------------------------------------------------
$Table[] = Array('Jabber',$NoBody);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Данные для участия в обсуждениях';
#-------------------------------------------------------------------------------
$Foto = (integer)$__USER['Foto'];
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Upload','UserFoto',$Foto?SPrintF('%01.2f Кб.',$Foto/1024):'не загружена');
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Персональная фотография (90 x 110)',$Comp);
#-------------------------------------------------------------------------------
if($Foto){
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load(
    'Form/Input',
    Array(
      'type'  => 'checkbox',
      'name'  => 'IsClear',
      'value' => 'yes'
    )
  );
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  $Table[] = Array(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'IsClear\'); return false;'),'Удалить фотографию'),$Comp);
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'    => 'button',
    'onclick' => 'UserPersonalDataChange();',
    'value'   => 'Сохранить'
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tab','User/Settings',new Tag('FORM',Array('name'=>'UserPersonalDataChangeForm','onsubmit'=>'return false;'),$Comp));
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Comp);
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------

?>
