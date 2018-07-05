<?php 


/**
 *
 *
 * @category  bgerp
 * @package   support
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class support_Systems extends core_Master
{
    
    
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'issue_Systems';
    
    
    /**
     * Заглавие на модела
     */
    public $title = 'Поддържани системи';
    
    
    
    public $singleTitle = 'Система';
    
    
    /**
     * Път към картинка 16x16
     */
    public $singleIcon = 'img/16/system-monitor.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'support/tpl/SingleLayoutSystem.shtml';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'admin, support';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'admin, support';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'admin, support';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'admin, support';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'support, ceo, admin';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'support, ceo, admin';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    public $canReject = 'admin, support';
    
    
    /**
     * Кой има право да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'support_Wrapper, doc_FolderPlg, plg_Created, plg_Rejected, plg_RowTools2, plg_Search, plg_State, plg_Modified';
    
    
    /**
     * Да се създаде папка при създаване на нов запис
     */
    public $autoCreateFolder = 'instant';
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces =
    // Интерфейс за корица на папка
    'doc_FolderIntf, support_IssueIntf';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'name=Система, prototype, folderId, description';
    
    
    /**
     * Кои документи могат да се добавят като бързо бутони
     */
    public $defaultDefaultDocuments = 'cal_Tasks';
    
    
    
    public $rowToolsField = 'id';

    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'name';
    

    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'name, description';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование,mandatory, width=100%');
        $this->FLD('prototype', 'key(mvc=support_Systems, select=name, allowEmpty)', 'caption=Прототип, width=100%');
        $this->FLD('description', 'richtext(rows=10,bucket=Support)', 'caption=Описание');
        $this->FLD('allowedTypes', 'keylist(mvc=support_IssueTypes, select=type)', 'caption=Сигнали->Използвани, width=100%, maxColumns=3');
        $this->FLD('defaultType', 'key(mvc=support_IssueTypes, select=type, allowEmpty)', 'caption=Сигнали->По подразбиране');

        $this->setDbUnique('name');
    }
    
    
    /**
     * Връща масив с всички типове на системата и на родителите
     *
     * @param integer|array $id
     *
     * @return array
     */
    public static function getAllowedFieldsArr($id)
    {
        if (is_array($id)) {
            $allSystemsArr = $id;
        } else {
            $allSystemsArr = support_Systems::getSystems($id);
        }
        
        // Запитване за извличане на системите
        $sQuery = support_Systems::getQuery();
        
        // Обхождаме всики наследени системи
        foreach ($allSystemsArr as $allSystemId) {
            
            // Добавяме OR
            $sQuery->orWhere($allSystemId);
        }
        
        // Обхождаме всички открити записи
        while ($sRec = $sQuery->fetch()) {
            
            // Обединяваме всички позволени типове
            $allowedTypes = keylist::merge($sRec->allowedTypes, $allowedTypes);
        }
        
        $allowedTypesArr = keylist::toArray($allowedTypes);
        
        return $allowedTypesArr;
    }
    
    
    /**
     * Връща всички системи и компоненти, които се използват
     *
     * @param integer $systemId - id на система
     *
     * @return array $arr - Масив с всички системи
     */
    public static function getSystems($systemId)
    {
        $arr = array();
        
        // Ако не е зададена система връщаме
        if (!$systemId) {
            return $arr;
        }
        
        // Добавяме в масива
        $arr[$systemId] = $systemId;
        
        // Вземаме записа
        $sRec = static::fetch($systemId);
        
        // Ако има прототип
        if ($sRec->prototype) {
            
            // Вземаме системата
            $arr += static::getSystems($sRec->prototype);
        }
        
        return $arr;
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        // Ако имаме създадена папка
        if ($rec->folderId) {
            
            // Записите за папката
            $folderRec = doc_Folders::fetch($rec->folderId);
            
            // Вземаме линка към папката
            $row->folderId = doc_Folders::recToVerbal($folderRec)->title;
        } else {
            
            // Заглавието на папката
            $title = $mvc->getFolderTitle($rec->id);
            
            // Добавяме бутон за създаване на папка
            $row->folderId = ht::createBtn(
                'Папка',
                array($mvc, 'createFolder', $rec->id),
                "Наистина ли желаете да създадетe папка за документи към|* \"{$title}\"?",
                             false,
                'ef_icon = img/16/folder_new.png'
            );
        }
    }


    /**
     * Интерфейсен метод за определяне името на папката
     */
    public function getFolderTitle($id)
    {
        $rec = self::fetch($id);

        $title = tr('Поддръжка на') . ' ' . self::getVerbal($rec, 'name');

        return $title;
    }
    
    
    /**
     * След създаване на папка, сменяма състоянието на активно
     */
    public function on_AfterForceCoverAndFolder($mvc, &$folderId, &$rec, $bForce = true)
    {
        $rec->state = 'active';
        $mvc->save($rec, 'state');
    }
    
    
    /**
     * Извиква се след изчисляването на необходимите роли за това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'edit') {
            if ($rec->state == 'active') {
//                $requiredRoles = 'no_one';
            }
        }
        
        // Ако листваме
        if ($action == 'list') {
            
            // Ако е активен
            if ($rec->state == 'active') {
                
                // Ако няма папка
                if (!$folderId = $rec->folderId) {
                    
                    // Вземаме id' то на папката
                    $folderId = support_Systems::forceCoverAndFolder($rec);
                }
                
                // Проверяваме дали имаме права в папката
                if (!doc_Folders::haveRightFor('single', $folderId, $userId)) {
                    
                    // Ако няма права в папката няма права и за листване
                    $requiredRoles = 'no_one';
                }
            }
        }
    }
    
    
    /**
     * Връща масив с допустимите системи
     *
     * @param core_Users $userId - id' то на потребителя
     *
     * @return array $accessedArr - Масив с допустимите ситеми
     */
    public static function getAccessed($userId = null)
    {
        // Масив с допустимите
        $accessedArr = array();
        
        $query = static::getQuery();
        
        // Обхождаме записите
        while ($rec = $query->fetch()) {
            
            // Ако има права за листване
            if (support_Systems::haveRightFor('list', $rec, $userId)) {
                
                // Добавяме към допустимите
                $accessedArr[$rec->id] = support_Systems::getVerbal($rec, 'name');
            }
        }

        return $accessedArr;
    }
    
    
    
    public static function on_AfterInputEditForm($mvc, $form)
    {
        // Ако формата е изпратена успешно
        if ($form->isSubmitted()) {
            
            // Ако е въведен прототип
            if ($form->rec->id) {
                
                // Ако сме избрали протип на същата система
                if ($form->rec->prototype == $form->rec->id) {
                    
                    // Сетваме грешката
                    $form->setError('prototype', 'Не може да се използва същата система.');
                }
            }
            
            // Ако сме избрали прототип
            if (!$form->rec->prototype) {
                
                // Ако не сме избрали тип
                if (!$form->rec->allowedTypes) {
                    
                    // Сетваме грешка, ако няма родител и няма позволен тип
                    $form->setError('allowedTypes', "Ако не сте избрали '{$form->fields['prototype']->caption}', трябва да изберете тип.");
                }
            } else {
                
                // Вземаме всички прототипи
                $prototypesArr = static::getSystems($form->rec->prototype);
                
                // Ако сме избрали за прототип някой от наследниците
                if ($prototypesArr[$form->rec->id]) {
                    
                    // Сетваме грешка
                    $form->setError('prototype', 'Не може да се използва наследника като родител.');
                }
            }
        }
        
        if ($form->isSubmitted()) {
            if ($form->rec->defaultType) {
                $parentAllowed = '';
                if ($form->rec->prototype) {
                    $parentAllowed = $mvc->getAllowedFieldsArr($form->rec->prototype);
                }
                
                $allAllowed = type_Keylist::merge($parentAllowed, $form->rec->allowedTypes);
                
                if (!type_Keylist::isIn($form->rec->defaultType, $allAllowed)) {
                    $form->setError('defaultType', 'Сигналът по подразбиране трябва да е добавен в използвани');
                }
            }
        }
    }
    

    /**
     * Модифициране на edit формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        // Ако сме в тесен режим
        if (Mode::is('screenMode', 'narrow')) {
            
            // Да има само 1 колони
            $data->form->setField('allowedTypes', array('maxColumns' => 1));
        }

        $query = support_IssueTypes::getQuery();

        while ($rec = $query->fetch("#state = 'active'")) {
            $options[$rec->id] = $rec->type;
        }

        $data->form->setSuggestions('allowedTypes', $options);
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $data->rec->allowedTypes = type_Keylist::fromArray($mvc->getAllowedFieldsArr($data->rec->id));
    }
    
    
    /**
     * Какви видове ресурси може да се добавят към модела
     *
     * @param  stdClass $rec
     * @return array    - празен масив ако няма позволени ресурси
     *                      ['assets'] - оборудване
     *                      ['hr']     - служители
     */
    public function getResourceTypeArray($rec)
    {
        return arr::make('assets', true);
    }
}
