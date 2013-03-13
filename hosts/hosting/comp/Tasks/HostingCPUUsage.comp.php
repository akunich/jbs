<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('classes/Server.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
$Settings = $Config['Tasks']['Types']['HostingCPUUsage'];
#-------------------------------------------------------------------------------
# достаём время выполнения
$ExecuteTime = Comp_Load('Formats/Task/ExecuteTime',Array('ExecuteTime'=>$Settings['ExecuteTime'],'DefaultTime'=>MkTime(10,0,0,Date('n'),Date('j')+1,Date('Y'))));
if(Is_Error($ExecuteTime))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
# если неактивна, то через день запуск
if(!$Settings['IsActive'])
	return $ExecuteTime;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$HostingServers = DB_Select('HostingServers',Array('ID','Address'));
#-------------------------------------------------------------------------------
switch(ValueOf($HostingServers)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	# No more...
	break;
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$NotifyedCount = 0;
$LockedCount = 0;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
foreach($HostingServers as $HostingServer){
	#-------------------------------------------------------------------------------
	# костыль, чтоб ткоа один сервер
	#if($HostingServer['ID'] != 16)
	#	continue;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Server = new Server();
	#-------------------------------------------------------------------------------
	$IsSelected = $Server->Select((integer)$HostingServer['ID']);
	#-------------------------------------------------------------------------------
	switch(ValueOf($IsSelected)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'true':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	# достаём за период
	$TFilter = SPrintF('%s - %s',date('Y-m-d',time() - $Settings['PeriodToLock']*24*3600),date('Y-m-d',time() - 24*3600));
	$BUsages = Call_User_Func_Array(Array($Server,'GetCPUUsage'),Array($TFilter));
	#-------------------------------------------------------------------------------
	switch(ValueOf($BUsages)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return $BUsages;
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#Debug(SPrintF('[comp/Tasks/HostingCPUUsage]: BUsage = %s',print_r($BUsages,true)));
	#-------------------------------------------------------------------------------
	# достаём за вчера
	$TFilter = SPrintF('%s - %s',date('Y-m-d',time() - 24*3600),date('Y-m-d',time() - 24*3600));
	$SUsages = Call_User_Func_Array(Array($Server,'GetCPUUsage'),Array($TFilter));
	#-------------------------------------------------------------------------------
	switch(ValueOf($SUsages)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return $SUsages;
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#Debug(SPrintF('[comp/Tasks/HostingCPUUsage]: SUsage = %s',print_r($SUsages,true)));
	#-------------------------------------------------------------------------------
	# достаём юзеров из биллинга, и их лимиты
	$Array = Array();
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($BUsages) as $Login)
		$Array[] = SPrintF("'%s'",$Login);
	#-------------------------------------------------------------------------------
	$Where = SPrintF('`ServerID` = %u AND `Login` IN (%s)',$HostingServer['ID'],Implode(',',$Array));
	#-------------------------------------------------------------------------------
	$Columns = Array(
			'ID','Login','UserID','Domain',
			'(SELECT `QuotaCPU` FROM `HostingSchemes` WHERE `HostingSchemes`.`ID` = `HostingOrdersOwners`.`SchemeID`) as `QuotaCPU`',
			'(SELECT `Name` FROM `HostingSchemes` WHERE `HostingSchemes`.`ID` = `HostingOrdersOwners`.`SchemeID`) as `Scheme`',
			'(SELECT `Url` FROM `HostingServers` WHERE `HostingServers`.`ID` = `HostingOrdersOwners`.`ServerID`) as `Url`'
			);
	#-------------------------------------------------------------------------------
	$HostingOrders = DB_Select('HostingOrdersOwners',$Columns,Array('Where'=>$Where));
	#-------------------------------------------------------------------------------
	switch(ValueOf($HostingOrders)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		# No more...
		break;
	case 'array':
		#-------------------------------------------------------------------------------
		foreach($HostingOrders as $HostingOrder){
			#-------------------------------------------------------------------------------
			# проверяем превышение за предыдущий день, если оно есть - то делаем остальное. если нет - не трогаем юзера.
			if(!IsSet($SUsages[$HostingOrder['Login']]))
				continue;
			#-------------------------------------------------------------------------------
			$SUsage = Round(($SUsages[$HostingOrder['Login']]['utime'] + $SUsages[$HostingOrder['Login']]['stime'])*100 / (24*3600),2);
			#-------------------------------------------------------------------------------
			$BUsage = Round(($BUsages[$HostingOrder['Login']]['utime'] + $BUsages[$HostingOrder['Login']]['stime'])*100 / ($Settings['PeriodToLock']*24*3600),2);
			#-------------------------------------------------------------------------------
			# параметры для уведомлений
			$Params = Array(
					'ID'			=> $HostingOrder['ID'],
					'UserID'		=> $HostingOrder['UserID'],
					'Login'			=> $HostingOrder['Login'],
					'Domain'		=> $HostingOrder['Domain'],
					'Scheme'		=> $HostingOrder['Scheme'],
					'SUsage'		=> $SUsage,
					'BUsage'		=> $BUsage,
					'QuotaCPU'		=> $HostingOrder['QuotaCPU'],
					'Url'			=> $HostingOrder['Url'],
					'PeriodToLock'		=> $Settings['PeriodToLock'],
					'UnLockOverlimits'	=> $Settings['UnLockOverlimits'],
					'UnLockOverlimitsPeriod'=> $Settings['UnLockOverlimitsPeriod']
					);
			#-------------------------------------------------------------------------------
			# шлём уведомление тем кто превысил порог уведомления, и превысил порог оповещения
			if($SUsage > $HostingOrder['QuotaCPU']*$Settings['NotifyRatio'] && $SUsage > $Settings['LockNotifyFrom']){
				#-------------------------------------------------------------------------------
				$NotifyedCount++;
				#-------------------------------------------------------------------------------
				Debug(SPrintF('[comp/Tasks/HostingCPUUsage]: Надо уведомление: Login = %s; SUsage = %s; BUsage = %s; QuotaCPU = %s',$HostingOrder['Login'],$SUsage,$BUsage,$HostingOrder['QuotaCPU']));
				#-------------------------------------------------------------------------------
				$IsSend = NotificationManager::sendMsg(new Message('HostingCPUUsageNotice',$HostingOrder['UserID'],Array('HostingOrder'=>$Params)));
				#-------------------------------------------------------------------------------
				switch(ValueOf($IsSend)){
				case 'error':
					return ERROR | @Trigger_Error(500);
				case 'exception':
					# No more...
				case 'true':
					# событие, чтоле прибить...
					break;
				default:
					return ERROR | @Trigger_Error(101);
				}
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
			#-------------------------------------------------------------------------------
			$BUsage = Round(($BUsages[$HostingOrder['Login']]['utime'] + $BUsages[$HostingOrder['Login']]['stime'])*100 / ($Settings['PeriodToLock']*24*3600),2);
			#-------------------------------------------------------------------------------
			# если есть превышения за вчера, за неделю, и разрешено лочить
			if($SUsage > $HostingOrder['QuotaCPU']*$Settings['LockRatio']	// вчера превышали
			&& $BUsage > $HostingOrder['QuotaCPU']*$Settings['LockRatio']	// всё время превышали
			&& $BUsage > $Settings['LockBeginFrom']				// всё время - больше чем порог блокировки
			&& $Settings['LockOverlimits']){				// разрешено блокироват
				#-------------------------------------------------------------------------------
				Debug(SPrintF('[comp/Tasks/HostingCPUUsage]: Надо лочить: Login = %s; SUsage = %s; BUsage = %s; QuotaCPU = %s',$HostingOrder['Login'],$SUsage,$BUsage,$HostingOrder['QuotaCPU']));
				#-------------------------------------------------------------------------------
				$LockedCount++;
				#-------------------------------------------------------------------------------
				if(!$Settings['CreateTicket']){
					#-------------------------------------------------------------------------------
					$IsSend = NotificationManager::sendMsg(new Message('HostingCPUUsageNoticeLock',$HostingOrder['UserID'],Array('HostingOrder'=>$Params)));
					#-------------------------------------------------------------------------------
					switch(ValueOf($IsSend)){
					case 'error':
						return ERROR | @Trigger_Error(500);
					case 'exception':
						# No more...
					case 'true':
						#-------------------------------------------------------------------------------
						# событие, чтоле прибить...
						#-------------------------------------------------------------------------------
						break;
						#-------------------------------------------------------------------------------
					default:
						return ERROR | @Trigger_Error(101);
					}
					#-------------------------------------------------------------------------------
				}else{
					#-------------------------------------------------------------------------------
					$Clause = DB_Select('Clauses','*',Array('UNIQ','Where'=>"`Partition` = 'CreateTicket/LOCK_OVERLIMITS'"));
					#-------------------------------------------------------------------------------
					switch(ValueOf($Clause)){
					case 'error':
						return ERROR | @Trigger_Error(500);
					case 'exception':
						Debug(SPrintF('[comp/Tasks/HostingCPUUsage]: Статья для создания тикета не обнаружена: CreateTicket/LOCK_OVERLIMITS'));
						break;
					case 'array':
						#-------------------------------------------------------------------------------
						# готовим текст сообщения
						$Replace = Array_ToLine($Params,'%');
						$Message = $Clause['Text'];
						#-------------------------------------------------------------------------------
						foreach(Array_Keys($Replace) as $Key)
							$Message = Str_Replace($Key,$Replace[$Key],$Message);
						#-------------------------------------------------------------------------------
						$ITicket = Array(
								'Theme'		=> $Clause['Title'],
								'PriorityID'	=> 'Low',
								'Flags'		=> 'CloseOnSee',
								'TargetGroupID'	=> 3100000,
								'TargetUserID'	=> 100,
								'UserID'	=> $HostingOrder['UserID'],
								'Message'	=> $Message
								);
						#-------------------------------------------------------------------------------
						$IsAdd = Comp_Load('www/API/TicketEdit',$ITicket);
						if(Is_Error($IsAdd))
							return ERROR | @Trigger_Error(500);
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
				# время выполнения задачи
				$ExecuteDate = Comp_Load('HostingOrders/SearchExecuteTime');
				if(Is_Error($ExecuteDate))
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
				#-------------------------------------------------------------------------------
				# лочим 
				$IsAdd = Comp_Load('www/Administrator/API/TaskEdit',Array('UserID'=>$HostingOrder['UserID'],'TypeID'=>'HostingSuspend','ExecuteDate'=>$ExecuteDate,'Params'=>Array($HostingOrder['ID'])));
				#-------------------------------------------------------------------------------
				switch(ValueOf($IsAdd)){
				case 'error':
					return ERROR | @Trigger_Error(500);
				case 'exception':
					return ERROR | @Trigger_Error(400);
				case 'array':
					break;
				default:
					return ERROR | @Trigger_Error(101);
				}
				#-------------------------------------------------------------------------------
				#-------------------------------------------------------------------------------
				# создаём задачу на разблокировку аккаунта
				$IsAdd = Comp_Load('www/Administrator/API/TaskEdit',Array('UserID'=>$HostingOrder['UserID'],'TypeID'=>'HostingActive','ExecuteDate'=>(Time() + $Settings['UnLockOverlimitsPeriod']*3600),'Params'=>Array($HostingOrder['ID'])));
				#-------------------------------------------------------------------------------
				switch(ValueOf($IsAdd)){
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
				#-------------------------------------------------------------------------------
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
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
if($LockedCount > 0 || $NotifyedCount > 0)
	$GLOBALS['TaskReturnInfo'] = Array(SPrintF('Notyfyed: %s',$NotifyedCount),SPrintF('Locked: %s',$LockedCount));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# запуск в 10 утра
return $ExecuteTime;
#-------------------------------------------------------------------------------
?>
