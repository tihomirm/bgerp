<?php



/**
 * Драйвър за експортиране на 'sales_Invoices' изходящи фактури към Bulmar Office
 * 
 * Класа трябва да има $exportableCsvFields за да може да се експортират данни от него в CSV формат
 * 
 * 
 * @category  bgerp
 * @package   bgerp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bgerp_plg_CsvExport extends core_Manager {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'bgerp_ExportIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Експортиране в Csv";
    
    
    /**
     * Можели да се добавя към този мениджър
     */
    function isApplicable($mvc)
    {
    	$exportableFields = arr::make($mvc->exportableCsvFields);
    	
    	return count($exportableFields) ? TRUE : FALSE;
    }
    
    
    /**
     * Подготвя формата за експорт
     * 
     * @param core_Form $form
     */
    function prepareExportForm(core_Form &$form)
    {
    	$sets = $selected = array();
    	$fields = $this->mvc->selectFields();
    	$exportableFields = arr::make($this->mvc->exportableCsvFields, TRUE);
    	
    	foreach ($fields as $name => $fld){
    		if(in_array($name, $exportableFields)){
    			$sets[] = "{$name}={$fld->caption}";
    			$selected[$name] = $name;
    		}
    	}
    	
    	$sets[] = "ExternalLink=Линк";
    	
    	$selectedFields = cls::get('type_Set')->fromVerbal($selected);
    	
    	$sets = implode(',', $sets);
    	$form->FNC('fields', "set($sets)", 'input,caption=Полета,mandatory');
    	$form->setDefault('fields', $selectedFields);
    	
    	$form->FNC('delimiter', 'varchar(1,size=3)', 'input,caption=Разделител,mandatory');
    	$form->FNC('enclosure', 'varchar(1,size=3)', 'input,caption=Ограждане,mandatory');
    	$form->FNC('encoding', 'enum(utf-8=Уникод|* (UTF-8),
                                    cp1251=Windows Cyrillic|* (CP1251),
                                    koi8-r=Rus Cyrillic|* (KOI8-R))', 'caption=Знаци, formOrder=4,input');
    	
    	$form->setOptions('delimiter', array(',' => ',', ';' => ';', ':' => ':', '|' => '|'));
    	$form->setOptions('enclosure', array('"' => '"', '\'' => '\''));
    }
    
    
    /**
     * Проверява импорт формата
     * 
     * @param core_Form $form
     */
    function checkExportForm(core_Form &$form)
    {
    }
    
    
    /**
     * Инпортиране на csv-файл в даден мениджър
     *
     * @param mixed $data - данни
     * @return mixed - експортираните данни
     */
    public function export($filter)
    {
    	$cu = core_Users::getCurrent();
    	$recs = core_Cache::get($this->mvc->className, "exportRecs{$cu}");
    	
    	$conf = core_Setup::get('EF_MAX_EXPORT_CNT', TRUE);
    	if(count($recs) > $conf) {
    		redirect(array($this, 'list'), FALSE, "Броят на заявените записи за експорт надвишава максимално разрешения|* - " . $conf->EF_MAX_EXPORT_CNT, 'error');
    	}
    	
    	$filedsArr = arr::make($filter->fields, TRUE);
    	
    	if ($filedsArr['ExternalLink']) {
    	    $this->prepareExternalLink($recs);
    	}
    	
    	$content = $this->prepareFileContent($recs, $filter->delimiter, $filter->enclosure, $filter->fields);
    	$content = iconv('utf-8', $filter->encoding, $content);
    	
    	return $content;
    }
    
    
    /**
     * Подготвя линковете за виждане от външната част
     * 
     * @param array $recs
     */
    protected function prepareExternalLink(&$recs)
    {
        foreach ((array)$recs as $id => $rec) {
            if ($this->mvc->haveRightFor('single', $id) && $rec->containerId) {
                $mid = doclog_Documents::saveAction(
                    array(
                        'action'      => doclog_Documents::ACTION_EXPORT, 
                        'containerId' => $rec->containerId,
                        'threadId' => $rec->threadId,
                    )
                );
                
                // Флъшваме екшъна за да се запише в модела
                doclog_Documents::flushActions();
                
                $recs[$id]->ExternalLink = bgerp_plg_Blank::getUrlForShow($rec->containerId, $mid);
            }
        }
    }
    
    
    /**
     * Подготвя контента за експортиране
     */
    private function prepareFileContent($recs, $delimiter, $enclosure, $fields)
    {
		$fields = arr::make($fields, TRUE);
		
    	/* за всеки ред */
    	$csv = '';
    	if(is_array($recs)){
    		foreach($recs as $rec) {
    			$this->mvc->invoke('BeforeExportCsv', array($rec));
    			 
    			// Всеки нов ред ва началото е празен
    			$rCsv = '';
    	
    			/* за всяка колона */
    			foreach($fields as $field => $caption) {
    				$type = $this->mvc->fields[$field]->type;
    					
    				if ($type instanceof type_Key) {
    					Mode::push('text', 'plain');
    					$value = $this->mvc->getVerbal($rec, $field);
    					Mode::pop('text');
    				} else {
    					$value = $rec->{$field};
    				}
    				$value = strip_tags($value);
    				
    				if (preg_match('/\\r|\\n|\,|"/', $value)) {
    					$value = $enclosure . str_replace($enclosure, "{$enclosure}{$enclosure}", $value) . $enclosure;
    				}
    				
    				$value = ($value) ? $enclosure . $value . $enclosure : '';
    				$rCsv .= ($rCsv ?  $delimiter : " ") . $value;
    			}
    	
    			/* END за всяка колона */
    			$csv .= $rCsv . "\r\n";
    		}
    	}
    	
    	return $csv;
    }
    
    
    /**
     * Връща името на експортирания файл
     *
     * @return string $name
     */
    public function getExportedFileName()
    {
    	$timestamp = time();
    	$name = $this->mvc->className . "Csv{$timestamp}.csv";
    	 
    	return $name;
    }
}