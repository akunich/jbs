<?php

#-------------------------------------------------------------------------------
/** @author Rootden for Lowhosting.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task','Address','Message','Attribs');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('libs/HTTP.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
#Debug(SPrintF('[comp/Tasks/SMS]: %s',print_r($Attribs,true)));
#-------------------------------------------------------------------------------
$Message = Trim($Message);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$User = DB_Select('Users', Array('GroupID','Params'), Array('UNIQ', 'ID' => $Attribs['UserID']));
if(!Is_Array($User))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// возможно, параметры не заданы/требуется немедленная отправка - время не опредлеяем
if(!IsSet($Attribs['IsImmediately']) || !$Attribs['IsImmediately']){
	#-------------------------------------------------------------------------------
	// проверяем, можно ли отправлять в заданное время
	$TransferTime = Comp_Load('Formats/Task/TransferTime',$Attribs['UserID'],$Address,'SMS',$Attribs['TimeBegin'],$Attribs['TimeEnd']);
	#-------------------------------------------------------------------------------
	switch(ValueOf($TransferTime)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'integer':
		return $TransferTime;
	case 'false':
		break;
	default:
		return ERROR | @Trigger_Error(100);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Tasks/SMS]: отправка SMS сообщения для (%u)', $Address));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$ServersSettings = Comp_Load('Servers/SMSSelectServer',$Address);
#-------------------------------------------------------------------------------
switch(ValueOf($ServersSettings)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	break;
case 'integer':
	#-------------------------------------------------------------------------------
	# чопик для крона, когад сервер не найден
	return $ServersSettings;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(100);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// добавляем подпись, если необходимо
if(!$Config['Notifies']['Methods']['SMS']['CutSign'])
	$Message = SPrintF("%s\n\n--\n%s",Trim($Message),$GLOBALS['__USER']['Sign']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$ServerSettings = $ServersSettings[0];
#-------------------------------------------------------------------------------
$AddressCountry = $ServersSettings[1];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($ServerSettings['Params']['PrefixString'])
	$Message = SPrintF("%s\n%s",$ServerSettings['Params']['PrefixString'],$Message);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = $Address;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!IsSet($ServerSettings['Params']['Provider']))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($ServerSettings['Params']['Provider'] == 'SMSpilot' && !IsSet($ServerSettings['Params']['ApiKey']))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!IsSet($ServerSettings['Login']))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!IsSet($ServerSettings['Password']))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!IsSet($ServerSettings['Params']['Sender']))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if(!IsSet($ServerSettings['Params']['ExceptionsSchemeID']))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// Если пользователь относится к группе 'Сотрудники' то плату не взымаем...
# TODO: однако, надо через Tree_Entrance('Groups',3000000) искать
#-------------------------------------------------------------------------------
if($User['GroupID'] == '3000000')
	$Attribs['ChargeFree'] = TRUE;
#-------------------------------------------------------------------------------
// Проверяем пользователя на исключения оплаты, сумма оплаченных счетов.
#-------------------------------------------------------------------------------
if(FloatVal($ServerSettings['Params']['ExceptionsPaidInvoices']) >= 0){
	#-------------------------------------------------------------------------------
	$IsSelect = DB_Select('InvoicesOwners','SUM(`Summ`) AS `Summ`',Array('UNIQ','Where'=>SPrintF('`UserID` = %u AND `IsPosted` = "yes" AND StatusDate > UNIX_TIMESTAMP() - %u * 24 * 60 *60',$Attribs['UserID'],$ServerSettings['Params']['ExceptionsPaidInvoicesPeriod'])));
	switch(ValueOf($IsSelect)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		#-------------------------------------------------------------------------------
		if($IsSelect['Summ'] >= FloatVal($ServerSettings['Params']['ExceptionsPaidInvoices']))
			$Attribs['ChargeFree'] = TRUE;
			//Debug(SPrintF('[comp/Tasks/SMS]: Оплаченных счетов (%s)', $IsSelect['Summ']));
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(100);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// Проверяем пользователя на исключения оплаты, активные заказы хостинга.
// мегакостыль =) // commented by lissyara, 2013-06-01 in 15:47 MSK
#-------------------------------------------------------------------------------
if($ServerSettings['Params']['ExceptionsSchemeID'] != 0){
	#-------------------------------------------------------------------------------
	$OrderHostings = DB_Select('HostingOrdersOwners', 'SchemeID', Array('Where' => SPrintF('`UserID` = %u AND `StatusID` = "Active"', $Attribs['UserID'])));
	if (Is_Error($OrderHostings))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$LimitSchemeID = Explode(',',$ServerSettings['Params']['ExceptionsSchemeID']);
	#-------------------------------------------------------------------------------
	foreach($OrderHostings as $OrderHosting){
		#-------------------------------------------------------------------------------
		if(In_Array((integer) $OrderHosting['SchemeID'], $LimitSchemeID)){
			#-------------------------------------------------------------------------------
			$Attribs['ChargeFree'] = TRUE;
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	//Debug(print_r($LimitSchemeID, true));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$MessageLength = MB_StrLen($Message);
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Tasks/SMS]: длинна: %s, сообщение (%s)',$MessageLength,$Message));
Debug(SPrintF('[comp/Tasks/SMS]: SMS шлюз (%s)', $ServerSettings['Params']['Provider']));
#Debug(SPrintF('[comp/Tasks/SMS]: API ключ (%s)', $ServerSettings['Params']['ApiKey']));
Debug(SPrintF('[comp/Tasks/SMS]: Отправитель (%s)', $ServerSettings['Params']['Sender']));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if (Is_Error(System_Load(SPrintF('classes/%s.class.php', $ServerSettings['Params']['Provider']))))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if (!IsSet($ServerSettings['Params'][$AddressCountry]))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
if($MessageLength <= 70){
	#-------------------------------------------------------------------------------
	$SMSCost = Str_Replace(',', '.', $ServerSettings['Params'][$AddressCountry]);
	$SMSCount = 1;
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$SMSCount = Ceil($MessageLength / 67);
	#-------------------------------------------------------------------------------
	# сообщение не может быть больше 10 частей... на самом деле, например у меня 
	# телефон поддерживает максимум 6 частей...
	if($SMSCount > 10){
		Debug(SPrintF('[comp/Tasks/SMS]: Слишком длинное сообщеие (%s частей), не отправлено', $SMSCount));
		return TRUE;
	}
	#-------------------------------------------------------------------------------
	$SMSCost = $SMSCount * Str_Replace(',', '.', $ServerSettings['Params'][$AddressCountry]);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($Attribs['ChargeFree'])
	$SMSCost = 0;
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Currency',$SMSCost);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Tasks/SMS]: Стоимость сообщения (%s) всего частей (%s)', $Comp, $SMSCount));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------
if ($SMSCost > 0){
	#-------------------------------------------------------------------------------
	$Where = Array(
			SPrintF('`UserID` = %u', $Attribs['UserID']),
			SPrintF('`Balance` >= %s', $SMSCost),
			'`TypeID` != "NaturalPartner"',
			);
	#-------------------------------------------------------------------------------
	$Contract = DB_Select('Contracts', Array('TypeID', 'ID', 'Balance'), Array('UNIQ','Where'=>$Where,'Limits'=>Array('Start'=>0,'Length'=>1)));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Contract)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		#-------------------------------------------------------------------------------
		# нет денег
		break;
		#-------------------------------------------------------------------------------
	case 'array':
		#-------------------------------------------------------------------------------
		$ContractID = $Contract['ID'];
		(integer) $After = $Contract['Balance'] - $SMSCost;
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(100);
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(!IsSet($ContractID) && !IsSet($After)){
		#-------------------------------------------------------------------------------
		Debug("[comp/Tasks/SMS]: Недостаточно денежных средств на любом договоре клиента");
		if($Config['Notifies']['Methods']['SMS']['IsEvent']){
			#-------------------------------------------------------------------------------
			$Event = Array('UserID' => $Attribs['UserID'], 'PriorityID' => 'Error', 'Text' => SPrintF('Не удалось отправить SMS сообщение для (%s), недостаточно денежных средств на любом договоре клиента', $Address));
			$Event = Comp_Load('Events/EventInsert', $Event);
			if(!$Event)
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		if(Is_Null($Task))
			return SPrintF('Недостаточно денежных средств на вашем балансе. Стоимость сообщения: %s',$SMSCost);
		#-------------------------------------------------------------------------------
		return TRUE;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Links = &Links();
#-------------------------------------------------------------------------------
$LinkID = Md5($ServerSettings['Params']['Provider']);
#-------------------------------------------------------------------------------
if(!IsSet($Links[$LinkID])){
	#-------------------------------------------------------------------------------
	$Links[$LinkID] = NULL;
	#-------------------------------------------------------------------------------
	$SMS = &$Links[$LinkID];
	#-------------------------------------------------------------------------------
	$SMS = new $ServerSettings['Params']['Provider']($ServerSettings['Login'],$ServerSettings['Password'],$ServerSettings['Params']['ApiKey'],$ServerSettings['Params']['Sender']);
	if (Is_Error($SMS))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$IsAuth = $SMS->balance();
	switch (ValueOf($IsAuth)) {
	case 'false':
		#-------------------------------------------------------------------------------
		Debug("[comp/Tasks/SMS]: Подключаемся и получаем баланс -> Error:'".$SMS->error."'");
		if($Config['Notifies']['Methods']['SMS']['IsEvent']){
			#-------------------------------------------------------------------------------
			$Event = Array('UserID' => $Attribs['UserID'], 'PriorityID' => 'Error', 'Text' => SPrintF('Не удалось отправить SMS сообщение для (%s), %s', $Address, 'шлюз временно недоступен.'));
			$Event = Comp_Load('Events/EventInsert', $Event);
			if(!$Event)
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		UnSet($Links[$LinkID]);
		#-------------------------------------------------------------------------------
		if(Is_Null($Task))
			return "Пожалуйста, попробуйте повторить попытку позже";
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		return TRUE;
		#-------------------------------------------------------------------------------
	case 'true':
		#-------------------------------------------------------------------------------
		Debug("[comp/Tasks/SMS]: Подключаемся и получаем баланс: '".$SMS->balance."'");
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	// Проверим баланс и отложим задачу в случае нехватки кредитов
	#-------------------------------------------------------------------------------
	$SMSBalanse = (integer) $SMS->balance;
	if ($SMSBalanse == 0 || $SMSBalanse < $SMSCost) {
		#-------------------------------------------------------------------------------
		if ($Config['Notifies']['Methods']['SMS']['IsEvent']) {
			#-------------------------------------------------------------------------------
			$Event = Array('UserID' => $Attribs['UserID'], 'PriorityID' => 'Error', 'Text' => SPrintF('Не удалось отправить SMS сообщение для (%s), %s', $Address, 'временно нет средств на шлюзе.'));
			$Event = Comp_Load('Events/EventInsert', $Event);
			if (!$Event)
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		if(Is_Null($Task))
			return "Пожалуйста, попробуйте повторить попытку позже";
		#-------------------------------------------------------------------------------
		UnSet($Links[$LinkID]);
		return 3600;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$SMS = &$Links[$LinkID];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$IsMessage = $SMS->send($Address,$Message,$ServerSettings['Params']['Sender']);
switch (ValueOf($IsMessage)) {
case 'false':
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/SMS]: Неудачно, ошибка: "%s"',$SMS->error));
	#-------------------------------------------------------------------------------
	if ($Config['Notifies']['Methods']['SMS']['IsEvent']) {
		#-------------------------------------------------------------------------------
		#$Event = Array('UserID' => $Attribs['UserID'],'PriorityID' => 'Error','Text' => SPrintF('Не удалось отправить SMS сообщение для (%s), %s', $Address, 'шлюз временно недоступен.'));
		$Event = Array('UserID' => $Attribs['UserID'],'PriorityID' => 'Error','Text' => SPrintF('Не удалось отправить SMS сообщение для (%s), причина (%s)',$Address,$SMS->error));
		$Event = Comp_Load('Events/EventInsert', $Event);
		if (!$Event)
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	if(Is_Null($Task))
		#return 'Пожалуйста, попробуйте повторить попытку позже';
		return ERROR | @Trigger_Error('Пожалуйста, попробуйте повторить попытку позже');
	#-------------------------------------------------------------------------------
	UnSet($Links[$LinkID]);
		return 3600;
	#-------------------------------------------------------------------------------
case 'true':
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/SMS]: Отправка успешна, ответ шлюза: %s',$SMS->success));
	#-------------------------------------------------------------------------------
	if(!$Attribs['ChargeFree'] && IsSet($After)){
		#------------------------------TRANSACTION--------------------------------------
		if (Is_Error(DB_Transaction($TransactionID = UniqID('PostingSMS'))))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$IsUpdated = DB_Update('Contracts', Array('Balance' => $After), Array('ID' => $ContractID));
		if (Is_Error($IsUpdated))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$IPosting = Array(
					'ContractID'	=> $ContractID,
					'ServiceID'	=> '2000',
					'Comment'	=> "SMS уведомление ($SMSCount шт)",
					'Before'	=> $Contract['Balance'],
					'After'		=> $After
				);
		#-------------------------------------------------------------------------------
		$PostingID = DB_Insert('Postings', $IPosting);
		if (Is_Error($PostingID))
		return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		if (Is_Error(DB_Commit($TransactionID)))
			return ERROR | @Trigger_Error(500);
		#-------------------------END TRANSACTION---------------------------------------
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Formats/Currency',$Contract['Balance']);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Comp1 = Comp_Load('Formats/Currency',$After);
		if(Is_Error($Comp1))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Tasks/SMS]: Договор (%s) баланс до оплаты (%s) после оплаты (%s)', $ContractID, $Comp, $Comp1));
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if (!$Config['Notifies']['Methods']['SMS']['IsEvent'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Event = Array('UserID'=>$Attribs['UserID'],'Text'=>SPrintF('SMS сообщение для (%s) отправлено', $Address));
#-------------------------------------------------------------------------------
$Event = Comp_Load('Events/EventInsert', $Event);
#-------------------------------------------------------------------------------
if (!$Event)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
?>
