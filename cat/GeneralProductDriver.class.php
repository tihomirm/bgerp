<?php

/**
 * Драйвър за универсален артикул
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Универсален артикул
 */
class cat_GeneralProductDriver extends cat_ProductDriver
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'techno2_SpecificationBaseDriver';


	/**
	 * Дефолт мета данни за всички продукти
	 */
	protected $defaultMetaData = 'canSell,canBuy';
	
	
	/**
	 * Добавя полетата на вътрешния обект
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addEmbeddedFields(core_Fieldset &$form)
	{
		// Добавя полетата само ако ги няма във формата
		
		if(!$form->getField('info', FALSE)){
			$form->FLD('info', 'richtext(rows=6, bucket=Notes)', "caption=Описание,mandatory,formOrder=4");
		} else {
			$form->setField('info', 'input');
		}
		
		if(!$form->getField('measureId', FALSE)){
			$form->FLD('measureId', 'key(mvc=cat_UoM, select=name,allowEmpty)', "caption=Мярка,mandatory,formOrder=4");
		} else {
			$form->setField('measureId', 'input');
		}
		
		if(!$form->getField('photo', FALSE)){
			$form->FLD('photo', 'fileman_FileType(bucket=pictures)', "caption=Изображение,formOrder=4");
		} else {
			$form->setField('photo', 'input');
		}
	}
	
	
	/**
	 * Рендира вградения обект
	 *
	 * @param stdClass $data
	 */
	public function renderEmbeddedData($data)
	{
		// Ако не е зададен шаблон, взимаме дефолтния
		$tpl = (empty($data->tpl)) ? getTplFromFile('cat/tpl/SingleLayoutBaseDriver.shtml') : $data->tpl;
		$tpl->placeObject($data->row);
		
		// Ако ембедъра няма интерфейса за артикул, то към него немогат да се променят параметрите
		if(!$this->EmbedderRec->haveInterface('cat_ProductAccRegIntf')){
			$data->noChange = TRUE;
		}
		
		$paramTpl = cat_products_Params::renderParams($data);
		$tpl->append($paramTpl, 'PARAMS');
		
		return $tpl;
	}
	
	
	/**
	 * Подготвя данните необходими за показването на вградения обект
	 *
	 * @param core_Form $innerForm
	 * @param stdClass $innerState
	 */
	public function prepareEmbeddedData()
	{
		$data = new stdClass();
		$innerForm = $this->innerForm;
		$innerState = $this->innerState;
		
		$fSet = new core_FieldSet;
		$this->addEmbeddedFields($fSet);
		$fields = $fSet->selectFields();
		
		$row = new stdClass();
		foreach ($fields as $name => $fld){
			$row->{$name} = $fld->type->toVerbal($innerState->{$name});
		}
		
		if($innerState->photo){
			$size = array(280, 150);
			$Fancybox = cls::get('fancybox_Fancybox');
			$row->image = $Fancybox->getImage($innerState->photo, $size, array(550, 550));
		}
		
		$data->row = $row;
		
		$data->masterId = $this->EmbedderRec->rec()->id;
		$data->masterClassId = $this->EmbedderRec->getClassId();
		
		cat_products_Params::prepareParams($data);
		
		return $data;
	}
	
	
	/**
	 * Връща информацията за продукта от драйвера
	 * 
	 * @param stdClass $innerState
	 * @param int $packagingId
	 * @return stdClass $res
	 */
	public function getProductInfo($packagingId = NULL)
	{
		$innerState = $this->innerState;
		$res = new stdClass();
		$res->productRec = new stdClass();
		
		$res->productRec->name = ($innerState->title) ? $innerState->title : $innerState->name;
		$res->productRec->info = $innerState->info;
		$res->productRec->measureId = $innerState->measureId;
		
		(!$packagingId) ? $res->packagings = array() : $res->packagingRec = new stdClass();
		
		return $res;
	}
	
	
	/**
	 * Връща стойността на продукта отговаряща на параметъра
	 * 
	 * @param string $sysId - систем ид на параметър (@see cat_Params)
	 * @return mixed - стойността на параметъра за продукта
	 */
	public function getParamValue($sysId)
	{
		return cat_products_Params::fetchParamValue($this->EmbedderRec->rec()->id, $this->EmbedderRec->getClassId(), $sysId);
	}
	
	
	/**
	 * Кои опаковки поддържа продукта
	 */
	public function getPacks()
	{
		return $options = array('' => cat_UoM::getTitleById($this->innerState->measureId));
	}
	
	
	/**
	 * Връща счетоводните свойства на обекта
	 */
	public function getFeatures()
	{
		return cat_products_Params::getFeatures($this->EmbedderRec->getClassId(), $this->EmbedderRec->rec()->id);
	}
	
	
	/**
	 * Връща описанието на артикула според драйвъра
	 * 
	 * @return core_ET
	 */
	public function getProductDescription()
	{
		$data = $this->prepareEmbeddedData();
		$data->noChange = TRUE;
		$data->tpl = getTplFromFile('cat/tpl/SingleLayoutBaseDriverShort.shtml');
		
		$tpl = $this->renderEmbeddedData($data);
		
		$title = ht::createLinkRef($this->EmbedderRec->getTitleById(), array($this->EmbedderRec->instance, 'single', $this->EmbedderRec->that));
		$tpl->removeBlock('INFORMATION');
		$tpl->replace($title, "TITLE");
		
		// Ако няма параметри, премахваме блока им от шаблона
		if(!count($data->params)){
			$tpl->removeBlock('PARAMS');
		}
		
		$tpl->push(('cat/tpl/css/GeneralProductStyles.css'), 'CSS');
		
		$wrapTpl = new ET("<div class='general-product-description'>[#paramBody#]</div>");
		$wrapTpl->append($tpl, 'paramBody');
		
		return $wrapTpl;
	}
	
	
	/**
	 * Кои документи са използвани в полетата на драйвера
	 */
	public function getUsedDocs()
	{
		// Мъчим се да извлечем използваните документи от описанието (ако има такива)
		return doc_RichTextPlg::getAttachedDocs($this->innerState->info);
	}
	
	
	/**
	 * Променя ключовите думи от мениджъра
	 */
	public function alterSearchKeywords(&$searchKeywords)
	{
		$RichText = cls::get('type_Richtext');
		$info = strip_tags($RichText->toVerbal($this->innerForm->info));
		$searchKeywords .= " " . plg_Search::normalizeText($info);
	}
	
	
	/**
	 * Подготвя формата за въвеждане на данни за вътрешния обект
	 *
	 * @param core_Form $form
	 */
	public function prepareEmbeddedForm(core_Form &$form)
	{
		if($this->EmbedderRec->haveInterface('marketing_InquiryEmbedderIntf')){
			$form->setField('photo', 'input=none');
			$form->setDefault('measureId', $this->getDriverUom());
			$form->setField('measureId', 'display=hidden');
		}
		
		if(isset($form->rec->folderId)){
			$Cover = doc_Folders::getCover($form->rec->folderId);
			
			// Ако корицата е категория и има позволени мерки, оставяме само тях
			if($Cover->getInstance() instanceof cat_Categories){
				$arr = keylist::toArray($Cover->fetchField('measures'));
				if(count($arr)){
					$options = array();
					foreach ($arr as $mId){
						$options[$mId] = cat_UoM::getTitleById($mId);
					}
					$form->setOptions('measureId', $options);
				}
			}
		}
		
		// Викаме метода на бащата
		parent::prepareEmbeddedForm($form);
	}
	
	
	/**
	 * Изображението на артикула
	 */
	public function getProductImage()
	{
		return $this->innerState->photo;
	}
	
	
	/**
	 * Колко е теглото на артикула
	 */
	public function getWeight()
	{
		return cat_products_Params::fetchParamValue($this->EmbedderRec->rec()->id, $this->EmbedderRec->getClassId(), 'transportWeight');
	}
	
	
	/**
	 * Колко е обема му
	 */
	public function getVolume()
	{
		return cat_products_Params::fetchParamValue($this->EmbedderRec->rec()->id, $this->EmbedderRec->getClassId(), 'transportVolume');
	}
}