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
$UserID = (integer) @$Args['UserID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM = new DOM();
#-------------------------------------------------------------------------------
$Links = &Links();
# Коллекция ссылок
$Links['DOM'] = &$DOM;
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Load('Window')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$IsPermission = Permission_Check('/Administrator/',(integer)$GLOBALS['__USER']['ID']);
#-------------------------------------------------------------------------------
switch(ValueOf($IsPermission)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return ERROR | @Trigger_Error(400);
  case 'false':
    # No more...
  case 'true':
    # No more...
  break;
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
if($UserID){
  #-----------------------------------------------------------------------------
  $User = DB_Select('Users',Array('ID','GroupID','Name'),Array('UNIQ','ID'=>$UserID));
  #-----------------------------------------------------------------------------
  switch(ValueOf($User)){
    case 'error':
      return ERROR | @Trigger_Error(500);
    case 'exception':
      return new gException('USER_NOT_FOUND','Пользователь не найден');
    case 'array':
      # No more...
    break;
    default:
      return ERROR | @Trigger_Error(101);
  }
}
#-------------------------------------------------------------------------------
$DOM->AddText('Title',$UserID?SPrintF('Новый запрос для [%s]',$User['Name']):'Новый запрос');
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/TicketEdit.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'TicketEditForm','onsubmit'=>'return false;'));
#-------------------------------------------------------------------------------
$Table = Array('Общие параметры');
#-------------------------------------------------------------------------------
$Prompt = 'Краткое описание Вашей проблемы или вопроса.<BR /><B>Например:</B> Проблемы с почтой';
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
  'Form/Input',
  Array(
    'name'  => 'Theme',
    'size'  => 65,
    'type'  => 'text'
  ),
  $Prompt
);
if(Is_Error($Comp))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = Array('Тема запроса',$Comp);
#-------------------------------------------------------------------------------
$Groups = DB_Select('Groups',Array('ID','Name','Comment'),Array('Where'=>"`IsDepartment` = 'yes'"));
#-------------------------------------------------------------------------------
switch(ValueOf($Groups)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return new gException('DEPARTMENTS_NOT_FOUND','Отделы не определены');
  case 'array':
    #---------------------------------------------------------------------------
    $Options = Array();
    #---------------------------------------------------------------------------
    foreach($Groups as $Group)
      $Options[$Group['ID']] = SPrintF('%s (%s)',$Group['Name'],$Group['Comment']);
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Form/Select',Array('name'=>'TargetGroupID'),$Options);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Table[] = Array('Отдел',$Comp);
    #---------------------------------------------------------------------------
    if($IsPermission){
      #-------------------------------------------------------------------------
      $Workers = DB_Select('Users',Array('ID','Name'),Array('Where'=>SPrintF("(SELECT `IsDepartment` FROM `Groups` WHERE `Groups`.`ID` = `Users`.`GroupID`) = 'yes' OR `ID` = 100")));
      #-------------------------------------------------------------------------
      switch(ValueOf($Workers)){
        case 'error':
          return ERROR | @Trigger_Error(500);
        case 'exception':
          return new gException('WORKERS_NOT_FOUND','Сотрудники не определены');
        case 'array':
          #---------------------------------------------------------------------
          $Options = Array();
          #---------------------------------------------------------------------
          foreach($Workers as $Worker){
            #-------------------------------------------------------------------
            $WorkerID = $Worker['ID'];
            #-------------------------------------------------------------------
            $Options[$WorkerID] = $Worker['Name'];
          }
          #---------------------------------------------------------------------
          $Comp = Comp_Load('Form/Select',Array('name'=>'TargetUserID'),$Options,$GLOBALS['__USER']['ID']);
          if(Is_Error($Comp))
            return ERROR | @Trigger_Error(500);
          #---------------------------------------------------------------------
          $Table[] = Array('Сотрудник',$Comp);
        break;
        default:
          return ERROR | @Trigger_Error(101);
      }
    }
    #---------------------------------------------------------------------------
    $Config = Config();
    #---------------------------------------------------------------------------
    $Priorities = $Config['Edesks']['Priorities'];
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Form/Select',Array('name'=>'PriorityID'),$Priorities);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Table[] = Array('Приоритет',$Comp);
    #---------------------------------------------------------------------------
    $Table[] = 'Сообщение';
    #---------------------------------------------------------------------------
    //$Smiles = System_XML('config/Smiles.xml');
    //if(Is_Error($Smiles))
    //  return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    //$Options = Array('NO'=>'Не выбран');
    #---------------------------------------------------------------------------
    //foreach($Smiles as $Smile)
    //  $Options[$Smile['Pattern']] = $Smile['Name'];
    #---------------------------------------------------------------------------
    //$Comp = Comp_Load('Form/Select',Array('name'=>'Smile','onchange'=>"if(value != 'NO'){ form.Message.value += value; }"),$Options);
    //if(Is_Error($Comp))
    //  return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Tr = new Tag('TR');
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Upload','TicketMessageFile');
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Tr->AddChild(new Tag('NOBODY',new Tag('TD',Array('class'=>'Comment'),'Прикрепить файл'),new Tag('TD',$Comp)));
    #---------------------------------------------------------------------------
    $Table[] = new Tag('TABLE',$Tr);
    #---------------------------------------------------------------------------
    $Comp = Comp_Load(
      'Form/TextArea',
      Array(
        'name'  => 'Message',
        'style' => 'width:100%;',
        'rows'  => 10
      )
    );
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Table[] = $Comp;
    #---------------------------------------------------------------------------
    $Disabled = Array();
    #---------------------------------------------------------------------------
    if(!$IsPermission)
      $Disabled[] = 'hidden';
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Edesks/Panel',$Disabled);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    #---------------------------------------------------------------------------
    $Tr = new Tag('TR',$Comp);
    #---------------------------------------------------------------------------
    $Comp = Comp_Load(
      'Form/Input',
      Array(
        'type'    => 'button',
        'onclick' => 'TicketEdit();',
        'value'   => 'Добавить'
      )
    );
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    if($IsPermission){
        # сотрудник, добавляем флаги
	$Config = Config();
	$Positions = $Config['Edesks']['Flags'];
	#---------------------------------------------------------------------------
	$Comp1 = Comp_Load('Form/Select',
			Array('name'=>'Flags'),
			$Positions);
	if(Is_Error($Comp1))
		return ERROR | @Trigger_Error(500);
	
	$Div = new Tag('DIV',$Comp1,new Tag('SPAN','и'),$Comp);
    }else{
        # юзер. тока кнопка
        $Div = new Tag('DIV',$Comp);
    }
    #---------------------------------------------------------------------------
    $Tr->AddChild(new Tag('TD',Array('align'=>'right'),$Div));
    $Table[] = new Tag('TABLE',Array('width'=>'100%'),$Tr);
    #---------------------------------------------------------------------------
    #---------------------------------------------------------------------------
    if($UserID){
      #-------------------------------------------------------------------------
      $Comp = Comp_Load(
        'Form/Input',
        Array(
          'type'  => 'hidden',
          'name'  => 'UserID',
          'value' => $UserID
        )
      );
      if(Is_Error($Comp))
        return ERROR | @Trigger_Error(500);
      #-------------------------------------------------------------------------
      $Form->AddChild($Comp);
    }
    #---------------------------------------------------------------------------
    $Comp = Comp_Load('Tables/Standard',$Table);
    if(Is_Error($Comp))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    $Form->AddChild($Comp);
    #---------------------------------------------------------------------------
    $Tr = new Tag('TR',new Tag('TD',Array('valign'=>'top'),$Form));
    #---------------------------------------------------------------------------
    if(!$UserID){
      #-------------------------------------------------------------------------
      $Users = DB_Select('Users',Array('ID','Name','(SELECT `Name` FROM `Groups` WHERE `Users`.`GroupID` = `Groups`.`ID`) as `GroupName`'),Array('Where'=>"(SELECT `IsDepartment` FROM `Groups` WHERE `Groups`.`ID` = `Users`.`GroupID`) = 'yes' AND `IsHidden` = 'no' AND UNIX_TIMESTAMP() - `EnterDate` < 600"));
      #-------------------------------------------------------------------------
      switch(ValueOf($Users)){
        case 'error':
          return ERROR | @Trigger_Error(500);
        case 'exception':
          # No more...
        break;
        case 'array':
          #---------------------------------------------------------------------
          $Table = new Tag('TABLE',Array('class'=>'Standard','cellspacing'=>5),new Tag('CAPTION','Сейчас в сети'));
          #---------------------------------------------------------------------
          $Block = new Tag('TR');
          #---------------------------------------------------------------------
          foreach($Users as $User){
#-------------------------------------------------------------------------------
$Parse = <<<EOD
<TD align="center" valign="top">
 <IMG height="110" weight="90" style="border:1px solid #DCDCDC;" src="/UserFoto?UserID=%s" />
 <BR />
 <SPAN>%s</SPAN>
 <BR />
 <SPAN style="font-size:11px;">[%s]</SPAN>
</TD>
EOD;
#-------------------------------------------------------------------------------
            $Block->AddHTML(SPrintF($Parse,$User['ID'],$User['Name'],$User['GroupName']));
            #-------------------------------------------------------------------
            if(Count($Block->Childs)%2 == 0){
              #-----------------------------------------------------------------
              $Table->AddChild($Block);
              #-----------------------------------------------------------------
              $Block = new Tag('TR');
            }
          }
          #---------------------------------------------------------------------
          if(Count($Block->Childs))
            $Table->AddChild($Block);
          #---------------------------------------------------------------------
          $Tr->AddChild(new Tag('TD',Array('valign'=>'top'),$Table));
        break;
        default:
          return ERROR | @Trigger_Error(101);
      }
    }
    #---------------------------------------------------------------------------
    $DOM->AddChild('Into',new Tag('TABLE',$Tr));
    #---------------------------------------------------------------------------
    if(Is_Error($DOM->Build(FALSE)))
      return ERROR | @Trigger_Error(500);
    #---------------------------------------------------------------------------
    return Array('Status'=>'Ok','DOM'=>$DOM->Object);
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
