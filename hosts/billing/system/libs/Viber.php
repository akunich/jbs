<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/

class Viber
{
	#-------------------------------------------------------------------------------
	// параметры
	public $Address	= 'https://chatapi.viber.com/pa/';
	public $Host	= 'chatapi.viber.com';
	public $Token	= '00-000-00';
	#-------------------------------------------------------------------------------
	public function __construct($Token) {
		$this->Token = $Token;
	}
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------

	// послать юзеру текст
	public function MessageSend
	(
		$RecipientID,		// получатель
		$Text			// Текст.
	)
	{
		#-------------------------------------------------------------------------------
		$Data['receiver']	= $RecipientID;
		$Data['text']		= $Text;
		$Data['type']		= 'text';
		#-------------------------------------------------------------------------------
		return $this->API('send_message', $Data);
		#-------------------------------------------------------------------------------
	}

	// послать юзеру файл
	public function FileSend
	(
		$RecipientID,		// получатель
		$Attachments = Array()	// массив с файлами
	)
	{
		foreach ($Attachments as $Attachment){
			#-------------------------------------------------------------------------------
			Debug(SPrintF('[system/libs/Viber]: обработка вложения (%s), размер (%s), тип (%s)',$Attachment['Name'],$Attachment['Size'],$Attachment['Mime']));
			#-------------------------------------------------------------------------------
			// а файлы в него отправить нельзя. отправляется ссылка на скачиванеи файла.
			// поэтому сам файл вываливаем в файловую систему, в hosts/__HOST__/tmp/public
			$Tmp = System_Element('tmp');
			if(Is_Error($Tmp))
				return ERROR | @Trigger_Error(500);
			#-------------------------------------------------------------------------------
			$Public = SPrintF('%s/public',$Tmp);
			#-------------------------------------------------------------------------------
			if(File_Exists($Public)){
				#-------------------------------------------------------------------------------
				if(!File_Exists(SPrintF('%s/Viber',$Public)))
					if(!MkDir(SPrintF('%s/Viber',$Public), 0700, true))
						return new gException('CANNOT_CREATE_DIRECTORY','Не удалось создать директорию для сохранения файла');
				#-------------------------------------------------------------------------------
				// для картинок jpeg, для остальных - .bin, чтобы точно ничего не выполнилось, ну его нах...
				$Extension = ($Attachment['Mime'] == 'image/jpeg')?'jpeg':'bin';
				$Url       = SPrintF('Viber/%s.%s.%s',Date('Y-m-d'),Md5($Attachment['Data']),$Extension);
				$Path      = SPrintF('%s/%s',$Public,$Url);
				#-------------------------------------------------------------------------------
				$IsWrite = IO_Write($Path,Base64_Decode($Attachment['Data']),TRUE);
				if(Is_Error($IsWrite))
					return ERROR | @Trigger_Error(500);
				#-------------------------------------------------------------------------------
				$Data['receiver']	= $RecipientID;
				#-------------------------------------------------------------------------------
				// если это картинка, то отправляем её как картинку (тока жипеги поддерживаются)
				$Mime = Explode('/',$Attachment['Mime']);
				if($Mime[0] == 'image'){
					#-------------------------------------------------------------------------------
					if($Attachment['Mime'] != 'image/jpeg'){
						#-------------------------------------------------------------------------------
						Debug(SPrintF('[system/libs/Viber]: необходима конвертация изображения в jpeg'));
						#-------------------------------------------------------------------------------
						$Command = SPrintF('convert %s %s',$Path,$Jpeg = SPrintF('%s.jpeg',$Path));
						#-------------------------------------------------------------------------------
						Debug(SPrintF('[system/libs/Viber]: $Command = %s',$Command));
						#-------------------------------------------------------------------------------
						$ImageMagick = @Proc_Open($Command,Array(Array('pipe','r'),Array('pipe','w'),Array('file',SPrintF('%s/logs/ImageMagic.log',$Tmp),'a')),$Pipes);
						if(!Is_Resource($ImageMagick))
							return ERROR | @Trigger_Error(500);
						#-------------------------------------------------------------------------------
						Proc_Close($ImageMagick);
						#-------------------------------------------------------------------------------
						UnLink($Path);
						#-------------------------------------------------------------------------------
						$Url = SPrintF('%s.jpeg',$Url);
						#-------------------------------------------------------------------------------
					}
					#-------------------------------------------------------------------------------
					$Data['type']	= 'picture';
					$Data['text']	= '';
					$Data['media']	= SPrintF('https://%s/public/%s',HOST_ID,$Url);
					#-------------------------------------------------------------------------------
				}else{
					#-------------------------------------------------------------------------------
					$Data['type']		= 'file';
					$Data['media']		= SPrintF('https://%s/public/%s',HOST_ID,$Url);
					$Data['size']		= $Attachment['Size'];
					$Data['file_name']	= $Attachment['Name'];
					#-------------------------------------------------------------------------------
				}
				#-------------------------------------------------------------------------------
				$Result = $this->API('send_message',$Data);
				#-------------------------------------------------------------------------------
			}else{
				#-------------------------------------------------------------------------------
				Debug(SPrintF('[system/libs/Viber.ph]: директория (%s) отсутствует, невозможно отправить файл',$Public));
				#-------------------------------------------------------------------------------
			}
			#-------------------------------------------------------------------------------
		}
		#-------------------------------------------------------------------------------
		return $Result;
		#-------------------------------------------------------------------------------
	}

	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// вебхук
	public function SetWebHook()
	{
		#-------------------------------------------------------------------------------
		$Data['url']   = SPrintF('https://%s/API/Viber',HOST_ID);
		#-------------------------------------------------------------------------------
		return $this->API('set_webhook', $Data);
		#-------------------------------------------------------------------------------
	}

	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// инфа
	public function get_account_info()
	{
		return $this->API('get_account_info'/*, $Data*/);
	}

	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// АПИ
	private function API($Method, $Data = Array()){
		#-------------------------------------------------------------------------------
		$HTTP = Array(
				'Address'	=> $this->Host,
				'Port'		=> 443,
				'Host'		=> $this->Host,
				'Protocol'	=> 'ssl',
				);
		#-------------------------------------------------------------------------------
		$Data['auth_token']	= $this->Token;
		#-------------------------------------------------------------------------------
		$Url = SPrintF('/pa/%s',$Method);
		#-------------------------------------------------------------------------------
		$Result = HTTP_Send($Url,$HTTP,Array(),Json_Encode($Data));
		if(Is_Error($Result))
			return ERROR | @Trigger_Error('[API]: не удалось выполнить запрос к серверу');
		#-------------------------------------------------------------------------------
		$Result = Trim($Result['Body']);
		#-------------------------------------------------------------------------------
		$Result = Json_Decode($Result,TRUE);
		#-------------------------------------------------------------------------------
		// вообще, надо разобраться на этом этапе с результатом, и вернуть уже итог, и в случае ошибки - параметры
		return $Result;
		#-------------------------------------------------------------------------------
		#-------------------------------------------------------------------------------
	}

	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	// проверяем подпись
	public function CheckSign($Body,$Sign){
		#-------------------------------------------------------------------------------
		$Hash = Hash_Hmac('sha256',$Body,$this->Token);
		#-------------------------------------------------------------------------------
		return Hash_Equals($Sign,$Hash);
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}

?>
