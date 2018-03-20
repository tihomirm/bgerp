<?php

/**
 * Мениджър на отчети за неплатени фактури по клиент
 *
 * @category  bgerp
 * @package   acc
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Счетоводство » Неплатени фактури по клиент
 */
class acc_reports_UnpaidInvoices extends frame2_driver_TableData
{
    
    // deals_Helper::getInvoicePayments($threadId)
    
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,acc';

    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;

    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'className';

    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     * @var varchar
     */
    protected $hashField;

    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var varchar
     */
    protected $newFieldToCheck;

    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields;

    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset            
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('contragent', 
            'key2(mvc=doc_Folders,select=title,allowEmpty, restrictViewAccess=yes,coverInterface=crm_ContragentAccRegIntf)', 
            'caption=Контрагент,mandatory,single=none,after=title');
        $fieldset->FLD('checkDate', 'date', 'caption=Към дата,after=contragent,mandatory');
        
        $fieldset->FLD('salesTotalNotPaid', 'double', 'input=none,single=none');
        $fieldset->FLD('salesTotalOverDue', 'double', 'input=none,single=none');
        $fieldset->FLD('purchaseTotalNotPaid', 'double', 'input=none,single=none');
        $fieldset->FLD('purchaseTotalOverDue', 'double', 'input=none,single=none');
    }

    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *            $Driver
     * @param embed_Manager $Embedder            
     * @param stdClass $data            
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        $checkDate = dt::today();
        $form->setDefault('checkDate', "{$checkDate}");
    }

    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec            
     * @param stdClass $data            
     * @return array
     */
    protected function prepareRecs($rec, &$data = NULL)
    {
        $recs = array();
        $isRec = array();
        
        // Масив със записи от изходящи фактури
        $sRecs = array();
        
        $sQuery = sales_Invoices::getQuery();
        
        $sQuery->where("#state != 'rejected'");
        
        $sQuery->where(array(
            "#createdOn < '[#1#]'",
            $rec->checkDate . ' 23:59:59'
        ));
        
        if ($rec->contragent) {
            
            $sQuery->where("#folderId = {$rec->contragent}");
        }
        
        while ($salesInvoices = $sQuery->fetch()) {
            
            if (sales_Sales::fetch(doc_Threads::getFirstDocument($salesInvoices->threadId)->that)->state == 'closed') {
                
                if (sales_Sales::fetch(doc_Threads::getFirstDocument($salesInvoices->threadId)->that)->closedOn >=
                     $rec->checkDate) {
                    
                    $threadsId[$salesInvoices->threadId] = $salesInvoices->threadId;
                    continue;
                }
                
                continue;
            }
            
            $threadsId[$salesInvoices->threadId] = $salesInvoices->threadId;
        }
        
        $salesTotalNotPaid = 0;
        $salesTotalOverDue = 0;
        
        if (is_array($threadsId)) {
            foreach ($threadsId as $thread) {
                
                // масив от фактури в тази нишка //
                $invoicesInThread = (deals_Helper::getInvoicesInThread($thread, $rec->checkDate, TRUE, TRUE, TRUE));
                
                $invoicePayments = (deals_Helper::getInvoicePayments($thread, $rec->checkDate));
                
                if (is_array($invoicePayments)) {
                    
                    // фактура от нишката и масив от платежни документи по тази фактура//
                    foreach ($invoicePayments as $inv => $paydocs) {
                        
                        if ($paydocs->notPaid <= 0)
                            continue;
                        
                        $Invoice = doc_Containers::getDocument($inv);
                        
                        if ($Invoice->className != 'sales_Invoices')
                            continue;
                        
                        $iRec = $Invoice->fetch(
                            'id,number,dealValue,discountAmount,vatAmount,rate,type,originId,containerId,currencyId,date,dueDate');
                        
                        $salesTotalNotPaid += $paydocs->notPaid;
                        
                        if ($iRec->dueDate && $paydocs->total > 0 && $iRec->dueDate < $rec->checkDate) {
                            
                            $salesTotalOverDue += $paydocs->notPaid;
                        }
                        // масива с фактурите за показване
                        if (! array_key_exists($iRec->id, $sRecs)) {
                            
                            $sRecs[$iRec->id] = (object) array(
                                'threadId' => $thread,
                                'className' => $Invoice->className,
                                'invoiceId' => $iRec->id,
                                'invoiceNo' => $iRec->number,
                                'invoiceDate' => $iRec->date,
                                'dueDate' => $iRec->dueDate,
                                'invoiceContainerId' => $iRec->containerId,
                                'currencyId' => $iRec->currencyId,
                                'rate' => $iRec->rate,
                                'invoiceValue' => $paydocs->total,
                                'invoiceVAT' => $iRec->vatAmount,
                                'invoiceCurrentSumm' => $paydocs->notPaid,
                                'payDocuments' => $paydocs->payments
                            );
                        }
                    }
                }
            }
        }
        
        // Масив със записи от входящи фактури
        $pRecs = array();
        $iRec = array();
        
        $pQuery = purchase_Purchases::getQuery();
        
        $pQuery->where("#state != 'rejected'");
        
        $pQuery->where(array(
            "#createdOn < '[#1#]'",
            $rec->checkDate . ' 23:59:59'
        ));
        
        if ($rec->contragent) {
            
            $pQuery->where("#folderId = {$rec->contragent}");
        }
        
        while ($purchaseInvoices = $pQuery->fetch()) {
            
            if (purchase_Purchases::fetch(doc_Threads::getFirstDocument($purchaseInvoices->threadId)->that)->state ==
                 'closed') {
                
                if (purchase_Purchases::fetch(doc_Threads::getFirstDocument($purchaseInvoices->threadId)->that)->closedOn >=
                 $rec->checkDate) {
                
                $threadsId[$purchaseInvoices->threadId] = $purchaseInvoices->threadId;
                continue;
            }
            
            continue;
        }
        
        $threadsId[$purchaseInvoices->threadId] = $purchaseInvoices->threadId;
    }
    
    $purchaseTotalNotPaid = 0;
    $purchaseTotalOverDue = 0;
    
    if (is_array($threadsId)) {
        foreach ($threadsId as $thread) {
            
            // масив от фактури в тази нишка //
            $invoicesInThread = (deals_Helper::getInvoicesInThread($thread, $rec->checkDate, TRUE, TRUE, TRUE));
            
            $invoicePayments = (deals_Helper::getInvoicePayments($thread, $rec->checkDate));
            
            if (is_array($invoicePayments)) {
                
                // фактура от нишката и масив от платежни документи по тази фактура//
                foreach ($invoicePayments as $inv => $paydocs) {
                    
                    if ($paydocs->notPaid <= 0)
                        continue;
                    
                    $Invoice = doc_Containers::getDocument($inv);
                    
                    if ($Invoice->className != 'purchase_Invoices')
                        continue;
                    
                    $iRec = $Invoice->fetch(
                        'id,number,dealValue,discountAmount,vatAmount,rate,type,originId,containerId,currencyId,date,dueDate');
                    
                    $purchaseTotalNotPaid += $paydocs->notPaid;
                    
                    if ($iRec->dueDate && $paydocs->total > 0 && $iRec->dueDate < $rec->checkDate) {
                        
                        $purchaseTotalOverDue += $paydocs->notPaid;
                    }
                    // масива с фактурите за показване
                    if (! array_key_exists($iRec->id, $pRecs)) {
                        
                        $pRecs[$iRec->id] = (object) array(
                            'threadId' => $thread,
                            'className' => $Invoice->className,
                            'invoiceId' => $iRec->id,
                            'invoiceNo' => $iRec->number,
                            'invoiceDate' => $iRec->date,
                            'dueDate' => $iRec->dueDate,
                            'invoiceContainerId' => $iRec->containerId,
                            'currencyId' => $iRec->currencyId,
                            'rate' => $iRec->rate,
                            'invoiceValue' => $paydocs->total,
                            'invoiceVAT' => $iRec->vatAmount,
                            'invoiceCurrentSumm' => $paydocs->notPaid,
                            'payDocuments' => $paydocs->payments
                        );
                    }
                }
            }
        }
    }
    
    $rec->salesTotalNotPaid = $salesTotalNotPaid;
    
    $rec->salesTotalOverDue = $salesTotalOverDue;
    
    $rec->purchaseTotalNotPaid = $purchaseTotalNotPaid;
    
    $rec->purchaseTotalOverDue = $purchaseTotalOverDue;
    
    if (count($sRecs)) {
        
        arr::natOrder($sRecs, 'invoiceDate');
    }
    
    if (count($pRecs)) {
        
        arr::natOrder($sRecs, 'invoiceDate');
    }
    
    $recs = $sRecs + $pRecs;
    
    return $recs;
}

