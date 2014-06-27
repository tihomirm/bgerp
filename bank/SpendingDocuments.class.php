<?php 


/**
 * Разходен банков документ
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_SpendingDocuments extends core_Master
{
    
    
	/**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'bank_CostDocument';
    
    
    /**
     * Какви интерфейси поддържа този мениджър
     */
    var $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=bank_transaction_SpendingDocument, bgerp_DealIntf, email_DocumentIntf, doc_ContragentDataIntf';
   
    
    /**
     * Заглавие на мениджъра
     */
    var $title = "Разходни банкови документи";
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    var $loadList = 'plg_RowTools, bank_Wrapper, bank_DocumentWrapper, plg_Printing, acc_plg_Contable,
     	plg_Sorting,doc_DocumentPlg, acc_plg_DocumentSummary,
     	plg_Search,doc_plg_MultiPrint, bgerp_plg_Blank, doc_EmailCreatePlg';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "tools=Пулт, number=Номер, valior, reason, folderId, currencyId, amount, state, createdOn, createdBy";
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'reason';
    
    
    /**
     * Заглавие на единичен документ
     */
    var $singleTitle = 'Разходен банков документ';
    
    
    /**
     * Икона на единичния изглед
     */
    var $singleIcon = 'img/16/bank_rem.png';
    
    
    /**
     * Абревиатура
     */
    var $abbr = "Rbd";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'bank, ceo';
    
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'bank, ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'bank, ceo';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'bank, ceo';
    
    
    /**
     * Кой може да го контира?
     */
    var $canConto = 'bank, ceo';
    
    
    /**
     * Кой може да сторнира
     */
    var $canRevert = 'bank, ceo';
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'bank/tpl/SingleCostDocument.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    var $searchFields = 'valior, reason, contragentName';
    
    
    /**
     * Групиране на документите
     */
    var $newBtnGroup = "4.4|Финанси";
    
    
    /**
     * Основна сч. сметка
     */
    public static $baseAccountSysId = '503';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	$this->FLD('operationSysId', 'varchar', 'caption=Операция,width=100%,mandatory');
    	$this->FLD('valior', 'date(format=d.m.Y)', 'caption=Вальор,width=6em,mandatory');
    	$this->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,mandatory,width=6em,summary=amount');
    	$this->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута,width=6em');
    	$this->FLD('rate', 'double(smartRound)', 'caption=Курс,width=6em');
    	$this->FLD('reason', 'richtext(rows=2)', 'caption=Основание,width=100%,mandatory');
    	$this->FLD('ownAccount', 'key(mvc=bank_OwnAccounts,select=bankAccountId)', 'caption=От->Банк. сметка,mandatory,width=16em');
    	$this->FLD('contragentName', 'varchar(255)', 'caption=Към->Контрагент,mandatory,width=16em');
    	$this->FLD('contragentIban', 'iban_Type(64)', 'caption=Към->Сметка,width=16em'); 
    	$this->FLD('contragentId', 'int', 'input=hidden,notNull');
    	$this->FLD('contragentClassId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull');
    	$this->FLD('debitAccId', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)','caption=debit,width=300px,input=none');
        $this->FLD('creditAccId', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)','caption=Кредит,width=300px,input=none');
    	$this->FLD('state', 
            'enum(draft=Чернова, active=Активиран, rejected=Сторнирана, closed=Контиран)', 
            'caption=Статус, input=none'
        );
    	$this->FLD('isReverse', 'enum(no,yes)', 'input=none,notNull,value=no');
    }
    
    
    /**
     * След запис на документа
     */
	public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if($rec->contragentIban){
    		
    		// Ако няма такава банкова сметка, тя автоматично се записва
    		bank_Accounts::add($rec->contragentIban, $rec->currencyId, $rec->contragentClassId, $rec->contragentId);
    	}
    }
    
    
	/**
	 *  Подготовка на филтър формата
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{
		// Добавяме към формата за търсене търсене по Каса
		bank_OwnAccounts::prepareBankFilter($data, array('ownAccount'));
	}
	
    
    /**
     * Подготовка на формата за добавяне
     */
    static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
    	$form = &$data->form;
    	$today = dt::verbal2mysql();
    	
    	$contragentId = doc_Folders::fetchCoverId($form->rec->folderId);
        $contragentClassId = doc_Folders::fetchField($form->rec->folderId, 'coverClass');
    	$form->setDefault('contragentId', $contragentId);
        $form->setDefault('contragentClassId', $contragentClassId);
        
        expect($origin = $mvc->getOrigin($form->rec));
        
    	if(empty($form->rec->id)) {
    		 $mvc->setDefaultsFromOrigin($origin, $form, $options);
    	}
    	
    	$form->setOptions('ownAccount', bank_OwnAccounts::getOwnAccounts());
    	$form->setSuggestions('contragentIban', bank_Accounts::getContragentIbans($form->rec->contragentId, $form->rec->contragentClassId));
        $form->setDefault('valior', $today);
        $form->setDefault('currencyId', acc_Periods::getBaseCurrencyId($today));
    	$form->setDefault('ownAccount', bank_OwnAccounts::getCurrent());
    	$form->setOptions('operationSysId', $options);
    	
    	if(isset($form->defaultOperation) && array_key_exists($form->defaultOperation, $options)){
    		$form->rec->operationSysId = $form->defaultOperation;
    	}
        
    	$form->setReadOnly('contragentName', cls::get($contragentClassId)->getTitleById($contragentId));
        $form->addAttr('currencyId', array('onchange' => "document.forms['{$data->form->formAttr['id']}'].elements['rate'].value ='';"));
    }
    
	
	/**
     * Помощна ф-я връщаща дефолт операцията за документа
     */
    private function getDefaultOperation(bgerp_iface_DealResponse $dealInfo)
    {
    	$paid = $dealInfo->paid;
    	$agreed = $dealInfo->agreed;
    	
    	// Ако е продажба пораждащия документ
    	if($dealInfo->dealType == bgerp_iface_DealResponse::TYPE_SALE){
    		if(isset($agreed->downpayment)){
    			$defaultOperation = (round($paid->downpayment, 2) < round($agreed->downpayment, 2)) ? 'bankAdvance2customer' : 'bank2customer';
    		} else {
    			$defaultOperation = 'bank2customer';
    		}
    	} elseif($dealInfo->dealType == bgerp_iface_DealResponse::TYPE_PURCHASE){
    		if(isset($agreed->downpayment)){
    			$defaultOperation = (round($paid->downpayment, 2) < round($agreed->downpayment, 2)) ? 'bank2supplierAdvance' : 'bank2supplier';
    		} else {
    			$defaultOperation = 'bank2supplier';
    		}
    	}
    	
    	return $defaultOperation;	
    }
    
    
	/**
     * Задава стойности по подразбиране от продажба/покупка
     * @param core_ObjectReference $origin - ориджин на документа
     * @param core_Form $form - формата
     * @param array $options - масив с сч. операции
     */
    private function setDefaultsFromOrigin(core_ObjectReference $origin, core_Form &$form, &$options)
    {
    	$form->setDefault('reason', "Към документ #{$origin->getHandle()}");
    	expect($dealInfo = $origin->getAggregateDealInfo());
    	expect(count($dealInfo->allowedPaymentOperations));
    	
    	$options = static::getOperations($dealInfo->allowedPaymentOperations);
    	expect(count($options));
    	
    	if($dealInfo->dealType != bgerp_iface_DealResponse::TYPE_DEAL){
    		$amount = ($dealInfo->agreed->amount - $dealInfo->paid->amount) / $dealInfo->shipped->rate;
    		$amount = ($amount <= 0) ? 0 : $amount;
    		
    		$form->defaultOperation = $this->getDefaultOperation($dealInfo);
    		if($form->defaultOperation == 'bank2supplierAdvance'){
    			$amount = ($dealInfo->agreed->downpayment - $dealInfo->paid->downpayment) / $dealInfo->agreed->rate;
    		}
    	}
    		 	 
    	$cId = ($dealInfo->shipped->currency) ? $dealInfo->shipped->currency : $dealInfo->paid->currency;
    	$form->rec->currencyId = currency_Currencies::getIdByCode($cId);
    		 
    	$rate = ($dealInfo->shipped->rate) ? $dealInfo->shipped->rate : $dealInfo->paid->rate;
    	$form->rec->rate = $rate;
    		 	 
    	if($dealInfo->dealType == bgerp_iface_DealResponse::TYPE_PURCHASE){
    		$form->rec->amount = currency_Currencies::round($amount, $dealInfo->shipped->currency);
    		 	
    		// Ако има банкова сметка по подразбиране
    		if($bankId = $dealInfo->agreed->payment->bankAccountId){
    		 	$bankId = bank_OwnAccounts::fetchField("#bankAccountId = {$bankId}", 'id');
    		 	if($bankId){
    		 		// Ако потребителя има права, логва се тихо
    		 		bank_OwnAccounts::selectSilent($bankId);
    		 	}
    		 }
    	}
    }
    
    
    /**
     * Връща платежните операции
     */
    private static function getOperations($operations)
    {
    	$options = array();
    
    	// Оставяме само тези операции в коитос е дебитира основната сметка на документа
    	foreach ($operations as $sysId => $op){
    		if($op['credit'] == static::$baseAccountSysId){
    			$options[$sysId] = $op['title'];
    		}
    	}
    	 
    	return $options;
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(!empty($data->toolbar->buttons['btnAdd'])){
    		$data->toolbar->removeBtn('btnAdd');
    	}
    }
    
    
    /**
     * Проверка след изпращането на формата
     */
    function on_AfterInputEditForm($mvc, $form)
    { 
    	if ($form->isSubmitted()){
    		
    		$rec = &$form->rec;
    		
	        $origin = $mvc->getOrigin($form->rec);
    		$dealInfo = $origin->getAggregateDealInfo();
    		
	        // Коя е дебитната и кредитната сметка
	        $operation = $dealInfo->allowedPaymentOperations[$rec->operationSysId];
	        $debitAcc = empty($operation['reverse']) ? $operation['debit'] : $operation['credit'];
	        $creditAcc = empty($operation['reverse']) ? $operation['credit'] : $operation['debit'];
	        $rec->debitAccId = $debitAcc;
	        $rec->creditAccId = $creditAcc;
	        $rec->isReverse = empty($operation['reverse']) ? 'no' : 'yes';
    		
    		// Проверяваме дали банковата сметка е в същата валута
    		$ownAcc = bank_OwnAccounts::getOwnAccountInfo($rec->ownAccount);	
	   	 	if($ownAcc->currencyId != $rec->currencyId) {
	   	 		$form->setError('currencyId', 'Банковата сметка е в друга валута');
	   	 	}
	   	 	
	   	 	// Ако няма валутен курс, взимаме този от системата
    		if(!$rec->rate) {
    			$currencyCode = currency_Currencies::getCodeById($rec->currencyId);
	    		$rec->rate = currency_CurrencyRates::getRate($rec->valior, $currencyCode, acc_Periods::getBaseCurrencyCode($rec->valior));
	    	} else {
	    		if($msg = currency_CurrencyRates::hasDeviation($rec->rate, $rec->valior, $currencyCode, NULL)){
	    			$form->setWarning('rate', $msg);
	    		}
	    	}
    	}
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->number = static::getHandle($rec->id);
    	if($fields['-list']){
    		$row->folderId = doc_Folders::recToVerbal(doc_Folders::fetch($rec->folderId))->title;
    	}
    	
    	if($fields['-single']) {
    		
    		$row->currencyId = currency_Currencies::getCodeById($rec->currencyId);
    		
    		if($rec->rate != '1') {
    			
			    $period = acc_Periods::fetchByDate($rec->valior);
			    $row->baseCurrency = currency_Currencies::getCodeById($period->baseCurrencyId);
	    		$row->equals = $mvc->fields['amount']->type->toVerbal($rec->amount * $rec->rate);
    		} else {
    			
    			unset($row->rate);
    		}
    		
    		$ownAcc = bank_OwnAccounts::getOwnAccountInfo($rec->ownAccount);	
    		$row->accCurrency = currency_Currencies::getCodeById($ownAcc->currencyId);
    		if($rec->contragentIban){
    			$row->accCurrencyIban = $row->accCurrency;
    		}
    		
	    	// Показваме заглавието само ако не сме в режим принтиране
	    	if(!Mode::is('printing')){
	    		$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
	    	}
	    	
	    	$ownCompany = crm_Companies::fetchOwnCompany();
	    	$Companies = cls::get('crm_Companies');
	    	$row->companyName = $Companies->getTitleById($ownCompany->companyId);
	    	$row->companyAddress = $Companies->getFullAdress($ownCompany->companyId);
    	}
    }
    
    
    /**
     * Поставя бутони за генериране на други банкови документи възоснова
     * на този, само ако документа е "чернова".
     */
	static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
    	if($data->rec->state == 'draft') {
    		
	    	// Ако дебитната сметка е за работа с контрагент слагаме бутон за
	    	// платежно нареждане ако е подочетно лице генерираме нареждане разписка
	    	if(bank_PaymentOrders::haveRightFor('add') && acc_Lists::getPosition($data->rec->debitAccId, 'crm_ContragentAccRegIntf')) {
	    		$data->toolbar->addBtn('Платежно нареждане', array('bank_PaymentOrders', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE, ''), NULL, 'ef_icon = img/16/view.png,title=Създаване на ново платежно нареждане');
	    	} elseif(bank_CashWithdrawOrders::haveRightFor('add') && acc_Lists::getPosition($data->rec->creditAccId, 'crm_PersonAccRegIntf')) {
	    		$data->toolbar->addBtn('Нареждане разписка', array('bank_CashWithdrawOrders', 'add', 'originId' => $data->rec->containerId, 'ret_url' => TRUE, ''), NULL, 'ef_icon = img/16/view.png,title=Създаване на ново нареждане разписка');
	    	}
    	}
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        return FALSE;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
        $row = new stdClass();
        $row->title = $this->singleTitle . " №{$id}";
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
		$row->recTitle = $rec->reason;
		
        return $row;
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     * 
     * @param int $threadId key(mvc=doc_Threads)
     * @return boolean
     */
	public static function canAddToThread($threadId)
    {
    	$threadRec = doc_Threads::fetch($threadId);
    	$coverClass = doc_Folders::fetchCoverClassName($threadRec->folderId);
    	
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	$docState = $firstDoc->fetchField('state');
    	
    	if(($firstDoc->haveInterface('bgerp_DealAggregatorIntf') && $docState == 'active')){
    		
    		// Ако няма позволени операции за документа не може да се създава
    		$dealInfo = $firstDoc->getAggregateDealInfo();
    		$options = self::getOperations($dealInfo->allowedPaymentOperations);
    			
    		return count($options) ? TRUE : FALSE;
    	}
		
    	return FALSE;
    }
    
    
	/**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     */
    public function getDealInfo($id)
    {
        $rec = self::fetchRec($id);
    
        /* @var $result bgerp_iface_DealResponse */
        $result = new bgerp_iface_DealResponse();
    
        // При продажба платеното се намалява, ако е покупка се увеличава
        $origin = static::getOrigin($rec);
        $sign = ($origin->className == 'purchase_Purchases') ? 1 : -1;
        
    	$result->paid->amount                 = $sign * $rec->amount * $rec->rate;
        $result->paid->currency               = currency_Currencies::getCodeById($rec->currencyId);
        $result->paid->rate 	              = $rec->rate;
        $result->paid->payment->bankAccountId = bank_OwnAccounts::fetchField($rec->ownAccount, 'bankAccountId');
        $result->paid->operationSysId         = $rec->operationSysId;
    	
        return $result;
    }
    
    
	/**
     * Информация за платежен документ
     * 
     * @param int|stdClass $id ключ (int) или запис (stdClass) на модел 
     * @return stdClass Обект със следните полета:
     *
     *   o amount       - обща сума на платежния документ във валутата, зададена от `currencyCode`
     *   o currencyCode - key(mvc=currency_Currencies, key=code): ISO код на валутата
     *   o currencyRate - double - валутен курс към основната (към датата на док.) валута
     *   o valior       - date - вальор на документа
     */
    public static function getPaymentInfo($id)
    {
        $rec = self::fetchRec($id);
        
        return (object)array(
            'amount'       => -$rec->amount,
            'currencyCode' => currency_Currencies::getCodeById($rec->currencyId),
        	'currencyRate' => $rec->rate,
            'valior'       => $rec->valior,
        );
    }
    
    
    /**
     * Интерфейсен метод на doc_ContragentDataIntf
     * Връща тялото на имейл по подразбиране
     */
    static function getDefaultEmailBody($id)
    {
        $handle = static::getHandle($id);
        $tpl = new ET(tr("Моля запознайте се с нашия разходен банков документ") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
    }
    
    
	/**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
        // Ако резултата е 'no_one' пропускане
    	if($res == 'no_one') return;
    	
    	// Документа не може да се контира, ако ориджина му е в състояние 'closed'
    	if($action == 'conto' && isset($rec)){
	    	$origin = $mvc->getOrigin($rec);
	    	if($origin && $origin->haveInterface('bgerp_DealAggregatorIntf')){
	    		$originState = $origin->fetchField('state');
		    	if($originState === 'closed'){
		        	$res = 'no_one';
		        }
	    	}
        }
    }
    
    
	/**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
    	// Документа не може да се създава  в нова нишка, ако е възоснова на друг
    	if(!empty($data->form->toolbar->buttons['btnNewThread'])){
    		$data->form->toolbar->removeBtn('btnNewThread');
    	}
    }
    
    
	/**
     * Вкарваме css файл за единичния изглед
     */
	static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$tpl->push('bank/tpl/css/styles.css', 'CSS');
    }
    
    
	/**
     * След оттегляне на документа
     *
     * @param core_Mvc $mvc
     * @param mixed $res
     * @param object|int $id
     */
    public static function on_AfterReject($mvc, &$res, $id)
    {
        // Нотифицираме origin-документа, че някой от веригата му се е променил
        if ($origin = $mvc->getOrigin($id)) {
            $ref = new core_ObjectReference($mvc, $id);
            $origin->getInstance()->invoke('DescendantChanged', array($origin, $ref));
        }
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(__CLASS__);
    
    	return $self->singleTitle . " №$rec->id";
    }
}