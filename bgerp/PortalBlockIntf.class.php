<?php


/**
 * Интерфейс за създаване на отчети от различни източници в системата
 *
 *
 * @category  bgerp
 * @package   cond
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_PortalBlockIntf extends embed_DriverIntf
{
    /**
     * Инстанция на класа имплементиращ интерфейса
     */
    public $class;
    
    
    /**
     * Подготвя данните
     * 
     * @param stdClass $dRec
     * @param null|integer $userId
     * 
     * @return stdClass
     */
    public function prepare($dRec, $userId = null)
    {
        
        return $this->class->prepare($dRec, $userId);
    }
    
    
    /**
     * Рендира данните
     * 
     * @param stdClass $data
     * 
     * @return core_ET
     */
    public function render($data)
    {
        
        return $this->class->render($data);
    }
    
    
    /**
     * Връща типа на блока за портала
     * 
     * @return string - other, tasks, notifications, calendar, recently
     */
    public function getBlockType()
    {
        
        return $this->class->getBlockType();
    }
}