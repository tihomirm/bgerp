<?php



/**
 * Мениджър за "Бележки за продажби" 
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class pos_ReceiptDetails extends core_Detail {
    
    
    /**
     * Заглавие
     */
    var $title = 'Детайли на бележката';
    
    
    /**
	 * Мастър ключ към дъските
	 */
	var $masterKey = 'receiptId';
    
    
    /**
     * Кой може да променя?
     */
    var $canAdd = 'pos, ceo';
    
    
    /**
     * Кой може да променя?
     */
    var $canEdit = 'pos, ceo';
    
    
    /**
     * Кой може да променя?
     */
    var $canWrite = 'pos, ceo';
    
    
    /**
     * Кой може да променя?
     */
    var $canList = 'no_one';
    

    /**
     * Кой може да променя?
     */
    var $canDelete = 'pos, ceo';
    
    
  	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('receiptId', 'key(mvc=pos_Receipts)', 'caption=Бележка, input=hidden, silent');
    	$this->FLD('action', 'varchar(32)', 'caption=Действие,width=7em;top:1px;position:relative');
    	$this->FLD('param', 'varchar(32)', 'caption=Параметри,width=7em,input=none');
    	$this->FNC('ean', 'varchar(32)', 'caption=ЕАН, input, class=ean-text');
    	$this->FLD('productId', 'key(mvc=cat_Products, select=name, allowEmpty)', 'caption=Продукт,input=none');
    	$this->FLD('price', 'double(decimals=2)', 'caption=Цена,input=none');
        $this->FLD('quantity', 'int', 'caption=К-во,placeholder=К-во,width=4em');
        $this->FLD('amount', 'double(decimals=2)', 'caption=Сума, input=none');
    	$this->FLD('value', 'varchar(32)', 'caption=Стойност, input=hidden');
    	$this->FLD('discountPercent', 'percent(min=0,max=1)', 'caption=Отстъпка->Процент,input=none');
        $this->FLD('fixbon', 'enum(yes=Да,no=Не)', 'caption=Фискален Бон,input=none,value=yes');
    }
    
    
    /**
     * Променяме рендирането на детайлите
     */
    function renderReceiptDetail($data)
    {
    	$tpl = new ET("");
    	$lastRow = Mode::get('lastAdded');
    	$blocksTpl = getTplFromFile('pos/tpl/terminal/ReceiptDetail.shtml');
    	$saleTpl = $blocksTpl->getBlock('sale');
    	$paymentTpl = $blocksTpl->getBlock('payment');
    	$clientTpl = $blocksTpl->getBlock('client');
    	if($data->rows) {
	    	foreach($data->rows as $row) {
	    		$action = $this->getAction($data->rows[$row->id]->action);
	    		$rowTpl = clone(${"{$action->type}Tpl"});
	    		$rowTpl->placeObject($row);
	    		if($lastRow == $row->id) {
	    			$rowTpl->replace("id='last-row'", 'lastRow');
	    			unset($lastRow);
	    			Mode::setPermanent('lastAdded', NULL);
	    		}
	    		$rowTpl->removeBlocks();
	    		$tpl->append($rowTpl);
	    	}
    	} else {
    		$tpl->append(new ET("<tr><td colspan='3' class='receipt-sale'>" . tr('Няма записи') . "</td></tr>"));
    	}
    	
    	return $tpl;
    }
    
    
    /**
     * Добавя отстъпка на избран продукт
     */
    function act_setDiscount()
    {
    	if(!$recId = Request::get('recId', 'int')){
    		core_Statuses::newStatus('Не е избран ред !', 'error');
    		return array();
    	}
    	
    	if(!$rec = $this->fetch($recId)){
    		return array();
    	}
    	
    	// Трябва да може да се редактира записа
    	if(!$this->haveRightFor('edit', $rec)) return array();
    	
    	$discount = Request::get('amount');
    	$discount = $this->fields['discountPercent']->type->fromVerbal($discount);
    	if(!isset($discount)){
    		core_Statuses::newStatus('Не е въведено валидна процентна отстъпка !', 'error');
    		return array();
    	}
    	
    	// Записваме променената отстъпка
    	$rec->discountPercent = $discount;
    	
    	if($this->save($rec)){
    		
    		core_Statuses::newStatus('Успешно зададохте отстъпка !');
    		
    		return $this->returnResponse($rec->receiptId);
    	} else {
    		core_Statuses::newStatus('Проблем при задаване на отстъпка !', 'error');
    	}
    	
    	return array();
    }
    
    
    /**
     * Връщане на отговор
     */
    private function returnResponse($receiptId)
    {
    	// Ако заявката е по ajax
        if (Request::get('ajax_mode')) {
        	$receiptTpl = $this->Master->getReceipt($receiptId);
		    $paymentTpl = $this->Master->renderPaymentTab($receiptId);
		    	
		    // Ще реплейснем само бележката
		    $resObj = new stdClass();
			$resObj->func = "html";
			$resObj->arg = array('id' => 'receipt-table', 'html' => $receiptTpl->getContent(), 'replace' => TRUE);
			
			// Ще реплесйнем и таба за плащанията
			$resObj1 = new stdClass();
			$resObj1->func = "html";
			$resObj1->arg = array('id' => 'tools-payment', 'html' => $paymentTpl->getContent(), 'replace' => TRUE);
        	
        	return array($resObj, $resObj1);
        } else {
        	
        	// Ако не сме в Ajax режим пренасочваме към терминала
        	return Redirect(array($this->Master, 'Terminal', $receiptId));
        }
    }
    
    
    /**
     * Промяна на количество на избран продукт
     */
    function act_setQuantity()
    {
    	// Трябва да има избран ред
    	if(!$recId = Request::get('recId', 'int')){
    		core_Statuses::newStatus('Не е избран ред !', 'error');
    		return array();
    	}
    	
    	// Трябва да има такъв запис
    	if(!$rec = $this->fetch($recId)) return array();
    	
    	// Трябва да може да се редактира записа
    	if(!$this->haveRightFor('edit', $rec)) return array();
    	
    	$quantityId = Request::get('amount');
    	
    	// Трябва да е подадено валидно количество
    	$quantityId = $this->fields['quantity']->type->fromVerbal($quantityId);
    	if(!$quantityId){
    		core_Statuses::newStatus('Не е въведено валидно количество !', 'error');
    		return array();
    	}
    	
    	// Преизчисляваме сумата
    	$rec->quantity = $quantityId;
    	$rec->amount = $rec->price * $rec->quantity;
    	
    	// Запис на новото количество
    	if($this->save($rec)){
    		
    		core_Statuses::newStatus('Успешно променихте количеството !');
    		
    		return $this->returnResponse($rec->receiptId);
    	} else {
    		core_Statuses::newStatus('Проблем при редакция на количество !', 'error');
    	}
    	
    	return array();
    }
    
    
    /**
     * Добавяне на плащане към бележка
     */
    function act_makePayment()
    {
    	// Трябва да е избрана бележка
    	if(!$recId = Request::get('receiptId', 'int')) return array();
    	
    	// Можем ли да добавяме към бележката
    	if(!$this->haveRightFor('add', (object)array('receiptId' => $recId)))  return array();
    	
    	// Трябва да има избран запис на бележка
    	if(!$receipt = $this->Master->fetch($recId)) return array();
    	
    	// Трябва да е подаден валидно ид на начин на плащане
    	$type = Request::get('type');
    	if(!pos_Payments::fetch($type))  return array();
    	
    	// Трябва да е подадена валидна сума
    	$amount = Request::get('amount');
    	$amount = $this->fields['amount']->type->fromVerbal($amount);
    	if(!$amount || $amount <= 0){
    		core_Statuses::newStatus('Трябва да въведете положителна сума !', 'error');
	    	return array();
    	}
    	
    	// Ако платежния метод не поддържа ресто, не може да се плати по-голяма сума
    	$diff = abs($receipt->paid - $receipt->total);
    	if(!pos_Payments::returnsChange($type) && (string)$amount > (string)$diff){
    		core_Statuses::newStatus('Не може с този платежен метод да се плати по-голяма сума от общата !', 'error');
	    	return array();
    	}
    	
    	// Подготвяме записа на плащането
    	$rec = new stdClass();
    	$rec->receiptId = $recId;
    	$rec->action = "payment|{$type}";
    	$rec->amount = $amount;
    	
    	// Запис на плащанетo
    	if($this->save($rec)){
    		core_Statuses::newStatus('Успешно направихте плащане !');
    		
    		return $this->returnResponse($recId);
    	} else {
    		core_Statuses::newStatus('Проблем при плащане !', 'error');
    	}
    	
    	return array();
    }
    
    
    /**
     * Изтрива запис от бележката
     */
    function act_DeleteRec()
    {
    	// Трябва да има ид на ред за изтриване
    	if(!$id = Request::get('recId', 'int')) return array();
    	
    	// Трябва да има такъв запис
    	if(!$rec = $this->fetch($id)) return array();
    	
    	// Трябва да можем да изтриваме от бележката
    	if(!$this->haveRightFor('delete', $rec))  return array();
    	
    	$receiptId = $rec->receiptId;
    	
    	if($this->delete($rec->id)){
    		core_Statuses::newStatus('Успешно изтрихте реда !');
    		
    		// Ъпдейт на бележката след изтриването
    		$this->Master->updateReceipt($receiptId);
    		
    		return $this->returnResponse($receiptId);
    	} else {
    		core_Statuses::newStatus('Проблем при изтриването на ред !', 'error');
    	}
    	
    	return array();
    }
    
    
    /**
     * Екшън добавящ продукт в бележката
     */
    function act_addProduct()
    {
    	// Трябва да има такава бележка
    	if(!$receiptId = Request::get('receiptId', 'int')) return array();
    	
    	// Трябва да можем да добавяме към нея
    	if(!$this->haveRightFor('add', (object)array('receiptId' => $receiptId)))  return array();
    	
    	// Запис на продукта
    	$rec = new stdClass();
    	$rec->receiptId = $receiptId;
    	$rec->action = 'sale|code';
    	$rec->quantity = 1;
    	
    	// Ако е зададен код на продукта
    	if($ean = Request::get('ean')) {
    		$rec->ean = $ean;
    	}
    	
    	// Ако е зададено ид на продукта
    	if($productId = Request::get('productId', 'int')) {
    		$rec->productId  = $productId;
    	}
    	
    	// Трябва да е подаден код или ид на продукт
    	if(!$rec->productId && !$rec->ean){
    		core_Statuses::newStatus('Не е посочен продукт !', 'error');
    		return array();
    	}
    	
    	// Намираме нужната информация за продукта
    	$this->getProductInfo($rec);
    		
    	// Ако не е намерен продукт
	    if(!$rec->productId) {
	    	core_Statuses::newStatus('Няма такъв продукт в системата или той не е продаваем !', 'error');
	    	return array();
	    }

	    // Ако няма цена
	    if(!$rec->price) {
	    	core_Statuses::newStatus('Артикула няма цена !', 'error');
	    	return array();
	    }
	    	
    	// Намираме дали този проект го има въведен 
		$sameProduct = $this->findSale($rec->productId, $rec->receiptId, $rec->value);
		if($sameProduct) {
				    				
			// Ако цената и опаковката му е същата като на текущия продукт,
			// не добавяме нов запис а ъпдейтваме стария
			$newQuantity = $rec->quantity + $sameProduct->quantity;
			$rec->quantity = $newQuantity;
			$rec->amount += $sameProduct->amount;
			$rec->id = $sameProduct->id;
		}
		
		// Добавяне/обновяване на продукта
    	if($this->save($rec)){
    		core_Statuses::newStatus('Артикула е добавен успешно !');
    		
    		return $this->returnResponse($rec->receiptId);
    	} else {
    		core_Statuses::newStatus('Проблем при добавяне на артикул !', 'error');
    	}
		
    	return array();
    }
    
    
    /**
     * Подготвя детайла на бележката
     * 
     * @param int $receiptId -ид на бележка
     */
    public function prepareReceiptDetails($receiptId)
    {
    	$res = new stdClass();
    	$query = $this->getQuery();
    	$query->where("#receiptId = '{$receiptId}'");
    	while($rec = $query->fetch()){
    		$res->recs[$rec->id] = $rec;
    		$res->rows[$rec->id] = $this->recToVerbal($rec);
    	}
    	
    	return $res;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	$varchar = cls::get('type_Varchar');
    	$receiptDate = $mvc->Master->fetchField($rec->receiptId, 'createdOn');
    	$row->currency = acc_Periods::getBaseCurrencyCode($receiptDate);
    	
    	$action = $mvc->getAction($rec->action);
    	switch($action->type) {
    		case "sale":
    			$mvc->renderSale($rec, $row, $receiptDate);
    			break;
    		case "payment":
    			$row->actionValue = pos_Payments::getTitleById($action->value);
    			break;
    		case "client":
    			$clientArr = explode("|", $rec->param);
    			$row->clientName = $clientArr[1]::getTitleById($clientArr[0]);
    			break;
    	}
    	
    	if($mvc->haveRightFor('delete', $rec)){
    		$delUrl = toUrl(array($mvc->className, 'deleteRec'));
    		$row->DEL_BTN = ht::createElement('img', array('src' => sbf('img/16/delete.png', ''), 
    													   'class' => 'pos-del-btn', 'data-recId' => $rec->id, 
    													   'data-warning' => tr('Наистина ли искате да изтриете записа?'), 
    													   'data-url' => $delUrl));
    	}
    }
    
    
    /**
     * Рендира информацията за направената продажба
     */
    function renderSale($rec, &$row, $receiptDate)
    {
    	$varchar = cls::get('type_Varchar');
    	$double = cls::get('type_Double');
    	$percent = cls::get('type_Percent');
    	$percent->params['decimals'] = $double->params['decimals'] = 2;
    	
    	$productInfo = cat_Products::getProductInfo($rec->productId, $rec->value);
    	
    	$vat = cat_Products::getVat($rec->productId, $receiptDate);
    	$rec->price = $rec->price * (1 - $rec->discountPercent);
    	$rec->price += ($rec->price * $vat);
    	$row->price = $double->toVerbal($rec->price);
    	$row->amount = $double->toVerbal($rec->price * $rec->quantity);
    	if($rec->discountPercent < 0){
    		$row->discountPercent = "+" . trim($row->discountPercent, '-');
    	}
    	
    	$row->productId = $varchar->toVerbal($productInfo->productRec->name);
    	$row->code = $varchar->toVerbal($productInfo->productRec->code);
    	$row->uomId = cat_UoM::getShortName($productInfo->productRec->measureId);
    	
    	$row->perPack = $double->toVerbal($productInfo->packagingRec->quantity);
    	if($rec->value) {
    		$row->packagingId = cat_Packagings::getTitleById($rec->value);
    	} else {
    		$row->packagingId = $row->uomId;
    		unset($row->uomId);
    	}
    	
    	if($rec->discountPercent == 0){
    		unset($row->discountPercent);
    	}
    }
    
    
    /**
     * Метод връщаш обект с информация за избраното действие
     * и неговата стойност
     * @param string $string - стринг където от вида "action|value"
     * @return stdClass $action - обект съдържащ ид и стойноста извлечени
     * от стринга
     */
    function getAction($string)
    {
    	$actionArr = explode("|", $string);
    	$allowed = array('sale', 'discount', 'client', 'payment');
    	expect(in_array($actionArr[0], $allowed), 'Не е позволена такава операция');
    	expect(count($actionArr) == 2, 'Стрингът не е в правилен формат');
    	
    	$action = new stdClass();
    	$action->type = $actionArr[0];
    	$action->value = $actionArr[1];
    	
    	return $action;
    }
    
    
    /**
     * Изчлича информацията за клиента, по зададен параметър
     * записва информацията за клиента във вида на стринг:
     * ид на клиента и неговия клас разделени с "|"
     * @param stdClass $rec
     */
    function getClientInfo(&$rec)
    {
    	//@TODO Функцията е прототипна
    	$action = static::getAction($rec->action);
    	
    	if($action->value == 'ccard') {
    		
    			// временно връща името на клиента, по подадено негово Id
	    		if($rec->param = crm_Persons::fetchField(array("#id = [#1#]", $rec->ean), 'id')){
	    			$rec->param .= "|crm_Persons";
	    		} else {
	    			return NULL;
	    	} 
	    }	
    }
    
    
    /**
     * Намира продукта по подаден номер и изчислява неговата цена
     * и отстъпка спрямо клиента, и ценоразписа
     * @param stdClass $rec
     */
    function getProductInfo(&$rec)
    {
    	if($rec->ean){
	    	if(!$product = cat_Products::getByCode($rec->ean)) {
	    		
	    		return $rec->productid = NULL;
	    	}
    	} else{
    		if(!$rec->productId) {
    			return $rec->productid = NULL;
    		}
    		expect($productId = cat_Products::fetch($rec->productId));
    		$product = (object)array('productId' => $rec->productId);
    	}
    	
    	$info = cat_Products::getProductInfo($product->productId, $product->packagingId);
    	if(empty($info->meta['canSell'])){
    		
    		return $rec->productid = NULL;
    	}
    	
    	if($info->packagingRec){
    		$rec->value = $info->packagingRec->packagingId;
    		$perPack = $info->packagingRec->quantity;
    	} else {
    		$perPack = 1;
    	}
    	$rec->productId = $product->productId;
    	$receiptRec = pos_Receipts::fetch($rec->receiptId);
    	$policyId = pos_Points::fetchField($receiptRec->pointId, 'policyId');
    	$price = new stdClass();
    	$price->price = price_ListRules::getPrice($policyId, $product->productId, $product->packagingId, $receiptRec->valior);
    	
    	$rec->price = $price->price;
    	$rec->param = cat_Products::getVat($rec->productId, $receiptRec->valior);
    	$rec->amount = $rec->price * $rec->quantity * $perPack;	
    }
    
    
	/**
     *  Намира последната продажба на даден продукт в текущата бележка
     *  @param int $productId - ид на продукта
     *  @param int $receiptId - ид на бележката
     *  @param int $packId - ид на опаковката
     *  @return mixed $rec/FALSE - Последния запис или FALSE ако няма
     */
    function findSale($productId, $receiptId, $packId)
    {
    	$query = $this->getQuery();
    	$query->where(array("#productId = [#1#]", $productId));
    	$query->where(array("#receiptId = [#1#]", $receiptId));
    	if($packId) {
    		$query->where(array("#value = [#1#]", $packId));
    	} else {
    		$query->where("#value IS NULL");
    	}
    	
    	$query->orderBy('#id', 'DESC');
    	$query->limit(1);
    	if($rec = $query->fetch()){
    		
    		return $rec;
    	} 
    	
    	return FALSE;
    }
    
    
    /**
     * Определяме кой е клиента на бележката
     * @param int $receiptId - id на бележка
     * @return mixed $rec - запис на клиента, FALSE ако няма
     */
    public function hasClient($receiptId)
    {
    	$query = $this->getQuery();
    	$query->where(array("#receiptId = [#1#]", $receiptId));
    	$query->where(array("#receiptId = [#1#]", $receiptId));
    	$query->where("#action = 'client|ccard'");
    	$query->orderBy("#id", "DESC");
    	
    	$rec = $query->fetch();
    	if(!$rec) return FALSE;
    	
    	$res = new stdClass();
    	list($res->id, $res->class) = explode('|', $rec->param);
    	
    	return $res;
    }
	
	
	/**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        $data->toolbar->removeBtn('btnAdd');
    }
    
    
	/**
	 * След като създадем елемент, ъпдейтваме Бележката
	 */
	static function on_AfterSave($mvc, &$id, $rec, $fieldsList = NULL)
    {
     	Mode::setPermanent('lastAdded', $id);
    	$mvc->Master->updateReceipt($rec->receiptId);
    }
    
    
	/**
	 * Модификация на ролите, които могат да видят избраната тема
	 */
    static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{ 
		if(($action == 'add' || $action == 'edit' || $action == 'delete') && isset($rec->receiptId)) {
			$masterRec = $mvc->Master->fetch($rec->receiptId);
			
			if($masterRec->state != 'draft') {
				$res = 'no_one';
			}
		}
	}
    
    function act_Test(){
    	$id = '51';
    	$data = static::fetchReportData($id);
    	bp($data);
    }
    /**
     * Използва се от репортите за извличане на данни за продажбата
     * 
     * @param int $receiptId - ид на бележка
     * @return array $result - масив от всички плащания и продажби на бележката;
     */
    static function fetchReportData($receiptId)
    {
    	expect($masterRec = pos_Receipts::fetch($receiptId));
    	$storeId = pos_Points::fetchField($masterRec->pointId, 'storeId');
    	$caseId = pos_Points::fetchField($masterRec->pointId, 'caseId');
    	
    	$result = array();
    	$query = static::getQuery();
    	$query->EXT('contragentClsId', 'pos_Receipts', 'externalName=contragentClass,externalKey=receiptId');
    	$query->EXT('contragentId', 'pos_Receipts', 'externalName=contragentObjectId,externalKey=receiptId');
    	$query->where("#receiptId = {$receiptId}");
    	$query->where("#action LIKE '%sale%' || #action LIKE '%payment%'");
    	
    	while($rec = $query->fetch()) {
    		$arr = array();
    		$obj = new stdClass();
    		if($rec->productId) {
    			$obj->action  = 'sale';
    			$obj->pack    = ($rec->value) ?  $rec->value : NULL;
    			$obj->value   = $rec->productId;
    			$obj->storeId = $storeId;
    		} else {
    			$obj->action = 'payment';
    			list(, $obj->value) = explode('|', $rec->action);
    			$obj->pack = NULL;
    			$obj->caseId = $caseId;
    		}
    		$obj->contragentClassId = $rec->contragentClsId;
    		$obj->contragentId      = $rec->contragentId;
    		$obj->quantity          = $rec->quantity;
    		$obj->amount            = $rec->amount + ($rec->amount * $rec->param);
    		$obj->date              = $masterRec->createdOn;
    		
    		$result[] = $obj;
    	}
    	
    	return $result;
    }
}