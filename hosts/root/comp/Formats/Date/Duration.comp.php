<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Stamp');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Hour    = (integer)($Stamp/3600);
$Minutes = (integer)(($Stamp - $Hour*3600)/60);
$Seconds = (integer)($Stamp - $Hour*3600 - $Minutes*60);
#-------------------------------------------------------------------------------
return SPrintF('%02u:%02u:%02u',$Hour,$Minutes,$Seconds);
#-------------------------------------------------------------------------------

?>
