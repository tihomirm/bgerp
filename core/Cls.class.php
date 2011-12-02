<?php

/**
 * Клас 'core_Cls' ['cls'] - Функции за работа с класове
 *
 * Класът core_Cls предоставя няколко полезни функции:
 *  - динамично зареждане на класове и съзадаване на обекти
 *  - поддържа информация за оригиналните имена на класовете
 *  - динамично свързва плъгините с новосъздадените обекти
 *  - намира дали даден клас/клас на обект е подклас на друг
 *
 * @category   Experta Framework
 * @package    core
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2010 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id$
 * @link
 * @since      v 0.1
 */
class core_Cls
{
    
    
    /**
     * Връща името на класа, от който е този обект или
     * прави стринга да отговаря на стандартите за име
     * на клас във фреймуърка:
     * - минимум един префикс (този на пакета)
     * - главна буква преди собственото име на класа
     *
     * @param mixed $class
     * @param boolean $save
     * @return string
     */
    function getClassName($className)
    {
        if( is_object($className) ) {
            if($className->className) {

                return $className->className;
            } else {

                return get_class($className);
            }
        }
        
        // Ако името е число, тогава го вземаме от coreClass
        if(is_numeric($className)) {
            $Classes = cls::get('core_Classes');
            $className = $Classes->fetchField($className, 'name');
            if(!$className) return FALSE;
        }
        
        // Ако се използва съкратено име, то името на приложението
        // се прибавя като приставка и долна черта отпред
        if (strpos($className, '_') === FALSE) {
            $className = EF_APP_CODE_NAME . '_' . $className;
        }
        
        // Капитализираме буквата след последната черта
        if( ($last = strrpos($className, '_')) > 0 ) {
            if ($last !== FALSE && $last < strlen($className)) {
                $className{$last + 1} = strtoupper($className{$last + 1});
            } else {
                error('Incorrect class name', $className);
            }
        }
        
        return $className;
    }
    
    
    /**
     * Зарежда указания клас. Името на класа $class може да съдържа само
     * букви, цифри и долна черта
     *
     * Пътя и суфикса не се проверяват за допустимост
     *
     * @param string $class
     * @param string $patch
     * @param string $suffix
     * @return mixed
     */
    function load($className, $silent = FALSE, $suffix = ".class.php")
    {
        $fullClassName = cls::getClassName($className);

        if($fullClassName === FALSE) {
            
            if (!$silent) {
                error("Няма такъв клас", "'{$className}'");
            }
            
            return FALSE;

        }
        
        // Проверяваме дали класа вече не съществува, и ако е така не правим нищо
        if (class_exists($fullClassName, FALSE)) {
            
            return TRUE;
        }
        
        // Проверяваме дали името на класа съдържа само допустими символи
        if (!preg_match("/^[a-z0-9_]+$/i", $fullClassName)) {
            
            if (!$silent) {
                error("Некоректно име на клас", "'{$className}'");
            }
            
            return FALSE;
        }
        
        // Определяме името на файла, в който трябва да се намира класа
        $fileName = str_replace('_', '/', $fullClassName) . $suffix;
        
        // Определяме пълния път до файла, където трябва да се намира класа
        $filePath = getFullPath($fileName);
        
        // Връщаме грешка, ако файлът не съществува или не може да се чете
        if (!$filePath) {
            
            if (!$silent) {
                error("Файлът с кода на класа не съществува или не е четим", $fileName);
            }
            
            return FALSE;
        }

        // Включваме файла
        if(!include_once($filePath)) {
            error("Не може да бъде парсиран файла", "'{$className}'  in '{$fileName}'");
        }
        
        // Проверяваме дали включения файл съдържа търсения клас
        if (!class_exists($fullClassName, FALSE)) {
            
            if (!$silent) {
                error("Не може да се намери класа в посочения файл", "'{$className}'  in '{$fileName}'");
            }
            
            return FALSE;
        }

        
        return TRUE;
    }
    
    
    /**
     * Връща инстанция на обект от указания клас
     * Ако класът има интефейс "Singleton", то ако няма преди създаден
     * обект - създава се, а ако има връща се вече съсдадения
     *
     * @param string $class
     * @param array  $initArr
     * @return object
     */
    function &get($class, $initArr = NULL)
    {
        static $singletons;
        
        $class = cls::getClassName($class);
        
        cls::load($class);
        
        if (cls::isSingleton($class)) {
            if (!isset($singletons[$class])) {
                $singletons[$class] = new stdClass();
                $singletons[$class] = cls::createObject($class, $initArr);
            }
            
            $obj =& $singletons[$class];
        } else {
            $obj = cls::createObject($class, $initArr);
        }
        
        return $obj;
    }
    
    
    /**
     * Създава инстанция на обект от указания клас, като го
     * инициализира с дадените данни и му закача плъгините
     *
     * @param string $class
     * @param array  $initArr
     * @return object
     */
    function &createObject($class, &$initArr=null)
    {
        $obj = new $class;
        
        // Прикача плъгините, които са регистрирани за този клас
        $Plugins =& cls::get('core_Plugins');
        
        if (is_a($Plugins, 'core_Plugins'))
        $Plugins->attach(&$obj);
        
        // Ако има допълнителни параметри - използва ги за инициализиране
        if (is_callable(array($obj, 'init'))) {
            
            $res = call_user_func(array(&$obj, 'init'), &$initArr);
            
            // Ако в резултат на инициализацията е върнат 
            // обект, то той се връща като резултат
            if (is_object($res)) {
                
                return $res;
            }
        }
        
        return $obj;
    }
    
    
    /**
     * Проверява дали даден клас трябва да е сингълтон
     *
     * @param string $class
     * @param string $interface
     * @return boolean
     */
    function isSingleton($class)
    {
        return is_callable(array($class, '_Singleton'));
    }
    
    
    /**
     * Връща истина, ако указаният клас е подклас на класа
     * посочен във втория стрингов параметър
     * за разлика от вградените фунции работи със стрингови параметри
     *
     * @param mixed  $class
     * @param string $parrentClass
     * @return boolean
     */
    function isSubclass($class, $parrentClass)
    {
        if (is_object($class)) {
            $className = strtolower(get_class($class));
        } else {
            cls::load($class);
            $className = strtolower($class);
        }
        
        $parrentClassLw = strtolower($parrentClass);
        
        do {
            if ($parrentClassLw === $className)
            
            return TRUE;
        } while (FALSE != ($className = strtolower(get_parent_class($className))));
        
        return FALSE;
    }
    
    
    /**
     * Вика функция с аргументи посочения масив
     * Формат1 за името на функцията: име_на_клас->име_на_метод
     * Формат2 за името на функцията: име_на_клас::име_на_статичен_метод
     */
    function callFunctArr($name, $arr)
    {
        $call = explode("->", $name);
        
        if (count($call) == 2) {
            $call[0] = cls::get($call[0]);
        } else {
            $call = explode("::", $name);
            
            $call = $name;
        }
        
        return call_user_func_array($call, $arr);
    }
    
