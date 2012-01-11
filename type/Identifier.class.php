<?php


/**
 * Клас  'type_Identifier' - Тип за идентификатор
 *
 *
 * @category   Experta Framework
 * @package    type
 * @author     Milen Georgiev
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$
 * @link
 * @since      v 0.1
 */
class type_Identifier extends type_Varchar {
    
    
    /**
     *  @todo Чака за документация...
     */
    function fromVerbal($value)
    {
        $value = parent::fromVerbal(str::trim($value));
        
        if( $value === '') return NULL;

        if (!self::isValid($value)) {
            $this->error = 'Некоректен идентификатор|* ' . $value;

            return FALSE;
        }
        
        return $value;
    }
    
    
    /**
     * Проверява дали е валиден
     */
    function isValid($value)
    {
        $len = $this->params[0]?'0,'.($this->params[0]-1):'0,63';
        $pattern = "/^[a-zA-Z_]{1}[a-zA-Z0-9_]{". $len ."}$/i";
        
        if(!preg_match($pattern, $value)) {

            return FALSE;
        }
        
        return TRUE;
    }
}