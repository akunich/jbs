<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Params');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Tasks']['Types']['GC']['StatisticsSettings'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Settings['IsActive'])
	return TRUE;
#-------------------------------------------------------------------------------
if(Date('N') != $Settings['DayOfWeek'])
	return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# Users
$Statistics = Array(
		'Stamp'		=> Time(),
		'Year'		=> Date('Y'),
		'Month'		=> Date('m'),
		'Day'		=> Date('d'),
		'TableID'	=> 'Users',
		'PackageID'	=> NULL,
		);
#-------------------------------------------------------------------------------
$Wheres = Array(
		'Total'		=> '1 = 1',
		'Active'	=> '(SELECT COUNT(*) FROM `OrdersOwners` WHERE `UserID` = `Users`.`ID`) > 0',
		'New'		=> '`RegisterDate` > UNIX_TIMESTAMP() - 7*24*3600',
		'Suspended'	=> Array('(SELECT COUNT(*) FROM `OrdersOwners` WHERE `UserID` = `Users`.`ID`) = 0 ','(SELECT COUNT(*) FROM `InvoicesOwners` WHERE `UserID` = `Users`.`ID`) > 0')
		);
#-------------------------------------------------------------------------------
foreach(Array_Keys($Wheres) as $Key){
	#-------------------------------------------------------------------------------
	$Count = DB_Count('Users',Array('Where'=>$Wheres[$Key]));
	if(Is_Error($Count))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Statistics[$Key] = $Count;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$IsInsert = DB_Insert('Statistics',$Statistics);
if(Is_Error($IsInsert))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# счета на оплату, число самих счетов
$Statistics = Array(
		'Stamp'		=> Time(),
		'Year'		=> Date('Y'),
		'Month'		=> Date('m'),
		'Day'		=> Date('d'),
		'TableID'	=> 'Invoices',
		'PackageID'	=> NULL,
		);
#-------------------------------------------------------------------------------
$Wheres = Array(
		'Total'		=> '1 = 1',
		'Active'	=> '`StatusID` = "Payed" AND `CreateDate` > UNIX_TIMESTAMP() - 7*24*3600',
		'New'		=> '`CreateDate` > UNIX_TIMESTAMP() - 7*24*3600',
		'Waiting'	=> '`StatusID` = "Waiting" AND `CreateDate` > UNIX_TIMESTAMP() - 7*24*3600',
		'Suspended'	=> '`StatusID` = "Rejected" AND `CreateDate` > UNIX_TIMESTAMP() - 7*24*3600',
		);
#-------------------------------------------------------------------------------
foreach(Array_Keys($Wheres) as $Key){
	#-------------------------------------------------------------------------------
	$Count = DB_Count('Invoices',Array('Where'=>$Wheres[$Key]));
	if(Is_Error($Count))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Statistics[$Key] = $Count;
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$IsInsert = DB_Insert('Statistics',$Statistics);
if(Is_Error($IsInsert))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# счета на оплату, суммы счетов
$Statistics = Array(
		'Stamp'		=> Time(),
		'Year'		=> Date('Y'),
		'Month'		=> Date('m'),
		'Day'		=> Date('d'),
		'TableID'	=> 'Invoices',
		'PackageID'	=> 'Summ',
		);
#-------------------------------------------------------------------------------
$Wheres = Array(
		'Total'		=> '1=1',
		'Active'	=> '`StatusID` = "Payed" AND `StatusDate` > UNIX_TIMESTAMP() - 7*24*3600',
		'New'		=> '`StatusDate` > UNIX_TIMESTAMP() - 7*24*3600',
		'Waiting'	=> '`StatusID` = "Waiting" AND `StatusDate` > UNIX_TIMESTAMP() - 7*24*3600',
		'Suspended'	=> '`StatusID` = "Rejected" AND `StatusDate` > UNIX_TIMESTAMP() - 7*24*3600',
		);
#-------------------------------------------------------------------------------
foreach(Array_Keys($Wheres) as $Key){
	#-------------------------------------------------------------------------------
        $Invoice = DB_Select('Invoices','SUM(`Summ`) AS `Summ`',Array('UNIQ','Where'=>$Wheres[$Key]));
	switch(ValueOf($Invoice)){
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
	$Statistics[$Key] = $Invoice['Summ'];
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$IsInsert = DB_Insert('Statistics',$Statistics);
if(Is_Error($IsInsert))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
# Штатные сервисы
$Where = Array('`Code` != "Default"','`IsHidden` = "no"');
#-------------------------------------------------------------------------------
$Services = DB_Select('Services',Array('ID','Code','Name'),Array('Where'=>$Where));
switch(ValueOf($Services)){
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
$Insert = Array(
		'Stamp'		=> Time(),
		'Year'		=> Date('Y'),
		'Month'		=> Date('m'),
		'Day'		=> Date('d'),
		'PackageID'	=> NULL
		);
#-------------------------------------------------------------------------------
foreach($Services as $Service){
	#-------------------------------------------------------------------------------
	$Statistics = $Insert;	
	$Statistics['TableID'] = $Service['Code'];
	#-------------------------------------------------------------------------------
	$Wheres = Array(
			'Total'		=> Array(),
			'Active'	=> Array('`StatusID` = "Active"'),
			'New'		=> Array('`OrderDate` > UNIX_TIMESTAMP() - 7*24*3600'),
			'Waiting'	=> Array('`StatusID` = "Waiting"'),
			'Suspended'	=> Array('`StatusID` = "Suspended"')
			);
	#-------------------------------------------------------------------------------
	foreach(Array_Keys($Wheres) as $Key){
		#-------------------------------------------------------------------------------
		$Where = $Wheres[$Key];
		#-------------------------------------------------------------------------------
		$Where[] = SPrintF('`ServiceID` = %u',$Service['ID']);
		#-------------------------------------------------------------------------------
		$Count = DB_Count('OrdersOwners',Array('Where'=>$Where));
		if(Is_Error($Count))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
		$Statistics[$Key] = $Count;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	$IsInsert = DB_Insert('Statistics',$Statistics);
	if(Is_Error($IsInsert))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	# по тарифам
	$Columns = Array('ID','Name');
	#-------------------------------------------------------------------------------
	if($Service['Code'] == 'Domain')
		$Columns[] = 'ServerID';
	#-------------------------------------------------------------------------------
	$Schemes = DB_Select(SPrintF('%sSchemes',$Service['Code']),$Columns,Array('SortOn'=>'SortID'));
	switch(ValueOf($Schemes)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		continue 2;
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	$Statistics = $Insert;
	$Statistics['TableID'] = $Service['Code'];
	#-------------------------------------------------------------------------------
	foreach($Schemes as $Scheme){
		#-------------------------------------------------------------------------------
		# костыль для доменов - слишком много тарифных планов
		if($Service['Code'] == 'Domain'){
			#-------------------------------------------------------------------------------
			$Count = DB_Count(SPrintF('%sOrdersOwners',$Service['Code']),Array('Where'=>SPrintF('`SchemeID` = %u',$Scheme['ID'])));
			#-------------------------------------------------------------------------------
			if(Is_Error($Count))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			if(!$Count)
				continue;
			#-------------------------------------------------------------------------------
			$Registrator = DB_Select('Servers',Array('ID','Params'),Array('UNIQ','ID'=>$Scheme['ServerID']));
			if(Is_Error($Registrator))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$Statistics['PackageID'] = SPrintF('%s / %s',$Registrator['Params']['Name'],$Scheme['Name']);
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			$Statistics['PackageID'] = $Scheme['Name'];
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		# поле есть только в OrdersOwners
		$Wheres['New'] = Array(SPrintF('(SELECT `OrderDate` FROM `OrdersOwners` WHERE `OrdersOwners`.`ID` = `%sOrdersOwners`.`OrderID`) > UNIX_TIMESTAMP() - 7*24*3600',$Service['Code']));
		#-------------------------------------------------------------------------------
		foreach(Array_Keys($Wheres) as $Key){
			#-------------------------------------------------------------------------------
			$Where = $Wheres[$Key];
			#-------------------------------------------------------------------------------
			$Where[] = SPrintF('`SchemeID` = %u',$Scheme['ID']);
			#-------------------------------------------------------------------------------
			$Count = DB_Count(SPrintF('%sOrdersOwners',$Service['Code']),Array('Where'=>$Where));
			if(Is_Error($Count))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$Statistics[$Key] = $Count;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		$IsInsert = DB_Insert('Statistics',$Statistics);
		if(Is_Error($IsInsert))
			return ERROR | @Trigger_Error(500);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
# TODO услуги настроенные вручную - тоже надо

#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return TRUE;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------







?>
