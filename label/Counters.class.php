<?php 


/**
 * Броячи за етикетите
 * 
 * @category  bgerp
 * @package   label
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_Counters extends core_Master
{
    
    
    /**
     * Плейсхолдер за брояча
     */
    static $counterPlace = '%';
    
    
    /**
     * Заглавие на модела
     */
    var $title = 'Броячи';
    
    
    /**
     * 
     */
    var $singleTitle = 'Брояч';
    
    
    /**
     * Път към картинка 16x16
     */
//    var $singleIcon = 'img/16/.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    var $singleLayoutFile = 'label/tpl/SingleLayoutCounters.shtml';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'label, admin, ceo';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'labelMaster, admin, ceo';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'label, admin, ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'label, admin, ceo';
    
    
    /**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'label, admin, ceo';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'label, admin, ceo';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'labelMaster, admin, ceo';
    
    
    /**
     * Плъгини за зареждане
     */
//    var $loadList = 'plg_Printing, bgerp_plg_Blank, plg_Search';
    var $loadList = 'label_Wrapper, plg_RowTools, plg_Created, plg_State';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
//    var $listFields = '';
    
    
    /**
     * 
     */
//    var $rowToolsField = 'id';

    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
//    var $rowToolsSingleField = 'id';
    

    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
//    var $searchFields = '';
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'label_CounterItems';
    
    
	/**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('name', 'varchar(128)', 'caption=Име, mandatory, width=100%');
        $this->FLD('min', 'int(min=0)', 'caption=Минимално, mandatory');
        $this->FLD('max', 'int(min=1)', 'caption=Максимално, mandatory');
        $this->FLD('step', 'int', 'caption=Стъпка, mandatory');
    }
    
    
    /**
     * Към максималния брояч в модела добавя стъпката и връща резултата
     * 
     * @param integer $counterId - id на записа
     * 
     * @return integer - Нов номер
     */
    static function getCurrent($counterId)
    {
        // Вземае записа
        $cRec = static::fetch($counterId);
        
        // Ако няма запис
        if ($maxVal = label_CounterItems::getMax($counterId)) {
            
            // Добавяме стъпката
            $maxVal += $cRec->step;
        } else {
            
            // Ако стъпката е отрицателна
            if ($cRec->step < 0) {
                
                // Използваме максималната стойност за начална
                $maxVal = $cRec->max;
            } else {
                
                // Използваме минимална стойност за начална
                $maxVal = $cRec->min;
            }
        }
        
        // Ако стъпката е отрицателна
        if ($cRec->step < 0) {
            
            // Очакваме да не надвишаваме брояча
            expect($maxVal >= $cRec->min,  "Броячът е изчерпан");
        } else {
            
            // Очакваме да не надвишаваме брояча
            expect($maxVal <= $cRec->max,  "Броячът е изчерпан");
        }
        
        // Връщаме стойността
        return $maxVal;
    }
    
    
    /**
     * Проверява в стринга има плейсхолдер за брояч, който да се замести
     * 
     * @param string $str - Стринга, който ще се проверява
     * 
     * @return boolean
     */
    static function haveCounterPlace($str)
    {
        // Ако в текста някъде се намира плейсхолдер за брояча
        if (strpos($str, static::$counterPlace) !== FALSE) {
            
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Замества плейсхолдера за брояч със съответната стойност
     * 
     * @param string $str - Стринг, в който ще се замества
     * @param integer $counterId - id на брояча
     * @param integer $labelId - id на етикета
     * 
     * @return string - Новия стринг
     */
    static function placeCounter($str, $counterId, $labelId)
    {
        // Ако име плейсхолдер за брояч
        if (static::haveCounterPlace($str)) {
            
            // Вземаем текущия брояч
            $counter = static::getCurrent($counterId);
            
            // Упдейтваме последния брояч
            $updated = label_CounterItems::updateCounter($counterId, $labelId, $counter);
            
            // Очакваме да няма грешка
            expect($updated);
            
            // Заместваме в стринга
            $str = str_replace(static::$counterPlace, $counter, $str);
        }
        
        return $str;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param label_Counter $mvc
     * @param core_Form $form
     */
    static function on_AfterInputEditForm($mvc, &$form)
    {
        // Ако формата е изпратена успешно
        if ($form->isSubmitted()) {
            
            // Ако максимума не е по - голяма от минимума
            if ($form->rec->max <= $form->rec->min) {
                
                // Сетваме грешка
                $form->setError('max', 'Максимума трябва да е над минимума');
            }
        }
    }
}
