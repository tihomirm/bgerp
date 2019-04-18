 <?php


/**
 * Мениджър на тестови отчети
 *
 * @category  bgerp
 * @package   myself
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Тестовe » Тестов отчет
 */
class myself_reports_TestReports extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,acc,sales,purchase';
    
    
    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'genericId';
    
    
    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'contragent,checkDate,crmGroup,typeOfInvoice,unpaid';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        //Период
        $fieldset->FLD('prognose', 'set(yes = )',  'caption=Прогноза,after=title,refreshForm,silent,single=none');
        
        $fieldset->FLD('period', 'key(mvc=acc_Periods,title=title)', 'caption = За месец,after=prognose,single=none');
        
        $fieldset->FLD('from', 'date', 'caption=Период->От,after=period,single=none');
        $fieldset->FLD('duration','time(suggestions=1 седмица| 1 месец| 2 месеца| 3 месеца| 6 месеца| 12 месеца)', 'caption=Период->Продължителност,after=from,single=none');
       
        
        
       
        $fieldset->FLD('group', 'treelist(mvc=cat_Groups,select=name, parentId=parentId)', 'caption=Артикули->Група артикули,after=duration,single=none');
        
        
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *                                      $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        if ($rec->prognose != 'yes') {
            
            $form->setField('period', 'input=none');
        }else{
             $form->setField('from', 'input=none');
            $form->setField('duration', 'input=none');
           
        }
       
        
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_Form         $form
     * @param stdClass          $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        $rec = $form->rec;
        
       // bp($rec,acc_Periods::fetch($rec->period));
        
        
        
        if ($form->isSubmitted()) {
           
        }
    }
    
    
    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)
    {
        $recs = array();
        $allInProd = array();
        
        //Артикулите , които са влагани в производство 
        
        $details = array('planning_DirectProductionNote'=>'planning_DirectProductNoteDetails','planning_ConsumptionNotes'=>'planning_ConsumptionNoteDetails');
       
        foreach ($details as $Master => $Details){
            
            $plQuery = $Details::getQuery();
            
            $plQuery->EXT('valior', "$Master", 'externalName=valior,externalKey=noteId');
          
            $plQuery->EXT('state', 'cat_Products', 'externalName=state,externalKey=productId');
            
            $plQuery->where("#state != 'rejected'");
            
            $plQuery->EXT('canStore', 'cat_Products', 'externalName=canStore,externalKey=productId');
            
            $plQuery->EXT('canBuy', 'cat_Products', 'externalName=canBuy,externalKey=productId');
            
            $plQuery->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
            
            //Продължителност на периода за показване
            $durationStr = cls::get('type_Time')->toVerbal($rec->duration);
            
            list($periodCount, $periodType)= explode(' ', $durationStr);
            
            //Край на избрания период за показване $dateEnd
            core_Lg::push('bg');
            
            if ($periodType == 'дни' || $periodType == 'ден' || $periodType == 'дена'){
                $dateEnd = dt::addDays($periodCount-1, $rec->from, false);
            }
            
            if ($periodType == 'мес.'){
                $dateEnd = dt::addMonths($periodCount, $rec->from, false);
                $dateEnd = dt::addDays(-1, $dateEnd, false);
            }
            
            if ($periodType == 'год.'){
                
                $monts = 12*$periodCount;
                $dateEnd = dt::addMonths($monts, $rec->from, false);
                $dateEnd = dt::addDays(-1, $dateEnd, false);
            }
            
            $rec->to = $dateEnd;
            
            
            if(isset($rec->from) && isset($rec->duration)){
                $plQuery->where("#valior >= '{$rec->from}' AND #valior <= '{$dateEnd}'");
            }
            core_Lg::pop();
            
            //Филтър по групи артикули
            if (isset($rec->group)) {
                $plQuery->likeKeylist('groups', $rec->group);
            }
            
            $plQuery->where("#canStore = 'yes' AND #canBuy = 'yes'");
            
        
            $startDate = dt::mysql2verbal(dt::addMonths(-12), $mask = 'Y-01-01');
            
            while ($prodRec = $plQuery->fetch()){
                
                $id = $prodRec->productId;
                
                // Запис в масива
                if (!array_key_exists($id, $allInProd)) {
                    $allInProd[$id] = (object) array(
                        
                        
                        'productId' => $prodRec->productId,                           //Id на артикула
                        'measure' => $prodRec->measureId,                             //Мярка\
                        'quantity' => $prodRec->quantity,                             //Текущ период - количество
                        'genericId' => null,
                        'generucQuantity' => null,
                        
                    );
                } else {
                    $obj = &$allInProd[$id];
                    
                    $obj->quantity += $prodRec->quantity;
                    
                }
                
                
                
            }
        }
        //Генерично заменяеми артикули
        $queryS = planning_ObjectResources::getQuery();
        
        while ($generics = $queryS->fetch()) {
            
            if (!is_array($genericProducts[$generics->likeProductId])){
                
            $genericProducts[$generics->likeProductId] = array($generics->objectId);
            
            }else{
                array_push($genericProducts[$generics->likeProductId] , $generics->objectId);
            }
        }
        
        
        //Всички влагани през периода артикули
        $prodIds = arr::extractValuesFromArray($allInProd, 'productId');
       
        $genericProd = array();
        
        foreach ($genericProducts as $key => $val){
              
            
            $result = array_intersect($prodIds, $val);  //Влагни артикули, който са от групата на генеричния
            
            
            foreach ($result as $k=>$v){
                
                //Масив с общите количества на генеричните артикули (сумата от количествата на съставните артикули)
                $genericQuantity[$key] += $allInProd[$v]->quantity;
                
                //Артикул, който е част от генеричен
                $genericProd[$v]=(object)array(
                    'productId' => $v,
                    'measure' => $allInProd[$v]->measure,
                    'quantity' => $allInProd[$v]->quantity,
                    'genericId' => $key,
                );
                
            }
            
        }
             
              $genProdIds = arr::extractValuesFromArray($genericProd, 'productId');
              
              
              //Изключваме от общия масив онези артикули, които са част от генеричен артикул
              foreach ($allInProd as $key => $val){
                  
                  if (in_array($val->productId, $genProdIds)){
                      
                      unset($allInProd[$key]);
                      
                  }
              }
              
              // Включваме артикулите, които са съставни на генеричните в общия масив
              foreach ($genericProd as $key =>$val){
                  
                  $val->generucQuantity = $genericQuantity[$val->genericId]; 
                  
                  array_unshift($allInProd, $val);
                   
              }
             $recs = $allInProd;
        
        return $recs;
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool     $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        
        if ($export === false) {
            
            $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
            
            $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
            
            $fld->FLD('quantity', 'varchar', "caption=Вложено");
        } else {
           
        }
        
        return $fld;
    }
    
    
   
    
    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *                       - записа
     * @param stdClass $dRec
     *                       - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $isPlain = Mode::is('text', 'plain');
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        
        $row = new stdClass();
    
        if (isset($dRec->genericId)) {
            $row->genericId = 'Генеричен артикул: '.cat_Products::getHyperlink($dRec->genericId).
                "<span class= 'fright'><span class= ''>" .
                ' Общо: '.
                core_Type::getByName('double(decimals=2)')->toVerbal($dRec->generucQuantity).' '.
                cat_UoM::fetchField($dRec->measure, 'shortName')."</span>";
        }else{
            
            $row->genericId = 'Незаменяеми артикули ';
            
        }
        
        if (isset($dRec->productId)) {
            $row->productId = cat_Products::getLinkToSingle_($dRec->productId, 'name');
        }
        if (isset($dRec->measure)) {
            $row->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
        }
        
        $row->quantity = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantity);
        
        return $row;
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
      
    }
    
    
    
    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass            $res
     * @param stdClass            $rec
     * @param stdClass            $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $res->paidAmount = (self::getPaidAmount($dRec));
        
        $res->paidDates = self::getPaidDates($dRec, false);
        
        $res->dueDate = self::getDueDate($dRec, false, $rec);
        
        if ($dRec->invoiceCurrentSumm < 0) {
            $invoiceOverSumm = - 1 * $dRec->invoiceCurrentSumm;
            $res->invoiceCurrentSumm = '';
            $res->invoiceOverSumm = ($invoiceOverSumm);
        }
        
        if ($dRec->dueDate && $dRec->invoiceCurrentSumm > 0 && $dRec->dueDate < $rec->checkDate) {
            $res->dueDateStatus = 'Просрочен';
        }
        
        $invoiceNo = str_pad($dRec->invoiceNo, 10, '0', STR_PAD_LEFT);
        
        $res->invoiceNo = $invoiceNo;
    }
}