<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Task','Address','Message','Attribs');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
// возможно, параметры не заданы/требуется немедленная отправка - время не опредлеяем
if(!IsSet($Attribs['IsImmediately']) || !$Attribs['IsImmediately']){
	#-------------------------------------------------------------------------------
	// проверяем, можно ли отправлять в заданное время
	$TransferTime = Comp_Load('Formats/Task/TransferTime',$Attribs['UserID'],$Address,'Email',$Attribs['TimeBegin'],$Attribs['TimeEnd']);
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
if(Is_Error(System_Load('libs/Server.php','classes/SendMailSmtp.class.php')))
        return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Tasks/Email]: отправка письма для (%s), тема (%s)',$Address,$Attribs['Theme']));
#-------------------------------------------------------------------------------
#Debug(SPrintF('[comp/Tasks/Email]: %s',print_r($Attribs,true)));
#-------------------------------------------------------------------------------
$GLOBALS['TaskReturnInfo'] = $Address;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Array 		= Explode("\n",$Attribs['Heads']);
#-------------------------------------------------------------------------------
$Array[]	= 'X-Priority: 3';
$Array[]	= 'X-MSMail-Priority: Normal';
$Array[]	= 'X-Mailer: JBS';
$Array[]	= 'X-MimeOLE: JBS';
$Array[]	= SPrintF('X-JBS-Origin: %s',HOST_ID);
#-------------------------------------------------------------------------------
$Heads = Implode("\n",$Array);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Boundary = "\r\n\r\n------==--" . HOST_ID;
#-------------------------------------------------------------------------------
if(IsSet($Attribs['HTML']) && $Attribs['HTML']){
	#-------------------------------------------------------------------------------
	// JBS-1315 - если задан HTML то осталяем только его
	$Message = SPrintF("\r\n%s",$Attribs['HTML']);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	// если нет HTML то используем текстовое сообщение
	$Message = SPrintF("%s\r\nContent-Transfer-Encoding: 8bit\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n%s",$Boundary,$Message);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# достаём вложения, если они есть, и прикладываем к сообщению
if(IsSet($Attribs['Attachments']) && Is_Array($Attribs['Attachments']) && SizeOf($Attribs['Attachments'])){
	#-------------------------------------------------------------------------------
	# достаём данные юзера которому идёт письмо
	$User = DB_Select('Users', Array('ID','Params'), Array('UNIQ', 'ID' => $Attribs['UserID']));
	if(!Is_Array($User))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	if($User['Params']['Settings']['SendEdeskFilesToEmail'] == "Yes"){
		#-------------------------------------------------------------------------------
		#Debug(SPrintF('[comp/Tasks/Email]: письмо содержит %u вложений',SizeOf($Attribs['Attachments'])));
		#-------------------------------------------------------------------------------
		foreach ($Attribs['Attachments'] as $Attachment){
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[comp/Tasks/Email]: обработка вложения (%s), размер (%s), тип (%s)',$Attachment['Name'],$Attachment['Size'],$Attachment['Mime']));
			#-------------------------------------------------------------------------------
			$Message = SPrintF("%s%s\r\nContent-Disposition: attachment;\r\n\tfilename=\"%s\"\r\nContent-Transfer-Encoding: base64\r\nContent-Type: %s;\r\n\tname=\"%s\"\r\n\r\n%s",$Message,$Boundary,Mb_Encode_MimeHeader($Attachment['Name']),$Attachment['Mime'],Mb_Encode_MimeHeader($Attachment['Name']),$Attachment['Data']);
			#Debug(SPrintF('[comp/Tasks/Email]: %s',$Attachment['Data']));
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
# закрываем сообщение
$Message = SPrintF("%s\r\n\r\n%s--",$Message,$Boundary);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Settings = SelectServerSettingsByTemplate('Email');
#-------------------------------------------------------------------------------
switch(ValueOf($Settings)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/Email]: не найден сервер для отправки почты, используется функция mail()'));
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(Is_Array($Settings)){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/Email]: отправка через SMTP'));
	#-------------------------------------------------------------------------------
	$mailSMTP = new SendMailSmtpClass($Settings['Login'],$Settings['Password'], SPrintF('%s://%s',$Settings['Protocol'],$Settings['Address']), '', $Settings['Port']);   
	#-------------------------------------------------------------------------------
	$IsMail = $mailSMTP->send($Address, $Attribs['Theme'], $Message, $Heads);
	if(!$IsMail)
		return ERROR | @Trigger_Error('[comp/Tasks/Email]: ошибка отправки почты через SMTP ');
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$IsMail = @Mail($Address,Mb_Encode_MimeHeader($Attribs['Theme']),$Message,$Heads);
	if(!$IsMail)
		return ERROR | @Trigger_Error('[comp/Tasks/Email]: ошибка отправки сообщения, проверьте работу функции mail в PHP');
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
if(!$Config['Notifies']['Methods']['Email']['IsEvent'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Event = Array(
		'UserID'=> $Attribs['UserID'],
		'Text'	=> SPrintF('Сообщение для (%s) с темой (%s) отправлено по электронной почте',$Address,$Attribs['Theme'])
		);
$Event = Comp_Load('Events/EventInsert',$Event);
#-------------------------------------------------------------------------------
if(!$Event)
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------

?>
