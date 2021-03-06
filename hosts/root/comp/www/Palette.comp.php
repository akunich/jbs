<?php


#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('libs/Color.php','classes/DOM.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
#-------------------------------------------------------------------------------
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Standard')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Цветовая палитра');
#-------------------------------------------------------------------------------
$HostsIDs = IO_Scan(SPrintF('%s/styles/',SYSTEM_PATH));
if(Is_Error($HostsIDs))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
# Все цвета в палитре
$Colors = Array('FFFFFF','000000');
#-------------------------------------------------------------------------------
$Palette = Styles_XML('Palette.xml');
if(Is_Error($Palette))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table = new Tag('TABLE',Array('class'=>'Standard','cellspacing'=>0,'cellpadding'=>0));
#-------------------------------------------------------------------------------
$Gradetion = $Palette['Gradetion'];
#-------------------------------------------------------------------------------
foreach($Palette['Colors'] as $Color){
  #-----------------------------------------------------------------------------
  $Tr = new Tag('TR');
  #-----------------------------------------------------------------------------
  for($i=1;$i<($Count = $Gradetion['Count'])*2;$i++){
    #---------------------------------------------------------------------------
    $IsLeft = ($i >= $Count);
    #---------------------------------------------------------------------------
    $Shift = (($IsLeft?$i-$Count:$Count-$i)*$Gradetion['Step']);
    #---------------------------------------------------------------------------
    $Shifted = Color_Shift(HexDec($Color),($IsLeft?0xFFFFFF:0x000000),$Shift);
    #---------------------------------------------------------------------------
    $Colors[] = $Shifted;
    #---------------------------------------------------------------------------
    $Td = new Tag('TD',Array('align'=>'center','bgcolor'=>$Shifted,'height'=>50,'width'=>50),$Shifted);
    #---------------------------------------------------------------------------
    if($i == $Count)
      $Td->AddAttribs(Array('style'=>'border:1px solid #DCDCDC;'));
    #---------------------------------------------------------------------------
    $Tr->AddChild($Td);
  }
  #-----------------------------------------------------------------------------
  $Table->AddChild($Tr);
}
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Table);
#-------------------------------------------------------------------------------
foreach($HostsIDs as $HostsID){
  #-----------------------------------------------------------------------------
  $Files = IO_Files(SPrintF('%s/styles/%s',SYSTEM_PATH,$HostsID));
  if(Is_Error($Files))
    return ERROR | @Trigger_Error(500);
  #-----------------------------------------------------------------------------
  if(Count($Files) < 1)
    continue;
  #-----------------------------------------------------------------------------
  # Все цвета в стиле
  $Finded = Array();
  #-----------------------------------------------------------------------------
  foreach($Files as $File){
    #---------------------------------------------------------------------------
    if(Preg_Match('/\/others\//',$File))
      continue;
    #---------------------------------------------------------------------------
    $Sourse = IO_Read($File);
    if(Is_Error($Sourse))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    if(Preg_Match_All('/#[a-zA-F0-9]{6}/',$Sourse,$Matches) > 1){
      #-------------------------------------------------------------------------
      foreach(Current($Matches) as $Match)
        $Finded[Md5($Match)] = Mb_SubStr($Match,1);
    }
  }
  #-----------------------------------------------------------------------------
  if(Count($Finded) < 1)
    continue;
  #-----------------------------------------------------------------------------
  $Tr = new Tag('TR');
  #-----------------------------------------------------------------------------
  foreach($Finded as $Color){
    #---------------------------------------------------------------------------
    if(In_Array($Color,$Colors))
      continue;
    #---------------------------------------------------------------------------
    $Tr->AddChild(new Tag('TD',Array('align'=>'center','bgcolor'=>SPrintF('#%s',$Color),'height'=>50,'width'=>50),$Color));
  }
  #-----------------------------------------------------------------------------
  if(Count($Tr->Childs) < 1)
    continue;
  #-----------------------------------------------------------------------------
  $DOM->AddChild('Into',new Tag('H1',$HostsID));
  #-----------------------------------------------------------------------------
  $Table = new Tag('TABLE',Array('class'=>'Standard','cellspacing'=>0,'cellpadding'=>0),$Tr);
  #-----------------------------------------------------------------------------
  $DOM->AddChild('Into',$Table);
}
#-------------------------------------------------------------------------------
$Out = $DOM->Build();
#-------------------------------------------------------------------------------
if(Is_Error($Out))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return $Out;
#-------------------------------------------------------------------------------


?>
