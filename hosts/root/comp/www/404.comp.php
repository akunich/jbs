<?php


#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.) */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
Header('HTTP/1.1 404 OK');
#-------------------------------------------------------------------------------
if(XML_HTTP_REQUEST)
  return new gException('PAGE_NOT_FOUND','Страница не найдена [404]');
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('classes/DOM.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
#-------------------------------------------------------------------------------
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Main')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title','Страница не найдена [404]');
#-------------------------------------------------------------------------------
$Parse = <<<EOD
<TABLE class="Standard" cellspacing="5">
 <TR>
  <TD width="48">
   <IMG src="SRC:{Images/Icons/404.gif}" alt="Ошибка 404" height="48" width="48" />
  </TD>
  <TD>
   <P>Возможно, страница была переименована или перемещена.</P>
  </TD>
 </TR>
</TABLE>
EOD;
#-------------------------------------------------------------------------------
$DOM->AddHTML('Into',$Parse);
#-------------------------------------------------------------------------------
$Out = $DOM->Build();
#-------------------------------------------------------------------------------
if(Is_Error($Out))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
return $Out;
#-------------------------------------------------------------------------------

?>
