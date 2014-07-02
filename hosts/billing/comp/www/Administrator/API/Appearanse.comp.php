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
$Copyright = (string) @$Args['Copyright'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','libs/Upload.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Upload = Upload_Get('TopLogo');
#-------------------------------------------------------------------------------
switch(ValueOf($Upload)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    # No more...
  break;
  case 'array':
    #---------------------------------------------------------------------------
    $File = SPrintF('styles/%s/Images/TopLogo.png',HOST_ID);
    #---------------------------------------------------------------------------
    $IsWrite = IO_Write(SPrintF('%s/%s',SYSTEM_PATH,$File),$Upload['Data'],TRUE);
    if(Is_Error($IsWrite))
      return ERROR | @Trigger_Error(500);
  break;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Upload = Upload_Get('Favicon');
#-------------------------------------------------------------------------------
switch(ValueOf($Upload)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    # No more...
  break;
  case 'array':
    #---------------------------------------------------------------------------
    $File = SPrintF('styles/%s/Images/favicon.ico',HOST_ID);
    #---------------------------------------------------------------------------
    $IsWrite = IO_Write(SPrintF('%s/%s',SYSTEM_PATH,$File),$Upload['Data'],TRUE);
    if(Is_Error($IsWrite))
      return ERROR | @Trigger_Error(500);
  break;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$IsUpdate = DB_Update('Config',Array('Value'=>$Copyright),Array('Where'=>"`Param` = 'Copyright'"));
if(Is_Error($IsUpdate))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Upload = Upload_Get('Logo');
#-------------------------------------------------------------------------------
switch(ValueOf($Upload)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    # No more...
  break;
  case 'array':
    #---------------------------------------------------------------------------
    $File = SPrintF('styles/%s/Images/Logo.bmp',HOST_ID);
    #---------------------------------------------------------------------------
    $IsWrite = IO_Write(SPrintF('%s/%s',SYSTEM_PATH,$File),$Upload['Data'],TRUE);
    if(Is_Error($IsWrite))
      return ERROR | @Trigger_Error(500);
  break;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Upload = Upload_Get('Stamp');
#-------------------------------------------------------------------------------
switch(ValueOf($Upload)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    # No more...
  break;
  case 'array':
    #---------------------------------------------------------------------------
    $File = SPrintF('styles/%s/Images/Stamp.bmp',HOST_ID);
    #---------------------------------------------------------------------------
    $IsWrite = IO_Write(SPrintF('%s/%s',SYSTEM_PATH,$File),$Upload['Data'],TRUE);
    if(Is_Error($IsWrite))
      return ERROR | @Trigger_Error(500);
  break;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Upload = Upload_Get('dSign');
#-------------------------------------------------------------------------------
switch(ValueOf($Upload)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    # No more...
  break;
  case 'array':
    #---------------------------------------------------------------------------
    $File = SPrintF('styles/%s/Images/dSign.bmp',HOST_ID);
    #---------------------------------------------------------------------------
    $IsWrite = IO_Write(SPrintF('%s/%s',SYSTEM_PATH,$File),$Upload['Data'],TRUE);
    if(Is_Error($IsWrite))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
  break;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Upload = Upload_Get('aSign');
#-------------------------------------------------------------------------------
switch(ValueOf($Upload)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    # No more...
  break;
  case 'array':
    #---------------------------------------------------------------------------
    $File = SPrintF('styles/%s/Images/aSign.bmp',HOST_ID);
    #---------------------------------------------------------------------------
    $IsWrite = IO_Write(SPrintF('%s/%s',SYSTEM_PATH,$File),$Upload['Data'],TRUE);
    if(Is_Error($IsWrite))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
  break;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok');
#-------------------------------------------------------------------------------

?>