/**
 * Връща фийлдсета на таблицата, която ще се рендира
 *
 * @param stdClass $rec
 *            - записа
 * @param boolean $export
 *            - таблицата за експорт ли е
 * @return core_FieldSet - полетата
 */
protected function getTableFieldSet($rec, $export = FALSE)
{
    $fld = cls::get('core_FieldSet');
    
    if ($export === FALSE) {
        
        $fld->FLD('invoiceNo', 'varchar', 'caption=Фактура No,smartCenter');
        $fld->FLD('invoiceDate', 'varchar', 'caption=Дата');
        $fld->FLD('dueDate', 'varchar', 'caption=Краен срок');
        $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
        $fld->FLD('invoiceValue', 'double(smartRound,decimals=2)', 'caption=Стойност');
        $fld->FLD('paidAmount', 'double(smartRound,decimals=2)', 'caption=Платено->сума');
        $fld->FLD('paidDates', 'varchar', 'caption=Платено->плащания,smartCenter');
        $fld->FLD('invoiceCurrentSumm', 'double(smartRound,decimals=2)', 'caption=Остатък');
    } else {
        
        $fld->FLD('invoiceNo', 'varchar', 'caption=Фактура No,smartCenter');
        $fld->FLD('invoiceDate', 'date', 'caption=Дата,smartCenter');
        $fld->FLD('dueDate', 'date', 'caption=Краен срок,smartCenter');
        $fld->FLD('dueDateStatus', 'varchar', 'caption=Състояние,smartCenter');
        $fld->FLD('currencyId', 'varchar', 'caption=Валута,tdClass=centered');
        $fld->FLD('invoiceValue', 'double(smartRound,decimals=2)', 'caption=Стойност');
        $fld->FLD('paidAmount', 'double(smartRound,decimals=2)', 'caption=Платено->сума');
        $fld->FLD('paidDates', 'varchar', 'caption=Платено->плащания,smartCenter');
        $fld->FLD('invoiceCurrentSumm', 'double(smartRound,decimals=2)', 'caption=Остатък');
    }
    return $fld;
}

