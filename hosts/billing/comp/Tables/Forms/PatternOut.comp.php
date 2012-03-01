<?php


#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('LinkID','Patterns','PatternOutID');
/******************************************************************************/
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Links = &Links();
# Коллекция ссылок
$Template = &$Links[$LinkID];
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('classes/Session.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table = $Options = Array();
#-------------------------------------------------------------------------------
foreach(Array_Keys($Patterns) as $PatternID)
  $Options[$PatternID] = $Patterns[$PatternID]['Name'];
#-------------------------------------------------------------------------------
$Session = &$Template['Session'];
#-------------------------------------------------------------------------------
if(IsSet($Session['PatternOutID']))
  $PatternOutID = $Session['PatternOutID'];
#-------------------------------------------------------------------------------
$Args = Args();
#-------------------------------------------------------------------------------
if(IsSet($Args['PatternOutID']))
  $PatternOutID = $Args['PatternOutID'];
#-------------------------------------------------------------------------------
if(!IsSet($Patterns[$PatternOutID]))
  $PatternOutID = Current(Array_Keys($Patterns));
#-------------------------------------------------------------------------------
$Session['PatternOutID'] = $PatternOutID;
#-------------------------------------------------------------------------------
$Where = $Patterns[$PatternOutID]['Where'];
#-------------------------------------------------------------------------------
if($Where){
  #-----------------------------------------------------------------------------
  $AddingWhere = &$Template['Source']['Adding']['Where'];
  #-----------------------------------------------------------------------------
  $AddingWhere[] = $Where;
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/Select',Array('name'=>'PatternOutID','onchange'=>'TableSuperSetIndex();'),$Options,$PatternOutID);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Шаблон',$Comp);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return $Comp;
#-------------------------------------------------------------------------------

?>
