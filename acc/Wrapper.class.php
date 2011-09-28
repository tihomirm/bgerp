<?php

/**
 * Клас 'acc_Wrapper'
 *
 * Поддържа системното меню и табовете на пакета 'Acc'
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id: Guess.php,v 1.29 2009/04/09 22:24:12 dufuz Exp $
 * @link
 * @since
 */

class acc_Wrapper extends core_Plugin
{
    /**
     *  Извиква се след рендирането на 'опаковката' на мениджъра
     */
    function on_AfterRenderWrapping($invoker, &$tpl)
    {
        $tabs = cls::get('core_Tabs');
        
        $tabs->TAB('acc_Balances', 'Баланси');
        $tabs->TAB('acc_Articles', 'Мемориални Ордери');
        $tabs->TAB('acc_Journal', 'Журнал');
        
        $tpl = $tabs->renderHtml($tpl, empty($invoker->currentTab)?$invoker->className:$invoker->currentTab);
        
        $tpl->append(tr($invoker->title) . " » ", 'PAGE_TITLE');

        $invoker->menuPage = 'Счетоводство:Книги';
    }
}