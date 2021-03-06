<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Attribs','Options','SelectedIDs','DisabledIDs');
/******************************************************************************/
Eval(COMP_INIT);
#******************************************************************************#
#******************************************************************************#
if(!Count($Options))
  return ERROR | @Trigger_Error('[comp/Form/Select]: параметр вариантов оказался пустым');
#-------------------------------------------------------------------------------
$Select = new Tag('SELECT');
#-------------------------------------------------------------------------------
if(IsSet($Attribs['prompt'])){
  #-----------------------------------------------------------------------------
  $Prompt = $Attribs['prompt'];
  #-----------------------------------------------------------------------------
  UnSet($Attribs['prompt']);
  #-----------------------------------------------------------------------------
  $LinkID = UniqID('Select');
  #-----------------------------------------------------------------------------
  $Links = &Links();
  #-----------------------------------------------------------------------------
  $Links[$LinkID] = &$Select;
  #-----------------------------------------------------------------------------
  $Comp = Comp_Load('Form/Prompt',$LinkID,$Prompt);
  if(Is_Error($Comp))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  UnSet($Links[$LinkID]);
}
#-------------------------------------------------------------------------------
$Select->AddAttribs($Attribs);
#-------------------------------------------------------------------------------
$OptionsIDs = Array_Keys($Options);
#-------------------------------------------------------------------------------
foreach($OptionsIDs as $OptionID){
  #-----------------------------------------------------------------------------
  $Option = $Options[$OptionID];
  #-----------------------------------------------------------------------------
  if(Is_Scalar($Option)){
    #---------------------------------------------------------------------------
    $Option = new Tag('OPTION',Array('value'=>$OptionID),$Options[$OptionID]);
    #---------------------------------------------------------------------------
    if(!Is_Null($SelectedIDs)){
      #-------------------------------------------------------------------------
      if(!Is_Array($SelectedIDs))
        $SelectedIDs = Array($SelectedIDs);
      #-------------------------------------------------------------------------
      if(In_Array($OptionID,$SelectedIDs))
        $Option->AddAttribs(Array('selected'=>'true'));
    }
    #---------------------------------------------------------------------------
    if(!Is_Null($DisabledIDs)){
      #-------------------------------------------------------------------------
      if(!Is_Array($DisabledIDs))
        $DisabledIDs = Array($DisabledIDs);
      #-------------------------------------------------------------------------
      if(In_Array($OptionID,$DisabledIDs))
        $Option->AddAttribs(Array('disabled'=>'true'));
    }
  }
  #-----------------------------------------------------------------------------
  $Select->AddChild($Option);
}
#-------------------------------------------------------------------------------
return $Select;
#-------------------------------------------------------------------------------

?>
