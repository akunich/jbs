<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
$Args = Args();
#-------------------------------------------------------------------------------
$DomainOrderID = (integer) @$Args['DomainOrderID'];
#-------------------------------------------------------------------------------
if(Is_Error(System_Load('modules/Authorisation.mod','classes/DOM.class.php','classes/Registrator.class.php')))
  return ERROR | @Trigger_Error(500);
#-------------------------------------------------------------------------------
$DomainOrder = DB_Select('DomainsOrdersOwners',Array('ID','UserID','SchemeID','DomainName','StatusID','RegistratorID'),Array('UNIQ','ID'=>$DomainOrderID));
#-------------------------------------------------------------------------------
switch(ValueOf($DomainOrder)){
  case 'error':
    return ERROR | @Trigger_Error(500);
  case 'exception':
    return ERROR | @Trigger_Error(400);
  case 'array':
    #---------------------------------------------------------------------------
    $__USER = $GLOBALS['__USER'];
    #---------------------------------------------------------------------------
    $IsPermission = Permission_Check('DomainsOrdersRead',(integer)$__USER['ID'],(integer)$DomainOrder['UserID']);
    #---------------------------------------------------------------------------
    switch(ValueOf($IsPermission)){
      case 'error':
        return ERROR | @Trigger_Error(500);
      case 'exception':
        return ERROR | @Trigger_Error(400);
      case 'false':
        return ERROR | @Trigger_Error(700);
      case 'true':
        # проверяем - администратор или нет. если нет - то ограничиваем частоту
	if(!$__USER['IsAdmin']){
	  #-----------------------------------------------------------------------
          $CacheID = Md5($__FILE__ . $__USER['ID']);
          $Result = CacheManager::get($CacheID);
          if($Result && $Result > 5){
            # число запросов за последние 5 минут больше 5 
            return new gException('WAIT_5_MINUT_BEFORE_NEXT_ATTEMPT','К сожалению, запросы к интерфейсу вышестоящего регистратора нельзя делать слишком часто. Подождите 5 минут, до следующей попытки');
          }
          #-----------------------------------------------------------------------
        }
        #-----------------------------------------------------------------------
        #-----------------------------------------------------------------------
	if(!$__USER['ID']['IsAdmin'])
	  if(!In_Array($DomainOrder['StatusID'],Array('Active','Suspended','OnDelegating','ForProlong','OnProlong','ForNsChange')))
            return new gException('WE_NOT_OWN_THIS_ORDER','Можно смотреть информацию только по доменам зарегистрированным у нас');
        #-----------------------------------------------------------------------
        $__USER = $GLOBALS['__USER'];
        #-----------------------------------------------------------------------
        $DomainScheme = DB_Select('DomainsSchemes','Name',Array('UNIQ','ID'=>$DomainOrder['SchemeID']));
        #-----------------------------------------------------------------------
        switch(ValueOf($DomainScheme)){
          case 'error':
            return ERROR | @Trigger_Error(500);
          case 'exception':
            return ERROR | @Trigger_Error(400);
          case 'array':
            #-------------------------------------------------------------------
            $DOM = new DOM();
            #-------------------------------------------------------------------
            $Links = &Links();
            # Коллекция ссылок
            $Links['DOM'] = &$DOM;
            #-------------------------------------------------------------------
            if(Is_Error($DOM->Load('Window')))
              return ERROR | @Trigger_Error(500);
            #-------------------------------------------------------------------
            $DOM->AddText('Title','Информация о домене из интерфейса регистратора');
            #-------------------------------------------------------------------
            $DOM->AddChild('Head',new Tag('SCRIPT',Array('type'=>'text/javascript','src'=>'SRC:{Js/Pages/DomainOrderChangeContactData.js}')));
            #-------------------------------------------------------------------
            #-------------------------------------------------------------------
            $Domain = SPrintF('%s.%s',$DomainOrder['DomainName'],$DomainScheme['Name']);
            #-------------------------------------------------------------------
	    # получем контактные данные домена
            $Registrator = new Registrator();
            #-------------------------------------------------------------------
            $IsSelected = $Registrator->Select((integer)$DomainOrder['RegistratorID']);
            #---------------------------------------------------------------------------
            switch(ValueOf($IsSelected)){
            case 'error':
              return ERROR | @Trigger_Error(500);
            case 'exception':
              return ERROR | @Trigger_Error(400);
            case 'true':
              break;
            default:
              return ERROR | @Trigger_Error(101);
            }
            #-------------------------------------------------------------------
	    $ContactDetail = $Registrator->GetContactDetail($Domain);
            switch(ValueOf($ContactDetail)){
            case 'error':
              return ERROR | @Trigger_Error(500);
            case 'exception':
              return new gException('CANNOT_GET_CURRENT_CONTACT_DATA','Не удалось получить текущие контактные данные от регистратора');
            case 'array':
              break;
            default:
              return ERROR | @Trigger_Error(101);
            }
            #-------------------------------------------------------------------
            #-------------------------------------------------------------------
            $Table = Array();
            #-------------------------------------------------------------------
            #-------------------------------------------------------------------
            $Table[] = Array('Доменное имя',$Domain);
            #-------------------------------------------------------------------
            $Messages = Messages();
            #-------------------------------------------------------------------
            $Table[] = 'Информация полученная из интерфейса регистратора';
            #-------------------------------------------------------------------
            #-------------------------------------------------------------------
            foreach(Array_Keys($ContactDetail['FullInfo']) as $Key){
              #------------------------------------------------------------------------
              if(StrLen(Trim($ContactDetail['FullInfo'][$Key])) > 0){
                $FullInfoValue = Trim($ContactDetail['FullInfo'][$Key]);
              }else{
                $FullInfoValue = '-';
              }
              #------------------------------------------------------------------------
              $Table[] = Array($Key,$FullInfoValue);
              #------------------------------------------------------------------------
            }
            #------------------------------------------------------------------------
            #------------------------------------------------------------------------
            $Comp = Comp_Load('Tables/Standard',$Table);
            if(Is_Error($Comp))
              return ERROR | @Trigger_Error(500);
            #-------------------------------------------------------------------
            #-------------------------------------------------------------------
            $DOM->AddChild('Into',$Comp);
            #-------------------------------------------------------------------
            if(Is_Error($DOM->Build(FALSE)))
              return ERROR | @Trigger_Error(500);
            #-------------------------------------------------------------------
            #-------------------------------------------------------------------
            if(IsSet($CacheID)){
	      $Result = CacheManager::get($CacheID);
	      #-------------------------------------------------------------------
	      $Count = ($Result)?$Result + 1:1;
	      Debug(SPrintF('[comp/www/DomainOrderRegistratorInfo]: Count = %s',$Count));
	      #-------------------------------------------------------------------
              CacheManager::add($CacheID,$Count,300);
	    }
            #-------------------------------------------------------------------
            #-------------------------------------------------------------------
            return Array('Status'=>'Ok','DOM'=>$DOM->Object);
          default:
            return ERROR | @Trigger_Error(101);
        }
      default:
        return ERROR | @Trigger_Error(101);
    }
  default:
    return ERROR | @Trigger_Error(101);
}
#-------------------------------------------------------------------------------

?>
