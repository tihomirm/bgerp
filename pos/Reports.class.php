<?php



/**
 * Модел Отчети
 *
 *
 * @category  bgerp
 * @package   pos
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class pos_Reports extends core_Master {
    
    
	/**
     * Какви интерфейси поддържа този мениджър
     */
    public $interfaces = 'doc_DocumentIntf, acc_TransactionSourceIntf=pos_transaction_Report, deals_DealsAccRegIntf, acc_RegisterIntf';
    
    
    /**
     * Заглавие
     */
    public $title = 'Отчети за POS продажби';
    
    
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = TRUE;
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'pos_Wrapper, plg_Printing, doc_DocumentPlg, acc_plg_Contable, acc_plg_DocumentSummary, plg_Search, 
   					bgerp_plg_Blank, plg_Sorting';
   
    
    /**
     * Наименование на единичния обект
     */
    public $singleTitle = "Отчет за POS продажби";
    
    
    /**
     * Икона на единичния обект
     */
    public $singleIcon = 'img/16/report.png';
    

    /**
	 *  Брой елементи на страница 
	 */
    public $listItemsPerPage = "40";
    
    
    /**
     * Брой продажби на страница
     */
    public $listDetailsPerPage = '50';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'pos, ceo';
    
    
	/**
     * Абревиатура
     */
    public $abbr = "Otc";
 
    
    /**
     * Кой може да пише?
     */
    public $canWrite = 'pos, ceo';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, pos';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, pos';
    
	
    /**
     * Кой има право да контира?
     */
    public $canConto = 'pos, ceo';
    
    
    /**
	 * Файл за единичен изглед
	 */
	public $singleLayoutFile = 'pos/tpl/SingleReport.shtml';
	
	
	/**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, title=Заглавие, pointId, total, paid, state, createdOn, createdBy';
    
    
	/**
     * Групиране на документите
     */
    public $newBtnGroup = "3.5|Търговия";
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('pointId', 'key(mvc=pos_Points, select=name)', 'caption=Точка, width=9em, mandatory,silent');
    	$this->FLD('paid', 'double(decimals=2)', 'caption=Сума->Платено, input=none, value=0, summary=amount');
    	$this->FLD('total', 'double(decimals=2)', 'caption=Сума->Продадено, input=none, value=0, summary=amount');
    	$this->FLD('state', 'enum(draft=Чернова,active=Активиран,rejected=Оттеглена)', 'caption=Състояние,input=none,width=8em');
    	$this->FLD('details', 'blob(serialize,compress)', 'caption=Данни,input=none');
    }
    
    
    /**
     * Подготовка на формата за добавяне
     */
    protected static function on_AfterPrepareEditForm($mvc, $res, $data)
    { 
    	$data->form->setDefault('pointId', pos_Points::getCurrent('id', FALSE));
    }
    
    
	/**
	 *  Подготовка на филтър формата
	 */
	protected static function on_AfterPrepareListFilter($mvc, $data)
	{	
        $data->query->orderBy('#createdOn', 'DESC');
		$data->listFilter->FNC('point', 'key(mvc=pos_Points, select=name, allowEmpty)', 'caption=Точка,width=12em,silent');
        $data->listFilter->showFields .= ',user,point';
        
        // Активиране на филтъра
        $data->listFilter->input(NULL, 'silent');
		
		if($filter = $data->listFilter->rec) {
	    		
	    	if($filter->point) {
	    		$data->query->where("#pointId = {$filter->point}");
	    	}
	    }
	}
	
	
	/**
	 * Изпълнява се преди вербалното представяне
	 */
	protected static function on_BeforeRecToVerbal($mvc, &$row, $rec, $fields)
    {
    	// Ако няма записани детайли извличаме актуалните
    	if(!$rec->details){
    		$mvc->extractData($rec);
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
    	$row->header = $mvc->singleTitle . "&nbsp;&nbsp;<b>{$row->ident}</b>" . " ({$row->state})" ;
    	$row->title = "Отчет за POS продажба №{$rec->id}";
    	$row->pointId = pos_Points::getHyperLink($rec->pointId, TRUE);
    	
    	$row->earliestReceipt = dt::mysql2verbal(pos_Receipts::fetchField($rec->details['receipts'][0]->id, 'createdOn'));
		$row->lastReceipt = dt::mysql2verbal(pos_Receipts::fetchField($rec->details['receipts'][count($rec->details['receipts']) -1]->id, 'createdOn'));
    	
    	if($fields['-single']) {
    		$pointRec = pos_Points::fetch($rec->pointId);
    		$row->storeId = store_Stores::getHyperLink($pointRec->storeId, TRUE);
	    	$row->caseId = cash_Cases::getHyperLink($pointRec->caseId, TRUE);
	    	$row->baseCurrency = acc_Periods::getBaseCurrencyCode($rec->createdOn);
    	}
    	
    	if($fields['-list']) {
    		$row->title = ht::createLink($row->title, array($mvc, 'single', $rec->id), NULL, "ef_icon={$mvc->singleIcon}");
    	}
    }
    
    
    /**
     * Извиква се след въвеждането на данните
     */
    public static function on_AfterInputEditForm($mvc,core_Form &$form)
    {
    	if($form->isSubmitted()) {
    		
    		// Можем ли да създадем отчет за този касиер или точка
    		if(!self::canMakeReport($form->rec->pointId)){
    			$form->setError('pointId', 'Не може да създадете отчет за тази точка');
    		}
    		
    		// Ако няма грешки, форсираме отчета да се създаде в папката на точката
    		if(!$form->gotErrors()){
    			$form->rec->folderId = pos_Points::forceCoverAndFolder($form->rec->pointId);
    		}
    	}	
    }
    
    
    /**
     * Функция която обновява информацията на репорта
     * извиква се след изпращането на формата и при
     * активация на документа
     * @param stdClass $rec - запис от модела
     */
    public function extractData(&$rec)
    {
    	// Извличаме информацията от бележките
    	$reportData = $this->fetchData($rec->pointId);
    	
    	$rec->details = $reportData;
    	$rec->total = $rec->paid = 0;
    	if(count($reportData['receiptDetails'])){
		    foreach($reportData['receiptDetails'] as $index => $detail) {
		    	list($action) = explode('|', $index);
		    	if($action == 'sale'){
		    		$rec->total += $detail->amount * (1 + $detail->param);
		    	} else {
		    		$rec->paid += $detail->amount;
		    	}
		    }
   	 	}
    }
    
    
    /**
     * Пушваме css и рендираме "детайлите"
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {	
    	// Рендираме продажбите
    	$tpl->append($mvc->renderListTable($data->rec->details), "SALES");
    	if($data->rec->details->pager){
    		$tpl->append($data->rec->details->pager->getHtml(), "SALE_PAGINATOR");
    	}
    	
    	$tpl->push('pos/tpl/css/styles.css', 'CSS');
    }
    
    
    /**
     * Обработка детайлите на репорта
     */
    protected static function on_AfterPrepareSingle($mvc, &$data)
    {
    	$detail = (object)$data->rec->details;
    	arr::orderA($detail->receiptDetails, 'action');
	    
    	// Табличната информация и пейджъра на плащанията
	    $detail->listFields = "value=Действие,pack=Мярка, quantity=Количество, amount=Сума ({$data->row->baseCurrency})";
    	$detail->rows = $detail->receiptDetails;
    	$mvc->prepareDetail($detail);
    	$data->rec->details = $detail;
	}
    
    
	/**
	 * Инстанциране на пейджъра и модификации по данните спрямо него
	 * @param stdClass $detail - Масив с детайли на отчета (плащания или продажби)
	 */
    public function prepareDetail(&$detail)
    {
    	$newRows = array();
    	
    	// Инстанцираме пейджър-а
    	$Pager = cls::get('core_Pager', array('itemsPerPage' => $this->listDetailsPerPage));
    	$Pager->itemsCount = count($detail->rows);
    	$Pager->calc();
    	
    	// Добавяме всеки елемент отговарящ на условието на пейджъра в нов масив
    	if($detail->rows){
    		
    		 // Подготвяме поле по което да сортираме
    		 foreach ($detail->rows as $key => &$value){
    		 	if($value->action == 'sale'){
    		 		$value->sortString = mb_strtolower(cat_Products::fetchField($value->value, 'name'));
    		 	}
    		 }
    		 
    		 usort($detail->rows, array($this, "sortResults"));
    		
    		 // Обръщаме във вербален вид
    		 $start = $Pager->rangeStart;
    		 $end = $Pager->rangeEnd - 1;
             $rowsCnt = count($detail->rows);
    		 for($i = 0; $i < $rowsCnt; $i++){
    		 	if($i >= $start && $i <= $end){
    		 		$keys = array_keys($detail->rows);
    		 		$newRows[] = $this->getVerbalDetail($detail->rows[$keys[$i]]);
    		 	}
    		 }
    		 
    		 // Заместваме стария масив с новия филтриран
    		 $detail->rows = $newRows;
    		
    		 // Добавяме пейджъра
    		 $detail->pager = $Pager;
    	}
    }
    
    
    /**
     * Сортира масива първо по код после по сума (ако кодовете съвпадат)
     */
    private function sortResults($a, $b) {
    	
    	return strcmp($a->sortString, $b->sortString);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а на формата за редактиране/добавяне
     */
    protected static function on_AfterPrepareEditToolbar($mvc, $data)
    {
    	if (!empty($data->form->toolbar->buttons['save'])) {
    		$data->form->toolbar->removeBtn('save');
    		$data->form->toolbar->addSbBtn('Контиране', 'save', 'ef_icon = img/16/disk.png, title = Контиране на документа');
    	}
    }
    
    
    /**
     * Функция обработваща детайл на репорта във вербален вид
     * @param stdClass $rec-> запис на продажба или плащане
     * @return stdClass $row-> вербалния вид на записа
     */
    private function getVerbalDetail($obj)
    {
    	$row = new stdClass();
    	
    	$varchar = cls::get("type_Varchar");
    	$double = cls::get("type_Double");
    	$double->params['decimals'] = 2;
    	
    	$currencyCode = acc_Periods::getBaseCurrencyCode($obj->date);
    	$row->quantity = "<span style='float:right'>" . $double->toVerbal($obj->quantity) . "</span>";
    	if($obj->action == 'sale') {
    		
    		// Ако детайла е продажба
    		$row->ROW_ATTR['class'] = 'report-sale';
    		$info = cat_Products::getProductInfo($obj->value, $obj->pack);
    		$row->pack = ($obj->pack) ? cat_Packagings::getTitleById($obj->pack) : cat_UoM::getTitleById($info->productRec->measureId);
    		$row->value = cat_Products::getHyperlink($obj->value, TRUE);
    		$obj->amount *= 1 + $obj->param;
    	} else {
    		
    		// Ако детайла е плащане
    		$row->pack = $currencyCode;
    		$value = ($obj->value != -1) ? cond_Payments::getTitleById($obj->value) : tr('В брой');
    		$row->value = tr("Плащания") . ": &nbsp;<i>{$value}</i>";
    		$row->ROW_ATTR['class'] = 'report-payment';
    		unset($row->quantity);
    	}
    	
    	$row->value = "<span style='white-space:nowrap;'>{$row->value}</span>";
    	$row->amount = "<span style='float:right'>" . $double->toVerbal($obj->amount) . "</span>"; 
    	
    	return $row;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public function getDocumentRow($id)
    {
    	$rec           = $this->fetch($id);
    	$title         = "Отчет за POS продажба №{$rec->id}";
        $row           = new stdClass();
        $row->title    = $title;
        $row->authorId = $rec->createdBy;
        $row->author   = $this->getVerbal($rec, 'createdBy');
        $row->state    = $rec->state;
		$row->recTitle = $title;
		
        return $row;
    }
    
    
    /**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    public static function getHandle($id)
    {
    	$rec = static::fetch($id);
    	$self = cls::get(get_called_class());
    	
    	return $self->abbr . $rec->id;
    }
    
    
    /**
     * Подготвя информацията за направените продажби и плащания
     * от всички бележки за даден период от време на даден потребител
     * на дадена точка
     * @param int $pointId - Ид на точката на продажба
     * @return array $result - масив с резултати
     * */
    private function fetchData($pointId)
    {
    	$details = $receipts = array();
    	$query = pos_Receipts::getQuery();
    	$query->where("#pointId = {$pointId}");
    	$query->where("#state = 'active'");
    	
    	// извличаме нужната информация за продажбите и плащанията
    	$this->fetchReceiptData($query, $details, $receipts);
    	
    	return array('receipts' => $receipts, 'receiptDetails' => $details);
    }
    
    
    /**
     * Връща продажбите и плащанията направени в търсените бележки групирани
     * @param core_Query $query - Заявка към модела
     * @param array $results - Масив в който ще връщаме резултатите
     * @param array $receipts - Масив от бележките които сме обходили
     */
    private function fetchReceiptData($query, &$results, &$receipts)
    {
    	while($rec = $query->fetch()) {
	    	
    		// запомняме кои бележки сме обиколили
    		$receipts[] = (object)array('id' => $rec->id);
    		
    		// Добавяме детайлите на бележката
	    	$data = pos_ReceiptDetails::fetchReportData($rec->id);
	    	foreach($data as $obj){
	    		$index = implode('|', array($obj->action, $obj->pack, $obj->contragentClassId, $obj->contragentId, $obj->value));
	    		if (!array_key_exists($index, $results)) {
	    			$results[$index] = $obj;
	    		} else {
	    			$results[$index]->quantity += $obj->quantity; 
	    			$results[$index]->amount += $obj->amount;
	    		}
	    	}
    	}
    }
	
	
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     * 
     * @param core_Manager $mvc
     * @param stdClass $rec
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if($rec->state == 'active'){
    		
    		// Ако няма записани детайли извличаме актуалните
    		if(!$rec->details){
    			$mvc->extractData($rec);
    		}
    	}
    	
    	if(empty($rec->id)){
    		$rec->isContable = 'yes';
    	}
    }
    
    
    /**
     * След създаване автоматично да се контира
     */
    public static function on_AfterCreate($mvc, $rec)
    {
    	// Контираме документа
    	$mvc->conto($rec);
    	
    	// Еднократно оттегляме всички празни чернови бележки
    	$mvc->rejectEmptyReceipts($rec->pointId);
    }
    
    
    /**
     * Оттегля всички празни чернови бележки в дадена точка от даден касиер
     * 
     * @param int $pointId - ид на точка
     */
    private function rejectEmptyReceipts($pointId)
    {
    	$rQuery = pos_Receipts::getQuery();
    	$rQuery->where("#pointId = {$pointId} AND #state = 'draft' AND #total = 0");
    	$count = $rQuery->count();
    	while($rRec = $rQuery->fetch()){
    		pos_Receipts::reject($rRec);
    	}
    	
    	core_Statuses::newStatus("|Оттеглени са|* {$count} |празни бележки|*");
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int $id първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    public static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
    	if($rec->state != 'draft'){
    		if($rec->state == 'active'){
    			$nextState = 'closed';
    			$msg = 'Приключени';
    		} else {
    			$nextState = 'active';
    			$msg = 'Активирани';
    		}
    		
    		// Всяка бележка в репорта се "затваря"
    		foreach($rec->details['receipts'] as $receiptRec){
    			$receiptRec->state = $nextState;
    			pos_Receipts::save($receiptRec);
    			$count++;
    		}
    		
    		core_Statuses::newStatus(tr("|{$msg} са|* '{$count}' |бележки за продажба|*"));
    	}
    }
    
    
    /**
     * След обработка на ролите
     */
	protected static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
	{
		// Никой неможе да редактира бележка
		if($action == 'activate' && !$rec) {
			$res = 'no_one';
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
        $cover = doc_Folders::getCover($folderId);
        
        return $cover->className == 'doc_UnsortedFolders' || $cover->className == 'pos_Points';
    }
    
    
	/**
     * Метод по подразбиране
     * Връща иконата на документа
     */
    public static function on_AfterGetIcon($mvc, &$res, $id = NULL)
    {
        if(!$res) { 
            $res = $mvc->singleIcon;
        }
    }
    
    
    /**
     * Перо в номенклатурите, съответстващо на този продукт
     *
     * Част от интерфейса: acc_RegisterIntf
     */
    public static function getItemRec($objectId)
    {
    	$result = NULL;
    	 
    	if ($rec = self::fetch($objectId)) {
    		$result = (object)array(
    				'num' => $objectId,
    				'title' => static::getRecTitle($rec),
    		);
    	}
    	
    	return $result;
    }
     
     
    /**
     * @see acc_RegisterIntf::itemInUse()
     * @param int $objectId
     */
    public static function itemInUse($objectId)
    {
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(__CLASS__);
    
    	return "{$self->singleTitle} №{$rec->id}";
    }
    
    
    /**
     * Проверява можели да се създаде отчет за този клиент. За създаване трябва
     * да е изпълнено:
     * 	1. Да има поне една активна (приключена) бележка за касиера и точката
     *  2. Да няма нито една започната, но неприключена бележка
     * 
     * @param int $pointId - ид на точка
     * @return boolean
     */
    public static function canMakeReport($pointId)
    {
    	
    	// Ако няма нито една активна бележка за посочената каса и касиер, не може да се създаде отчет
    	if(!pos_Receipts::fetch("#pointId = {$pointId} AND #state = 'active'")){
    		
    		return FALSE;
    	}
    	
    	// Ако има неприключена започната бележка в тачката от касиера, също не може да се направи отчет
    	if(pos_Receipts::fetch("#pointId = {$pointId} AND #total != 0 AND #state = 'draft'")){
    		
    		return FALSE;
    	}
    	
    	return TRUE;
    }
}
