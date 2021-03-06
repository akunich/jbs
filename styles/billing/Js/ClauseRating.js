//------------------------------------------------------------------------------
/** @author Бреславский А.В. (Joonte Ltd.) */
//------------------------------------------------------------------------------
function ClauseSetRating($ClauseID,$Rating,$Controll){
  //----------------------------------------------------------------------------
  var $Form = document.forms['ClauseRatingForm'];
  //----------------------------------------------------------------------------
  var $HTTP = new HTTP();
  //----------------------------------------------------------------------------
  if(!$HTTP.Resource){
    //--------------------------------------------------------------------------
    alert('Не удалось создать HTTP соединение');
    //--------------------------------------------------------------------------
    return false;
  }
  //----------------------------------------------------------------------------
  $HTTP.onLoaded = function(){
    //--------------------------------------------------------------------------
    HideProgress();
  }
  //----------------------------------------------------------------------------
  $HTTP.onAnswer = function($Answer){
    //--------------------------------------------------------------------------
    switch($Answer.Status){
      case 'Error':
        ShowAlert($Answer.Error.String,'Warning');
      break;
      case 'Exception':
        ShowAlert(ExceptionsStack($Answer.Exception),'Warning');
      break;
      case 'Ok':
        //----------------------------------------------------------------------
        ShowAlert('Спасибо! Ваша оценка сохранена!');
        //----------------------------------------------------------------------
        document.getElementById('ClauseRating').innerHTML = $Answer.Rating;
        //----------------------------------------------------------------------
        $Form.Rating.disabled = true;
      break;
      default:
        alert('Не известный ответ');
    }
  };
  //----------------------------------------------------------------------------
  var $Args = {ClauseID:$ClauseID,Rating:$Rating,Controll:$Controll};
  //----------------------------------------------------------------------------
  if(!$HTTP.Send('/API/ClauseSetRating',$Args)){
    //--------------------------------------------------------------------------
    alert('Не удалось отправить запрос на сервер');
    //--------------------------------------------------------------------------
    return false;
  }
  //----------------------------------------------------------------------------
  ShowProgress('Сохранение оценки');
}