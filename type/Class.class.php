<?php

/**
 * Клас  'type_Class' - Ключ към запис в мениджъра core_Classes
 *
 * Може да се селектира по име на интерфейс
 *
 * @category   Experta Framework
 * @package    type
 * @author     Milen Georgiev
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 */
class type_Class extends type_Key {
    
    
    /**
     *  Инициализиране на типа
     */
    function init($params)
    {
        parent::init($params);
        
        $this->params['mvc'] = 'core_Classes';
        setIfNot($this->params['select'], 'name');
    }
    
    
    /**
     * Рендира INPUT-a
     */
    function renderInput_($name, $value="", $attr = array())
    {
        expect($this->params['mvc'], $this);
        
        $mvc = cls::get($this->params['mvc']);
        
        if(!$value) {
            $value = $attr['value'];
        }
        
        $interface = $this->params['interface'];
        
        $options = $mvc->getOptionsByInterface($interface, $this->params['select']);
        
        if($this->params['allowEmpty']) {
            $options = arr::combine( array(NULL => ''), $options);
        }
        
        $tpl = ht::createSmartSelect($options, $name, $value, $attr,
                                     $this->params['maxRadio'],
                                     $this->params['maxColumns'],
                                     $this->params['columns']);
        
        return $tpl;
    }
    
    
    /**
     * Връща вътрешното представяне на вербалната стойност
     */
    function fromVerbal($value)
    {
        if(!$value) return NULL;
        
        $value = (int) $value;
        
        $interface = $this->params['interface'];
        
        $mvc = cls::get($this->params['mvc']);
        
        $options = $mvc->getOptionsByInterface($interface, $this->params['select']);
        
        if(!$options[$value]) {
            $this->error = 'Несъщесвуващ клас';
            
            return FALSE;
        } else {
            
            return $value;
        }
    }
}