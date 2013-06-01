<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Result = Array();
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Config = Config();
#-------------------------------------------------------------------------------
$Settings = $Config['Interface']['Notes']['Administrator']['Announcement'];
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if(!$Settings['ShowAnnouncement'])
	return $Result;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Where = Array(
		"`Partition` = '/User/Announcement'",
		"`IsPublish` = 'yes'",
		"`IsDOM` = 'yes'",
		"`IsXML` = 'yes'",
	);
#-------------------------------------------------------------------------------
$Announcements = DB_Select('Clauses',Array('ID','Text'),Array('Where'=>$Where));
switch(ValueOf($Announcements)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return $Result;
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$NoBody = new Tag('NOBODY');
#-------------------------------------------------------------------------------
$UniqID = UniqID('AnnouncementsText');
#-------------------------------------------------------------------------------
$Text = SPrintF('Внимание, пользователям показываются объявления. Число отображаемых объявлений: %s штук',SizeOf($Announcements));
#-------------------------------------------------------------------------------
$OnClick = SPrintF("var Style = document.getElementById('%s').style;Style.display = (Style.display != 'none'?'none':'');",$UniqID);
#-------------------------------------------------------------------------------
$NoBody->AddChild(new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>$OnClick),$Text));
#-------------------------------------------------------------------------------
$Div = new Tag('DIV',Array('ID'=>$UniqID,'style'=>'display:none;'));
#-------------------------------------------------------------------------------
foreach($Announcements as $Announcement){
	#-------------------------------------------------------------------------------
	$Div->AddChild(new Tag('HR',Array('size'=>1)));
	#-------------------------------------------------------------------------------
	$Div->AddHTML($Announcement['Text']);
	#-------------------------------------------------------------------------------
	$Div->AddChild(new Tag('DIV',Array('align'=>'right'),new Tag('A',Array('href'=>SPrintF("javascript: var Window = window.open('/Administrator/ClauseEdit?ClauseID=%s','ClauseEdit',SPrintF('left=%%u,top=%%u,width=800,height=680,toolbar=0, scrollbars=1, location=0',(screen.width-800)/2,(screen.height-600)/2));",$Announcement['ID'])),'[редактировать]')));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$NoBody->AddChild($Div);
#-------------------------------------------------------------------------------
$Result[] = $NoBody;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $Result;
#-------------------------------------------------------------------------------

?>
