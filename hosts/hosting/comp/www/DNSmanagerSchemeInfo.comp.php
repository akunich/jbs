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
$DNSmanagerSchemeID = (string) @$Args['DNSmanagerSchemeID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DNSmanagerScheme = DB_Select('DNSmanagerSchemes','*',Array('UNIQ','ID'=>$DNSmanagerSchemeID));
#-------------------------------------------------------------------------------
switch(ValueOf($DNSmanagerScheme)){
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
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Window')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Тариф хостинга');
#-------------------------------------------------------------------------------
$Table = Array('Общая информация');
#-------------------------------------------------------------------------------
$Table[] = Array('Название тарифа',$DNSmanagerScheme['Name']);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Currency',$DNSmanagerScheme['CostDay']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Цена 1 дн.',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$ServersGroup = DB_Select('ServersGroups','*',Array('UNIQ','ID'=>$DNSmanagerScheme['ServersGroupID']));
if(!Is_Array($ServersGroup))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Группа серверов',$ServersGroup['Name']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$HardServerName = 'Любой сервер группы';
#-------------------------------------------------------------------------------
$HardServer = DB_Select('Servers','*',Array('UNIQ','ID'=>$DNSmanagerScheme['HardServerID']));
switch(ValueOf($DNSmanagerScheme)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	#-------------------------------------------------------------------------------
	$HardServerName = $HardServer['Address'];
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Table[] = Array('Сервер размещения',$HardServerName);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Logic',$DNSmanagerScheme['IsReselling']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Права реселлера',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Logic',$DNSmanagerScheme['IsActive']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Тариф активен',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Logic',$DNSmanagerScheme['IsProlong']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Возможность продления',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Logic',$DNSmanagerScheme['IsSchemeChange']);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Возможность смены тарифа',$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($DNSmanagerScheme['MaxOrders'] > 0)
	$Table[] = Array('Максимальное число заказов',$DNSmanagerScheme['MaxOrders']);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Table[] = 'Общие ограничения';
#-------------------------------------------------------------------------------
#------------------------------------------------------------------------------
if($DNSmanagerScheme['Reseller'])
	$Table[] = Array('Реселлер',$DNSmanagerScheme['Reseller']);
#------------------------------------------------------------------------------
#------------------------------------------------------------------------------
if($DNSmanagerScheme['ViewArea'])
	$Table[] = Array('Область DNS (view)',$DNSmanagerScheme['ViewArea']);
#------------------------------------------------------------------------------
#------------------------------------------------------------------------------
$Table[] = Array('Число доменов',$DNSmanagerScheme['DomainLimit']);
#------------------------------------------------------------------------------
#------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#------------------------------------------------------------------------------
$DOM->AddChild('Into',$Comp);
#------------------------------------------------------------------------------
#------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
	return ERROR | @Trigger_Error(500);
#------------------------------------------------------------------------------
#------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object);
#------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
