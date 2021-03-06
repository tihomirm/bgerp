<?php


/**
 * Обекти в плановете на помещенията
 *
 *
 * @category  bgerp
 * @package   floor
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class floor_Objects extends core_Detail {


    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'planId';


   /**
     * Необходими плъгини
     */
    public $loadList = 'plg_Created, plg_RowTools2, plg_State2, plg_Rejected, floor_Wrapper,plg_SaveAndNew';
    
    
    /**
     * Заглавие
     */
    public $title = 'Обекти';
    

    /**
     * Заглавие в единичния изглед
     */
    public $singleTitle = 'Обект';
    

    /**
     * Права за писане
     */
    public $canWrite = 'floor,admin,ceo';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'floor,admin,ceo';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'floor,admin,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'floor,admin,ceo';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'floor,admin,ceo';
    
      
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/wooden-box.png';
    
      
    /**
     * Полета, които ще се показват в листов изглед
     */
    // public $listFields = 'order,name,state';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('planId', 'key(mvc=floor_Plans,select=name)', 'caption=План');
        $this->FLD('name', 'varchar(8)', 'caption=Наименование, mandatory,remember=info');
        $this->FLD('width', 'float(m=0,decimals=2)', 'caption=Широчина,unit=m,mandatory');
        $this->FLD('height', 'float(m=0,decimals=2)', 'caption=Дълбочина,unit=m,mandatory');
        $this->FLD('round', 'percent', 'caption=Заобленост,remember');
        $this->FLD('x', 'float(m=0,decimals=2)', 'caption=Позиция->X,unit=m');
        $this->FLD('y', 'float(m=0,decimals=2)', 'caption=Позиция->Y,unit=m');

        $this->setDbUnique('name');
    }
}