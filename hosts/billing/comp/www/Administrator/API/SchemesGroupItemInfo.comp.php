<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('ServiceID','SchemeID','Length','SchemesGroupID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/www/Administrator/SchemesGroupItemInfo]: ServiceID = %s, SchemeID = %s, Length = %s, SchemesGroupID = %s',$ServiceID,$SchemeID,$Length,$SchemesGroupID));
# достаём название сервиса
if($ServiceID > 0){
	#-------------------------------------------------------------------------------
	$Service = DB_Select('ServicesOwners',Array('ID','`NameShort` AS `Name`','Code'),Array('UNIQ','ID'=>$ServiceID));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Service)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		$Service = Array('Name' => SPrintF('Deleted:%s',$ServiceID));
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
}else{
	$Service = Array('Name' => 'Любой сервис');
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($ServiceID > 0 && $SchemeID > 0 && $Service['Code'] != 'Default'){
	#-------------------------------------------------------------------------------
	$Scheme = DB_Select(SPrintF('%sSchemesOwners',$Service['Code']),Array('ID','Name','PackageID'),Array('UNIQ','ID'=>$SchemeID));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Scheme)){
	case 'error':
	return ERROR | @Trigger_Error(500);
	case 'exception':
		$Scheme = Array('Name' => SPrintF('Deleted:%s',$ServiceID));
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
}else{
	#-------------------------------------------------------------------------------
	$Scheme = Array('Name' => 'Любой тариф');
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($SchemesGroupID){
	#-------------------------------------------------------------------------------
	$SchemesGroup = DB_Select('SchemesGroups',Array('ID','Name'),Array('UNIQ','ID'=>$SchemesGroupID));
	#-------------------------------------------------------------------------------
	switch(ValueOf($SchemesGroup)){
	case 'error':
	return ERROR | @Trigger_Error(500);
	case 'exception':
		$SchemesGroup = Array('Name' => SPrintF('Deleted:%s',$SchemesGroupID));
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Out = $SchemesGroupID?SPrintF('* %s',$SchemesGroup['Name']):SPrintF('%s/%s',$Service['Name'],$Scheme['Name']);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/String',$Out,$Length);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Comp;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
?>
