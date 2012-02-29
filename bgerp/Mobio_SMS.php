<?php

/**
 * Урл за изпращане на СМС-и през Мобио
 */

// defIfNot(MOBIO_URL);

/**
 * SMS-и през Мобио
 *
 *
 * @category  bgerp
 * @package   bgerp
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     SMS
 */
class bgerp_Mobio_SMS extends core_BaseClass
{
    
    
    /**
     * Интерфeйси
     */
    var $interfaces = 'bgerp_SMSIntf';
    
    /**
     * Изпраща SMS 
     */
    function send($number, $message, $sender)
    {
		if (!defined(MOBIO_URL)) {
			return FALSE;
		}
		bgerp_SmsLog::add('Mobio', $number, $message, $sender);
    }    
}
