<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('StatusID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
switch($StatusID){
  case 'OnForming':
    $Color = 'F9E47D';
  break;
  case 'Public':
    $Color = 'F1FCCE';
  break;
  case 'Complite':
    $Color = 'D5F66C';
  break;
  default:
    $Color = '999999';
}
#-------------------------------------------------------------------------------
return Array('bgcolor'=>SPrintF('#%s',$Color));
#-------------------------------------------------------------------------------

?>
