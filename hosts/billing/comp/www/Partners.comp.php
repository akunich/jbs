<?php


#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
if(Is_Error(System_Load('classes/DOM.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Main')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Наши партнеры');
#-------------------------------------------------------------------------------
$Profiles = DB_Select('Profiles','*',Array('SortOn'=>'StatusDate','IsDesc'=>TRUE,'Where'=>"`TemplateID` = 'Partner' AND `StatusID` = 'Checked'"));
#-------------------------------------------------------------------------------
switch(ValueOf($Profiles)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Information','Партнеры не найдены. Для размещения партнерской информации используются профили [Партнер].','Notice');
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $DOM->AddChild('Into',$Comp);
  break;
  case 'array':
    #---------------------------------------------------------------------------
    $Table = new Tag('TABLE',Array('class'=>'Standard','cellspacing'=>5));
    #---------------------------------------------------------------------------
    foreach($Profiles as $Profile){
      #-------------------------------------------------------------------------
      $Compile = Comp_Load('www/Administrator/API/ProfileCompile',Array('ProfileID'=>$Profile['ID']));
      #-------------------------------------------------------------------------
      switch(ValueOf($Compile)){
        case 'error':
          return ERROR | @Trigger_Error(500);
        case 'exception':
          return ERROR | @Trigger_Error(400);
        case 'array':
          #---------------------------------------------------------------------
          $Compile = $Compile['Attribs'];
          #---------------------------------------------------------------------
          $Td = new Tag('TD',Array('colspan'=>2,'class'=>'Separator'));
          #---------------------------------------------------------------------
          if($Compile['Type'] != 'Не указан')
            $Td->AddHTML(SPrintF('<SPAN>%s: </SPAN>',$Compile['Type']));
          #---------------------------------------------------------------------
          $Td->AddHTML(SPrintF('<NOBODY><B>%s</B>, %s</NOBODY>',$Compile['Name'],$Compile['City']));
          #---------------------------------------------------------------------
          if($Compile['Phone'])
            $Td->AddHTML(SPrintF('<SPAN>, %s</SPAN>',$Compile['Phone']));
          #---------------------------------------------------------------------
          $Table->AddChild(new Tag('TR',$Td));
          #---------------------------------------------------------------------
          $Tr = new Tag('TR');
          #---------------------------------------------------------------------
          $NoIndex = new Tag('NOINDEX',new Tag('A',Array('class'=>'Image','target'=>'blank','href'=>$Compile['SiteURL']),
            new Tag('IMG',Array('border'=>0,'style'=>'max-width:200px;max-height:150px;','src'=>$Compile['LogoURL']))));
          #---------------------------------------------------------------------
          $Tr->AddChild(new Tag('TD',Array('class'=>'Standard','style'=>'padding:0px;','align'=>'center'),$NoIndex));
          #---------------------------------------------------------------------
          $Tr->AddChild(new Tag('TD',Array('class'=>'Standard'),new Tag('P',$Compile['Comment'])));
          #---------------------------------------------------------------------
          $Table->AddChild($Tr);
        break;
        default:
          return ERROR | @Trigger_Error(101);
      }
    }
    #---------------------------------------------------------------------------
    $DOM->AddChild('Into',$Table);
  break;
  default:
    return ERROR | @Trigger_Error(101);
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
