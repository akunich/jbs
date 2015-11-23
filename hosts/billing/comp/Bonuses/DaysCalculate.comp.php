<?php

#-------------------------------------------------------------------------------
/** @author Alex Keda, for www.host-food.ru */
/******************************************************************************/
/******************************************************************************/
$__args_list = Array('DaysFromBallance','Scheme','Order','UserID');
/******************************************************************************/
Eval(COMP_INIT);
/******************************************************************************/
/******************************************************************************/
# начальная сумма оплаты - грубое число дней на цену дня
$CostPay = $DaysFromBallance * $Scheme['CostDay'];
#-------------------------------------------------------------------------------
$DaysPay = $DaysFromBallance;
#-------------------------------------------------------------------------------
#Debug(SPrintF('[comp/Bonuses/DaysCalculate]: ContractBalance = %s;  DaysFromBallance = %s; $CostPay = %s',$Order['ContractBalance'],$DaysPay,$CostPay));
#-------------------------------------------------------------------------------
# счётчик итераций
$Iteration  = 0;
#-------------------------------------------------------------------------------
# начальный шаг прибавления дней
$Step = 50;
#-------------------------------------------------------------------------------
# перебираем дни, добавляя по одному, пока сумма оплаты не превысит балланс
while($CostPay < $Order['ContractBalance']){
	#-------------------------------------------------------------------------------
	# прибавляем 1 к счётчику итераций
	$Iteration++;
	#-------------------------------------------------------------------------------
	# прибавляем шаг к счётчику дней
	$DaysPay = $DaysPay + $Step;
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(Is_Error(DB_Transaction($TransactionID = UniqID('DaysCalculate'))))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Services/Politics',$Order['UserID'],$Order['GroupID'],$Order['ServiceID'],$Scheme['ID'],$DaysPay,'calculate DaysFromBallance');
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	$CostPay = 0.00;
	#-------------------------------------------------------------------------------
	$Comp = Comp_Load('Services/Bonuses',$DaysPay,$Order['ServiceID'],$Scheme['ID'],$UserID,$CostPay,$Scheme['CostDay'],FALSE);
	if(Is_Error($Comp))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	$CostPay = $Comp['CostPay'];
	#-------------------------------------------------------------------------------
	#-------------------------------------------------------------------------------
	if(Is_Error(DB_Roll($TransactionID)))
		return ERROR | @Trigger_Error(500);
	#-------------------------------------------------------------------------------
	#Debug(SPrintF('[comp/Bonuses/DaysCalculate]: Iteration = %s; ContractBalance = %s;  DaysFromBallance = %s; CostPay = %s',$Iteration,$Order['ContractBalance'],$DaysPay,$CostPay));
	#-------------------------------------------------------------------------------
	# если шаг больше 1 и цена оплаты больше чем балланс, вычитаем шаг и выставляем его = 1
	if($Step > 1 && $CostPay > $Order['ContractBalance']){
		#-------------------------------------------------------------------------------
		$DaysPay = $DaysPay - $Step;
		#-------------------------------------------------------------------------------
		$Step = 1;
		#-------------------------------------------------------------------------------
		$CostPay = 0;
		#-------------------------------------------------------------------------------
	}
	#-------------------------------------------------------------------------------
}
#-------------------------------------------------------------------------------
Debug(SPrintF('[comp/Bonuses/DaysCalculate]: подбор дней по сумме, число итераций = %s;',$Iteration));
#-------------------------------------------------------------------------------
# итоговое число дней, с учётом всех бонусов и скидок
$DaysFromBallance = $DaysPay - 1;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------
return $DaysFromBallance;
#-------------------------------------------------------------------------------
#-------------------------------------------------------------------------------

?>