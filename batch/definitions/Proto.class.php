<?php


/**
 * Базов драйвер за видове партиди
 *
 *
 * @category  bgerp
 * @package   batch
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class batch_definitions_Proto extends core_BaseClass
{
	
	
	/**
	 * Автоматичен стринг
	 */
	const AUTO_VALUE_STRING = 'Автоматично';
	
	
	/**
	 * Интерфейси които имплементира
	 */
	public $interfaces = 'batch_BatchTypeIntf';
	
	
	/**
	 * Зареден запис
	 */
	protected $rec;
	
	
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
    }
    
    
    /**
     * Кой може да избере драйвера
     */
    public function canSelectDriver($userId = NULL)
    {
    	return TRUE;
    }
    
    
    /**
     * Връща автоматичния партиден номер според класа
     *
     * @param mixed $documentClass - класа за който ще връщаме партидата
     * @param int $id - ид на документа за който ще връщаме партидата
     * @return mixed $value - автоматичния партиден номер, ако може да се генерира
     */
    public function getAutoValue($documentClass, $id)
    {
    }
    
    
    /**
     * Проверява дали стойността е невалидна
     *
     * @param string $value - стойноста, която ще проверяваме
     * @param quantity $quantity - количеството
     * @param string &$msg -текста на грешката ако има
     * @return boolean - валиден ли е кода на партидата според дефиницията или не
     */
    public function isValid($value, $quantity, &$msg)
    {
    	return TRUE;
    }
    
    
    /**
     * Добавя записа
     *
     * @param stdClass $rec
     * @return void
     */
    public function setRec($rec)
    {
    	$this->rec = $rec;
    }
    
    
    /**
     * Проверява дали стойността е невалидна
     *
     * @return core_Type - инстанция на тип
     */
    public function getBatchClassType()
    {
    	$Type = core_Type::getByName('varchar');

    	return $Type;
    }
    
    
    /**
     * Разбива партидата в масив
     * 
     * @param varchar $value - партида
     * @return array $array - масив с партидата
     */
    public function makeArray($value)
    {
    	$value = $this->denormalize($value);
    	
    	return array($value => $this->toVerbal($value));
    }
    
    
    /**
     * Нормализира стойноста на партидата в удобен за съхранение вид
     * 
     * @param string $value
     * @return string $value
     */
    public function normalize($value)
    {
    	return trim($value);
    }
    
    
    /**
     * Денормализира партидата
     * 
     * @param text $value
     * @return text $value
     */
    public function denormalize($value)
    {
    	return $value;
    }
    
    
    /**
     * Кой може да избере драйвера
     */
    public function toVerbal($value)
    {
    	return cls::get('type_Varchar')->toVerbal($value);
    }
    
    
    /**
     * Каква е стойноста, която означава че партидата трябва да се генерира автоматично
     *
     * @return string
     */
    public function getAutoValueConst()
    {
    	return static::AUTO_VALUE_STRING;
    }
    
    
    /**
     * Какви са свойствата на партидата
     * 
     * @param varchar $value - номер на партидара
     * @return array - свойства на партидата
     * 	масив с ключ ид на партидна дефиниция и стойност свойството
     */
    public function getFeatures($value)
    {
    	$classId = $this->getClassId();
    	
    	return array($classId => $value, batch_definitions_ExpirationDate::getClassId() => '10/12/2016');
    }
    
    
    /**
	 * Връща масив с опции за лист филтъра на партидите
	 *
	 * @return array - масив с опции
	 * 		[ключ_на_филтъра] => [име_на_филтъра]
	 */
	public function getListFilterOptions()
    {
    	return array();
    }
    
    
    /**
	 * Добавя филтър към заявката към  batch_Items възоснова на избраната опция (@see getListFilterOptions)
	 *
	 * @param core_Query $query - заявка към batch_Items
	 * @param varchar $value -стойност на филтъра
	 * @param string $featureCaption - Заглавие на колоната на филтъра
	 * @return void
	 */
	public function filterItemsQuery(core_Query &$query, $value, &$featureCaption)
	{
    	
    }
}