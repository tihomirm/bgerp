<?php


/**
 * Клас 'deals_WrapperPol'
 *
 *
 * @category  bgerp
 * @package   deals
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class deals_WrapperPol extends deals_Wrapper
{
    function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
		if(haveRole('ceo,dealsMaster')){
        	$tabs->TAB('deals_AdvanceReports', 'Отчети');
        	$tabs->TAB('deals_Deals', 'Аванси', array('deals_Deals', 'listAdvances'));
        }
        
        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'ПОЛ';
    }
}