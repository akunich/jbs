//------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
//------------------------------------------------------------------------------

function Dispatch(){
	//------------------------------------------------------------------------------
	var $Form = document.forms['DispatchForm'];
	//------------------------------------------------------------------------------
	$HTTP = new HTTP();
	//------------------------------------------------------------------------------
	if(!$HTTP.Resource){
		//------------------------------------------------------------------------------
		alert('Не удалось создать HTTP соединение');
		//------------------------------------------------------------------------------
		return false;
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	$HTTP.onLoaded = function(){
		//------------------------------------------------------------------------------
		HideProgress();
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	$HTTP.onAnswer = function($Answer){
		//------------------------------------------------------------------------------
		switch($Answer.Status){
		case 'Error':
			//------------------------------------------------------------------------------
			ShowAlert($Answer.Error.String,'Warning');
			//------------------------------------------------------------------------------
			break;
			//------------------------------------------------------------------------------
		case 'Exception':
			//------------------------------------------------------------------------------
			ShowAlert(ExceptionsStack($Answer.Exception),'Warning');
			//------------------------------------------------------------------------------
			break;
			//------------------------------------------------------------------------------
		case 'Ok':
			//------------------------------------------------------------------------------
			var $Span = document.createElement('SPAN');
			//------------------------------------------------------------------------------
			var $innerHTML = SPrintF('Сообщения поставлены в очередь рассылки.<BR />Пользователей по фильтру: %u, из них:<BR />',$Answer.Users);
			//------------------------------------------------------------------------------
			for ($Key in $Answer.Messages){
				//------------------------------------------------------------------------------
				$innerHTML += $Key + ' =&gt; ' + $Answer.Messages[$Key] + '<BR />';
				//------------------------------------------------------------------------------
			}
			//------------------------------------------------------------------------------
			$Span.innerHTML = $innerHTML;
			//------------------------------------------------------------------------------
			ShowAlert($Span);
			//------------------------------------------------------------------------------
			break;
			//------------------------------------------------------------------------------
		default:
			alert('Не известный ответ');
		}
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
	var $Args = FormGet($Form);
	//------------------------------------------------------------------------------
	if(!$HTTP.Send('/Administrator/API/Dispatch',$Args)){
		//------------------------------------------------------------------------------
		alert('Не удалось отправить запрос на сервер');
		//------------------------------------------------------------------------------
		return false;
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
	ShowProgress('Установка сообщений в очередь для рассылки');
	//------------------------------------------------------------------------------
	//------------------------------------------------------------------------------
}

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
function ShowHTMLform(){
	//------------------------------------------------------------------------------
	var $Form = document.forms['DispatchForm'];
	//------------------------------------------------------------------------------
	// форма для текстового ввода
	$('#MessageText').slideToggle();
	// форма для заголовков
	$('#MessageHeadersPrompt').slideToggle();
	$('#Headers').slideToggle();
	// форма для текста в HTML
	$('#HTML').slideToggle();
	//------------------------------------------------------------------------------
	// в зависимости от того установлен чекбокс или нет - меняем текст заголовка
	//------------------------------------------------------------------------------
	if($Form.IsHTML.checked){
		//------------------------------------------------------------------------------
		document.getElementById("MessageTextPrompt").innerHTML="Сообщение в HTML формате";
		//------------------------------------------------------------------------------
	}else{
		//------------------------------------------------------------------------------
		document.getElementById("MessageTextPrompt").innerHTML="Сообщение в текстовом формате";
		//------------------------------------------------------------------------------
	}
	//------------------------------------------------------------------------------
}


