<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа purchase_ClosedDeals
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class purchase_transaction_CloseDeal
{
    /**
     * 
     * @var purchase_ClosedDeals
     */
    public $class;
    
    
    /**
     * Извлечен краткия баланс
     */
    private $shortBalance;
    
    
    /**
     * Дата
     */
    private $date;
    
    
    /**
     * Кеш на извънредния разход
     */
    private $bl6912 = 0;
    
    
    /**
     * Кеш на извънредния приход
     */
    private $bl7912 = 0;
    
    
    /**
     * Финализиране на транзакцията, изпълнява се ако всичко е ок
     * 
     * @param int $id
     * @return stdClass
     * @see acc_TransactionSourceIntf::getTransaction
     */
    public function finalizeTransaction($id)
    {
        $rec = $this->class->fetchRec($id);
        $rec->state = 'active';
        
    	if ($id = $this->class->save($rec)) {
            $this->class->invoke('AfterActivation', array($rec));
        }
        
        return $id;
    }
    
    
    /**
     *  Имплементиране на интерфейсен метод (@see acc_TransactionSourceIntf)
     *  Създава транзакция която се записва в Журнала, при контирането
     */
    public function getTransaction($id)
    {
    	// Извличаме мастър-записа
    	expect($rec = $this->class->fetchRec($id));
    	$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
    	$docRec = cls::get($rec->docClassId)->fetch($rec->docId);
    	
    	$dealItem = acc_Items::fetch("#classId = {$firstDoc->instance->getClassId()} AND #objectId = '$firstDoc->that' ");
    	
    	// Създаване на обекта за транзакция
    	$result = (object)array(
    			'reason'      => $rec->notes,
    			'valior'      => $this->class->getValiorDate($rec),
    			'totalAmount' => 0,
    			'entries'     => array()
    	);
    	
    	if($rec->closeWith){
    		if($dealItem){
    			$closeDealItem = acc_Items::fetchItem('purchase_Purchases', $rec->closeWith);
    			$closeEntries = $this->class->getTransferEntries($dealItem, $result->totalAmount, $closeDealItem, $rec);
    			$result->entries = array_merge($result->entries, $closeEntries);
    		}
    	} else {
    		$this->shortBalance = new acc_ActiveShortBalance(array('itemsAll' => $dealItem->id));
    		$this->blAmount = $this->shortBalance->getAmount('401');
    		
    		$dealInfo = $this->class->getDealInfo($rec->threadId);
    		 
    		// Кеширане на перото на текущата година
    		$date = ($dealInfo->get('invoicedValior')) ? $dealInfo->get('invoicedValior') : $dealInfo->get('agreedValior');
    		$this->date = acc_Periods::forceYearItem($date);
    		
    		// Създаване на запис за прехвърляне на всеки аванс
    		$entry2 = $this->trasnferDownpayments($dealInfo, $docRec, $downpaymentAmounts, $firstDoc);
    		$result->totalAmount += $downpaymentAmounts;
    		 
    		// Ако тотала не е нула добавяме ентритата
    		if(count($entry2)){
    			$result->entries[] = $entry2;
    		}
    		
    		$entry3 = $this->transferVatNotCharged($dealInfo, $docRec, $result->totalAmount, $firstDoc);
    		 
    		// Ако тотала не е нула добавяме ентритата
    		if(count($entry3)){
    			$result->entries[] = $entry3;
    		}
    		
    		// Ако има сума различна от нула значи има приход/разход
    		$amount = $this->blAmount + $downpaymentAmounts;
    		 
    		$entry = $this->getCloseEntry($amount, $result->totalAmount, $docRec, $firstDoc);
    		
    		if(count($entry)){
    			$result->entries[] = $entry;
    		}
    		
    		$entry4 = $this->getCompensateEntry($amount, $result->totalAmount, $docRec, $firstDoc);
    		
    		if(count($entry4)){
    			$result->entries[] = $entry4;
    		}
    		
    		$entry5 = $this->transferIncomeToYear($amount, $result->totalAmount, $docRec, $firstDoc);
    		
    		if(count($entry5)){
    			$result->entries[] = $entry5;
    		}
    	}
    	 
    	// Връщане на резултата
    	return $result;
    }


    /**
     * Отнасяне на извънредния приход ИЛИ разход от с/ки 7912 или 6912 по с/ка 123 - Печалби и загуби от текущата година
     * 
     * 		Отнасяме натрупаните отписани задължения (извънредния приход) по сделката като печалба по сметка 123 - Печалби и загуби от текущата година
     * 
     * 		Dt: 7912 - Отписани задължения по Покупки
     * 		Ct: 123 - Печалби и загуби от текущата година
     * 
     * 		ИЛИ
     * 
     * 		Отнасяме натрупаните извънредните разходи по сделката като загуба по сметка 123 - Печалби и загуби от текущата година
     * 
     * 		Dt: 123 - Печалби и загуби от текущата година
     * 		Ct: 6912 - Извънредни разходи по Покупки
     */
    private function transferIncomeToYear($amount, &$totalAmount, $docRec, $firstDoc)
    {
    	$entry = array();
    	
    	if($this->bl6912 != 0){
    		$entry = array('amount' => $this->bl6912,
    				'debit' => array('123', $this->date->year),
    				'credit' => array('6912',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'reason' => 'Извънредни разходи по покупка',
    		);
    		
    		$totalAmount += $this->bl6912;
    	}
    	
    	if($this->bl7912 != 0){
    		$entry = array('amount' => $this->bl7912,
    				'debit' => array('7912', 
    							array($docRec->contragentClassId, $docRec->contragentId),
    							array($firstDoc->className, $firstDoc->that)),
    				'credit' => array('123', $this->date->year),
    				'reason' => 'Извънредни приходи по покупка'
    		);
    		
    		$totalAmount += $this->bl7912;
    	}
    	
    	return $entry;
    }
    
    
    /**
     * САМО ако за сделката има обороти и салда И по двете с/ки: 7912 и 6912 за извънредни приходи / разходи по Покупки, 
     * съставяме статията:
     * 
     * 		Dt: 7912 - Отписани задължения по Покупки
     * 		Ct: 6912 - Извънредни разходи по Покупки
     * 
     * 		със сума - по-малката измежду: кредитното салдо на с/ка 7912, и дебитното салдо на с/ка 6912 по сделката
     */
    private function getCompensateEntry($amount, &$totalAmount, $docRec, $firstDoc)
    {
    	$entry = array();
    	
    	if(empty($this->bl6912) || empty($this->bl7912)) return $entry;
    	
    	$minAmount = min(array($this->bl6912, $this->bl7912));
    	
    	$entry = array('amount' => $minAmount,
    			'debit' => array('7912',
    					array($docRec->contragentClassId, $docRec->contragentId),
    					array($firstDoc->className, $firstDoc->that)),
    			'credit'  => array('6912',
    					array($docRec->contragentClassId, $docRec->contragentId),
    					array($firstDoc->className, $firstDoc->that)),
    			'reason' => 'Приспадане на извънредни приходи/разходи по покупка');
    	
    	$this->bl6912 -= $minAmount;
    	$this->bl7912 -= $minAmount;
    	$totalAmount += $minAmount;
    	
    	return $entry;
    }
    
    
    /**
     * Ако в текущата сделка салдото по сметка 402 е различно от "0"
     *
     * 		Намаляваме задължението си към доставчика със сумата на платения му аванс, респективно - намаляваме
     * 		направените към Доставчика плащания с отрицателната сума на евентуално върнат ни аванс, без да сме
     * 		платили такъв (т.к. системата допуска създаването на revert операция без наличието на права такава преди това),
     * 		със сумата 1:1 (включително и ако е отрицателна) на дебитното салдо на с/ка 402
     *
     * 			Dt: 401 Задължения към доставчици
     * 			Ct: 402 Вземания от доставчици по аванси
     */
    private function trasnferDownpayments(bgerp_iface_DealAggregator $dealInfo, $docRec, &$downpaymentAmounts, $firstDoc)
    {
    	$entryArr = array();
    
    	$docRec = $firstDoc->rec();
    
    	$jRecs = acc_Journal::getEntries(array($firstDoc->className, $firstDoc->that));
    
    	// Колко е направеното авансовото плащане
    	$downpaymentAmount = acc_Balances::getBlAmounts($jRecs, '402')->amount;
    	if($downpaymentAmount == 0) return $entryArr;
    	 
    	// Валутата на плащането е тази на сделката
    	$currencyId = currency_Currencies::getIdByCode($dealInfo->get('currency'));
    	$amount = $downpaymentAmount / $dealInfo->get('rate');
    	 
    	$entry = array();
    	$entry['amount'] = $downpaymentAmount;
    	$entry['debit'] = array('401',
    			array($docRec->contragentClassId, $docRec->contragentId),
    			array($firstDoc->className, $firstDoc->that),
    			array('currency_Currencies', $currencyId),
    			'quantity' => $amount);
    	 
    	$entry['credit'] = array('402',
    			array($docRec->contragentClassId, $docRec->contragentId),
    			array($firstDoc->className, $firstDoc->that),
    			array('currency_Currencies', $currencyId),
    			'quantity' => $amount);
    	$entry['reason'] = 'Приспадане на авансово плащане';
    	 
    	$downpaymentAmounts += $entry['amount'];
    	 
    	return $entry;
    }


    /**
     * Прехвърля не неначисленото ДДС
     * Ако в текущата сделка салдото по сметка 4530 е различно от "0":
     *
     * Сметка 4530 има Кредитно (Ct) салдо;
     *
     * 		Увеличаваме задълженията си към Доставчика със сумата на надфактурираното ДДС, със сумата на кредитното салдо на с/ка 4530
     *
     * 			Dt: 4530 - ДДС за начисляване
     * 			Ct: 401 - Задължения към доставчици
     *
     * Сметка 4530 има Дебитно (Dt) салдо;
     *
     * 		Тъй като отделеното за начисляване и нефактурирано (неначислено) ДДС не може да бъде възстановено, както се е
     * 		очаквало при отделянето му за начисляване по с/ка 4530, го отнасяме като извънреден разход по сделката,
     * 		със сумата на дебитното салдо (отделеното, но неначислено ДДС) на с/ка 4530
     *
     * 			Dt: 6912 - Извънредни разходи по Покупки
     * 			Ct: 4530 - ДДС за начисляване
     */
    private function transferVatNotCharged($dealInfo, $docRec, &$total, $firstDoc)
    {
    	$entries = array();
    	 
    	$jRecs = acc_Journal::getEntries(array($firstDoc->className, $firstDoc->that));
    	$blAmount = acc_Balances::getBlAmounts($jRecs, '4530')->amount;
    	 
    	$total += abs($blAmount);
    	 
    	if($blAmount == 0) return $entries;
    	 
    	// Сметка 4530 има Кредитно (Ct) салдо
    	if($blAmount < 0){
    		$entries = array('amount' => abs($blAmount),
    				'debit'  => array('4530', array($firstDoc->className, $firstDoc->that)),
    				'credit' => array('401',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						array('currency_Currencies', currency_Currencies::getIdByCode($dealInfo->get('currency'))),
    						'quantity' => abs($blAmount)),
    				'reason' => 'Доначисляване на ДДС');
    		$this->blAmount -= abs($blAmount);
    	} elseif($blAmount > 0){
    
    		// Сметка 4530 има Дебитно (Dt) салдо
    		$entries1 = array('amount' => $blAmount,
    				'debit' => array('6912',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'credit'  => array('4530',
    						array($firstDoc->className, $firstDoc->that),
    						'quantity' => $blAmount),
    				'reason' => 'Отделено, но невъзстановимо ДДС');
    		
    		$this->bl6912 += $blAmount;
    		$entries = $entries1;
    
    	}
    	 
    	// Връщаме ентритата
    	return $entries;
    }
    
    
    /**
     * Отчитане на извънредните приходи/разходи от сделката
     * Ако в текущата сделка салдото по сметка 401 е различно от "0"
     *
     * Сметка 401 има Кредитно (Ct) салдо
     *
     * 		Намаляваме задълженията си към Доставчика с неиздължената сума с обратна (revers) операция,
     *		със сумата на кредитното салдо на с/ка 401
     *
     * 			Dt: 401 - Задължения към доставчици
     * 			Ct: 7912 - Отписани задължения по Покупки
     *
     * Сметка 401 има Дебитно (Dt) салдо
     *
     * 		Намаляваме плащанията към Доставчика с надплатената сума с обратна (revers) операция, със сумата
     * 		на дебитното салдо на с/ка 401
     *
     * 			Dt: 6912 - Извънредни разходи по Покупки
     * 			Ct: 401 - Задължения към доставчици
     *
     */
    private function getCloseEntry($amount, &$totalAmount, $docRec, $firstDoc)
    {
    	$entry = array();
    	
    	if(round($amount, 2) == 0) return $entry;
    	 
    	// Сметка 401 има Дебитно (Dt) салдо
    	if($amount > 0){
    		$entry1 = array(
    				'amount' => $amount,
    				'credit' => array('401',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
    						'quantity' => $amount / $docRec->currencyRate),
    				'debit'  => array('6912',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'reason' => 'Извънредни разходи - надплатени'
    		);
    		
    		$this->bl6912 += $amount;
    		$totalAmount +=  $amount;
    		
    		// Сметка 401 има Кредитно (Ct) салдо
    	} elseif($amount < 0){
    		$entry1 = array(
    				'amount' => -1 * $amount,
    				'credit'  => array('7912',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that)),
    				'debit' => array('401',
    						array($docRec->contragentClassId, $docRec->contragentId),
    						array($firstDoc->className, $firstDoc->that),
    						array('currency_Currencies', currency_Currencies::getIdByCode($docRec->currencyId)),
    						'quantity' => -1 * $amount / $docRec->currencyRate),
    				'reason' => 'Извънредни приходи - недоплатени');
    		
    		$this->bl7912 += abs($amount);
    		$totalAmount += -1 * $amount;
    	}
    	 
    	// Връщане на записа
    	return $entry1;
    }
}