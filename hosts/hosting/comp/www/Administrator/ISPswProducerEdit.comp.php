<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
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
$DOM->AddText('Title','Услуги → Лицензии ISPsystem → Настройка соединения с ISPsystem');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY');
$Comp = Comp_Load('Tab','Administrator/ISPsw',$NoBody);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
$DOM->AddChild('Into',$Comp);
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/Administrator/ISPswProducerEdit.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
# get settings
$Config = Config();
$ISPswProducer = $Config['IspSoft']['Settings'];
#-------------------------------------------------------------------------------
$Table = Array();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Общая информация';
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'type'   => 'text',
			'name'   => 'VisibleName',
			'size'   => 30,
			'prompt' => 'Имя продавца ПО, для отображения',
			'value'  => $ISPswProducer['VisibleName']
		)
	);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
$Table[] = Array('Имя для отображения',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Параметры соединения';
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'   => 'text',
    'name'   => 'Address',
    'prompt' => 'Используется для связи с сервером продавца ПО',
    'value'  => $ISPswProducer['Address']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Адрес сервера',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'   => 'text',
    'size'   => 15,
    'prompt' => 'часть пути в URL, после доменной части',
    'name'   => 'PrefixAPI',
    'value'  => $ISPswProducer['PrefixAPI']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Адрес API',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => 'text',
    'size'  => 6,
    'name'  => 'Port',
    'value' => $ISPswProducer['Port']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Порт',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'Protocol'),Array('ssl'=>'ssl','tcp'=>'tcp'),$ISPswProducer['Protocol']);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Протокол',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'   => 'text',
    'size'   => 15,
    'prompt' => 'Имя пользователя, имеющего право выполнять заказы и оплату ПО',
    'name'   => 'Login',
    'value'  => $ISPswProducer['Login']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Логин',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'  => ($ISPswProducer['Password']?'password':'text'),
    'size'  => 15,
    'name'  => 'Password',
    'value' => $ISPswProducer['Password']
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Пароль',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
   Array(
         'type'  => 'text',
         'size'  => 15,
	     'name'  => 'BalanceLowLimit',
         'value' => $ISPswProducer['BalanceLowLimit'],
         'prompt'=> 'При снижении балланса счёта у регистратора ниже этой суммы, группе "Бухгалтерия" (при её отсутствии - всем сотрудникам) будут отсылаться уведомления о необходимости пополнения счёта. Для отключения уведомлений, введите ноль.'
	     )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Порог уведомления о баллансе',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'type'    => 'button',
			'onclick' => 'ISPswProducerEdit();',
			'value'   => 'Сохранить'
		)
	);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
$Table[] = $Comp;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
$Form = new Tag('FORM',Array('name'=>'ISPswProducerEditForm','onsubmit'=>'return false;'),$Comp);
$DOM->AddChild('Into',$Form);




#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Out = $DOM->Build();
#-------------------------------------------------------------------------------
if(Is_Error($Out))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return $Out;
#-------------------------------------------------------------------------------


?>
