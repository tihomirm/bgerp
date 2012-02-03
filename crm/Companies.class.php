<?php



/**
 * Константи за инициализиране на таблицата с контактите
 */
defIfNot('BGERP_OWN_COMPANY_ID', '1');


/**
 * Име на собствената компания (тази за която ще работи bgERP)
 */
defIfNot('BGERP_OWN_COMPANY_NAME', 'Моята Фирма ООД');


/**
 * Държавата на собствената компания (тази за която ще работи bgERP)
 */
defIfNot('BGERP_OWN_COMPANY_COUNTRY', 'Bulgaria');


/**
 * Фирми
 *
 * Мениджър на фирмите
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class crm_Companies extends core_Master
{
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = array(
        // Интерфайс на всички счетоводни пера, които представляват контрагенти
        'crm_ContragentAccRegIntf',
        
        // Интерфайс за всякакви счетоводни пера
        'acc_RegisterIntf',
        
        // Интерфейс за корица на папка
        'doc_FolderIntf'
    );
    
    
    /**
     * Заглавие
     */
    var $title = "Фирми";
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Фирма";
    
    
    /**
     * Икона на единичния обект
     */
    var $singleIcon = 'img/16/group.png';
    
    
    /**
     * Класове за автоматично зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_Printing, plg_State,
                     Groups=crm_Groups, crm_Wrapper, plg_SaveAndNew, plg_PrevAndNext,
                     plg_Sorting, fileman_Files, recently_Plugin, plg_Search, plg_Rejected,
                     acc_plg_Registry,doc_FolderPlg, plg_LastUsedKeys,plg_Select';
    
    
    /**
     * Полетата, които ще видим в таблицата
     */
    var $listFields = 'nameList=Име,phonesBox=Комуникации,addressBox=Адрес,id=№,name=';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    var $searchFields = 'name,pCode,place,country,email,tel,fax,website,vatId';
    
    
    /**
     * Кой  може да пише?
     */
    var $canWrite = 'crm,admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'crm,admin';
    
    
    /**
     * Детайли, на модела
     */
    var $details = 'CompanyExpandData=crm_Persons,ContragentBankAccounts=bank_Accounts,ContragentLocations=crm_Locations';
    
    
    /**
     * @todo Чака за документация...
     */
    var $features = 'place, country';
    
    
    /**
     * @var crm_Groups
     */
    var $Groups;
    
    
    /**
     * Файл с шаблон за единичен изглед на статия
     */
    var $singleLayoutFile = 'crm/tpl/SingleCompanyLayout.shtml';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    var $lastUsedKeys = 'groupList';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Име на фирмата
        $this->FLD('name', 'varchar(255)', 'caption=Фирма,width=100%,mandatory,remember=info');
        $this->FNC('nameList', 'varchar', 'sortingLike=name');
        
        // Адресни данни
        $this->FLD('country', 'key(mvc=drdata_Countries,select=commonName,allowEmpty)', 'caption=Държава,remember');
        $this->FLD('pCode', 'varchar(255)', 'caption=П. код,recently');
        $this->FLD('place', 'varchar(255)', 'caption=Град,width=100%');
        $this->FLD('address', 'varchar(255)', 'caption=Адрес,width=100%');
        
        // Комуникации
        $this->FLD('email', 'emails', 'caption=Имейл,width=100%');
        $this->FLD('tel', 'drdata_PhoneType', 'caption=Телефони,width=100%');
        $this->FLD('fax', 'drdata_PhoneType', 'caption=Факс,width=100%');
        $this->FLD('website', 'url', 'caption=Web сайт,width=100%');
        
        // Данъчен номер на фирмата
        $this->FLD('vatId', 'drdata_VatType', 'caption=Данъчен №,remember=info,width=100%');
        
        // Допълнителна информация
        $this->FLD('info', 'richtext', 'caption=Бележки,height=150px');
        $this->FLD('logo', 'fileman_FileType(bucket=pictures)', 'caption=Лого');
        
        // Данни за съдебната регистрация
        $this->FLD('regCourt', 'varchar', 'caption=Решение по регистрация->Съдилище,width=60%');
        $this->FLD('regDecisionNumber', 'int', 'caption=Решение по регистрация->Номер');
        $this->FLD('regDecisionDate', 'date', 'caption=Решение по регистрация->Дата');
        
        // Фирмено дело
        $this->FLD('regCompanyFileNumber', 'int', 'caption=Фирмено дело->Номер');
        $this->FLD('regCompanyFileYear', 'int', 'caption=Фирмено дело->Година');
        
        // В кои групи е?
        $this->FLD('groupList', 'keylist(mvc=crm_Groups,select=name)', 'caption=Групи->Групи,remember');
        
        // Състояние
        $this->FLD('state', 'enum(active=Вътрешно,closed=Нормално,rejected=Оттеглено)', 'caption=Състояние,value=closed,notNull,input=none');
    }
    
    
    /**
     * Подредба и филтър на on_BeforePrepareListRecs()
     * Манипулации след подготвянето на основния пакет данни
     * предназначен за рендиране на списъчния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        // Подредба
        if($data->listFilter->rec->order == 'alphabetic' || !$data->listFilter->rec->order) {
            $data->query->orderBy('#name');
        } elseif($data->listFilter->rec->order == 'last') {
            $data->query->orderBy('#createdOn=DESC');
        }
        
        if($data->listFilter->rec->alpha) {
            if($data->listFilter->rec->alpha{0} == '0') {
                $cond = "#name NOT REGEXP '^[a-zA-ZА-Яа-я]'";
            } else {
                $alphaArr = explode('-', $data->listFilter->rec->alpha);
                $cond = array();
                $i = 1;
                
                foreach($alphaArr as $a) {
                    $cond[0] .= ($cond[0] ? ' OR ' : '') .
                    "(LOWER(#name) LIKE LOWER('[#{$i}#]%'))";
                    $cond[$i] = $a;
                    $i++;
                }
            }
            
            $data->query->where($cond);
        }
        
        if($data->groupId = Request::get('groupId', 'key(mvc=crm_Groups,select=name)')) {
            $data->query->where("#groupList LIKE '%|{$data->groupId}|%'");
        }
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('order', 'enum(alphabetic=Азбучно,last=Последно добавени)',
            'caption=Подредба,input,silent');
        $data->listFilter->FNC('groupId', 'key(mvc=crm_Groups,select=name,allowEmpty)',
            'placeholder=Всички групи,caption=Група,input,silent');
        $data->listFilter->FNC('alpha', 'varchar', 'caption=Буква,input=hidden,silent');
        
        $data->listFilter->view = 'horizontal';
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        
        // Показваме само това поле. Иначе и другите полета 
        // на модела ще се появят
        $data->listFilter->showFields = 'search,order,groupId';
        
        $data->listFilter->input('alpha,search,order,groupId', 'silent');
    }
    
    
    /**
     * Премахване на бутон и добавяне на нови два в таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareListToolbar($mvc, $res, $data)
    {
        if($data->toolbar->removeBtn('btnAdd')) {
            $data->toolbar->addBtn('Нова фирма',
                array('Ctr' => $this, 'Act'=>'Add'),
                'id=btnAdd,class=btn-add');
        }
    }
    
    
    /**
     * Модифициране на edit формата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $form = $data->form;
        
        if(empty($form->rec->id)) {
            // Слагаме Default за поле 'country'
            $Countries = cls::get('drdata_Countries');
            $form->setDefault('country', $Countries->fetchField("#commonName = '" .
                    BGERP_OWN_COMPANY_COUNTRY . "'", 'id'));
        }
        
        for($i = 1989; $i <= date('Y'); $i++) $years[$i] = $i;
        
        $form->setSuggestions('regCompanyFileYear', $years);
        
        $dcQuery = drdata_DistrictCourts::getQuery();
        
        while($dcRec = $dcQuery->fetch()) {
            $dcName = drdata_DistrictCourts::getVerbal($dcRec, 'type');
            $dcName .= ' - ';
            $dcName .= drdata_DistrictCourts::getVerbal($dcRec, 'city');
            $dcSug[$dcName] = $dcName;
        }
        
        $form->setSuggestions('regCourt', $dcSug);
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    function on_AfterInputeditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        if($form->isSubmitted()) {
            
            // Правим проверка за дублиране с друг запис
            if(!$rec->id) {
                $nameL = "#" . plg_Search::normalizeText(STR::utf2ascii($rec->name)) . "#";
                
                $buzType = arr::make(strtolower(STR::utf2ascii("АД,АДСИЦ,ЕАД,ЕООД,ЕТ,ООД,КД,КДА,СД,LTD,SRL")));
                
                foreach($buzType as $word) {
                    $nameL = str_replace(array("#{$word}", "{$word}#"), array('', ''), $nameL);
                }
                
                $nameL = trim(str_replace('#', '', $nameL));
                
                $query = $mvc->getQuery();
                
                while($similarRec = $query->fetch(array("#searchKeywords LIKE '% [#1#] %'", $nameL))) {
                    $similars[$similarRec->id] = $similarRec;
                    $similarName = TRUE;
                }
                
                $vatNumb = preg_replace("/[^0-9]/", "", $rec->vatId);
                
                if($vatNumb) {
                    $query = $mvc->getQuery();
                    
                    while($similarRec = $query->fetch(array("#vatId LIKE '%[#1#]%'", $vatNumb))) {
                        $similars[$similarRec->id] = $similarRec;
                    }
                    $similarVat = TRUE;
                }
                
                if(count($similars)) {
                    foreach($similars as $similarRec) {
                        $similarCompany .= "<li>";
                        $similarCompany .= ht::createLink($similarRec->name, array($mvc, 'single', $similarRec->id), NULL, array('target' => '_blank'));
                        
                        if($similarRec->vatId) {
                            $similarCompany .= ", " . $mvc->getVerbal($similarRec, 'vatId');
                        }
                        
                        if(trim($similarRec->place)) {
                            $similarCompany .= ", " . $mvc->getVerbal($similarRec, 'place');
                        } else {
                            $similarCompany .= ", " . $mvc->getVerbal($similarRec, 'country');
                        }
                        $similarCompany .= "</li>";
                    }
                    
                    $fields = ($similarVat && $similarName) ? "name,vatId" : ($similarName ? "name" : "vatId");
                    
                    $sledniteFirmi = (count($similars) == 1) ? "следната фирма" : "следните фирми";
                    
                    $form->setWarning($fields, "Възможно е дублиране със {$sledniteFirmi}|*: <ul>{$similarCompany}</ul>");
                }
            }
            
            if($rec->place) {
                $rec->place = drdata_Address::canonizePlace($rec->place);
            }
            
            if($rec->regCompanyFileYear && $rec->regDecisionDate) {
                $dYears = abs($rec->regCompanyFileYear - (int) $rec->regDecisionDate);
                
                if($dYears > 1) {
                    $form->setWarning('regCompanyFileYear,regDecisionDate', "Годината на регистрацията на фирмата и фирменото дело се различават твърде много.");
                }
            }
        }
    }
    
    
    /**
     * Манипулации със заглавието
     *
     * @param core_Mvc $mvc
     * @param core_Et $tpl
     * @param stdClass $data
     */
    function on_BeforeRenderListTitle($mvc, $tpl, $data)
    {
        if($data->listFilter->rec->groupId) {
            $data->title = "Фирми в групата|* \"<b style='color:green'>" .
            $this->Groups->getTitleById($data->groupId) . "</b>\"";
        } elseif($data->listFilter->rec->search) {
            $data->title = "Фирми отговарящи на филтъра|* \"<b style='color:green'>" .
            type_Varchar::escape($data->listFilter->rec->search) .
            "</b>\"";
        } elseif($data->listFilter->rec->alpha) {
            if($data->listFilter->rec->alpha{0} == '0') {
                $data->title = "Фирми, които не започват с букви";
            } else {
                $data->title = "Фирми започващи с буквите|* \"<b style='color:green'>{$data->listFilter->rec->alpha}</b>\"";
            }
        } else {
            $data->title = NULL;
        }
    }
    
    
    /**
     * Добавяне на табове
     *
     * @param core_Et $tpl
     * @return core_et $tpl
     */
    function renderWrapping_($tpl)
    {
        $mvc = $this;
        
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphavit'));
        
        $alpha = Request::get('alpha');
        
        $selected = 'none';
        
        $letters = arr::make('0-9,А-A,Б-B,В-V=В-V-W,Г-G,Д-D,Е-E,Ж-J,З-Z,И-I,Й-J,К-Q=К-K-Q-C,' .
            'Л-L,М-M,Н-N,О-O,П-P,Р-R,С-S,Т-T,У-U,Ф-F,Х-H=Х-X-H,Ц-Ч,Ш-Щ,Ю-Я', TRUE);
        
        foreach($letters as $a => $set) {
            $tabs->TAB($a, '|*' . str_replace('-', '<br>', $a), array($mvc, 'list', 'alpha' => $set));
            
            if($alpha == $set) {
                $selected = $a;
            }
        }
        
        $tpl = $tabs->renderHtml($tpl, $selected);
        
        //$tpl->prepend('<br>');
        
        return $tpl;
    }
    
    
    /**
     * Промяна на данните от таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec, $fields)
    {
        $row->nameList = $row->name;
        
        $row->nameTitle = mb_strtoupper($rec->name);
        $row->nameLower = mb_strtolower($rec->name);
        
        if($fields['-single']) {
            // Fancy ефект за картинката
            $Fancybox = cls::get('fancybox_Fancybox');
            
            $tArr = array(200, 150);
            $mArr = array(600, 450);
            
            if($rec->logo) {
                $row->image = $Fancybox->getImage($rec->logo, $tArr, $mArr);
            } else {
                $row->image = "<img class=\"hgsImage\" src=" . sbf('img/noimage120.gif') . " alt='no image'>";
            }
        }
        
        $row->country = tr($mvc->getVerbal($rec, 'country'));
        
        $pCode = $mvc->getVerbal($rec, 'pCode');
        $place = $mvc->getVerbal($rec, 'place');
        $address = $mvc->getVerbal($rec, 'address');
        
        $row->addressBox = $row->country;
        $row->addressBox .= ($pCode || $place) ? "<br>" : "";
        
        $row->addressBox .= $pCode ? "{$pCode} " : "";
        $row->addressBox .= $place;
        
        $row->addressBox .= $address ? "<br/>{$address}" : "";
        
        $tel = $mvc->getVerbal($rec, 'tel');
        $fax = $mvc->getVerbal($rec, 'fax');
        $eml = $mvc->getVerbal($rec, 'email');
        
        // phonesBox
        $row->phonesBox .= $tel ? "<div class='telephone'>{$tel}</div>" : "";
        $row->phonesBox .= $fax ? "<div class='fax'>{$fax}</div>" : "";
        $row->phonesBox .= $eml ? "<div class='email'>{$eml}</div>" : "";
        
        $row->title = $mvc->getTitleById($rec->id);
        
        $vatType = new drdata_VatType();
        
        $vat = $vatType->toVerbal($rec->vatId);
        
        $row->title .= ($vat ? "&nbsp;&nbsp;<div style='float:right'>{$vat}</div>" : "");
        $row->nameList .= ($vat ? "<div style='font-size:0.8em;margin-top:5px;'>{$vat}</div>" : "");
        
        //bp($row);
        // END phonesBox
    }
    
    
    /**
     * След всеки запис (@see core_Mvc::save_())
     */
    function on_AfterSave(crm_Companies $mvc, $id, $rec)
    {
        if($rec->groupList) {
            $mvc->updateGroupsCnt = TRUE;
        }
        $mvc->updatedRecs[$id] = $rec;
        
        /**
         * @TODO Това не трябва да е тук, но по някаква причина не сработва в on_Shutdown()
         */
        $mvc->updateRoutingRules($rec);
    }
    
    
    /**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    function on_Shutdown($mvc)
    {
        if($mvc->updateGroupsCnt) {
            $mvc->updateGroupsCnt();
        }
        
        if(count($mvc->updatedRecs)) {
            foreach($mvc->updatedRecs as $id => $rec) {
                $mvc->updateRoutingRules($rec);
            }
        }
    }
    
    
    /**
     * Прекъсва връзките на изтритите визитки с всички техни имейл адреси.
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param core_Query $query
     */
    function on_AfterDelete($mvc, $res, $query)
    {
        foreach ($query->getDeletedRecs() as $rec) {
            // изтриваме всички правила за рутиране, свързани с визитката
            email_Router::removeRules('person', $rec->id);
        }
    }
    
    
    /**
     * Обновява информацията за количеството на визитките в групите
     */
    function updateGroupsCnt()
    {
        $query = $this->getQuery();
        
        while($rec = $query->fetch()) {
            $keyArr = type_Keylist::toArray($rec->groupList);
            
            foreach($keyArr as $groupId) {
                $groupsCnt[$groupId]++;
            }
        }
        
        if(count($groupsCnt)) {
            foreach($groupsCnt as $groupId => $cnt) {
                $groupsRec = new stdClass();
                $groupsRec->companiesCnt = $cnt;
                $groupsRec->id = $groupId;
                $this->Groups->save($groupsRec, 'companiesCnt');
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function updateRoutingRules($rec)
    {
        if ($rec->state == 'rejected') {
            // Визитката е оттеглена - изтриваме всички правила за рутиране, свързани с нея
            email_Router::removeRules('company', $rec->id);
        } else {
            if ($rec->email) {
                static::createRoutingRules($rec->email, $rec->id);
            }
        }
    }
    
    /**
     * Създава `From` и `Doman` правила за рутиране след запис на визитка
     *
     * Използва се от @link crm_Persons::updateRoutingRules() като инструмент за добавяне на
     * правила
     *
     * @access protected
     * @param string $email
     * @param int $objectId
     */
    protected static function createRoutingRules($email, $objectId)
    {
        /**
         * @TODO $email съдържа списък от имейл адреси!?
         */
        // Приоритетът на всички правила, генериране след запис на визитка е нисък и намаляващ с времето
        $priority = email_Router::dateToPriority(dt::now(), 'low', 'desc');
        
        // Създаване на `From` правило
        email_Router::saveRule(
            (object)array(
                'type' => email_Router::RuleFrom,
                'key' => email_Router::getRoutingKey($email, NULL, email_Router::RuleFrom),
                'priority' => $priority,
                'objectType' => 'company',
                'objectId' => $objectId
            )
        );
        
        // Създаване на `Domain` правило
        if ($key = email_Router::getRoutingKey($email, NULL, email_Router::RuleDomain)) {
            // $key се генерира само за непублични домейни (за публичните е FALSE), така че това 
            // е едновременно индиректна проверка дали домейнът е публичен.
            email_Router::saveRule(
                (object)array(
                    'type' => email_Router::RuleDomain,
                    'key' => $key,
                    'priority' => $priority,
                    'objectType' => 'company',
                    'objectId' => $objectId
                )
            );
        }
    }
    
    
    /**
     * Ако е празна таблицата с контактите я инициализираме с един нов запис
     * Записа е с id=1 и е с данните от файла bgerp.cfg.php
     *
     * @param unknown_type $mvc
     * @param unknown_type $res
     */
    function on_AfterSetupMvc($mvc, &$res)
    {
        if (!$mvc->fetch(BGERP_OWN_COMPANY_ID)){
            
            $rec = new stdClass();
            $rec->id = BGERP_OWN_COMPANY_ID;
            $rec->name = BGERP_OWN_COMPANY_NAME;
            
            // Страната не е стринг, а id
            $Countries = cls::get('drdata_Countries');
            $rec->country = $Countries->fetchField("#commonName = '" . BGERP_OWN_COMPANY_COUNTRY . "'", 'id');
            
            if($mvc->save($rec, NULL, 'REPLACE')) {
                
                $res .= "<li style='color:green'>Фирмата " . BGERP_OWN_COMPANY_NAME . " е записана с #id=" .
                BGERP_OWN_COMPANY_ID . " в базата с контктите</li>";
            }
        }
        
        if(Request::get('Full')) {
            
            $query = $mvc->getQuery();
            
            while($rec = $query->fetch()) {
                if($rec->id == BGERP_OWN_COMPANY_ID) {
                    $rec->state = 'active';
                } elseif($rec->state == 'active') {
                    $rec->state = 'closed';
                }
                
                $mvc->save($rec, 'state');
            }
        }
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('pictures', 'Снимки', 'jpg,jpeg', '3MB', 'user', 'every_one');
    }
    
    /****************************************************************************************
     *                                                                                      *
     *  Методи на интерфейс "doc_FoldersIntf"                                               *
     *                                                                                      *
     ****************************************************************************************/
    
    
    /**
     * Връща заглавието на папката
     */
    static function getRecTitle($rec)
    {
        $title = $rec->name;
        
        $country = drdata_Countries::fetchField($rec->country, 'commonName');
        
        if($rec->place && ($country == BGERP_OWN_COMPANY_COUNTRY)) {
            $title .= ' - ' . $rec->place;
        } else {
            $title .= ' - ' . $country;
        }
        
        return $title;
    }
    
    /*******************************************************************************************
     * 
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     * 
     ******************************************************************************************/
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = NULL;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->id,
                'title' => $rec->name,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::getLinkToObj
     * @param int $objectId
     */
    static function getLinkToObj($objectId)
    {
        $self = cls::get(__CLASS__);
        
        if ($rec = $self->fetch($objectId)) {
            $result = ht::createLink($rec->name, array($self, 'Single', $objectId));
        } else {
            $result = '<i>неизвестно</i>';
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
    }
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */
    
    
    /**
     * Връща данните на фирмата с посочения имейл
     * @param email   $email - Имейл
     * @param integer $id    - id' то на записа
     *
     * return object
     */
    static function getRecipientData($email, $id = NULL)
    {
        //Ако не е валиден имейл, връщаме празна стойност
        if (!type_Email::isValidEmail($email)) return;
        
        //Вземаме данните от визитката
        $query = crm_Companies::getQuery();
        
        if ($id) {
            $query->where($id);
        } else {
            $query->where("#email LIKE '%{$email}%'");
        }
        $query->orderBy('createdOn');
        
        //Шаблон за регулярния израз
        $pattern = '/[\s,:;\\\[\]\(\)\>\<]/';
        
        while ($company = $query->fetch()) {
            //Ако има права за single
            if(!crm_Companies::haveRightFor('single', $company)) {
                
                continue;
            }
            
            //Всички имейли
            $values = preg_split($pattern, $company->email, NULL, PREG_SPLIT_NO_EMPTY);
            
            //Проверяваме дали същия емайл го има въведено в модела
            if (count($values)) {
                foreach ($values as $val) {
                    if ($val == $email) {
                        
                        $contrData->recipient = $company->name;
                        $contrData->phone = $company->tel;
                        $contrData->fax = $company->fax;
                        $contrData->country = crm_Companies::getVerbal($company, 'country');
                        $contrData->pcode = $company->pCode;
                        $contrData->place = $company->place;
                        $contrData->address = $company->address;
                        $contrData->email = $company->email;
                        
                        return $contrData;
                    }
                }
            }
        }
    }
}