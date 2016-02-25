<?php



/**
 * Клас 'core_Tabs' - Изглед за табове
 *
 *
 * @category  ef
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Tabs extends core_BaseClass
{
	
	/**
	 * Масив с табове
	 */
	protected $tabs = array();
	
	
	/**
	 * Масив с табове
	 */
	protected $urlParam = 'Tab';
	
	
	/**
	 * Да се рендира ли селектирания таб при принтиране
	 */
	protected $hideSelectedTabOnPrinting = FALSE;
	
	
	/**
	 * Дали да се показва винаги първия таб ако няма избран
	 */
	public $showFirstIfNotSelected = FALSE;
	
	
    /**
     * Инициализиране на обекта
     */
    function init($params = array())
    {
        parent::init($params);
        
        setIfNot($this->htmlClass, 'tab-control');
    }
    
    
    /**
     * Задаване на нов таб
     */
    function TAB($tab, $caption = NULL, $url = NULL, $class = NULL)
    {
        if ($url === NULL) {
            if (!$tab) {
                $url = '';
            } else {
                $url = toUrl(array($tab));
            }
        } elseif (is_array($url)) {
            if(count($url)) {
                $url = toUrl($url);
            } else {
                $url = FALSE;
            }
        }
        
        $this->tabs[$tab] = $url;
        $this->captions[$tab] = $caption ? $caption : $tab;
        $this->classes[$tab] = $class;
    }
    
    
    /**
     * Рендира табове-те
     */
    function renderHtml_($body, $selectedTab = NULL, $hint = NULL, $hintBtn = NULL)
    {
        // Ако няма конфигурирани табове, рендираме само тялото       
        if (!count($this->tabs)) {
            return $body;
        }
        
        // Изчисляване сумата от символите на всички табове
		foreach($this->captions as $tab => $caption) {
			$sumLen += mb_strlen(strip_tags(trim($caption))) + 1;
		}

        //      ,       
        if (!$selectedTab) {
            $selectedTab = Request::get('selectedTab');
        }
        
        if (!$selectedTab) {
        	$selectedTab = $this->getSelected();
        }
        
        //  ,     
        if (!$selectedTab) {
            $selectedTab = key($this->tabs);
        }
        
        foreach ($this->tabs as $tab => $url) {
            
            if ($tab == $selectedTab) {
                $selectedUrl = $url;
                $selected = 'selected';
            } else {
                $selected = '';
            }
            
            $title = tr($this->captions[$tab]);

            $tabClass = $this->classes[$tab];
            
            $displayNone = '';
            
            // Ако е оказано да не рендираме селектирания таб и режима е xhtml,pdf или printing, скриваме го
            if($this->hideSelectedTabOnPrinting === TRUE && $selected){
            	if(Mode::is('printing') || Mode::is('text', 'xhtml') || Mode::is('pdf')){
            		$displayNone = 'display:none !important';
            	}
            }
            
            if ($url) {
                $url = ht::escapeAttr($url);
                $head .= "<div onclick=\"openUrl('{$url}', event)\" style='cursor:pointer;{$displayNone}' class='tab {$selected}'>";
                $head .= "<a onclick=\"return openUrl('{$url}', event);\" href='{$url}' class='tab-title {$tabClass}' style='{$displayNone}'>{$title}</a>";
                if($selected) {
                    $head .= $hintBtn;
                }
            } else {
                $head .= "<div class='tab {$selected}'>";
                $head .= "<span class='tab-title  {$tabClass}'>{$title}</span>";
            }
            
            $head .= "</div>\n";
        }
 
        $html = "<div class='tab-control {$this->htmlClass}'>\n";
        $html .= "<div class='tab-row'><div class='row-holder'>\n";
        $html .= "[#1#]\n";
        $html .= "</div></div>\n";
        
        if($this->htmlId) {
            $idAttr = " id=\"{$this->htmlId}\"";
        }
        $html .= "<div class=\"tab-page clearfix21\"{$idAttr}>{$hint}[#2#]</div>\n";
        $html .= "</div>\n";
        
        $tabsTpl = new ET($html, $head, $body);
        
        return $tabsTpl;
    }
    
    
    /**
     * Дали в таба има таб с посочено име
     * 
     * @param string $name - име на таб, за който проверяваме
     * @return boolean - дали е в таба или не
     */
    public function hasTab($name)
    {
    	return array_key_exists($name, $this->tabs);
    }
    
    
    /**
     * Кой е избрания таб от урл-то
     */
    public function getSelected()
    {
    	$selected = Request::get($this->urlParam);
    	
    	return $selected;
    }
    
    
    /**
     * Кой е първия добавен таб
     */
    public function getFirstTab()
    {
    	return key($this->tabs);
    }
    
    
    /**
     * Кой е урл параметъра
     */
    public function getUrlParam()
    {
    	return $this->urlParam;
    }
    
    
    /**
     * кои са зададените табове в обекта
     */
    public function getTabs()
    {
    	return $this->tabs;
    }
}