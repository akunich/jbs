<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php','libs/Tree.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Window')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Настройка уведомлений');
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/UserNotifiesSet.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Notifies = $Config['Notifies'];
#-------------------------------------------------------------------------------
$Methods = $Notifies['Methods'];
#-------------------------------------------------------------------------------
if($Methods['SMS']['IsActive']){
	#-------------------------------------------------------------------------------
	if($__USER['MobileConfirmed'] == 0){
		#-------------------------------------------------------------------------------
		$Row2 = Array(new Tag('TD', Array('colspan' => 5, 'class' => 'Standard', 'style' => 'background-color:#FDF6D3;'), 'Для настройки SMS уведомлений, подтвердите свой номер телефона'));
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		$Regulars = Regulars();
		$MobileCountry = 'SMSPriceDefault';
		$RegCountrys = array('SMSPriceRu' => $Regulars['SMSPriceRu'], 'SMSPriceUa' => $Regulars['SMSPriceUa'], 'SMSPriceSng' => $Regulars['SMSPriceSng'], 'SMSPriceZone1' => $Regulars['SMSPriceZone1'], 'SMSPriceZone2' => $Regulars['SMSPriceZone2']);
		#-------------------------------------------------------------------------------
		foreach ($RegCountrys as $RegCountryKey => $RegCountry)
			if (Preg_Match($RegCountry, $__USER['Mobile']))
				$MobileCountry = $RegCountryKey;
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Tasks/SMS]: Страна определена (%s)', $MobileCountry));
		#-------------------------------------------------------------------------------
		if(!IsSet($Config['SMSGateway']['SMSPrice'][$MobileCountry]))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Formats/Currency',Str_Replace(',','.',$Config['SMSGateway']['SMSPrice'][$MobileCountry]));
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Message = SPrintF('SMS уведомления платные (%s), рекомендуем включать только "Уведомления о блокировках заказов"',$Comp);
		# прочкать SMSExceptionsPaidInvoices, если надо - получить сумму счетов, надпись по итогам вывести
		if($Config['SMSGateway']['SMSExceptions']['SMSExceptionsPaidInvoices'] >= 0){
			#-------------------------------------------------------------------------------
			$IsSelect = DB_Select('InvoicesOwners','SUM(`Summ`) AS `Summ`',Array('UNIQ','Where'=>SPrintF('`UserID` = %u AND `IsPosted` = "yes"',$__USER['ID'])));
			switch(ValueOf($IsSelect)){
			case 'error':
				return ERROR | @Trigger_Error(500);
			case 'exception':
				return ERROR | @Trigger_Error(400);
			case 'array':
				#-------------------------------------------------------------------------------
				$Comp = Comp_Load('Formats/Currency',$IsSelect['Summ']);
				if(Is_Error($Comp))
					return ERROR | @Trigger_Error(500);
				Debug(SPrintF('[comp/www/UserNotifiesSet]: оплачено счетов на сумму (%s)', $Comp));
				#-------------------------------------------------------------------------------
				$Comp = Comp_Load('Formats/Currency',$Config['SMSGateway']['SMSExceptions']['SMSExceptionsPaidInvoices']);
				if(Is_Error($Comp))
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
				$Message = ($IsSelect['Summ'] >= $Config['SMSGateway']['SMSExceptions']['SMSExceptionsPaidInvoices'])?SPrintF('Сумма ваших оплаченных счетов больше %s, SMS для вас бесплатны',$Comp):$Message;
				#-------------------------------------------------------------------------------
				break;
				#-------------------------------------------------------------------------------
			default:
				return ERROR | @Trigger_Error(100);
			}
		#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$Row2 = Array(new Tag('TD', Array('colspan' => 5, 'class' => 'Standard', 'style' => 'background-color:#FDF6D3;'), $Message));
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	if($Config['SMSGateway']['SMSExceptions']['SMSExceptionsPaidInvoices'] == 0 && $__USER['MobileConfirmed'] > 0)
		UnSet($Row2);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Row = Array(new Tag('TD',Array('class'=>'Head'),'Тип сообщения'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$uNotifies = Array();
#-------------------------------------------------------------------------------
foreach(Array_Keys($Methods) as $MethodID){
	#-------------------------------------------------------------------------------
	$Method = $Methods[$MethodID];
	#-------------------------------------------------------------------------------
	if(!$Method['IsActive'])
		continue;
	#-------------------------------------------------------------------------------
	$uNotifies[$MethodID] = Array();
	#-------------------------------------------------------------------------------
	$Row[] = new Tag('TD',Array('class'=>'Head'),$Method['Name']);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table = IsSet($Row2)?Array($Row2, $Row):Array($Row);
#-------------------------------------------------------------------------------
$Rows = DB_Select('Notifies','*',Array('Where'=>SPrintF('`UserID` = %u',$__USER['ID'])));
#-------------------------------------------------------------------------------
switch(ValueOf($Rows)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	# No more...
	break;
case 'array':
	#-------------------------------------------------------------------------------
	foreach($Rows as $Row)
		$uNotifies[$Row['MethodID']][] = $Row['TypeID'];
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Types = $Notifies['Types'];
$Code = 'Default';
#-------------------------------------------------------------------------------
foreach(Array_Keys($Types) as $TypeID){
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[comp/www/UserNotifiesSet]: TypeID = %s',$TypeID));
	$Type = $Types[$TypeID];
	#-------------------------------------------------------------------------------
	$Entrance = Tree_Entrance('Groups',(integer)$Type['GroupID']);
	#-------------------------------------------------------------------------------
	switch(ValueOf($Entrance)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		#-------------------------------------------------------------------------------
		if(!In_Array($GLOBALS['__USER']['GroupID'],$Entrance))
			continue 2;
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# проверяем, есть ли такие услуги у юзера
	$Code = IsSet($Type['Code'])?$Type['Code']:$Code;
	$Regulars = SPrintF('/^%s/',$Code);
	#-------------------------------------------------------------------------------
	if(Preg_Match($Regulars,$TypeID)){
		#-------------------------------------------------------------------------------
		# код уведомления совпадает с уведомлением
		$Count = DB_Count(SPrintF('%sOrdersOwners',$Code),Array('Where'=>SPrintF('`UserID` = %u',$__USER['ID'])));
		if(Is_Error($Count))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		if(!$Count)
			continue;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(IsSet($Type['Title']))
		$Table[] = Array(new Tag('TD',Array('colspan'=>5,'class'=>'Separator'),$Type['Title']));
	#-------------------------------------------------------------------------------
	$Row = Array(new Tag('TD',Array('class'=>'Comment'),$Type['Name']));
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($Methods) as $MethodID){
		#-------------------------------------------------------------------------------
		$Method = $Methods[$MethodID];
		#-------------------------------------------------------------------------------
		if(!$Method['IsActive'])
			continue;
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load(
				'Form/Input',
				Array(
					'name'	=> SPrintF('%s[]',$MethodID),
					'type'	=> 'checkbox',
					'value'	=> $TypeID
					)
				);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		// Если телефон не подтвержден то не выводить активными галочки для смс.
		if($MethodID == 'SMS' && $__USER['MobileConfirmed'] == 0){
			#-------------------------------------------------------------------------------
			$Comp->AddAttribs(Array('disabled' => 'true'));
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			if (!In_Array($TypeID, $uNotifies[$MethodID]))
				$Comp->AddAttribs(Array('checked' => 'true'));
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$Row[] = new Tag('TD',Array('align'=>'center'),$Comp);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$Table[] = $Row;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'type'    => 'button',
    'onclick' => 'UserNotifiesSet();',
    'value'   => 'Сохранить'
  )
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array(new Tag('TD',Array('colspan'=>6,'align'=>'right'),$Comp));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Extended',$Table);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tab','User/Settings',new Tag('FORM',Array('name'=>'UserNotifiesSetForm','onsubmit'=>'return false;'),$Comp));
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