/**
 * Връща платена сума
 *
 * @param stdClass $dRec            
 * @param boolean $verbal            
 * @return mixed $paidAmount
 */
private static function getPaidAmount($dRec, $verbal = TRUE)
{
    foreach ($dRec->payDocuments as $v) {
        
        $paidAmount += $v->amount;
    }
    
    return $paidAmount;
}

/**
 * Връща дати на плащания
 *
 * @param stdClass $dRec            
 * @param boolean $verbal            
 * @return mixed $paidDates
 */
private static function getPaidDates($dRec, $verbal = TRUE)
{
    foreach ($dRec->payDocuments as $onePayDoc) {
        
        $Document = doc_Containers::getDocument($onePayDoc->containerId);
        
        $payDocClass = $Document->className;
        
        $paidDatesList .= "," . $payDocClass::fetch($Document->that)->valior;
    }
    
    if ($verbal === TRUE) {
        
        $amountsValiors = explode(",", trim($paidDatesList, ','));
        
        foreach ($amountsValiors as $v) {
            
            $paidDate = dt::mysql2verbal($v, $mask = "d.m.y");
            
            $paidDates .= "$paidDate" . "<br>";
        }
    } else {
        $amountsValiors = explode(",", trim($paidDatesList, ','));
        
        foreach ($amountsValiors as $v) {
            
            $paidDate = dt::mysql2verbal($v, $mask = "d.m.y");
            
            $paidDates .= "$paidDate" . "\n\r";
        }
    }
    return $paidDates;
}

/**
 * Връща просрочие на плащане
 *
 * @param stdClass $dRec            
 * @param boolean $verbal            
 * @return mixed $dueDate
 */
private static function getDueDate($dRec, $verbal = TRUE, $rec)
{
    if ($verbal === TRUE) {
        
        if ($dRec->dueDate) {
            $dueDate = dt::mysql2verbal($dRec->dueDate, $mask = "d.m.y");
            
            if ($dRec->dueDate && $dRec->invoiceCurrentSumm > 0 && $dRec->dueDate < $rec->checkDate) {
                
                $dueDate = ht::createHint($dueDate, 'фактурата е просрочена', 'warning');
            }
        } else {
            $dueDate = '';
        }
    } else {
        
        if ($dRec->dueDate) {
            $dueDate = dt::mysql2verbal($dRec->dueDate, $mask = "d.m.y");
            
            // if ($dRec->dueDate && $dRec->invoiceCurrentSumm > 0 && $dRec->dueDate < $rec->checkDate) {
            
            // $dueDate .= ' *';
            // }
        } else {
            $dueDate = '';
        }
    }
    
    return $dueDate;
}

/**
 * Вербализиране на редовете, които ще се показват на текущата страница в отчета
 *
 * @param stdClass $rec
 *            - записа
 * @param stdClass $dRec
 *            - чистия запис
 * @return stdClass $row - вербалния запис
 */
