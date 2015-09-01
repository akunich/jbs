<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Tasks']['Types']['GC']['DomainCheckPriceListSettings'];
#-------------------------------------------------------------------------------
if(!$Settings['IsActive'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Theme = "Проверка стоимости доменных имён";
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('classes/DomainServer.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Servers = DB_Select('Servers',Array('ID','Params'),Array('Where'=>Array('`IsActive` = "yes"','(SELECT `ServiceID` FROM `ServersGroups` WHERE `Servers`.`ServersGroupID` = `ServersGroups`.`ID`) = 20000')));
#-------------------------------------------------------------------------------
switch(ValueOf($Servers)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	# No more...
	Debug("[comp/Tasks/GC/DomainCheckPriceList]: Регистраторы не найдены");
	#-------------------------------------------------------------------------------
	return TRUE;
	#-------------------------------------------------------------------------------
case 'array':
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
foreach($Servers as $Registrator){
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/Tasks/GC/DomainCheckPriceList]: Проверка цен на домены для %s (ID %d, тип %s)',$Registrator['Params']['Name'],$Registrator['ID'],$Registrator['Params']['SystemID']));
	#-------------------------------------------------------------------------------
	$Server = new DomainServer();
	#-------------------------------------------------------------------------------
	$IsSelected = $Server->Select((integer)$Registrator['ID']);
	#-------------------------------------------------------------------------------
	switch(ValueOf($IsSelected)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return new gException('TRANSFER_TO_OPERATOR','Задание не может быть выполнено автоматически и передано оператору');
	case 'true':
		break;
	default:
		return new gException('WRONG_STATUS','Регистратор не определён');
	}
	#-------------------------------------------------------------------------------
	$Prices = $Server->DomainPriceList();
	#-------------------------------------------------------------------------------
	switch(ValueOf($Prices)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		#-------------------------------------------------------------------------------
		switch($Prices->CodeID){
		case 'REGISTRATOR_ERROR':
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[comp/Tasks/GC/DomainCheckPriceList]: %s: %s',$Registrator['Params']['Name'],$Prices->String));
			#-------------------------------------------------------------------------------
			break;
			#-------------------------------------------------------------------------------
		default:
			#-------------------------------------------------------------------------------
			$Message = SPrintF('Для регистратора %s (ID %d, тип %s) не реализована проверка стоимости доменов',$Registrator['Params']['Name'],$Registrator['ID'],$Registrator['Params']['SystemID']);
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[comp/Tasks/GC/DomainCheckPriceList]: %s',$Message));
			#-------------------------------------------------------------------------------
			if($Settings['IsEvent']){
				#-------------------------------------------------------------------------------
				$Event = Array('Text' => $Message,'PriorityID' => 'Error','IsReaded' => FALSE);
				$Event = Comp_Load('Events/EventInsert', $Event);
				if(!$Event)
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		continue 2;
		#-------------------------------------------------------------------------------
	case 'array':
		#-------------------------------------------------------------------------------
		break;
	default:
		return new gException('WRONG_STATUS','Задание не может быть в данном статусе');
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# достаём данные тарифов по этому регистратору в биллинге
	$DomainSchemes = DB_Select('DomainSchemes',Array('*'),Array('Where'=>Array(SPrintF('`ServerID` = %u',$Registrator['ID']))));
	#-------------------------------------------------------------------------------
	switch(ValueOf($DomainSchemes)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Tasks/GC/DomainCheckPriceList]: у регистратора %s/%u нет тарифных планов',$Registrator['Params']['Name'],$Registrator['ID']));
		#-------------------------------------------------------------------------------
		$Schemes = Array();
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	case 'array':
		#-------------------------------------------------------------------------------
		$Schemes = Array();
		#-------------------------------------------------------------------------------
		foreach($DomainSchemes as $Scheme)
			$Schemes[$Scheme['Name']] = $Scheme;
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# перебираем тарифы 
	foreach(Array_Keys($Prices) as $Key){
		#-------------------------------------------------------------------------------
		#Debug(SPrintF('[comp/Tasks/GC/DomainCheckPriceList]: регистратор = %s; зона = %s; период = %s-%s; валюта = %s; цена регистрации = %s; цена продления = %s',$Registrator['Params']['Name'],$Key,$Prices[$Key]['min.period'],$Prices[$Key]['max.period'],$Prices[$Key]['curr'],$Prices[$Key]['new'],$Prices[$Key]['renew']));
		#-------------------------------------------------------------------------------
		# считаем цену регистрации
		$NewPriceReg = ($Prices[$Key]['new'] * (100 + IntVal($Settings['DomainMinMarginPercent']))) / 100;
		#-------------------------------------------------------------------------------
		if(($NewPriceReg - $Prices[$Key]['new']) < $Settings['DomainMinMarginSumm'])
			$NewPriceReg = $Prices[$Key]['new'] + $Settings['DomainMinMarginSumm'];
		#-------------------------------------------------------------------------------
		# округляем в большую сторону до 10 рублей
		$NewPriceReg = Ceil($NewPriceReg / 10) * 10;
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		# считаем цену продления
		$NewPriceProlong = ($Prices[$Key]['renew'] * (100 + IntVal($Settings['DomainMinMarginPercent']))) / 100;
		#-------------------------------------------------------------------------------
		if(($NewPriceProlong - $Prices[$Key]['renew']) < $Settings['DomainMinMarginSumm'])
			$NewPriceProlong = $Prices[$Key]['renew'] + $Settings['DomainMinMarginSumm'];
		#-------------------------------------------------------------------------------
		# округляем в большую сторону до 10 рублей
		$NewPriceProlong = Ceil($NewPriceProlong / 10) * 10;
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		# на перенос выставляем максимальную из цен - регистрация/перенос
		$NewPriceTransfer = $NewPriceReg;
		#-------------------------------------------------------------------------------
		if($NewPriceProlong > $NewPriceReg)
			$NewPriceTransfer = $NewPriceProlong;
		#-------------------------------------------------------------------------------
		$NewPriceTransfer = (In_Array($Key,Array('ru','su','рф')))?0:$NewPriceTransfer;
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
		# проверяем наличие такого тарифа в биллинге
		if(In_Array($Key,Array_Keys($Schemes))){
			#-------------------------------------------------------------------------------
			# тариф есть, сверяем цены
			# регистрация
			#-------------------------------------------------------------------------------
			if($Settings['DomainPriceDeviationPercent'] || $Settings['DomainPriceDeviationSumm']){
				#-------------------------------------------------------------------------------
				$Deviation = $Schemes[$Key]['CostOrder'] * (100 + $Settings['DomainPriceDeviationPercent'])/100 - $Schemes[$Key]['CostOrder'];
				#-------------------------------------------------------------------------------
				if($Deviation < $Settings['DomainPriceDeviationSumm'])
					$Deviation = $Settings['DomainPriceDeviationSumm'];
				#-------------------------------------------------------------------------------
				if(Abs($Schemes[$Key]['CostOrder'] - $NewPriceReg) > $Deviation){
					#-------------------------------------------------------------------------------
					Debug(SPrintF('[comp/Tasks/GC/DomainCheckPriceList]: регистрация, зона = %s; цена не укладывается в девиацию: биллинг = %s; регистратор = %s расчётная = %s',$Key,$Schemes[$Key]['CostOrder'],$Prices[$Key]['new'],$NewPriceReg));
					#-------------------------------------------------------------------------------
					# надо ли обновлять при повышении цены домена
					$NeedUpdate = ($Settings['DomainUpdatePriceUp'] && $NewPriceReg > $Schemes[$Key]['CostOrder'])?TRUE:FALSE;
					#-------------------------------------------------------------------------------
					# надо ли обновлять при понижении цены (если уже не задано на предыдущей проверке)
					if(!$NeedUpdate && $Settings['DomainUpdatePriceLow'] && $NewPriceReg < $Schemes[$Key]['CostOrder'])
						$NeedUpdate = TRUE;
					#-------------------------------------------------------------------------------
					if($NeedUpdate){
						#-------------------------------------------------------------------------------
						# прописываем новую цену в базе данных
						$IsUpdate = DB_Update('DomainSchemes',Array('CostOrder'=>$NewPriceReg),Array('ID'=>$Schemes[$Key]['ID']));
						if(Is_Error($IsUpdate))
							return ERROR | @Trigger_Error(500);
						#-------------------------------------------------------------------------------
						$Message = SPrintF('%s/%s: цена регистрации изменена %s->%s',$Registrator['Params']['Name'],$Key,IntVal($Schemes[$Key]['CostOrder']),$NewPriceReg);
						#-------------------------------------------------------------------------------
						Debug(SPrintF('[comp/Tasks/GC/DomainCheckPriceList]: %s',$Message));
						#-------------------------------------------------------------------------------
						if($Settings['IsEvent']){
							#-------------------------------------------------------------------------------
							$Event = Array('Text' => $Message,'PriorityID' => 'Notice','IsReaded' => FALSE);
							$Event = Comp_Load('Events/EventInsert', $Event);
							if(!$Event)
								return ERROR | @Trigger_Error(500);
							#-------------------------------------------------------------------------------
						}
						#-------------------------------------------------------------------------------
					}else{
						#-------------------------------------------------------------------------------
						# базу не обновляем, но событие может быть надо
						#-------------------------------------------------------------------------------
						$Message = SPrintF('%s/%s: цена регистрации не укладывается в девиацию, необходимо изменить %s->%s',$Registrator['Params']['Name'],$Key,IntVal($Schemes[$Key]['CostOrder']));
						#-------------------------------------------------------------------------------
						Debug(SPrintF('[comp/Tasks/GC/DomainCheckPriceList]: %s',$Message));
						#-------------------------------------------------------------------------------
						if($Settings['IsEvent']){
							#-------------------------------------------------------------------------------
							$Event = Array('Text' => $Message,'PriorityID' => 'Notice','IsReaded' => FALSE);
							$Event = Comp_Load('Events/EventInsert', $Event);
							if(!$Event)
								return ERROR | @Trigger_Error(500);
							#-------------------------------------------------------------------------------
						}
						#-------------------------------------------------------------------------------
					}
					#-------------------------------------------------------------------------------
				}
				#-------------------------------------------------------------------------------
			}

			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			# продление
			#-------------------------------------------------------------------------------
			if($Settings['DomainPriceDeviationPercent'] || $Settings['DomainPriceDeviationSumm']){
				#-------------------------------------------------------------------------------
				$Deviation = $Schemes[$Key]['CostProlong'] * (100 + $Settings['DomainPriceDeviationPercent'])/100 - $Schemes[$Key]['CostProlong'];
				#-------------------------------------------------------------------------------
				if($Deviation < $Settings['DomainPriceDeviationSumm'])
					$Deviation = $Settings['DomainPriceDeviationSumm'];
				#-------------------------------------------------------------------------------
				if(Abs($Schemes[$Key]['CostProlong'] - $NewPriceProlong) > $Deviation){
					#-------------------------------------------------------------------------------
					Debug(SPrintF('[comp/Tasks/GC/DomainCheckPriceList]: продление, зона = %s; цена не укладывается в девиацию: биллинг = %s; регистратор = %s расчётная = %s',$Key,$Schemes[$Key]['CostProlong'],$Prices[$Key]['renew'],$NewPriceProlong));
					#-------------------------------------------------------------------------------
					# надо ли обновлять при повышении цены домена
					$NeedUpdate = ($Settings['DomainUpdatePriceUp'] && $NewPriceProlong > $Schemes[$Key]['CostProlong'])?TRUE:FALSE;
					#-------------------------------------------------------------------------------
					# надо ли обновлять при понижении цены (если уже не задано на предыдущей проверке)
					if(!$NeedUpdate && $Settings['DomainUpdatePriceLow'] && $NewPriceProlong < $Schemes[$Key]['CostProlong'])
						$NeedUpdate = TRUE;
					#-------------------------------------------------------------------------------
					if($NeedUpdate){
						#-------------------------------------------------------------------------------
						# прописываем новую цену в базе данных
						$IsUpdate = DB_Update('DomainSchemes',Array('CostProlong'=>$NewPriceProlong),Array('ID'=>$Schemes[$Key]['ID']));
						if(Is_Error($IsUpdate))
							return ERROR | @Trigger_Error(500);
						#-------------------------------------------------------------------------------
						$Message = SPrintF('%s/%s: цена продления изменена %s->%s',$Registrator['Params']['Name'],$Key,IntVal($Schemes[$Key]['CostProlong']),$NewPriceProlong);
						#-------------------------------------------------------------------------------
						Debug(SPrintF('[comp/Tasks/GC/DomainCheckPriceList]: %s',$Message));
						#-------------------------------------------------------------------------------
						if($Settings['IsEvent']){
							#-------------------------------------------------------------------------------
							$Event = Array('Text' => $Message,'PriorityID' => 'Notice','IsReaded' => FALSE);
							$Event = Comp_Load('Events/EventInsert', $Event);
							if(!$Event)
								return ERROR | @Trigger_Error(500);
							#-------------------------------------------------------------------------------
						}
						#-------------------------------------------------------------------------------
					}else{
						#-------------------------------------------------------------------------------
						# базу не обновляем, но событие может быть надо
						$Message = SPrintF('%s/%s: цена продления не укладывается в девиацию, необходимо изменить %s->%s',$Registrator['Params']['Name'],$Key,IntVal($Schemes[$Key]['CostProlong']),$NewPriceProlong);
						#-------------------------------------------------------------------------------
						Debug(SPrintF('[comp/Tasks/GC/DomainCheckPriceList]: %s',$Message));
						#-------------------------------------------------------------------------------
						if($Settings['IsEvent']){
							#-------------------------------------------------------------------------------
							$Event = Array('Text' => $Message,'PriorityID' => 'Notice','IsReaded' => FALSE);
							$Event = Comp_Load('Events/EventInsert', $Event);
							if(!$Event)
								return ERROR | @Trigger_Error(500);
							#-------------------------------------------------------------------------------
						}
						#-------------------------------------------------------------------------------
					}
					#-------------------------------------------------------------------------------
				}
				#-------------------------------------------------------------------------------
			}

			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			# перенос, если цена отличается от той что в биллинге
			#-------------------------------------------------------------------------------
			if(IntVal($Schemes[$Key]['CostTransfer']) != $NewPriceTransfer){
				#-------------------------------------------------------------------------------
				# прописываем новую цену в базе данных
				$IsUpdate = DB_Update('DomainSchemes',Array('CostTransfer'=>$NewPriceTransfer),Array('ID'=>$Schemes[$Key]['ID']));
				if(Is_Error($IsUpdate))
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
				$Message = SPrintF('%s/%s: цена переноса изменена %s->%s',$Registrator['Params']['Name'],$Key,IntVal($Schemes[$Key]['CostTransfer']),$NewPriceTransfer);
				#-------------------------------------------------------------------------------
				Debug(SPrintF('[comp/Tasks/GC/DomainCheckPriceList]: %s',$Message));
				#-------------------------------------------------------------------------------
				if($Settings['IsEvent']){
					#-------------------------------------------------------------------------------
					$Event = Array('Text' => $Message,'PriorityID' => 'Notice','IsReaded' => FALSE);
					$Event = Comp_Load('Events/EventInsert', $Event);
					if(!$Event)
						return ERROR | @Trigger_Error(500);
					#-------------------------------------------------------------------------------
				}
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			# тарифа нет, если не разрешено автодобавление - топаем на след. круг цикла
			if(!$Settings['IsDomainSchemeCreate'])
				continue;
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			$Scheme = Array(
					'CreateDate'	=> Time(),
					'GroupID'	=> 1,
					'UserID'	=> 1,
					'Name'		=> $Key,
					'IsProlong'	=> TRUE,
					'IsTransfer'	=> TRUE,
					'CostOrder'	=> $NewPriceReg,
					'CostProlong'	=> $NewPriceProlong,
					'CostTransfer'	=> $NewPriceTransfer,
					'ServerID'	=> $Registrator['ID'],
					'MinOrderYears'	=> $Prices[$Key]['min.period'],
					'MaxActionYears'=> $Prices[$Key]['max.period']
					);
			#-------------------------------------------------------------------------------
			$IsInsert = DB_Insert('DomainSchemes',$Scheme);
			if(Is_Error($IsInsert))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$Message = SPrintF('Для регистратора %s добавлен новый тарифный план: %s',$Registrator['Params']['Name'],$Key);
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[comp/Tasks/GC/DomainCheckPriceList]: %s',$Message));
			#-------------------------------------------------------------------------------
			if($Settings['IsEvent']){
				#-------------------------------------------------------------------------------
				$Event = Array('Text' => $Message,'PriorityID' => 'Notice','IsReaded' => FALSE);
				$Event = Comp_Load('Events/EventInsert', $Event);
				if(!$Event)
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}

	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	# заново достаём все доменные зоны в биллинге, сравниваем с теми которые у регистратора - ищщем лишние
	$NewDomainSchemes = DB_Select('DomainSchemes',Array('*'),Array('Where'=>Array(SPrintF('`ServerID` = %u',$Registrator['ID']))));
	#-------------------------------------------------------------------------------
	switch(ValueOf($NewDomainSchemes)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/Tasks/GC/DomainCheckPriceList]: у регистратора %s/%u так и нет тарифных планов',$Registrator['Params']['Name'],$Registrator['ID']));
		#-------------------------------------------------------------------------------
		continue 2;
		#-------------------------------------------------------------------------------
	case 'array':
		#-------------------------------------------------------------------------------
		$NewSchemes = Array();
		#-------------------------------------------------------------------------------
		foreach($NewDomainSchemes as $NewScheme)
			$NewSchemes[] = $NewScheme['Name'];
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	# сравниваем список от регистратора со списокм в биллинге
	$RegList = Array_Keys($Prices);
	#-------------------------------------------------------------------------------
	ASort($NewSchemes);
	#-------------------------------------------------------------------------------
	ASort($RegList);
	#-------------------------------------------------------------------------------
	$DomainsOdd = Array_Diff($NewSchemes,$RegList);
	#-------------------------------------------------------------------------------
	if(SizeOf($DomainsOdd)){
		#-------------------------------------------------------------------------------
		foreach($DomainsOdd as $Odd){
			#-------------------------------------------------------------------------------
			$Message = SPrintF('Обнаружен тариф отсутствующий у регистратора: %s/%s',$Registrator['Params']['Name'],$Odd);
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[comp/Tasks/GC/DomainCheckPriceList]: %s',$Message));
			#-------------------------------------------------------------------------------
			if($Settings['IsEvent']){
				#-------------------------------------------------------------------------------
				$Event = Array('Text' => $Message,'PriorityID' => 'Error','IsReaded' => FALSE);
				$Event = Comp_Load('Events/EventInsert', $Event);
				if(!$Event)
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>