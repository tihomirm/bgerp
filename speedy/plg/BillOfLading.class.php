<?php


/**
 * Клас 'speedy_plg_BillOfLading' за изпращане на товарителница към SPEEDY
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class speedy_plg_BillOfLading extends core_Plugin
{
    /**
     * След дефиниране на полетата на модела
     *
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        setIfNot($mvc->canMakebilloflading, 'debug');//speedy,ceo
    }
    
    
    /**
     * Добавя бутони за контиране или сторниране към единичния изглед на документа
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        $rec = &$data->rec;
        
        // Бутон за Товарителница
        if ($mvc->haveRightFor('makebilloflading', $rec)) {
            $data->toolbar->addBtn('Speedy', array($mvc, 'makebilloflading', 'documentId' => $rec->id, 'ret_url' => true), "id=btnSpeedy", 'ef_icon = img/16/tick-circle-frame.png,title=Изпращане на товарителница към Speedy');
        }
    }
    
    
    /**
     * Преди изпълнението на контролерен екшън
     *
     * @param core_Manager $mvc
     * @param core_ET      $res
     * @param string       $action
     */
    public static function on_BeforeAction(core_Manager $mvc, &$res, $action)
    {
        if (strtolower($action) == 'makebilloflading') {
            $mvc->requireRightFor('makebilloflading');
            expect($id = Request::get('documentId', 'int'));
            expect($rec = $mvc->fetch($id));
            $mvc->requireRightFor('makebilloflading', $rec);
            
            // Адаптер към библиотеката на speedy
            $adapter = new speedy_Adapter();
            $connectResult = $adapter->connect();
            if($connectResult->success !== true){
                
                // Има ли връзка с тяхната услуга
                followRetUrl(null, $connectResult->errorMsg, 'error');
            }
            
            // Подготовка на формата
            $form = self::getBillOfLadingForm($mvc, $rec, $adapter);
            $form->FLD('senderAddress', 'varchar', 'after=senderName,caption=Данни за подател->Адрес,hint=Адресът е настроен в профика в Speedy');
            $senderAddress = $adapter->getSenderAddress();
            $form->setReadOnly('senderAddress', $senderAddress);
            
            $form->input();
            
            if($form->isSubmitted()){
                $fRec = $form->rec;
                if($fRec->isFragile == 'yes' && empty($fRec->amountInsurance)){
                    $form->setError('amountInsurance,isFragile', 'Чупливата папка, трябва да има обявена стойност');
                }
                
                if($fRec->isDocuments == 'yes' && !empty($fRec->amountInsurance)){
                    $form->setError('isDocuments,amountInsurance', 'Документите не може да имат обявена стойност');
                }
                
                if($fRec->isDocuments == 'yes' && $fRec->isPaletize == 'yes'){
                    $form->setError('isDocuments,isPaletize', 'Документите не могат да са на палети');
                }
                
                if(isset($fRec->amountInsurance) && $fRec->totalWeight > 32){
                    $form->setError('amountInsurance,totalWeight', 'Не може да има обявена стойност, на пратки с тегло над 32 кг');
                }
                
                if(!$form->gotErrors()){
                    
                    // Опит за създаване на товарителница
                    try{
                        $bolId = $adapter->getBol($form->rec);
                    } catch(ServerException $e){
                        $mvc->logErr("Проблем при генериране на товарителница", $id);
                        $msg = $adapter->handleException($e);
                        
                        $form->setError('senderPhone', $msg);
                    }
                }
                
                // Записване на товарителницата като PDF
                try{
                    $bolFh = $adapter->getBolPdf($bolId);
                    $fileId = fileman::fetchByFh($bolFh, 'id');
                    doc_Linked::add($rec->containerId, $fileId, 'doc', 'file', 'Товарителница');
                    
                } catch(ServerException $e){
                    reportException($e);
                    $mvc->logErr("Проблем при генериране на PDF на товарителница", $id);
                    
                    core_Statuses::newStatus('Проблем при генериране на PDF на товарителница', 'error');
                }
                
                if(!$form->gotErrors()){
                    followRetUrl(null, "Успешно генерирана товарителница|*: №{$bolId}");
                }
            }
            
            $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png, title = Изпращане на товарителницата');
            $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
            
            // Записваме, че потребителя е разглеждал този списък
            $mvc->logInfo('Разглеждане на формата за генериране на товарителница');
            
            $res = $mvc->renderWrapping($form->renderHtml());
          
            
            return false;
        }
    }
    
    
    /**
     * Подготвя формата за товарителницата
     * 
     * @param core_Mvc $mvc
     * @param stdClass $documentRec
     * @param speedy_Adapter $adapter
     * 
     * @return core_Form
     */
    private static function getBillOfLadingForm($mvc, $documentRec, $adapter)
    {
        $form = cls::get('core_Form');
        $rec = &$form->rec;
        $form->title = 'Попълване на товарителница за Speedy към|* ' . $mvc->getFormTitleLink($documentRec);
        
        $form->FLD('senderPhone', 'drdata_PhoneType(type=tel,unrecognized=error)', 'caption=Данни за подател->Телефон,mandatory');
        $form->FLD('senderName', 'varchar', 'caption=Данни за подател->Фирма/Име,mandatory');
        $form->FLD('senderNotes', 'text(rows=2)', 'caption=Данни за подател->Уточнение');
        
        $form->FLD('receiverPhone', 'drdata_PhoneType(type=tel,unrecognized=error)', 'caption=Данни за получател->Телефон,mandatory');
        $form->FLD('receiverName', 'varchar', 'caption=Данни за получател->Фирма/Име,mandatory');
        $form->FLD('receiverPerson', 'varchar', 'caption=Данни за получател->Лице за конт,mandatory');
        
        $form->FLD('isPrivatePerson', 'set(yes=ЧЛ)', 'caption=Данни за получател->,inlineTo=receiverName,silent,removeAndRefreshForm=receiverPerson');
        $form->FLD('receiverSpeedyOffice', 'customKey(mvc=speedy_Offices,key=num,select=extName,allowEmpty)', 'caption=Данни за получател->Офис на Спиди,removeAndRefreshForm=service|date|receiverCountryId|receiverPlace|receiverAdress|receiverPCode,silent');
        $form->FLD('receiverCountryId', 'key(mvc=drdata_Countries,select=commonName,selectBg=commonNameBg,allowEmpty)', 'caption=Данни за получател->Държава,removeAndRefreshForm=service|date|receiverPlace|receiverPCode|receiverAdress,silent,mandatory');
        $form->FLD('receiverPCode', 'varchar', 'caption=Данни за получател->Пощ. код,removeAndRefreshForm=service,silent,mandatory');
        $form->FLD('receiverPlace', 'varchar', 'caption=Данни за получател->Нас. място,removeAndRefreshForm=service,silent,mandatory');
        $form->FLD('receiverAdress', 'varchar', 'caption=Данни за получател->Адрес,mandatory');
        $form->FLD('receiverNotes', 'text(rows=2)', 'caption=Данни за получател->Уточнение');
        
        $form->FLD('service', 'varchar', 'caption=Параметри на пратката 1.->Услуга,mandatory,removeAndRefreshForm=date,silent');
        $form->FLD('date', 'varchar', 'caption=Параметри на пратката 1.->Изпращане на,mandatory');
        
        $form->FLD('payer', 'enum(sender=1.Подател,receiver=2.Получател,third=3.Фирмен обект)', 'caption=Параметри на пратката 1.->Платец,mandatory');
        $form->FLD('payerPackaging', 'enum(same=Както к.у.,sender=1.Подател,receiver=2.Получател,third=3.Фирмен обект)', 'caption=Параметри на пратката 1.->Платец опаковка,mandatory');
        
        $form->FLD('isDocuments', 'set(yes=Документи)', 'caption=Параметри на пратката 2.->,inlineTo=payerPackaging,silent,removeAndRefreshForm=amountInsurance|isFragile|insurancePayer');
        $form->FLD('palletCount', 'int(min=0,Max=10)', 'caption=Параметри на пратката 2.->Бр. пакети,mandatory');
        $form->FLD('content', 'text(rows=2)', 'caption=Параметри на пратката 2.->Съдържание,mandatory,recently');
        $form->FLD('packaging', 'varchar', 'caption=Параметри на пратката 2.->Опаковка,mandatory,recently');
        $form->FLD('totalWeight', 'double(min=0,max=50)', 'caption=Параметри на пратката 2.->Общо тегло,unit=кг,mandatory');
        $form->FLD('isPaletize', 'set(yes=Да)', 'caption=Параметри на пратката 2.->Палетизирана,after=amountInsurance');
        //$form->FLD('declare', 'set(yes=Декларирам че не изпращам акцизна стока с неплатен акциз!,)', 'caption=Параметри на пратката 2.->Друго');
        
        $form->FLD('amountCODBase', 'double(min=0)', 'caption=Параметри на пратката 3.->Наложен платеж,unit=BGN,silent,removeAndRefreshForm=amountInsurance|isFragile|insurancePayer');
        $form->FLD('codType', 'set(post=Като паричен превод,including=Вкл. цената на к.у. в НП)', 'caption=Параметри на пратката 3.->Вид,after=amountCODBase,input=none');
        
        $form->FLD('amountInsurance', 'double', 'caption=Параметри на пратката 3.->Обявена стойност,unit=BGN,silent,removeAndRefreshForm=insurancePayer|isFragile');
        $form->FLD('insurancePayer', 'enum(same=Както к.у.,sender=1.Подател,receiver=2.Получател,third=3.Фирмен обект)', 'caption=Параметри на пратката 3.->Платец обявена ст.,input=none');
        $form->FLD('isFragile', 'set(yes=Да)', 'caption=Параметри на пратката 3.->Чупливост,after=amountInsurance,input=none');
        
        $form->FLD('options', 'enum(,open=Отвори преди плащане/получаване,test=Тествай преди плащане/получаване)', 'caption=Параметри на пратката 3.->Опции преди плащане/получаване,silent,removeAndRefreshForm=returnServiceId|returnPayer');
        $form->FLD('returnServiceId', 'varchar', 'caption=Параметри на пратката 3.->Услуга за Връщане,input=none,after=options');
        $form->FLD('returnPayer', 'enum(same=Както к.у.,sender=1.Подател,receiver=2.Получател,third=3.Фирмен обект)', 'caption=Параметри на пратката 3.->Платец на Връщането,input=none,after=returnServiceId');
        
        
        $form->input(null, 'silent');
        
        if(isset($rec->receiverSpeedyOffice)){
            $form->setField('receiverCountryId', 'input=none');
            $form->setField('receiverPlace', 'input=none');
            $form->setField('receiverAdress', 'input=none');
            $form->setField('receiverPCode', 'input=none');
        }
        
        if($rec->isDocuments == 'yes'){
            $form->setField('amountInsurance', 'input=none');
            $form->setField('isFragile', 'input=none');
            $form->setField('insurancePayer', 'input=none');
        }
        
        if($mvc instanceof sales_Sales){
            $paymentType = $documentRec->paymentMethodId;
            $amountCod = $documentRec->amountDeal;
        } elseif($mvc instanceof store_DocumentMaster){
            $firstDocument = doc_Threads::getFirstDocument($documentRec->threadId);
            $paymentType = $firstDocument->fetchField('paymentMethodId');
            $amountCod = ($documentRec->chargeVat == 'separate') ? $documentRec->amountDelivered + $documentRec->amountDeliveredVat : $documentRec->amountDelivered;
        }
        
        if(isset($paymentType)){
            if(cond_PaymentMethods::isCOD($paymentType)){
                $form->setDefault('amountCODBase', round($amountCod, 2));
            }
        }
        
        if($rec->amountCODBase){
            $form->setField('codType', 'input');
        }
        
        if(isset($rec->amountInsurance)){
            $form->setField('isFragile', 'input');
            $form->setField('insurancePayer', 'input');
            $form->setDefault('insurancePayer', 'same');
        }
        
        $logisticData = $mvc->getLogisticData($documentRec);
       
        if($form->cmd != 'refresh'){
            $Cover = doc_Folders::getCover($documentRec->folderId);
            if($Cover->haveInterface('crm_PersonAccRegIntf')){
                $form->setDefault('receiverName', $logisticData['toPerson']);
                $form->setDefault('isPrivatePerson', 'yes');
            } else{
                $receiverName = !empty($logisticData['toPerson']) ? $logisticData['toPerson'] : $logisticData['toCompany'];
                $form->setDefault('receiverName', $receiverName);
                $form->setDefault('receiverPerson', $logisticData['toPerson']);
            }
        }
        
        if($rec->isPrivatePerson == 'yes'){
            $form->setField('receiverPerson', 'input=none');
        }
        
        $receiverCountryId = drdata_Countries::getIdByName($logisticData['toCountry']);
        $form->setDefault('palletCount', 1);
        $form->setDefault('payerPackaging', 'same');
        
        $profile = crm_Profiles::getProfile();
        $phones = drdata_PhoneType::toArray($profile->tel);
        $phone = $phones[0]->original;
        $form->setDefault('senderName', $profile->name);
        $form->setDefault('senderPhone', $phone);
        $form->setDefault('declare', 'yes');
        $form->setDefault('totalWeight', $logisticData['totalWeight']);
        
        if(!isset($rec->receiverSpeedyOffice)){
            $form->setDefault('receiverCountryId', drdata_Countries::getIdByName($logisticData['toCountry']));
            
            if($rec->receiverCountryId == $receiverCountryId){
                $form->setDefault('receiverPlace', $logisticData['toPlace']);
                $form->setDefault('receiverAdress', $logisticData['toAddress']);
                $form->setDefault('receiverPCode', $logisticData['toPCode']);
            }
        }
        
        if((isset($form->rec->receiverCountryId) && !empty($form->rec->receiverPCode)) || !empty($form->rec->receiverSpeedyOffice)){
           try{
                $serviceOptions = $adapter->getServicesBySites($form->rec->receiverCountryId, $form->rec->receiverPlace, $form->rec->receiverPCode, $form->rec->receiverSpeedyOffice);
           } catch(ServerException $e){
               $serviceOptions = array();
               $msg = $adapter->handleException($e);
               $form->setError('receiverCountryId,receiverPCode', $msg);
           }
        }
        
        if(countR($serviceOptions)){
            $form->setOptions('service', $serviceOptions);
            $form->setDefault('service', key($serviceOptions));
            $form->input('service', 'silent');
        } else {
            $form->setError('service', 'Няма налична услуга за доставка');
            $form->rec->service = null;
            $form->setReadOnly('service');
        }
        $form->input('service', 'silent');
        
        if(!isset($form->rec->service)){
            $form->setField('date', 'input=none');
        } else {
            try{
                $takingDates =  $adapter->getAllowedTakingDays($form->rec->service);
                $form->setOptions('date', $takingDates);
                $form->setDefault('date', key($takingDates));
                
            } catch(ServerException $e){
                $serviceOptions = array();
                $msg = $adapter->handleException($e);
                $form->setError('receiverCountryId,receiverPCode', $msg);
            }
        }
        
        if(!empty($rec->options)){
            $form->setField('returnServiceId', 'input');
            $form->setOptions('returnServiceId', array('same' => 'Както к.у.') + $serviceOptions);
            $form->setField('returnPayer', 'input');
        }
        
        return $form;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if($action == 'makebilloflading' && isset($rec)){
            if($rec->state != 'active'){
                $requiredRoles = 'no_one';
            }
        }
    }
}