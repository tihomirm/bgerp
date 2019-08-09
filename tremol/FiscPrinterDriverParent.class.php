<?php


/**
 *
 *
 * @category  bgerp
 * @package   tremol
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class tremol_FiscPrinterDriverParent extends peripheral_DeviceDriver
{
    public $interfaces = 'peripheral_FiscPrinterIntf';
    
    public $title = 'ФУ на Тремол';
    
    public $canCashReceived = 'admin, peripheral, cashMaster, posMaster';
    
    public $canCashPaidOut = 'admin, peripheral, cashMaster, posMaster';
    
    public $canMakeReport = 'admin, peripheral, cashMaster, posMaster';
    
    protected $rcpNumPattern = '/^[a-z0-9]{8}-[a-z0-9]{4}-[0-9]{7}$/i';
    
    
    /**
     * Максимална дължина за касовите апарати
     * @var integer
     */
    protected $crLen = 32;
    
    
    /**
     * Максимална дължина за фискалните принтери
     * @var integer
     */
    protected $fpLen = 48;
    
    
    /**
     * Максимална дължина за фискалните принтери - за име на артикул
     * @var integer
     */
    protected $fpPluNameLen = 34;
    
    
    /**
     * "Маргин" при печатане на текст - по един # в началото и в края
     * @var integer
     */
    protected $mLen = 2;
    
    
    /**
     * Дефолтни кодове на начините на плащане
     */
    const DEFAULT_PAYMENT_MAP = array('Брой'       => 0,
                                      'Чек'        => 1,
                                      'Талон'      => 2,
                                      'В.Талон'    => 3,
                                      'Амбалаж'    => 4,
                                      'Обслужване' => 5,
                                      'Повреди'    => 6,
                                      'Карта'      => 7,
                                      'Банка'      => 8);
    
    
    /**
     * Дефолтни кодове на начините на плащане
     */
    const DEFAULT_STORNO_REASONS_MAP = array('Операторска грешка' => 0,
                                             'Връщане/Рекламация' => 1,
                                             'Данъчно облекчение' => 2,);
    
    
    /**
     * Дефолтни кодове за ДДС групите
     */
    const DEFAULT_VAT_GROUPS_MAP = array('A' => 0,
                                         'B' => 1,
                                         'V' => 2,
                                         'G' => 3,);
    
    
    /**
     * Помощна функция при вкарване/изкарване на средства от ФУ
     * 
     * @param stdClass $pRec
     * @param integer $operator
     * @param integer $operPass
     * @param double $amount
     * @param array $retUrl
     * @param boolean $printAvailability
     * @param string $text
     * @param string $actTypeVerb
     * @param null|core_Et $jsTpl
     */
    abstract protected function getResForCashReceivedOrPaidOut($pRec, $operator, $operPass, $amount, $retUrl = array(), $printAvailability = false, $text = '', $actTypeVerb = '', &$jsTpl = null);
    
    
    /**
     * Помощна фунцкция при отпечаване на отчет
     * 
     * @param stdClass $pRec
     * @param stdClass $rec
     * @param string $rVerb
     * @param null|core_Et $jsTpl
     */
    abstract protected function getResForReport($pRec, $rec, $rVerb = '', &$jsTpl = null);
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('serverIp', 'ip', 'caption=Настройки за връзка със ZFPLAB сървър->IP адрес, mandatory');
        $fieldset->FLD('serverTcpPort', 'int', 'caption=Настройки за връзка със ZFPLAB сървър->TCP порт, mandatory');
        
        $fieldset->FLD('driverVersion', 'enum(19.07.25,19.06.13)', 'caption=Настройки на ФУ->Версия, mandatory, notNull');
        $fieldset->FLD('fpType', 'enum(cashRegister=Касов апарат, fiscalPrinter=Фискален принтер)', 'caption=Настройки на ФУ->Тип, mandatory, notNull');
        $fieldset->FLD('serialNumber', 'varchar(8)', 'caption=Настройки на ФУ->Сериен номер');
        
        $fieldset->FLD('type', 'enum(tcp=TCP връзка, serial=Сериен порт)', 'caption=Настройки за връзка с ФУ->Връзка, mandatory, notNull, removeAndRefreshForm=tcpIp|tcpPort|tcpPass|serialPort|serialSpeed');
        $fieldset->FLD('tcpIp', 'ip', 'caption=Настройки за връзка с ФУ->IP адрес, mandatory');
        $fieldset->FLD('tcpPort', 'int', 'caption=Настройки за връзка с ФУ->Порт, mandatory');
        $fieldset->FLD('tcpPass', 'password', 'caption=Настройки за връзка с ФУ->Парола, mandatory');
        
        $fieldset->FLD('serialPort', 'varchar', 'caption=Настройки за връзка с ФУ->Порт, mandatory');
        $fieldset->FLD('serialSpeed', 'int', 'caption=Настройки за връзка с ФУ->Скорост, mandatory');
        
        if ($fieldset instanceof core_Form) {
            $fieldset->input('type');
            
            if ($fieldset->rec->type != 'serial') {
                $fieldset->setField('serialPort', 'input=none');
                $fieldset->setField('serialSpeed', 'input=none');
            } else {
                $fieldset->setField('tcpIp', 'input=none');
                $fieldset->setField('tcpPort', 'input=none');
                $fieldset->setField('tcpPass', 'input=none');
            }
        }
        
        $fieldset->FLD('paymentMap9', 'varchar', 'caption=Настройки на апарата за плащания->Позиция 9');
        $fieldset->FLD('paymentMap10', 'varchar', 'caption=Настройки на апарата за плащания->Позиция 10');
        $fieldset->FLD('paymentMap11', 'varchar', 'caption=Настройки на апарата за плащания->Позиция 11');
        
        $fieldset->FLD('header', 'enum(yes=Да,no=Не)', 'caption=Надпис хедър в касовата бележка->Добавяне, mandatory, notNull, removeAndRefreshForm');
        $fieldset->FLD('headerPos', 'enum(center=Центрирано,left=Ляво,right=Дясно)', 'caption=Надпис хедър в касовата бележка->Позиция, mandatory, notNull');
        $fieldset->FLD('headerText1', "varchar({$this->fpLen})", 'caption=Надпис хедър в касовата бележка->Текст 1');
        $fieldset->FLD('headerText2', "varchar({$this->fpLen})", 'caption=Надпис хедър в касовата бележка->Текст 2');
        $fieldset->FLD('headerText3', "varchar({$this->fpLen})", 'caption=Надпис хедър в касовата бележка->Текст 3');
        $fieldset->FLD('headerText4', "varchar({$this->fpLen})", 'caption=Надпис хедър в касовата бележка->Текст 4');
        $fieldset->FLD('headerText5', "varchar({$this->fpLen})", 'caption=Надпис хедър в касовата бележка->Текст 5');
        $fieldset->FLD('headerText6', "varchar({$this->fpLen})", 'caption=Надпис хедър в касовата бележка->Текст 6');
        $fieldset->FLD('headerText7', "varchar({$this->fpLen})", 'caption=Надпис хедър в касовата бележка->Текст 7');
        if ($fieldset instanceof core_Form) {
            $fieldset->input('header');
            if ($fieldset->rec->header == 'no') {
                $fieldset->setField('headerText1', 'input=none');
                $fieldset->setField('headerText2', 'input=none');
                $fieldset->setField('headerText3', 'input=none');
                $fieldset->setField('headerText4', 'input=none');
                $fieldset->setField('headerText5', 'input=none');
                $fieldset->setField('headerText6', 'input=none');
                $fieldset->setField('headerText7', 'input=none');
                $fieldset->setField('headerPos', 'input=none');
            }
        }
        
        $fieldset->FLD('footer', 'enum(yes=Да, no=Не)', 'caption=Надпис футър в касовата бележка->Добавяне, mandatory, notNull, removeAndRefreshForm');
        $fieldset->FLD('footerPos', 'enum(center=Центрирано,left=Ляво,right=Дясно)', 'caption=Надпис футър в касовата бележка->Позиция, mandatory, notNull');
        $fieldset->FLD('footerText', "varchar({$this->fpLen})", 'caption=Надпис футър в касовата бележка->Текст');
        if ($fieldset instanceof core_Form) {
            $fieldset->input('footer');
            if ($fieldset->rec->footer == 'no') {
                $fieldset->setField('footerText', 'input=none');
                $fieldset->setField('footerPos', 'input=none');
            }
        }
    }
    
    
    /**
     * Може ли вградения обект да се избере
     *
     * @param NULL|int $userId
     *
     * @return bool
     */
    public function canSelectDriver($userId = null)
    {
        return false;
    }
    
    
    /**
     * Помощна фунцкия за подготвяне на текста за печат
     *
     * @param array|string $tArr
     * @param integer       $maxLen
     * 
     * @return array
     */
    protected function parseTextToArr($tArr, $maxLen = 30)
    {
        $resStrArr = array();
        
        if (!is_array($tArr)) {
            $tArr = array($tArr);
        }
        
        $minLen = $maxLen - 5;
        
        foreach ($tArr as $tStr) {
            $tStr = hyphen_Plugin::getHyphenWord($tStr, $minLen, $maxLen, '<wbr>');
            
            $resStrArr = array_merge($resStrArr, explode('<wbr>', $tStr));
        }
        
        return $resStrArr;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param tremol_FiscPrinterDriverParent $Driver
     * @param peripheral_Devices          $Embedder
     * @param stdClass                    $data
     */
    protected static function on_AfterPrepareEditForm($Driver, $Embedder, &$data)
    {
        $form = &$data->form;
        
        $pQuery = cond_Payments::getQuery();
        $arrMap = array_flip(self::DEFAULT_PAYMENT_MAP);
        $oMapArr = array('' => '');
        
        while ($pRec = $pQuery->fetch()) {
            if (isset($pRec->code) && isset($arrMap[$pRec->code])) continue;
            
            if (!$pRec->currencyCode) continue;
            
            $oMapArr[$pRec->currencyCode] = $pRec->currencyCode;
        }
        $form->setOptions('paymentMap9', $oMapArr);
        $form->setOptions('paymentMap10', $oMapArr);
        $form->setOptions('paymentMap11', $oMapArr);
        
        if (!$form->rec->id) {
            $form->setDefault('footerText', 'Отпечатано с bgERP');
            $form->setDefault('serialSpeed', 115200);
            $form->setDefault('serverIp', '127.0.0.1');
            $form->setDefault('serverTcpPort', 4444);
            $form->setDefault('tcpPort', 8000);
            $form->setDefault('tcpPass', 1234);
        }
    }
    
    
    /**
     * Помощна функция за позициониране на текст - добавя интервали в началото
     *
     * @param string $text
     * @param string $pos
     * @param int    $maxLen
     *
     * @return string
     */
    protected static function formatText($text, $pos, $maxLen = 32)
    {
        $text = trim($text);
        
        if ($pos == 'right') {
            $l = mb_strlen($text);
            if ($maxLen > $l) {
                $text = str_repeat(' ', $maxLen - $l) . $text;
            }
        } elseif ($pos == 'center') {
            $l = mb_strlen($text);
            if ($maxLen > $l) {
                $text = str_repeat(' ', (int) (($maxLen - $l) / 2)) . $text;
            }
        }
        
        return $text;
    }
    
    
    /**
     *
     * @param tremol_FiscPrinterDriverParent $Driver
     * @param peripheral_Devices        $Embedder
     * @param object                    $data
     */
    public static function on_AfterPrepareRetUrl($Driver, $Embedder, &$data)
    {
        if ($data->form->cmd == 'save') {
            $data->retUrl = array($Embedder, 'single', $data->form->rec->id, 'update' => true);
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     *
     * @param tremol_FiscPrinterDriverParent $Driver
     * @param peripheral_Devices        $mvc
     * @param object                    $res
     * @param object                    $data
     */
    public static function on_AfterPrepareSingleToolbar($Driver, $mvc, &$res, $data)
    {
        if (haveRole($Driver->canMakeReport)) {
            $data->toolbar->addBtn('Отчети', array($Driver, 'Reports', 'pId' => $data->rec->id, 'ret_url' => true, 'rand' => str::getRand()), 'ef_icon = img/16/report.png, title=Отпечатване на отчети, row=2');
        }
        
        if (haveRole($Driver->canCashReceived) || haveRole($Driver->canCashPaidOut)) {
            $data->toolbar->addBtn('Средства', array($Driver, 'CashReceivedOrPaidOut', 'pId' => $data->rec->id, 'ret_url' => true, 'rand' => str::getRand()), 'ef_icon = img/16/money.png, title=Вкарване или изкарване на пари от касата, row=1');
        }
    }
    
    
    /**
     * Дали във ФУ има е нагласена подадената валута
     * 
     * @param stdClass $rec
     * @param string $currencyCode
     * @return boolean
     */
    public function isCurrencySupported($rec, $currencyCode)
    {
        if (!$currencyCode) {
            
            return false;
        }
        
        if ($currencyCode == 'BGN') {
            
            return true;
        }
        
        if ($currencyCode != 'BGN') {
            
            foreach(array(9,10,11) as $v) {
                $r = 'paymentMap' . $v;
                
                if ($rec->{$r} == $currencyCode) {
                    
                    return true;
                }
            }
        }
        
        return false;
    }
    
    
    /**
     * Какъв е кода на плащането в настройките на апарата
     *
     * @param stdClass $rec
     * @param int $paymentId
     * 
     * @return string|null
     */
    public function getPaymentCode($rec, $paymentId)
    {
        // Ако не е подаден код на плащане се приема, че е брой
        if(empty($paymentId)){
            
            return self::DEFAULT_PAYMENT_MAP['Брой'];
        }
        
        $pRec = cond_Payments::fetch($paymentId);
        if(!$pRec) {
            
            return;
        }
        
        $name = $pRec->title;
        if (!empty($name)) {
            if (array_key_exists($name, self::DEFAULT_PAYMENT_MAP)) {
                
                return self::DEFAULT_PAYMENT_MAP[$name];
            }
        }
        
        foreach(array(9,10,11) as $v) {
            $r = 'paymentMap' . $v;
            if ($pRec->currencyCode && $rec->{$r} == $pRec->currencyCode) {
                
                return $v;
            }
        }
    }
    
    
    /**
     * Какъв е кода на основанието за сторниране
     *
     * @param stdClass $rec - запис
     * @param string $reason   - основание
     * @return string|null  - намерения код или null, ако няма
     */
    public function getStornoReasonCode($rec, $reason)
    {
        return self::DEFAULT_STORNO_REASONS_MAP[$reason];
    }
    
    
    /**
     * Какви са разрешените основания за сторниране
     *
     * @param stdClass $rec - запис
     * @return array  - $res
     */
    public function getStornoReasons($rec)
    {
        $res = arr::make(array_keys(self::DEFAULT_STORNO_REASONS_MAP), true);
        
        return $res;
    }
    
    
    /**
     * Връща цената с ддс и приспадната отстъпка, подходяща за касовия апарат
     *
     * @param float      $priceWithoutVat
     * @param float      $vat
     * @param float|null $discountPercent
     *
     * @return float
     *
     * @see peripheral_FiscPrinterIntf
     */
    public function getDisplayPrice($priceWithoutVat, $vat, $discountPercent)
    {
        $displayPrice = $priceWithoutVat * (1 + $vat);
        
        if (!empty($discountPercent)) {
            $discountedPrice = round($displayPrice * $discountPercent, 2);
            $displayPrice = $displayPrice - $discountedPrice;
        }
        
        return $displayPrice;
    }
    
    
    /**
     * Какъв е кода отговарящ на ДДС групата на артикула
     *
     * @param int $groupId  - ид на ДДС група
     * @param stdClass $rec - запис
     * @return string|null  - намерения код или null, ако няма
     */
    public function getVatGroupCode($groupId, $rec)
    {
        $sysId = acc_VatGroups::fetchField($groupId, 'sysId');
        
        if (!isset($sysId)) {
            
            return ;
        }
        
        return self::DEFAULT_VAT_GROUPS_MAP[$sysId];
    }
    
    
    /**
     * Екшън за вкарване/изкараване на пари от касата
     *
     * @return core_ET
     */
    public function act_CashReceivedOrPaidOut()
    {
        $cancelBtn = 'Отказ';
        $canReceived = haveRole($this->canCashReceived);
        $canCashPaidOut = haveRole($this->canCashPaidOut);
        expect($canReceived || $canCashPaidOut);
        
        $pId = Request::get('pId', 'int');
        
        $pRec = peripheral_Devices::fetch($pId);
        
        expect($pRec);
        
        peripheral_Devices::requireRightFor('single', $pRec);
        
        $form = cls::get('core_Form');
        
        $enumStr = '';
        if ($canReceived) {
            $enumStr .= 'received=Захранване';
        }
        
        if ($canCashPaidOut) {
            $enumStr .= $enumStr ? ',' : '';
            $enumStr .= 'paidOut=Изплащане';
        }
        
        $form->FLD('type', "enum({$enumStr})", 'caption=Действие, mandatory, removeAndRefreshForm');
        
        $len = ($pRec->fpType == 'fiscalPrinter') ? $this->fpLen : $this->crLen;
        
        $len -= $this->mLen;
        
        $form->FLD('amount', 'double(min=0)', 'caption=Сума, mandatory');
        $form->FLD('text', "varchar({$len})", 'caption=Текст');
        $form->FLD('printAvailability', 'enum(yes=Да,no=Не)', 'caption=Отпечатване на->Наличност');
        
        $form->input();
        
        $rec = $form->rec;
        
        $jsTpl = null;
        
        $rand = Request::get('rand');
        
        $hash = md5(serialize($rec));
        
        $randStr = 'tremol_cashRAndP_' . $rand;
        
        // Защита от случайно повторно отпечатване
        if (($rVal = Mode::get($randStr)) && ($rVal == $hash)) {
            $form->setWarning('report', 'Това действие вече е извършено');
        }
        
        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $retUrl = array('peripheral_Devices', 'single', $pId);
        }
        
        if ($form->isSubmitted()) {
            Mode::setPermanent($randStr, $hash);
            
            $operator = 1;
            $operPass = '0';
            
            $amount = $rec->amount;
            if ($amount && $rec->type == 'paidOut') {
                $amount *= -1;
            }
            
            $printAvailability = $form->rec->printAvailability == 'yes' ? true : false;
            
            $actTypeVerb = $form->fields['type']->type->toVerbal($rec->type);
            $actTypeVerb = tr(mb_strtolower($actTypeVerb));
            
            $this->getResForCashReceivedOrPaidOut($pRec, $operator, $operPass, $amount, $retUrl, $printAvailability, $rec->text, $actTypeVerb, $jsTpl);
            
            $cancelBtn = 'Назад';
        }
        
        $submitTitle = 'Захранване';
        $submitIcon = 'img/16/money_add.png';
        if ($rec->type == 'paidOut') {
            $submitTitle = 'Изплащане';
            $submitIcon = 'img/16/money_delete.png';
        }
        
        $form->toolbar->addSbBtn($submitTitle, 'save', "ef_icon = {$submitIcon}");
        
        $form->title = 'Вкарване или изкарване на пари от касата|* ' . peripheral_Devices::getLinkToSingle($pRec->id, 'name');
        
        $form->toolbar->addBtn($cancelBtn, $retUrl, 'ef_icon = img/16/close-red.png');
        
        $html = $form->renderHtml();
        
        if ($jsTpl) {
            $html->appendOnce($jsTpl, 'SCRIPTS');
        }
        
        $tpl = cls::get('peripheral_Devices')->renderWrapping($html);
        
        core_Form::preventDoubleSubmission($tpl, $form);
        
        return $tpl;
    }
    
    
    /**
     * Екшън за отпечатване/записване на отчети
     *
     * @return core_ET
     */
    public function act_Reports()
    {
        expect(haveRole($this->canMakeReport));
        
        $submitTitle = 'Отпечатване';
        $closeBtnName = 'Отказ';
        
        $pId = Request::get('pId', 'int');
        
        $pRec = peripheral_Devices::fetch($pId);
        
        expect($pRec);
        
        peripheral_Devices::requireRightFor('single', $pRec);
        
        $form = cls::get('core_Form');
        
        $form->FLD('report', 'enum(day=Дневен,operator=Операторски (дневен),period=Период,month=Месечен,year=Годишен,klen=КЛЕН,csv=CSV)', 'caption=Отчет->Вид, mandatory, removeAndRefreshForm=zeroing,isDetailed,operNum,fromDate,toDate,flagReports,flagReceipts,csvFormat,printIn,saveType,printType');
        
        $form->input('report');
        
        $form->FLD('zeroing', 'enum(no=Не, yes=Да)', 'caption=Отчет->Нулиране, mandatory');
        $form->FLD('isDetailed', 'enum(no=Не, yes=Да)', 'caption=Отчет->Детайлен, mandatory');
        
        if ($form->rec->report == 'operator') {
            $form->FLD('operNum', 'int(min=0, max=20)', 'caption=Отчет->Оператор, mandatory');
            $form->setField('isDetailed', 'input=none');
            $form->setDefault('operNum', 1);
        } elseif (($form->rec->report == 'period') || ($form->rec->report == 'month') || ($form->rec->report == 'year') || ($form->rec->report == 'klen') || ($form->rec->report == 'csv')) {
            $form->FLD('fromDate', 'date', 'caption=Дата->От, mandatory');
            $form->FLD('toDate', 'date', 'caption=Дата->До, mandatory');
            
            if ($form->rec->report == 'period') {
                $form->setDefault('fromDate', date('d-m-Y', strtotime('this week')));
                $form->setDefault('toDate', dt::now(false));
            } elseif ($form->rec->report == 'month') {
                if (date('d') <= 20) {
                    $form->setDefault('fromDate', date('d-m-Y', strtotime('first day of previous month')));
                    $form->setDefault('toDate', date('d-m-Y', strtotime('last day of previous month')));
                } else {
                    $form->setDefault('fromDate', date('d-m-Y', strtotime('first day of this month')));
                    $form->setDefault('toDate', dt::now(false));
                }
            } elseif (($form->rec->report == 'year') || ($form->rec->report == 'klen') || ($form->rec->report == 'csv')) {
                $y = date('Y');
                if ((date('n') <= 11) && (($form->rec->report != 'klen') && ($form->rec->report != 'csv'))) {
                    $y--;
                    $form->setDefault('fromDate', date('d-m-Y', strtotime(date('01-01-' . $y))));
                    $form->setDefault('toDate', date('d-m-Y', strtotime(date('31-12-' . $y))));
                } else {
                    $form->setDefault('fromDate', date('d-m-Y', strtotime(date('01-01-' . $y))));
                    $form->setDefault('toDate', dt::now(false));
                }
                
                if (($form->rec->report == 'klen') || ($form->rec->report == 'csv')) {
                    $form->FLD('printType', 'enum(print=Отпечатване, save=Запис)', 'caption=Действие, mandatory, removeAndRefreshForm=saveType');
                    
                    $form->input('printType');
                    
                    if ($form->rec->printType == 'save') {
                        $form->FLD('saveType', 'enum(sd=SD карта, usb=USB)', 'caption=Запис в, mandatory');
                        
                        $submitTitle = 'Запис';
                    } else {
                        $form->FLD('printIn', 'enum(PC=Компютър, FP=Фискално устройство)', 'caption=Отпечатване в, mandatory');
                    }
                    
                    $form->setField('zeroing', 'input=none');
                    
                    if ($form->rec->report == 'csv') {
                        $form->setField('isDetailed', 'input=none');
                        $form->FLD('csvFormat', 'enum(yes=Да, no=Не)', 'caption=CSV формат, mandatory');
                        
                        $form->FLD('flagReceipts', 'int(min=0, max=7)', 'caption=Флаг->ФБ, mandatory');
                        $form->FLD('flagReports', 'int(min=0, max=7)', 'caption=Флаг->Отчет, mandatory');
                        
                        $form->setDefault('flagReceipts', 1);
                        $form->setDefault('flagReports', 1);
                        
                        if ($form->rec->printType != 'save') {
                            $form->setOptions('printIn', array('PC' => 'Компютър'));
                        }
                    }
                }
            }
        }
        
        $form->input();
        
        $rec = $form->rec;
        
        $jsTpl = null;
        
        if ($form->isSubmitted()) {
            if ($rec->zeroing == 'yes') {
                $form->setWarning('report, zeroing', 'Отчетът ще бъде нулиран');
            }
        }
        
        $rand = Request::get('rand');
        
        $hash = md5(serialize($rec));
        
        $randStr = 'tremol_reports_' . $rand;
        
        // Защита от случайно повторно отпечатване
        if (($rVal = Mode::get($randStr)) && ($rVal == $hash)) {
            $form->setWarning('report', 'Този отчет вече е отпечатан');
        }
        
        if ($form->isSubmitted()) {
            Mode::setPermanent($randStr, $hash);
            
            $rVerb = $form->getFieldType('report')->toVerbal($rec->report);
            $rVerb = mb_strtolower($rVerb);
            
            $jsTpl = null;
            
            $this->getResForReport($pRec, $rec, $rVerb, $jsTpl);
            
            $closeBtnName = 'Назад';
        } else {
            $form->toolbar->addSbBtn($submitTitle, 'save', 'ef_icon = img/16/print_go.png');
        }
        
        $form->title = 'Генериране на отчет в ФУ|* ' . peripheral_Devices::getLinkToSingle($pRec->id, 'name');
        
        $retUrl = getRetUrl();
        if (empty($retUrl)) {
            $retUrl = array('peripheral_Devices', 'single', $pId);
        }
        
        $form->toolbar->addBtn($closeBtnName, $retUrl, 'ef_icon = img/16/close-red.png');
        
        $html = $form->renderHtml();
        
        if ($jsTpl) {
            $html->appendOnce($jsTpl, 'SCRIPTS');
        }
        
        $tpl = cls::get('peripheral_Devices')->renderWrapping($html);
        
        core_Form::preventDoubleSubmission($tpl, $form);
        
        return $tpl;
    }
}