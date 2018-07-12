<?php


/**
 * Зони в палетния склад
 *
 *
 * @category  bgerp
 * @package   pallet
 *
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pallet_Zones extends core_Manager
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'store_Zones';
    
    
    /**
     * Заглавие
     */
    public $title = 'Зони';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_LastUsedKeys, pallet_Wrapper, plg_RowTools2';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'storeId';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,pallet';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,pallet';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,pallet';
    
    
    /**
     * Кой може да го види?
     */
    public $canView = 'ceo,pallet';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,pallet';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,pallet';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo,pallet';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'code,comment';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад,input=hidden');
        $this->FLD('code', 'varchar(4)', 'caption=Код,mandatory');
        $this->FLD('comment', 'varchar(32)', 'caption=Коментар,mandatory');
        
        $this->setDbUnique('storeId,code');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     * Забранява изтриването/редакцията на зоните, които не са празни
     *
     * @param core_Mvc      $mvc
     * @param string        $requiredRoles
     * @param string        $action
     * @param stdClass|NULL $rec
     * @param int|NULL      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($rec->id && ($action == 'delete')) {
            $mvc->palletsInStoreArr = pallet_Pallets::getPalletsInStore();
            
            $rec = $mvc->fetch($rec->id);
            
            if ($mvc->palletsInStoreArr[$rec->id]) {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Смяна на заглавието
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListTitle($mvc, $data)
    {
        $selectedStoreName = store_Stores::getHyperlink(store_Stores::getCurrent(), true);
        $data->title = "|Зони в склад|* <b style='color:green'>{$selectedStoreName}</b>";
    }
    
    
    /**
     * Преди извличане на записите от БД
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $selectedStoreId = store_Stores::getCurrent();
        
        $data->query->where("#storeId = {$selectedStoreId}");
        $data->query->orderBy('id');
    }
    
    
    /**
     * При нов запис
     *
     * @param core_Mvc $mvc
     * @param int      $id
     * @param stdClass $rec
     */
    protected static function on_BeforeSave($mvc, &$id, $rec)
    {
        if (!$rec->id) {
            $rec->storeId = store_Stores::getCurrent();
        }
    }
}
