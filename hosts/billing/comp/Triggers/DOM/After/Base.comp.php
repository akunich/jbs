<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('LinkID');
/******************************************************************************/
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Links = &Links();
# Коллекция ссылок
$DOM = &$Links[$LinkID];
#-------------------------------------------------------------------------------
$TitleTag = $DOM->GetByTagName('TITLE');
#-------------------------------------------------------------------------------
$Title = Current($TitleTag);
#-------------------------------------------------------------------------------
$Title->AddText(SPrintF('%s - %s',Str_Replace('→','-',$Title->Text),HOST_ID),TRUE);
#-------------------------------------------------------------------------------
$Where = SPrintF("`Partition` = 'Header:%s'",DB_Escape(IsSet($GLOBALS['_GET']['ServiceID'])?$GLOBALS['_GET']['ServiceID']:$GLOBALS['__URI']));
#-------------------------------------------------------------------------------
$Clauses = DB_Select('Clauses','ID',Array('Where'=>$Where));
#-------------------------------------------------------------------------------
switch(ValueOf($Clauses)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	# No more...
	break;
case 'array':
	#-------------------------------------------------------------------------------
	$Clause = Current($Clauses);
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Clauses/Load',$Clause['ID']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$MessageID = SPrintF('clause_%s_%s',IsSet($GLOBALS['__USER']['ID'])?$GLOBALS['__USER']['ID']:10,SubStr(Md5(JSON_Encode($Comp)),0,6));
	#-------------------------------------------------------------------------------
	if(IsSet($_COOKIE[$MessageID]))
		break;
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Information',$Comp['DOM'],'Warning',$MessageID);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$DOM->AddChild('Into',new Tag('SPAN',$Comp,new Tag('BR')),TRUE);
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
