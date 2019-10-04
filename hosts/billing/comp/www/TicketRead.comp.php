<?php

#-------------------------------------------------------------------------------
/** @author Великодный В.В. (Joonte Ltd.)
    rewritten by Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('Args');
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = IsSet($Args)?$Args:Args();
#-------------------------------------------------------------------------------
$TicketID	= (integer) @$Args['TicketID'];
$IsInternal	= (boolean) @$Args['IsInternal'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php')))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// если это мобильный, или ширина окна меньше 400 пикселов - отпарвляем на мобильную версию
if((IsSet($_COOKIE['IsMobile']) && $_COOKIE['IsMobile']) || @$_COOKIE['wScreen'] < 400)
	if(!$IsInternal)
		return Array('Status'=>'Url','Location'=>SPrintF('/TicketMessages?TicketID=%u',$TicketID));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Columns = Array(
		'ID','UserID','Theme','UpdateDate','StatusID','SeenByPersonal','LastSeenBy','Flags',
		'(SELECT `Name` FROM `Users` WHERE `Users`.`ID` = `Edesks`.`LastSeenBy`) AS `LastSeenByName`',
		'(SELECT `IsDepartment` FROM `Groups` WHERE `Groups`.`ID` = (SELECT `GroupID` FROM `Users` WHERE `Users`.`ID` = `Edesks`.`UserID`)) AS `IsDepartment`',
		);
$Ticket = DB_Select('Edesks',$Columns,Array('UNIQ','ID'=>$TicketID));
#-------------------------------------------------------------------------------
switch(ValueOf($Ticket)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$__USER = $GLOBALS['__USER'];
#-------------------------------------------------------------------------------
$IsPermission = Permission_Check('TicketRead',(integer)$__USER['ID'],(integer)$Ticket['UserID']);
#-------------------------------------------------------------------------------
switch(ValueOf($IsPermission)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'false':
	return ERROR | @Trigger_Error(700);
case 'true':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
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
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/TicketRead.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
$Script = new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/TicketFunctions.js}'));
#-------------------------------------------------------------------------------
$DOM->AddChild('Head',$Script);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$DOM->AddAttribs('Body',Array('onload'=>"window.document.getElementById('Message').focus();"));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Formats/Edesk/Number',$TicketID);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DOM->AddText('Title',HtmlSpecialChars(SPrintF('#%s | %s',$Comp,$Ticket['Theme'])));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'name'  => 'TicketID',
			'type'  => 'hidden',
			'value' => $Ticket['ID']
			)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form = new Tag('FORM',Array('name'=>'TicketReadForm','onsubmit'=>'return false;','OnKeyPress'=>'ctrlEnterEvent(event,true) && TicketAddMessage();'),$Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$MaxMessageID = DB_Select('EdesksMessagesOwners',Array('MAX(`ID`) AS `MaxMessageID`','COUNT(*) AS `NumMessages`'),Array('UNIQ','Where'=>SPrintF('`EdeskID` = %u',$Ticket['ID'])));
#-------------------------------------------------------------------------------
switch(ValueOf($MaxMessageID)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	return ERROR | @Trigger_Error(400);
case 'array':
	break;
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'name'  => 'MaxID',
			'type'  => 'hidden',
			'value' => $MaxMessageID['MaxMessageID']
			)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form->AddChild($Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load(
		'Form/Input',
		Array(
			'name'  => 'OpenTicketUserID',
			'type'  => 'hidden',
			'value' => $__USER['ID']
			)
		);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form->AddChild($Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// передаём, внутренний ли вызов был
$Comp = Comp_Load('Form/Input',Array('name'=>'IsInternal','type'=>'hidden','value'=>($IsInternal)?1:0));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form->AddChild($Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// передаём, админ ли ходит
$Comp = Comp_Load('Form/Input',Array('name'=>'IsAdmin','type'=>'hidden','value'=>($GLOBALS['__USER']['IsAdmin'])?1:0));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Form->AddChild($Comp);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Tr = new Tag('TR');
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Upload','TicketMessageFile');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('NOBODY',new Tag('TD',Array('class'=>'Comment','id'=>'EdeskAddFileText'),'Прикрепить файл'),new Tag('TD',$Comp)));
#-------------------------------------------------------------------------------
// если это обычный юзер, то ему кнопок не надо
if($__USER['ID'] == $Ticket['UserID']){
	#-------------------------------------------------------------------------------
	# add SeenByUser field
	$IsUpdate = DB_Update('Edesks',Array('SeenByUser'=>Time()),Array('ID'=>$TicketID));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	// это техподдержка, достаём шаблоны ответов
	$Articles = DB_Select('Clauses','*',Array('Where'=>"`GroupID` = 11 AND `IsPublish` = 'yes'",'SortOn'=>'Partition'));
	#-------------------------------------------------------------------------------
	switch(ValueOf($Articles)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		#-------------------------------------------------------------------------------
		$A = new Tag('A',Array('title'=>'как добавить шаблоны быстрых ответов','href'=>'http://wiki.joonte.com/index.php?title=TiketAnswerTemplate'),'шаблоны ответов');
		$Tr->AddChild(new Tag('TD',$A));
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	case 'array':
		#-------------------------------------------------------------------
		foreach($Articles as $Article){
			#-------------------------------------------------------------------------------
			// уадляем теги, пустоту в начале и конце
			$Text = Trim(Strip_Tags($Article['Text']));
			// удаляем пробелы в начале строки
			$Text = Str_Replace("\n ","\n",$Text);
			// удаляем дублированные пробелы
			$Text = Str_Replace("  "," ",$Text);
			// удаляем вовзрат каретки
			$Text = Str_Replace("\r","",$Text);
			// удаляем дублликаты переносов строк
			$Text = Str_Replace("\n\n","\n",$Text);
			// готовим жаба-скрипты
			$Text = Str_Replace("\n",'\\n',$Text);
			# format: SortOrder:ImageName.gif
			// картинка кнопки, достаём её
			$Partition = Explode(":", $Article['Partition']);
			// достаём расширение картинки
			$Extension = IsSet($Partition[1])?Explode(".", StrToLower($Partition[1])):'';
			#-------------------------------------------------------------------------------
			// если есть чё-то после точки, и если оно похоже на расширение картинки, ставим это как картинку
			$Image = 'Info.gif'; #дефолтовую информационную картинку
			if(IsSet($Extension[1]) && In_Array($Extension[1],Array('png','gif','jpg','jpeg')))
				$Image = $Partition[1];
			#-------------------------------------------------------------------------------
			# делаем кнопку, если это системная кнопка или этого админа
			if((!Preg_Match('/@/',$Partition[0]) && $Partition[0] < 2000 && $__USER['Params']['Settings']['EdeskButtons'] == "No") || StrToLower($Partition[0]) == StrToLower($__USER['Email'])){
				#-------------------------------------------------------------------------------
				$Comp = Comp_Load('Buttons/Standard',Array('onclick'=>SPrintF("form.Message.value += '%s';form.Message.focus();",$Text),'style'=>'cursor: pointer;'),$Article['Title'],$Image);
				if(Is_Error($Comp))
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
				$Tr->AddChild(new Tag('TD',Array('id'=>'EdeskAdminButton'),$Comp));
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		break;
		#-------------------------------------------------------------------------------
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	# add SeenByPersonal/LastSeenBy fields
	$IsUpdate = DB_Update('Edesks',Array('SeenByPersonal'=>Time(),'LastSeenBy'=>$__USER['ID']),Array('ID'=>$TicketID));
	if(Is_Error($IsUpdate))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Table[] = new Tag('TABLE',$Tr);
#-------------------------------------------------------------------------------
// разная подсказка в окне сообщения, и разный цвет фона
if($__USER['ID'] == $Ticket['UserID']){
	#-------------------------------------------------------------------------------
	// обычный юзер, владелец тикета
	$Color = "white";
	$PlaceHolder = "Введите ваше сообщение";
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	// техподдржка
	if($Ticket['LastSeenBy'] == $__USER['ID']){
		#-------------------------------------------------------------------------------
		$Color = "white";
		$PlaceHolder = FALSE;
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		$PlaceHolder = (StrLen($Ticket['LastSeenByName']) > 0)?SPrintF('Тикет просматривается сотрудником %s',$Ticket['LastSeenByName']):FALSE;
		$TimePeriod = Time() - $Ticket['SeenByPersonal'];
		#-------------------------------------------------------------------------------
		if($TimePeriod < 60){
			#-------------------------------------------------------------------------------
			$Color = "lightcoral";
			#-------------------------------------------------------------------------------
		}elseif($TimePeriod < 120){
			#-------------------------------------------------------------------------------
			$Color = "lightpink";
			#-------------------------------------------------------------------------------
		}elseif($TimePeriod < 180){
			#-------------------------------------------------------------------------------
			$Color = "khaki";
			#-------------------------------------------------------------------------------
		}elseif($TimePeriod < 240){
			#-------------------------------------------------------------------------------
			$Color = "lemonchiffon";
			#-------------------------------------------------------------------------------
		}elseif($TimePeriod < 300){
			#-------------------------------------------------------------------------------
			$Color = "gainsboro";
			#-------------------------------------------------------------------------------
		}else{
			#-------------------------------------------------------------------------------
			if($PlaceHolder)
				$PlaceHolder = SPrintF('Тикет был просмотрен сотрудником %s, %s в %s',$Ticket['LastSeenByName'],Date('Y-m-d',$Ticket['SeenByPersonal']),Date('H:i:s',$Ticket['SeenByPersonal']));
			$Color = "white";
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
// ширина окна тикетов - на мобильном и десктопе разная
if((IsSet($_COOKIE['IsMobile']) && $_COOKIE['IsMobile']) || $IsInternal){
	#-------------------------------------------------------------------------------
	// мобильный - 100% ширина
	$WindowWidth = '100%';
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$WindowWidth = Ceil(Max(@$_COOKIE['wScreen']/1.5,630));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
# параметры для области ввода текста
$Array = Array(
		'name'		=> 'Message',
		'id'		=> 'Message',
		'rows'		=> 5,
		'AutoFocus'	=> 'yes',
		'style'		=> SPrintF('background:%s; width:%s;',$Color,$WindowWidth)
		);
#-------------------------------------------------------------------------------
//'style'               => SPrintF('background:%s; width:%u;',$Color,Max(@$_COOKIE['wScreen']/1.5,630)),
#-------------------------------------------------------------------------------
# подсказка, если есть, и разная для юзеров/админов
if($PlaceHolder){
	#-------------------------------------------------------------------------------
	if($__USER['ID'] == $Ticket['UserID']){
		#-------------------------------------------------------------------------------
		$Array['PlaceHolder'] = $PlaceHolder;
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		$Array['prompt'] = $PlaceHolder;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
// смотрит не создатель сообщения
if($__USER['ID'] != $Ticket['UserID']){
	#-------------------------------------------------------------------------------
	// достаём последнее сообщение
	$LastMessage = DB_Select('EdesksMessagesOwners',Array('ID','Content','UserID'),Array('UNIQ','SortOn'=>'ID','IsDesc'=>TRUE,'Limits'=>Array(0,1),'Where'=>SPrintF('`EdeskID` = %u',$Ticket['ID'])));
	#-------------------------------------------------------------------------------
	switch(ValueOf($LastMessage)){
	case 'error':
		return ERROR | @Trigger_Error(500);
	case 'exception':
		return ERROR | @Trigger_Error(400);
	case 'array':
		break;
	default:
		return ERROR | @Trigger_Error(101);
	}
	#-------------------------------------------------------------------------------
	// а некоторые в конце запроса пишут спасибо... может стоит оценивать число строк,
	// если больше 3-4 - отрезать верхнюю половину сообщения и анализировать её
	$Words = Explode(" ",Mb_StrToLower(Str_Replace(Array("\r\n", "\r", "\n")," ",$LastMessage['Content'])));
	#-------------------------------------------------------------------------------
        $Count = IntVal(SizeOf($Words) / 2);
	#-------------------------------------------------------------------------------
	if($Count > 3){
		#-------------------------------------------------------------------------------
		// сообщение больше 7 слов, достаточно чтобы и поддороваться и задать вопрос и сказать спасибо
		$Message = ''; $i = 0;
	        #-------------------------------------------------------------------------------
        	foreach($Words as $Word){
			#-------------------------------------------------------------------------------
			$Message = SPrintF('%s %s',Trim($Message),Trim($Word));
			#-------------------------------------------------------------------------------
			#Debug(SPrintF('[comp/www/TicketRead]: Count = %s; Words = %s; $i = %s; $Message = %s',$Count,SizeOf($Words),$i,$Message));
			#-------------------------------------------------------------------------------
			$i++;
			#-------------------------------------------------------------------------------
			if($i > $Count || $i > 30)	// ну откуда там больше слов по делу, в начале сообщения-то?
				break;
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		Debug(SPrintF('[comp/www/TicketRead]: num words = %s; $Message = %s',SizeOf($Words),$Message));
		#-------------------------------------------------------------------------------
	}else{
		#-------------------------------------------------------------------------------
		// иначе просто сообщение целиком, в нижнем регистре
		$Message = Mb_StrToLower($LastMessage['Content']);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	Debug(SPrintF('[comp/www/TicketRead]: __USER[ID] = %s; Ticket[UserID] = %s; LastMessage[UserID] = %s;',$__USER['ID'],$Ticket['UserID'],$LastMessage['UserID']));
	#-------------------------------------------------------------------------------
	// если последнее сообщение от владельца тикета - тогда продолжаем
	if($LastMessage['UserID'] == $Ticket['UserID']){
		#-------------------------------------------------------------------------------
		// проверяем на здравствуйте, или на первое сообщение в тикете - тогда надо написать добрый (утро/день/вечер)
		if(StriStr($Message,'здравствуйте') !== FALSE || $MaxMessageID['NumMessages'] == 1){
			#-------------------------------------------------------------------------------
			if(Date("H") >= 04){$Hi = "Доброе утро";}
			if(Date("H") >= 10){$Hi = "Добрый день";}
			if(Date("H") >= 18){$Hi = "Добрый вечер";}
			if(Date("H") >= 23 || Date("H") < 04){$Hi = "Доброй ночи";}
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[comp/www/TicketRead]: обнарухено "здравствуйте" или единственное сообщение в тикете - автодобавление в окно ответа: $Hi = %s',$Hi));
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		// шаблоны приветствия
		$HiMessages = Array(
				'доброй ночи'	=> 'Здравствуйте',
				'доброе утро'	=> 'Здравствуйте',
				'добрый день'	=> 'Здравствуйте',
				'добрый вечер'	=> 'Здравствуйте',
				'доброго вечера'=> 'Здравствуйте',
				'спасибо'	=> 'Всегда рады Вам помочь'
				);
		#-------------------------------------------------------------------------------
		// перебираем ключи шаблонов, ищем в тексте совпадения
		foreach(Array_Keys($HiMessages) as $Key){
			#-------------------------------------------------------------------------------
			if(StrStr($Message,$Key) !== FALSE){
				#-------------------------------------------------------------------------------
				$Hi = $HiMessages[$Key];
				#-------------------------------------------------------------------------------
				Debug(SPrintF('[comp/www/TicketRead]: обнаружен шаблон Key = %s, добавляем Hi = %s;',$Key,$Hi));
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		// дописываем переносы и разделитель
		if(IsSet($Hi))
		$Hi = SPrintF("%s.\n--\n",$Hi);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Form/TextArea',$Array,IsSet($Hi)?$Hi:'');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Table[] = $Comp;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Disabled = Array();
#-------------------------------------------------------------------------------
if($__USER['ID'] == $Ticket['UserID'])
	$Disabled[] = 'hidden';
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Edesks/Panel',$Disabled);
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Tr = new Tag('TR',$Comp);
#-------------------------------------------------------------------------------
$Img = new Tag('IMG',Array('width'=>1,'height'=>20,'src'=>'SRC:{Images/SeparateLine.png}'));
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('TD',Array('id'=>'EdeskSeparator','align'=>'center','width'=>10),$Img));
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Buttons/Standard',Array(),'Предыдущий запрос','Previos.gif');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Query = Array("`StatusID` != 'Closed'","(SELECT `IsDepartment` FROM `Groups` WHERE `Groups`.`ID` = `Edesks`.`TargetGroupID`) = 'yes'",($Ticket['UserID'] != $__USER['ID']?SPrintF("(SELECT `IsDepartment` FROM `Groups` WHERE `Groups`.`ID` = (SELECT `GroupID` FROM `Users` WHERE `Users`.`ID` = `Edesks`.`UserID`)) = '%s'",$Ticket['IsDepartment']?'yes':'no'):SPrintF('`UserID` = %u',$Ticket['UserID'])));
#-------------------------------------------------------------------------------
$Where = $Query;
#-------------------------------------------------------------------------------
$Where[] = SPrintF('`UpdateDate` < %u',$Ticket['UpdateDate']);
#-------------------------------------------------------------------------------
$Previos = DB_Select('Edesks','ID',Array('UNIQ','Where'=>$Where,'SortOn'=>'UpdateDate','Limits'=>Array(0,1)));
#-------------------------------------------------------------------------------
switch(ValueOf($Previos)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	$Comp->AddAttribs(Array('disabled'=>'true'));
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
case 'array':
	#-------------------------------------------------------------------------------
	$Comp->AddAttribs(Array('onclick'=>SPrintF("ShowWindow('/TicketRead',{TicketID:%u});",$Previos['ID'])));
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('TD',Array('width'=>30,'id'=>'EdeskPrevious'),$Comp));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Buttons/Standard',Array(),'Следующий запрос','Next.gif');
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Where = $Query;
#-------------------------------------------------------------------------------
$Where[] = SPrintF('`UpdateDate` > %u',$Ticket['UpdateDate']);
#-------------------------------------------------------------------------------
$Next = DB_Select('Edesks','ID',Array('UNIQ','Where'=>$Where,'SortOn'=>'UpdateDate','Limits'=>Array(0,1)));
#-------------------------------------------------------------------------------
switch(ValueOf($Next)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	#-------------------------------------------------------------------------------
	$Comp->AddAttribs(Array('disabled'=>'true'));
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
case 'array':
	#-------------------------------------------------------------------------------
	$Comp->AddAttribs(Array('onclick'=>SPrintF("ShowWindow('/TicketRead',{TicketID:%u});",$Next['ID'])));
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('TD',Array('width'=>30,'id'=>'EdeskNext'),$Comp));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('TD'));
#-------------------------------------------------------------------------------
// кнопка отправить
$Submit = Comp_Load(
		'Form/Input',
		Array(
			'type'    => 'button',
			'onclick' => IsSet($GLOBALS['__USER']['IsEmulate'])?"javascript:ShowConfirm('Вы действительно хотите написать в тикет от чужого имени?','TicketAddMessage();');":'TicketAddMessage();',
			'value'   => 'Добавить',
			'style'=>'display: inline-block;'
			)
		);
if(Is_Error($Submit))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
// кнопка назад, тока для мобильных
$Back = Comp_Load(
		'Form/Input',
		Array(
			'id'		=> 'EdeskBackButton',
			'type'		=> 'button',
			'onclick'	=> SPrintF('document.location = "%s";',($GLOBALS['__USER']['IsAdmin'])?'/Administrator/Tickets':'/Tickets'),
			'value'		=> 'Назад',
			)
		);
if(Is_Error($Back))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$Div = new Tag('DIV',$Back,$Submit,new Tag('SPAN','и'));
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($__USER['ID'] == $Ticket['UserID']){ # is ordinar user
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('name'=>'Flags','type'=>'checkbox','value'=>'Closed'));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Div->AddChild(new Tag('NOBODY',$Comp,new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'Flags\'); return false;'),'закрыть запрос (проблема решена)')));
	#-------------------------------------------------------------------------------
}else{ # user -> support
	#-------------------------------------------------------------------------------
	$Config = Config();
	#-------------------------------------------------------------------------------
	$Positions = $Config['Edesks']['Flags'];
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Select',Array('name'=>'Flags'),$Positions,$Ticket['Flags']);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Div->AddChild(new Tag('NOBODY',$Comp));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Where = $Query;
#-------------------------------------------------------------------------------
$Where[] = SPrintF("`UpdateDate` > %u AND EDESKS_MESSAGES(`ID`,%u) > 0",$Ticket['UpdateDate'],$__USER['ID']);
#-------------------------------------------------------------------------------
$Next = DB_Select('Edesks',Array('ID','Theme'),Array('UNIQ','Where'=>$Where,'SortOn'=>'UpdateDate','Limits'=>Array(0,1)));
#-------------------------------------------------------------------------------
switch(ValueOf($Next)){
case 'error':
	return ERROR | @Trigger_Error(500);
case 'exception':
	# No more...
	break;
case 'array':
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Form/Input',Array('name'=>'IsNext','type'=>'checkbox','value'=>$Next['ID']));
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$Div->AddChild(new Tag('NOBODY',$Comp,new Tag('SPAN',Array('style'=>'cursor:pointer;','onclick'=>'ChangeCheckBox(\'IsNext\'); return false;'),'к следующему')));
	#-------------------------------------------------------------------------------
	break;
	#-------------------------------------------------------------------------------
default:
	return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
$Tr->AddChild(new Tag('TD',Array('align'=>'right'),$Div));
#-------------------------------------------------------------------------------
$Table[] = new Tag('TABLE',Array('width'=>'100%'),$Tr);
#-------------------------------------------------------------------------------
$Comp = Comp_Load('Tables/Standard',$Table,Array('width'=>'100%'));
if(Is_Error($Comp))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
if($IsInternal){
	#-------------------------------------------------------------------------------
	// это внутренний вызов компонента, iframe не нужен
	$Form->AddChild($Comp);
	#-------------------------------------------------------------------------------
}else{
	#-------------------------------------------------------------------------------
	$Iframe = new Tag('IFRAME',Array('id'=>'TicketReadMessages','src'=>SPrintF('/TicketMessages?TicketID=%u&Iframe=1',$Ticket['ID']),'width'=>'100%','style'=>SPrintF('height:%u;',Max(@$_COOKIE['hScreen']/2.5,240))),'Загрузка...');
	#-------------------------------------------------------------------------------
	$Form->AddChild(new Tag('TABLE',new Tag('TR',new Tag('TD',$Iframe)),new Tag('TR',new Tag('TD',$Comp))));
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
$DOM->AddChild('Into',$Form);
#-------------------------------------------------------------------------------
if(Is_Error($DOM->Build(FALSE)))
	return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return Array('Status'=>'Ok','DOM'=>$DOM->Object,'Form'=>$Form);
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>
