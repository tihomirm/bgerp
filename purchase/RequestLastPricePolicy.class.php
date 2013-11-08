<?php



/**
* Имплементация на ценова политика "По последна цена"
* Връща последната цена на която е купен даден артикул
* на този клиент (от последната контирана покупка в папката на
* клиента)
*
* @category  bgerp
* @package   purchase
* @author    Ivelin Dimov <ivelin_pdimov@abv.com>
* @copyright 2006 - 2013 Experta OOD
* @license   GPL 3
* @since     v 0.1
* @title     Политика "По последна покупна цена"
*/
class purchase_RequestLastPricePolicy extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Последна цена';


    /**
     * Интерфейс за ценова политика
     */
    public $interfaces = 'price_PolicyIntf';
    
    
	/**
     * Връща продуктите, които могат да се купят от посочения клиент
     *
     * @return array() - масив с опции, подходящ за setOptions на форма
     */
    public function getProducts($customerClass, $customerId, $datetime = NULL)
    {
    	return cat_Products::getByProperty('canBuy');
    }
    
    
    /**
     * Връща последната цена за посочения продукт направена в покупка към контрагента
     * @return object $rec->price  - цена
     * 				  $rec->discount - отстъпка
     */
    function getPriceInfo($customerClass, $customerId, $productId, $productManId, $packagingId = NULL, $quantity = NULL, $date = NULL)
    {
       if(!$date){
       	   $date = dt::now();
        }
        
        // Намира последната цена на която продукта е бил 
        // продаден на този контрагент
        $detailQuery = purchase_RequestDetails::getQuery();
        $detailQuery->EXT('contragentClassId', 'purchase_Requests', 'externalName=contragentClassId,externalKey=requestId');
        $detailQuery->EXT('contragentId', 'purchase_Requests', 'externalName=contragentId,externalKey=requestId');
        $detailQuery->EXT('valior', 'purchase_Requests', 'externalName=valior,externalKey=requestId');
        $detailQuery->EXT('state', 'purchase_Requests', 'externalName=state,externalKey=requestId');
        $detailQuery->where("#contragentClassId = {$customerClass}");
        $detailQuery->where("#contragentId = {$customerId}");
        $detailQuery->where("#valior <= '{$date}'");
        $detailQuery->where("#productId = '{$productId}'");
        $detailQuery->where("#classId = {$productManId}");
        $detailQuery->where("#state = 'active'");
        $detailQuery->orderBy('#valior,#id', 'DESC');
        $lastRec = $detailQuery->fetch();
        
        if(!$lastRec){
        	
        	return NULL;
        }
        
        return (object)array('price' => $lastRec->price, 'discount' => $lastRec->discount);
    }
    
    
    /**
     * Заглавие на ценоразписа за конкретен клиент 
     * 
     * @param mixed $customerClass
     * @param int $customerId
     * @return string
     */
    public function getPolicyTitle($customerClass, $customerId)
    {
        return $this->title;
    }
}