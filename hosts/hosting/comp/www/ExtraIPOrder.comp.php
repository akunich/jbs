<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$ContractID	=  (string) @$Args['ContractID'];
$ExtraIPSchemeID= (integer) @$Args['ExtraIPSchemeID'];
$StepID		= (integer) @$Args['StepID'];
$HostingOrderID	= (integer) @$Args['HostingOrderID'];
$VPSOrderID	= (integer) @$Args['VPSOrderID'];
$DSOrderID	= (integer) @$Args['DSOrderID'];
$OrderType	=  (string) @$Args['OrderType'];	# тип заказа к которому цепляем IP
$DependOrderID	= (integer) @$Args['DependOrderID'];	# номер заказа к которому цепляем IP
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php','libs/WhoIs.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
$UniqID = UniqID('ExtraIPSchemes');
#-------------------------------------------------------------------------------
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
$DOM->AddText('Title','Заказ выделенного IP адреса');
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/Order.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'ExtraIPOrderForm','onsubmit'=>'return false;'));
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# составляем список серверов на которых можно добавлять IP адреса
$ExtraIPSchemes = DB_Select('ExtraIPSchemes',Array('ID','Params'),Array('Where'=>"`IsActive` = 'yes'"));
#-------------------------------------------------------------------------------
switch(ValueOf($ExtraIPSchemes)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return new gException('NO_IP_SCHEMES','Нет ни одного тарифа на выделенные IP адреса');
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$ServerIDs = Array();
#-------------------------------------------------------------------------------
foreach($ExtraIPSchemes as $ExtraIPScheme)
	foreach($ExtraIPScheme['Params']['Servers'] as $iServerID)
		if(!In_Array($iServerID,$ServerIDs))
			$ServerIDs[] = $iServerID;
#-------------------------------------------------------------------------------
if(!SizeOf($ServerIDs))
	return new gException('NO_SERVERS_FOR_IP_SCHEMES','У существующих тарифных планов не отмечено ни одного сервера на котором можно было бы добавлять IP адреса');
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($StepID){
	#-------------------------------------------------------------------------------
	Debug("[comp/www/ExtraIPOrder]: StepID = $StepID");
	# intermediate step
	if($StepID == 1){
		#-------------------------------------------------------------------------------
		$Table[] = new Tag('TD',Array('colspan'=>2,'width'=>300,'class'=>'Standard','style'=>'background-color:#FDF6D3;'),'Необходимо выбрать заказ хостинга, VPS или выделенного сервера, к которому будет прикреплен заказ выделенного IP адреса. Обратите внимание, что нужно выбрать что-то одно - адрес нельзя прикрепить к разным услугам.');
		#-------------------------------------------------------------------------------
		$OrderCount = 0;
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		# Where общее для Hosting/VPS/DS
		$Where = Array(
				SPrintF('`ContractID` = %u',$ContractID),
				SPrintF('`ServerID` IN (%s)',Implode(',',$ServerIDs)),
				"`StatusID` = 'Active' OR `StatusID` = 'Waiting'"
				);
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		# create select, using ContractID for HostingOrders
		$Columns = Array('ID','Login','OrderID','(SELECT `Address` FROM `Servers` WHERE `Servers`.`ID` = (SELECT `ServerID` FROM `OrdersOwners` WHERE `OrdersOwners`.`ID` = `HostingOrdersOwners`.`OrderID`)) AS `Address`');
		#-------------------------------------------------------------------------------
		$HostingOrders = DB_Select('HostingOrdersOwners',$Columns,Array('Where'=>$Where));
		switch(ValueOf($HostingOrders)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			# No more...
			break;
		case 'array':
			#-------------------------------------------------------------------------------
			$Options = Array();
			#-------------------------------------------------------------------------------
			foreach($HostingOrders as $HostingOrder){
				#-------------------------------------------------------------------------------
				$HostingOrderID = $HostingOrder['OrderID'];
				#-------------------------------------------------------------------------------
				$Options[$HostingOrderID] = SPrintF('%s [%s]',$HostingOrder['Login'],$HostingOrder['Address']);
				#-------------------------------------------------------------------------------
				$OrderCount++;
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			$Comp = Comp_Load('Form/Select',Array('name'=>'HostingOrderID','style'=>'width: 240px;'),$Options);
			if(Is_Error($Comp))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$Table[] = Array('Заказ хостинга',$Comp);
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		# create select, using ContractID for VPSOrders
		$Columns = Array('ID','Login','OrderID','(SELECT `Address` FROM `Servers` WHERE `Servers`.`ID` = (SELECT `ServerID` FROM `OrdersOwners` WHERE `OrdersOwners`.`ID` = `VPSOrdersOwners`.`OrderID`)) AS `Address`');
		#-------------------------------------------------------------------------------
		$VPSOrders = DB_Select('VPSOrdersOwners',$Columns,Array('Where'=>$Where));
		switch(ValueOf($VPSOrders)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			# No more...
			break;
		case 'array':
			#-------------------------------------------------------------------------------
			$Options = Array('Не использовать');
			#-------------------------------------------------------------------------------
			foreach($VPSOrders as $VPSOrder){
				#-------------------------------------------------------------------------------
				$VPSOrderID = $VPSOrder['OrderID'];
				#-------------------------------------------------------------------------------
				$Options[$VPSOrderID] = SPrintF('%s [%s]',$VPSOrder['Login'],$VPSOrder['Address']);
				#-------------------------------------------------------------------------------
				$OrderCount++;
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			$Comp = Comp_Load('Form/Select',Array('name'=>'VPSOrderID','style'=>'width: 240px;'),$Options);
			if(Is_Error($Comp))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$Table[] = Array('Заказ виртуального сервера',$Comp);
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		# create select, using ContractID for DSOrders
		$Columns = Array('ID','IP','OrderID','(SELECT `Name` FROM `DSSchemes` WHERE `DSSchemes`.`ID` = `SchemeID`) as `Name`');
		#-------------------------------------------------------------------------------
		$DSOrders = DB_Select('DSOrdersOwners',$Columns,Array('Where'=>$Where));
		switch(ValueOf($DSOrders)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			# No more...
			break;
		case 'array':
			#-------------------------------------------------------------------------------
			$Options = Array('Не использовать');
			#-------------------------------------------------------------------------------
			foreach($DSOrders as $DSOrder){
				#-------------------------------------------------------------------------------
				$DSOrderID = $DSOrder['OrderID'];
				#-------------------------------------------------------------------------------
				$Options[$DSOrderID] = SPrintF('%s [%s]',$DSOrder['IP'],$DSOrder['Name']);
				#-------------------------------------------------------------------------------
				$OrderCount++;
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			$Comp = Comp_Load('Form/Select',Array('name'=>'DSOrderID','style'=>'width: 240px;'),$Options);
			if(Is_Error($Comp))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$Table[] = Array('Заказ выделенного сервера',$Comp);
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		# check - have it Owner some orders or not
		if($OrderCount < 1)
			return new gException('ExtraIP_OWNER_NOT_HAVE_ORDERS','Выбранный профиль не имеет никаких заказанных услуг, или, для этих услуг невозможно заказать IP адреса. Выберите другой, или, закажите какую-либо услугу: хостинг, VPS, выделенный сервера. После этого, вы сможете заказать дополнительный IP адрес.');
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load(
				'Form/Input',
				Array(
					'name'	=> 'ContractID',
					'type'	=> 'hidden',
					'value'	=> $ContractID
					)
				);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Form->AddChild($Comp);
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load(
					'Form/Input',
					Array(
						'type'		=> 'button',
						'name'		=> 'Submit',
						'onclick'	=> "ShowWindow('/ExtraIPOrder',FormGet(form));",
						'value'		 => 'Продолжить'
					)
				);
		#-------------------------------------------------------------------------------
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Table[] = $Comp;
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Tables/Standard',$Table);
		#-------------------------------------------------------------------------------
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Form->AddChild($Comp);
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load(
				'Form/Input',
				Array(
					'name'	=> 'StepID',
					'value'	=> 2,
					'type'	=> 'hidden',
					)
				);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Form->AddChild($Comp);
		#-------------------------------------------------------------------------------
		$DOM->AddChild('Into',$Form);
		#-------------------------------------------------------------------------------
	}else{ # $StepID 1 -> another
		#-------------------------------------------------------------------------------
		# check, select or not some order
		if(!$HostingOrderID && !$VPSOrderID && !$DSOrderID)
			return new gException('ExtraIP_ORDER_NOT_SELECTED','Необходимо выбрать заказ к которому прикрепляется IP адрес');
		#-------------------------------------------------------------------------------
		# select used order
		# and check, select only one order or more
		$SelectCount = 0;
		#-------------------------------------------------------------------------------
		if($HostingOrderID){
			#-------------------------------------------------------------------------------
			$SelectCount++;
			#-------------------------------------------------------------------------------
			$OrderType = "Hosting";
			#-------------------------------------------------------------------------------
			$DependOrderID = $HostingOrderID;
			#-------------------------------------------------------------------------------
			$Columns = Array('(SELECT `ServersGroupID` FROM `Servers` WHERE `Servers`.`ID` = (SELECT `ServerID` FROM `OrdersOwners` WHERE `OrdersOwners`.`ID` = `HostingOrdersOwners`.`OrderID`)) AS `ServersGroupID`');
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		if($VPSOrderID){
			#-------------------------------------------------------------------------------
			$SelectCount++;
			#-------------------------------------------------------------------------------
			$OrderType = "VPS";
			#-------------------------------------------------------------------------------
			$DependOrderID = $VPSOrderID;
			#-------------------------------------------------------------------------------
			$Columns = Array('(SELECT `ServersGroupID` FROM `Servers` WHERE `Servers`.`ID` = (SELECT `ServerID` FROM `OrdersOwners` WHERE `OrdersOwners`.`ID` = `VPSOrdersOwners`.`OrderID`)) AS `ServersGroupID`');
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		if($DSOrderID){
			#-------------------------------------------------------------------------------
			$SelectCount++;
			#-------------------------------------------------------------------------------
			$OrderType = "DS";
			#-------------------------------------------------------------------------------
			$DependOrderID = $DSOrderID;
			#-------------------------------------------------------------------------------
			$Columns = Array('(SELECT `ServersGroupID` FROM `DSSchemes` WHERE `DSSchemes`.`ID` = `DSOrdersOwners`.`SchemeID`) AS `ServersGroupID`');
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		if($SelectCount > 1)
			return new gException('ExtraIP_SELECTED_MORE_THAN_ONE_ORDER','IP адрес можно прикрепить только к одному заказу. Выберите лишь один пункт.');
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		# выбираем ServerID заказа
		$Order = DB_Select('OrdersOwners',Array('ID','ServerID'),Array('UNIQ','ID'=>$DependOrderID));
		#-------------------------------------------------------------------------------
		switch(ValueOf($Order)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			return new gException('ORDER_NOT_FOUND',SPrintF('Заказ (%s/%u) не найден. Обратитесь в службу поддержки пользователей.',$OrderType,$DependOrderID));
		case 'array':
			break;

		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
		$ServerID = $Order['ServerID'];
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		# тип заказа к которому надо прицепить IP адрес
		$Comp = Comp_Load(
				'Form/Input',
				Array(
					'name'	=> 'OrderType',
					'type'	=> 'hidden',
					'value'	=> $OrderType
					)
				);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Form->AddChild($Comp);
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		# номер заказа к которому надо прицепить IP адрес
		$Comp = Comp_Load(
				'Form/Input',
				Array(
					'name'	=> 'DependOrderID',
					'type'	=> 'hidden',
					'value'	=> $DependOrderID
					)
				);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Form->AddChild($Comp);
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load(
				'Form/Input',
				Array(
					'name'	=> 'ContractID',
					'type'	=> 'hidden',
					'value'	=> $ContractID
					)
				);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Form->AddChild($Comp);
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Services/Schemes','ExtraIPSchemes',$__USER['ID'],Array('Name'),$UniqID);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Columns = Array('ID','Name','Comment','CostMonth','CostInstall','Params');
		#-------------------------------------------------------------------------------
		$ExtraIPSchemes = DB_Select($UniqID,$Columns,Array('SortOn'=>Array('SortID'),'Where'=>"`IsActive` = 'yes'"));
		#-------------------------------------------------------------------------------
		switch(ValueOf($ExtraIPSchemes)){
		case 'error':
			return ERROR | @Trigger_Error(500);
		case 'exception':
			return new gException('ExtraIP_SCHEMES_NOT_FOUND','Активные тарифы на выделенные IP адреса не найдены. Обратитесь в службу поддержки пользователей.');
		case 'array':
			#-------------------------------------------------------------------------------
			$NoBody = new Tag('NOBODY');
			#-------------------------------------------------------------------------------
			$Tr = new Tag('TR');
			#-------------------------------------------------------------------------------
			$Tr->AddChild(new Tag('TD',Array('class'=>'Head','colspan'=>2),'Тариф'));
			#-------------------------------------------------------------------------------
			$Tr->AddChild(new Tag('TD',Array('class'=>'Head','align'=>'center','style'=>'white-space: nowrap;'),'Цена в месяц'));
			#-------------------------------------------------------------------------------
			$Td = new Tag('TD',Array('class'=>'Head','align'=>'center','style'=>'white-space: nowrap;'),new Tag('SPAN','Цена подключения'),new Tag('SPAN',Array('style'=>'font-weight:bold;font-size:14px;'),'?'));
			#-------------------------------------------------------------------------------
			$LinkID = UniqID('Prompt');
			#-------------------------------------------------------------------------------
			$Links[$LinkID] = &$Td;
			#-------------------------------------------------------------------------------
			$Comp = Comp_Load('Form/Prompt',$LinkID,'Стоимость подключения услуги. Взимается единоразово, при подключении.');
			if(Is_Error($Comp))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$Tr->AddChild($Td);
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			UnSet($Links[$LinkID]);
			#-------------------------------------------------------------------------------
			$Rows = Array($Tr);
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			foreach($ExtraIPSchemes as $ExtraIPScheme){
				#-------------------------------------------------------------------------------
				# если сервер заказа не содержится в тарифе на выделенный IP - пропускаем тариф
				if(!In_Array($ServerID,$ExtraIPScheme['Params']['Servers']))
					continue;
				#-------------------------------------------------------------------------------
				#-------------------------------------------------------------------------------
				$Comp = Comp_Load(
						'Form/Input',
						Array(
							'name'	=> 'ExtraIPSchemeID',
							'type'	=> 'radio',
							'value'	=> $ExtraIPScheme['ID']
							)
						);
				if(Is_Error($Comp))
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
				if($ExtraIPScheme['ID'] == $ExtraIPSchemeID)
					$Comp->AddAttribs(Array('checked'=>'true'));
				#-------------------------------------------------------------------------------
				$Comment = $ExtraIPScheme['Comment'];
				#-------------------------------------------------------------------------------
				if($Comment)
					$Rows[] = new Tag('TR',new Tag('TD',Array('colspan'=>2)),new Tag('TD',Array('colspan'=>2,'class'=>'Standard','style'=>'background-color:#FDF6D3;'),$Comment));
				#-------------------------------------------------------------------------------
				$CostMonth = Comp_Load('Formats/Currency',$ExtraIPScheme['CostMonth']);
				if(Is_Error($CostMonth))
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
				#-------------------------------------------------------------------------------
				$CostInstall = Comp_Load('Formats/Currency',$ExtraIPScheme['CostInstall']);
				if(Is_Error($CostInstall))
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
				$Rows[] = new Tag(
							'TR',
							new Tag('TD',Array('width'=>20),$Comp),
							new Tag('TD',Array('class'=>'Comment','align'=>'right','style'=>'white-space: nowrap;'),$ExtraIPScheme['Name']),
							new Tag('TD',Array('class'=>'Standard','align'=>'right'),$CostMonth),
							new Tag('TD',Array('class'=>'Standard','align'=>'right'),$CostInstall)
						);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			$Comp = Comp_Load('Tables/Extended',$Rows,Array('align'=>'center'));
			if(Is_Error($Comp))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$Table[] = $Comp;
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		default:
			return ERROR | @Trigger_Error(101);
		}
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		$Div = new Tag('DIV',Array('align'=>'right'),'');
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load(
				'Form/Input',
				Array(
					'type'		=> 'button',
					'onclick'	=> 'Order("ExtraIP");',
					'value'		=> 'Продолжить'
					)
				);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Div->AddChild($Comp);
		#-------------------------------------------------------------------------------
		$Table[] = $Div;
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Tables/Standard',$Table,Array('width'=>400));
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Form->AddChild($Comp);
		#-------------------------------------------------------------------------------
		$DOM->AddChild('Into',$Form);
		#-------------------------------------------------------------------------------
	}	# end of $StepID is set, and $StepID != 1 or 2
	#-------------------------------------------------------------------------------
}else{ # $StepID is set -> $StepID not set
	#-------------------------------------------------------------------------------
	$Contracts = DB_Select('Contracts',Array('ID','Customer'),Array('Where'=>SPrintF("`UserID` = %u AND `TypeID` != 'NaturalPartner'",$__USER['ID'])));
	switch(ValueOf($Contracts)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return new gException('CONTRACTS_NOT_FOUND','Система не обнаружила у Вас ни одного договора. Пожалуйста, перейдите в раздел [Мой офис - Договоры] и сформируйте хотя бы 1 договор.');
	case 'array':
		#-------------------------------------------------------------------------------
		$Options = Array();
		#-------------------------------------------------------------------------------
		foreach($Contracts as $Contract){
			#-------------------------------------------------------------------------------
			$Customer = $Contract['Customer'];
			#-------------------------------------------------------------------------------
			$Number = Comp_Load('Formats/Contract/Number',$Contract['ID']);
			if(Is_Error($Number))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			if(Mb_StrLen($Customer) > 20)
				$Customer = SPrintF('%s...',Mb_SubStr($Customer,0,20));
			#-------------------------------------------------------------------------------
			$Options[$Contract['ID']] = SPrintF('#%s / %s',$Number,$Customer);
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Form/Select',Array('name'=>'ContractID'),$Options,$ContractID);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$NoBody = new Tag('NOBODY',$Comp);
		#-------------------------------------------------------------------------------
		$Window = JSON_Encode(Array('Url'=>'/ExtraIPOrder','Args'=>Array()));
		#-------------------------------------------------------------------------------
		$A = new Tag('A',Array('href'=>SPrintF("javascript:ShowWindow('/ContractMake',{Window:'%s'});",Base64_Encode($Window))),'[новый]');
		#-------------------------------------------------------------------------------
		$NoBody->AddChild($A);
		#-------------------------------------------------------------------------------
		$Table = Array(Array('Базовый договор',$NoBody));
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load(
				'Form/Input',
				Array(
					'type'		=> 'button',
					'name'		=> 'Submit',
					'onclick'	=> "ShowWindow('/ExtraIPOrder',FormGet(form));",
					'value'		=> 'Продолжить'
					)
				);
		#-------------------------------------------------------------------------------
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Table[] = $Comp;
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load('Tables/Standard',$Table);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Form->AddChild($Comp);
		#-------------------------------------------------------------------------------
		$Comp = Comp_Load(
				'Form/Input',
				Array(
					'name'	=> 'StepID',
					'value'	=> 1,
					'type'	=> 'hidden',
					)
				);
		if(Is_Error($Comp))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Form->AddChild($Comp);
		#-------------------------------------------------------------------------------
		$DOM->AddChild('Into',$Form);
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Out = $DOM->Build(FALSE);
#-------------------------------------------------------------------------------
if(Is_Error($Out))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
?>
