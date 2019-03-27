<?php


/**
 * Планиране - опаковка
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('planning_DirectProductionNote', 'Протоколи->Производство', 'ceo,planning,store');
        $this->TAB('planning_ConsumptionNotes', 'Протоколи->Влагане', 'ceo,planning,store');
        $this->TAB('planning_ReturnNotes', 'Протоколи->Връщане', 'ceo,planning,store');
        $this->TAB('planning_Jobs', 'Задания', 'ceo,planning,job');
        $this->TAB('planning_Tasks', 'Операции->Списък', 'ceo,taskWorker');
        $this->TAB('planning_ProductionTaskDetails', 'Операции->Прогрес', 'ceo,taskWorker');
        $this->TAB('planning_Centers', 'Центрове->Списък', 'ceo, planning');
        $this->TAB('planning_Points', 'Центрове->Терминали', 'debug');
        
        $this->TAB(array('planning_AssetResources', 'type' => 'material'), 'Ресурси->Оборудване', 'ceo,planning');
        $this->TAB(array('planning_AssetResources', 'type' => 'nonMaterial'), 'Ресурси->Нематериални', 'ceo,planning');
        $this->TAB('planning_Hr', 'Ресурси->Хора', 'ceo,planning');
        $this->TAB('planning_AssetGroups', 'Ресурси->Групи', 'ceo,planning');
        $this->TAB('planning_Stages', 'Ресурси->Етапи', 'ceo,planning');
        
        $this->TAB('planning_FoldersWithResources', 'Настройки->Папки с ресурси', 'ceo,planning');
        $this->TAB('planning_AssetResourcesNorms', 'Настройки->Норми', 'ceo,planning');
        
        $this->TAB('planning_WorkCards', 'Раб. карти', 'ceo,planning,admin');
        
        $this->title = 'Планиране';
    }
    
    
    /**
     * Дефолтен контролър
     */
    public function act_getStartCtr()
    {
        if (haveRole('ceo,planning,store')) {
            redirect(array('planning_DirectProductionNote', 'list'));
        } elseif (haveRole('job')) {
            redirect(array('planning_Jobs', 'list'));
        } else {
            redirect(array('planning_Tasks', 'list'));
        }
    }
}
