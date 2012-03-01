<?php


#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$Base     =  (string) @$Args['Base'];
$IsFormat = (boolean) @$Args['IsFormat'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Data = String_XML_Parse($Base);
if(Is_Exception($Data))
  return new gException('XML_NOT_VALID','Ошибка в синтаксисе',$Data);
#-------------------------------------------------------------------------------
$Data = Current($Data->Childs);
#-------------------------------------------------------------------------------
$DOM = new DOM($Data);
#-------------------------------------------------------------------------------
foreach(Array('Head','Body','Floating') as $LinkID){
  #-----------------------------------------------------------------------------
  if(!IsSet($DOM->Links[$LinkID]))
    return new gException('ID_NOT_FOUND',SPrintF('Идентификатор (%s) не найден, пример (<ELEMENT id="%s"></ELEMENT>)',$LinkID,$LinkID));
}
#-------------------------------------------------------------------------------
$Answer = Array('Status'=>'Ok');
#-------------------------------------------------------------------------------
if($IsFormat){
  #-----------------------------------------------------------------------------
  $Base = $Data->ToXMLString();
  #-----------------------------------------------------------------------------
  $Answer['Base'] = $Base;
}
#-------------------------------------------------------------------------------
$IsWrite = IO_Write(SPrintF('%s/hosts/%s/templates/Base.xml',SYSTEM_PATH,HOST_ID),$Base,TRUE);
if(Is_Error($IsWrite))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return $Answer;
#-------------------------------------------------------------------------------

?>