protected function detailRecToVerbal($rec, &$dRec)
{
    $isPlain = Mode::is('text', 'plain');
    $Int = cls::get('type_Int');
    $Date = cls::get('type_Date');
    
    $row = new stdClass();
    
    $invoiceNo = str_pad($dRec->invoiceNo, 10, "0", STR_PAD_LEFT);
    
    $row->invoiceNo = ht::createLinkRef($invoiceNo, 
        array(
            $dRec->className,
            'single',
            $dRec->invoiceId
        ));
    
    $row->invoiceDate = $Date->toVerbal($dRec->invoiceDate);
    
    $row->dueDate = self::getDueDate($dRec, TRUE, $rec);
    
    $row->currencyId = $dRec->currencyId;
    
    $invoiceValue = $dRec->invoiceValue + $dRec->invoiceVat;
    
    $row->invoiceValue = core_Type::getByName('double(decimals=2)')->toVerbal($invoiceValue);
    
    $row->invoiceCurrentSumm = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->invoiceCurrentSumm);
    
    $row->paidAmount = core_Type::getByName('double(decimals=2)')->toVerbal(self::getPaidAmount($dRec));
    
    $row->paidDates = "<span class= 'small'>" . self::getPaidDates($dRec, TRUE) . "</span>";
    
    if ($dRec->dueDate && $dRec->invoiceCurrentSumm > 0 && $dRec->dueDate < $rec->checkDate) {
        
        $row->ROW_ATTR['class'] = 'bold red state-active';
    }
    
    if ($dRec->className == 'sales_Invoices') {
        $row->className = 'Фактури ПРОДАЖБИ';
    } else {
        $row->className = 'Фактури ПОКУПКИ';
    }
    
    return $row;
}

/**
 * След рендиране на единичния изглед
 *
 * @param cat_ProductDriver $Driver            
 * @param embed_Manager $Embedder            
 * @param core_ET $tpl            
 * @param stdClass $data            
 */
protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
{
    $fieldTpl = new core_ET(
        tr(
            "|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
                                <small><div><!--ET_BEGIN contragent-->|Контрагент|*: [#contragent#]<!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN salesTotalNotPaid-->|Обща стойност на НЕПЛАТЕНИТЕ задължения по фактури ПРОДАЖБИ|*: [#salesTotalNotPaid#]<!--ET_END from--></div></small>
                                <small><div><!--ET_BEGIN salesTotalOverDue-->|Обща стойност на ПРОСРОЧЕНИТЕ задължения по фактури ПРОДАЖБИ|*: [#salesTotalOverDue#]<!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN purchaseTotalNotPaid-->|Обща стойност на НЕПЛАТЕНИТЕ задължения по фактури ПОКУПКИ|*: [#purchaseTotalNotPaid#]<!--ET_END from--></div></small>
                                <small><div><!--ET_BEGIN purchaseTotalOverDue-->|Обща стойност на ПРОСРОЧЕНИТЕ задължения по фактури ПОКУПКИ|*: [#purchaseTotalOverDue#]<!--ET_END to--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
    
    if (isset($data->rec->contragent)) {
        $fieldTpl->append(doc_Folders::fetch($data->rec->contragent)->title, 'contragent');
    } else {
        $fieldTpl->append('Всички', 'contragent');
    }
    
    if (isset($data->rec->salesTotalNotPaid)) {
        $fieldTpl->append(core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->salesTotalNotPaid), 
            'salesTotalNotPaid');
    }
    
    if (isset($data->rec->salesTotalOverDue)) {
        $fieldTpl->append(core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->salesTotalOverDue), 
            'salesTotalOverDue');
    }
    
    if (isset($data->rec->purchaseTotalNotPaid)) {
        $fieldTpl->append(core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->purchaseTotalNotPaid), 
            'purchaseTotalNotPaid');
    }
    
    if (isset($data->rec->purchaseTotalOverDue)) {
        $fieldTpl->append(core_Type::getByName('double(decimals=2)')->toVerbal($data->rec->purchaseTotalOverDue), 
            'purchaseTotalOverDue');
    }
    
    $tpl->append($fieldTpl, 'DRIVER_FIELDS');
}

/**
 * След подготовка на реда за експорт
 *
 * @param frame2_driver_Proto $Driver            
 * @param stdClass $res            
 * @param stdClass $rec            
 * @param stdClass $dRec            
 */
protected static function on_AfterGetCsvRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec)
{
    $res->paidAmount = core_Type::getByName('double(decimals=2)')->toVerbal(self::getPaidAmount($dRec));
    
    $res->paidDates = self::getPaidDates($dRec, FALSE);
    
    $res->dueDate = self::getDueDate($dRec, FALSE, $rec);
    
    if ($dRec->dueDate && $dRec->invoiceCurrentSumm > 0 && $dRec->dueDate < $rec->checkDate) {
        
        $res->dueDateStatus = 'Просрочен';
    }
    
    $invoiceNo = str_pad($dRec->invoiceNo, 10, "0", STR_PAD_LEFT);
    
    $res->invoiceNo = $invoiceNo;
}
}



