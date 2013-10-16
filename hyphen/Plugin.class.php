<?php


/**
 * Плъгин за хифинация
 * 
 * @category  vendors
 * @package   hyphen
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class hyphen_Plugin extends core_Plugin
{
    
    
	/**
     * Минималната дължина на стринга, над която ще се хифенира стринга
     */
    const TRANSFER_WORD_MIN_LENGTH = 15;
    
    
	/**
     * Минималната дължина след която ще се добавя знак за хифенация
     */
    const MIN_LENGTH_HYPHEN = 4;
    
    
    /**
     * Максималната дължина след която ще се добавя знак за хифенация
     */
    const MAX_LENGTH_HYPHEN = 10;
    
    
    /**
     * Прихваща извикването на hyphenText
     * Хифенира подадения текст, като добавя <wbr>
     */
    function on_HyphenText($mvc, &$html)
    {
        // Ако сме в текстов режим, връщаме
        if (Mode::is('text', 'plain')) return ;
        
        // Ако сме в широк режим и не сме в дебъг, връщаме
        if (Mode::is('screenMode', 'wide') && !isDebug()) return ;
        
        // Шаблона, за намиране, на думите, които ще хифинираме
        $pattern = "/(\[#[^\#\]]*\#\])|(\<[^\>]*\>)|([\s]+)|(?'words'[^\s\<\[\#\]]{" . static::TRANSFER_WORD_MIN_LENGTH .",})/iu";
        
        // Намираме думите
        preg_match_all($pattern, $html, $matches);
        
        // Обхождаме масива
        foreach ((array)$matches['words'] as $match) {
            
            // Ако има текст
            if (!trim($match)) continue;
            
            // Хифенираме думата
            $hyphenedStr = static::getHyphenWord($match);
            
            // Заместваме
            $html = str_replace($match, $hyphenedStr, $html);
        }
    }
    
    
	/**
     * Хифенира стринговете
     */
    static function getHyphenWord($string)
    {
        // Брояча за сивмовилите
        $i = 0;
        
        // За циклене по стринга
        $p = 0;
        
        // Резултатния стринг
        $resStr = '';
        
        // Обхождаме всички символи
        while('' != ($char = core_String::nextChar($string, $p))) {

            // Флаг, дали да се добавя знак за хифенация
            $addHyphen = FALSE;
            
            // Увеличаваме брояча
            $i++;
            
            // Ако брояча е под първия минимум
            if ($i <= static::MIN_LENGTH_HYPHEN) {
                
                // Добавяме символа
                $resStr .= $char;
                
                continue;
            }
            
            // Pointer за следващия символ
            $pNext += strlen($char);
            
            // Взмема следващия символ
            $nextChar = core_String::nextChar($string, $pNext);
            
            // Ако има следващ
            if ($nextChar != '') {
                
                // Ако сегашния символ не е съгласна, а следващия е съгласна
                if (!core_String::isConsonent($char) && core_String::isConsonent($nextChar)) {
                    
                    // Вдигаме влага за добавяне на хифенация
                    $addHyphen = TRUE;
                    
                } else {
                    
                    // Ако брояча е над втория допустим праг
                    if ($i > static::MAX_LENGTH_HYPHEN) {
                        
                        // Вдигаме влага за добавяне на хифенация
                        $addHyphen = TRUE;
                    }
                }
            }
            
            // Ако флага е вдигнат
            if ($addHyphen) {
//                $resStr .= $char . "&#173;"; // Знак за softHyphne
                $resStr .= $char . "<wbr>";
                
                // Нулираме брояча
                $i = 0;
            } else {
                
                // Добавяме символа
                $resStr .= $char;
            }
        }
        
        return $resStr;
    }
}
