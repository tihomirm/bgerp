<?php


/**
 * На колко процента разлика между очакваната и въведената сума при
 * превалутиране да сетва предупреждение
 */
defIfNot('BANK_EXCHANGE_DIFFERENCE', '5');

/**
 * class bank_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъра Bank
 *
 *
 * @category  bgerp
 * @package   bank
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'bank_OwnAccounts';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Необходими пакети
     */
    var $depends = 'drdata=0.1';
    
    
    /**
     * Описание на модула
     */
    var $info = "Банкови сметки, операции и справки";
    
    
    /**
     * Описание на конфигурационните константи за този модул
     */
    var $configDescription = array(
            
            //Задаване на основна валута
            'BANK_EXCHANGE_DIFFERENCE' => array ('double', 'mandatory'),
        );
    
	
	/**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'bank_Accounts',
            'bank_OwnAccounts',
            'bank_IncomeDocument',
        	'bank_CostDocument',
            'bank_InternalMoneyTransfer',
            'bank_ExchangeDocument',
        	'bank_PaymentOrders',
            'bank_CashWithdrawOrders',
        	'bank_DepositSlips',
        );
        
        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'bank';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(2.2, 'Финанси', 'Банки', 'bank_OwnAccounts', 'default', "bank, ceo"),
        );
        
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}