    /**
     * 
     * Дали интерфейс е наследник на друг интерфейс
     *
     * @param string $intf
     * @param string $parentIntf
     * @return bool
     */
    static function isSubinterfaceOf($intf, $parentIntf) {
    	try {
    		$r = new ReflectionClass($intf);
    	} catch (ReflectionException $e) {
    		return false;
    	}
    	
    	try {
    		return $r->isSubclassOf($parentIntf);
    	} catch (ReflectionException $e) {
    		error('Грешка', "<pre>".(string)$e."</pre>");
    	}
    	
    }


    /**
     * Връща обект - адаптер за интерфайса към посочения клас
     */
    function getInterface($interface, $class, $params = NULL, $silent = FALSE)
    {
        if(is_scalar($class)) {
            $classObj = cls::get($class, $params);
        } else {
            $classObj = $class;
        }

        // Очакваме, че $classObj е обект
        expect(is_object($classObj), $class);

        $classObj->interfaces = arr::make($classObj->interfaces);

        if(isset($classObj->interfaces[$interface])) {
            $interfaceObj = cls::get($classObj->interfaces[$interface]);
        } elseif( in_array($interface, $classObj->interfaces) ) {
            $interfaceObj = cls::get($interface); 
        } elseif(!$silent) {
            expect(FALSE, "Адаптера за интерфейса {$interface} не се поддържа от класа " . cls::getClassName($class)); 
        } else {
            return FALSE;
        }

        $interfaceObj->class = $classObj;

        return $interfaceObj;
    }


    /**
     * Връща заглавието на класа от JavaDoc коментар или от свойството $title
     */
    function getTitle($class)
    {
        
        try {
            $rfl = new ReflectionClass($class);
        } catch( ReflectionException $e) {
            bp($e->getMessage());
        }
        
        $comment = $rfl->getDocComment();

        $comment = trim(substr($comment, 3, -2));
        
        $lines = explode("\n", $comment);
        
        foreach($lines as $l) {
            $l = ltrim($l, "\n* \r\t");
            
            if(!$firstLine && $l) {
                $firstLine = $l;
            }

            if(strpos($l, '@title') === 0) {
                $titleLine = trim(ltrim(substr($l, 6), ':')); 
            }
        }

        if($titleLine) return $titleLine;
        
        $obj = cls::get($class);

        if($obj->title) return $obj->title;

        return $firstLine;
    }

}

// Съкратено име, за по-лесно писане
class_alias('core_Cls', 'cls');