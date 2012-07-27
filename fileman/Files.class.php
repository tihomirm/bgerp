<?php


/**
 * Какъв е шаблона за манипулатора на файла?
 */
defIfNot('FILEMAN_HANDLER_PTR', '$*****');


/**
 * Каква да е дължината на манипулатора на файла?
 */
defIfNot('FILEMAN_HANDLER_LEN', strlen(FILEMAN_HANDLER_PTR));


/**
 * Клас 'fileman_Files' -
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class fileman_Files extends core_Master 
{
    
    
    /**
     * Детайла, на модела
     */
    var $details = 'fileman_FileDetails';
    
    
    /**
     * 
     */
    var $canEdit = 'no_one';
    
    /**
     * Всички потребители могат да разглеждат файлове
     */
    var $canSingle = 'user';
    
    /**
     * 
     */
    var $canDelete = 'no_one';
    
    
    /**
     * 
     */
     var $singleLayoutFile = 'fileman/tpl/SingleLayoutFile.shtml';
    
     
    /**
     * Заглавие на модула
     */
    var $title = 'Файлове';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        // Файлов манипулатор - уникален 8 символно/цифров низ, започващ с буква.
        // Генериран случайно, поради което е труден за налучкване
        $this->FLD("fileHnd", "varchar(" . strlen(FILEMAN_HANDLER_PTR) . ")",
            array('notNull' => TRUE, 'caption' => 'Манипулатор'));
        
        // Име на файла
        $this->FLD("name", "varchar(255)",
            array('notNull' => TRUE, 'caption' => 'Файл'));
        
        // Данни (Съдържание) на файла
        $this->FLD("dataId", "key(mvc=fileman_Data)",
            array('caption' => 'Данни Id'));
        
        // Клас - притежател на файла
        $this->FLD("bucketId", "key(mvc=fileman_Buckets, select=name)",
            array('caption' => 'Кофа'));
        
        // Състояние на файла
        $this->FLD("state", "enum(draft=Чернова,active=Активен,rejected=Оттеглен)",
            array('caption' => 'Състояние'));
        
        // Плъгини за контрол на записа и модифицирането
        $this->load('plg_Created,plg_Modified,Data=fileman_Data,Buckets=fileman_Buckets,' .
            'Download=fileman_Download,Versions=fileman_Versions,fileman_Wrapper');
        
        // Индекси
        $this->setDbUnique('fileHnd');
        $this->setDbUnique('name,bucketId', 'uniqName');
    }
    
    
    /**
     * Преди да запишем, генерираме случаен манипулатор
     */
    static function on_BeforeSave(&$mvc, &$id, &$rec)
    {
        // Ако липсва, създаваме нов уникален номер-държател
        if(!$rec->fileHnd) {
            do {
                
                if(16 < $i++) error('Unable to generate random file handler', $rec);
                
                $rec->fileHnd = str::getRand(FILEMAN_HANDLER_PTR);
            } while($mvc->fetch("#fileHnd = '{$rec->fileHnd}'"));
        } elseif(!$rec->id) {
            
            $existingRec = $mvc->fetch("#fileHnd = '{$rec->fileHnd}'");
            
            $rec->id = $existingRec->id;
        }
    }
    
    
    /**
     * Сингъла на файловете
     */
    function act_Single()
    {
        // Манипулатора на файла
        $fh = Request::get('id');
        
        // Очакваме да има подаден манипулатор на файла
        expect($fh, 'Липсва манупулатора на файла');
        
        // Ескейпваме манипулатора
        $fh = $this->db->escape($fh);

        // Записа за съответния файл
        $fRec = fileman_Files::fetchByFh($fh);
        
        // Очакваме да има такъв запис
        expect($fRec, 'Няма такъв запис.');
        
        // Задаваме id' то на файла да е самото id, а не манупулатора на файла
        Request::push(array('id' => $fRec->id));
        
        return parent::act_Single();
    }
    
    
    /**
     * Задава файла с посоченото име в посочената кофа
     */
    function setFile($path, $bucket, $fname = NULL, $force = FALSE)
    {
        if($fname === NULL) $fname = basename($path);
        
        $Buckets = cls::get('fileman_Buckets');
        
        expect($bucketId = $Buckets->fetchByName($bucket));
        
        $fh = $this->fetchField(array("#name = '[#1#]' AND #bucketId = {$bucketId}",
                $fname,
            ), "fileHnd");
        
        if(!$fh) {
            $fh = $this->addNewFile($path, $bucket, $fname);
        } elseif($force) {
            $this->setContent($fh, $path);
        }
        
        return $fh;
    }
    
    
    /**
     * Добавя нов файл в посочената кофа
     */
    function addNewFile($path, $bucket, $fname = NULL)
    {
        if($fname === NULL) $fname = basename($path);
        
        $Buckets = cls::get('fileman_Buckets');
        
        $bucketId = $Buckets->fetchByName($bucket);
        
        $fh = $this->createDraftFile($fname, $bucketId);
        
        $this->setContent($fh, $path);
        
        return $fh;
    }
    
    
    /**
     * Добавя нов файл в посочената кофа от стринг
     */
    function addNewFileFromString($string, $bucket, $fname = NULL)
    {
        $me = cls::get('fileman_Files');
        
        if($fname === NULL) $fname = basename($path);
        
        $Buckets = cls::get('fileman_Buckets');
        
        $bucketId = $Buckets->fetchByName($bucket);
        
        $fh = $me->createDraftFile($fname, $bucketId);
        
        $me->setContentFromString($fh, $string);
        
        return $fh;
    }
    
    
    /**
     * Създаваме нов файл в посочената кофа
     */
    function createDraftFile($fname, $bucketId)
    {
        expect($bucketId, 'Очаква се валидна кофа');
        
        $rec = new stdClass();
        $rec->name = $this->getPossibleName($fname, $bucketId);
        $rec->bucketId = $bucketId;
        $rec->state = 'draft';
        
        $this->save($rec);
        
        return $rec->fileHnd;
    }


    /**
     * Променя името на съществуващ файл
     * Връща новото име, което може да е различно от желаното ново име
     */
    static function rename($id, $newName) 
    {
        expect($rec = static::fetch($id));

        if($rec->name != $newName) { 
            $rec->name = static::getPossibleName($newName, $rec->bucketId); 
            static::save($rec);
        }

        return $rec->name;
    }
    
    
    /**
     * Връща първото възможно има, подобно на зададеното, така че в този
     * $bucketId да няма повторение на имената
     */
    static function getPossibleName($fname, $bucketId)
    {
        // Конвертираме името към такова само с латински букви, цифри и знаците '-' и '_'
        $fname = STR::utf2ascii($fname);
        $fname = preg_replace('/[^a-zA-Z0-9\-_\.]+/', '_', $fname);
        
        // Циклим докато генерираме име, което не се среща до сега
        $fn = $fname;
        
        if(($dotPos = strrpos($fname, '.')) !== FALSE) {
            $firstName = substr($fname, 0, $dotPos);
            $ext = substr($fname, $dotPos);
        } else {
            $firstName = $fname;
            $ext = '';
        }
        
        // Двоично търсене за свободно име на файл
        $i = 1;
        
        while(self::fetchField(array("#name = '[#1#]' AND #bucketId = '{$bucketId}'", $fn), 'id')) {
            $fn = $firstName . '_' . $i . $ext;
            $i = $i * 2;
        }
        
        // Търсим първото незаето положение за $i в интервала $i/2 и $i
        if($i > 4) {
            $min = $i / 4;
            $max = $i / 2;
            
            do {
                $i =  ($max + $min) / 2;
                $fn = $firstName . '_' . $i . $ext;
                
                if(self::fetchField(array("#name = '[#1#]' AND #bucketId = '{$bucketId}'", $fn), 'id')) {
                    $min = $i;
                } else {
                    $max = $i;
                }
            } while ($max - $min > 1);
            
            $i = $max;
            
            $fn = $firstName . '_' . $i . $ext;
        }
        
        return $fn;
    }
    
    
    /**
     * Ако имаме нови данни, които заменят стари
     * такива указваме, че старите са стара версия
     * на файла и ги разскачаме от файла
     */
    function setData($fileHnd, $newDataId)
    {
        $rec = $this->fetch("#fileHnd = '{$fileHnd}'");
        
        // Ако новите данни са същите, като старите 
        // нямаме смяна
        if($rec->dataId == $newDataId) return $rec->dataId;
        
        // Ако имаме стари данни, изпращаме ги в историята
        if($rec->dataId) {
            $verRec->fileHnd = $fileHnd;
            $verRec->dataId = $rec->dataId;
            $verRec->from = $rec->modifiedOn;
            $verRec->to = dt::verbal2mysql();
            $this->Versions->save($verRec);
            
            // Намаляваме с 1 броя на линковете към старите данни
            $this->Data->decreaseLinks($rec->dataId);
        }
        
        // Записваме новите данни
        $rec->dataId = $newDataId;
        $rec->state = 'active';
        
        $this->save($rec);
        
        // Увеличаваме с 1 броя на линковете към новите данни
        $this->Data->increaseLinks($newDataId);
        
        return $rec->dataId;
    }
    
    
    /**
     * Задава данните на даден файл от съществуващ файл в ОС
     */
    function setContent($fileHnd, $osFile)
    {
        $dataId = $this->Data->absorbFile($osFile);
        
        return $this->setData($fileHnd, $dataId);
    }
    
    
    /**
     * Задава данните на даден файл от стринг
     */
    function setContentFromString($fileHnd, $string)
    {
        $dataId = $this->Data->absorbString($string);
        
        return $this->setData($fileHnd, $dataId);
    }
    
    
    /**
     * Връща данните на един файл като стринг
     */
    static function getContent($hnd)
    {
        expect($path = fileman_Files::fetchByFh($hnd, 'path'));
        
        return file_get_contents($path);
    }
    
    
    /**
     * Копира данните от един файл на друг файл
     */
    function copyContent($sHnd, $dHnd)
    {
        $sRec = $this->fetch("#fileHnd = '{$sHnd}'");
        
        if($sRec->state != 'active') return FALSE;
        
        return $this->setData($fileHnd, $sRec->dataId);
    }
    
    
    /**
     * Връща записа за посочения файл или негово поле, ако е указано.
     * Ако посоченото поле съществува в записа за данните за файла,
     * връщаната стойност е от записа за данните на посочения файл
     */
    static function fetchByFh($fh, $field = NULL)
    {
        $Files = cls::get('fileman_Files');
        
        $rec = $Files->fetch("#fileHnd = '{$fh}'");
        
        if($field === NULL) return $rec;
        
        if(!isset($rec->{$field})) {
            $Data = cls::get('fileman_Data');
            
            $dataFields = $Data->selectFields("");
            
            if($dataFields[$field]) {
                $rec = $Data->fetch($rec->dataId);
            }
        }
        
        return $rec->{$field};
    }
    
    
    /**
     * Какви роли са необходими за качване или сваляне?
     */
    static function on_BeforeGetRequiredRoles($mvc, &$roles, $action, $rec = NULL, $userId = NULL)
    {
        if($action == 'download' && is_object($rec)) {
            $roles = $mvc->Buckets->fetchField($rec->bucketId, 'rolesForDownload');
        } elseif($action == 'add' && is_object($rec)) {
            $roles = $mvc->Buckets->fetchField($rec->bucketId, 'rolesForAdding');
        } else {
            
            return;
        }
        
        return FALSE;
    }
    
    
    /**
     * Извиква се след конвертирането на реда ($rec) към вербални стойности ($row)
     */
    static function on_AfterRecToVerbal($mvc, $row, $rec)
    {   
        try {
            $row->name = $mvc->Download->getDownloadLink($rec->fileHnd);
        } catch(core_Exception_Expect $e) {
             
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function makeBtnToAddFile($title, $bucketId, $callback, $attr = array())
    {
        $function = $this->getJsFunctionForAddFile($bucketId, $callback);
        
        return ht::createFnBtn($title, $function, NULL, $attr);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function makeLinkToAddFile($title, $bucketId, $callback, $attr = array())
    {
        $attr['onclick'] = $this->getJsFunctionForAddFile($bucketId, $callback);
        $attr['href'] = $this->getUrLForAddFile($bucketId, $callback);
        $attr['target'] = 'addFileDialog';
        
        return ht::createElement('a', $attr, $title);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function getUrLForAddFile($bucketId, $callback)
    {
        Request::setProtected('bucketId,callback');
        $url = array('fileman_Upload', 'dialog', 'bucketId' => $bucketId, 'callback' => $callback);
        
        return toUrl($url);
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getJsFunctionForAddFile($bucketId, $callback)
    {
        $url = $this->getUrLForAddFile($bucketId, $callback);
        
        $windowName = 'addFileDialog';
        
        if(Mode::is('screenMode', 'narrow')) {
            $args = 'resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
        } else {
            $args = 'width=400,height=320,resizable=yes,scrollbars=yes,status=no,location=no,menubar=no,location=no';
        }
        
        return "openWindow('{$url}', '{$windowName}', '{$args}'); return false;";
    }
    
    
    /**
     * Превръща масив с fileHandler' и в масив с id' тата на файловете
     * 
     * @param array $fh - Масив с манупулатори на файловете
     * 
     * @return array $newArr - Масив с id' тата на съответните файлове
     */
    static function getIdFromFh($fh)
    {
        //Преобразуваме към масив
        $fhArr = (array)$fh;
        
        //Създаваме променлива за id' тата
        $newArr = array();
        
        foreach ($fhArr as $val) {
            
            //Ако няма стойност, прескачаме
            if (!$val) continue;
            
            //Ако стойността не е число
            if (!is_numeric($val)) {
                
                //Вземема id'то на файла
                try {
                    $id = static::fetchByFh($val, 'id');
                } catch (Exception $e) {
                    //Ако няма такъв fh, тогава прескачаме
                    continue;
                }   
            } else {
                
                //Присвояваме променливата, като id
                $id = $val;
            }
            
            //Записваме в масива
            $newArr[$id] = $id;
        }
        
        return $newArr;
    }
    
    
    /**
     * Изпълнява се преди подготовката на single изглед
     */
    function on_BeforeRenderSingle($mvc, $tpl, &$data)
    {
//        // Проверяваме за права
//        $mvc->requireRightFor('single', $data->rec->id);
        
        $dataId = $data->rec->dataId;
        $fileHnd = $data->rec->fileHnd;
        
        expect($dataId, 'Няма данни за файла');

        $iRec = bgerp_FileInfo::getFileInfo($data->rec);
        
        bp($data->rec, $iRec);
        
        bp($dataId);
        
        
        
        
        
        $row = &$data->row;
        $rec = $data->rec;

        // Ако има активен линк за сваляне
        if (($dRec = fileman_Download::fetch("#fileId = {$rec->id}")) && (dt::mysql2timestamp($dRec->expireOn)>time())) {

            // Името на файла
            $fileName = fileman_Download::getVerbal($dRec, 'fileName');
            
            // Линка на файла
            $link = sbf(EF_DOWNLOAD_ROOT . '/' . $dRec->prefix . '/' . $fileName, '', TRUE);
            
            // До кога е активен линка
            $expireOn = dt::mysql2Verbal($dRec->expireOn, 'smartTime');

            // Задаваме шаблоните 
            $row->expireOn = $expireOn; 
            $row->link = $link;
        }
        
        // Вербалното име на файла
        $row->fileName = $mvc->getVerbal($rec,'name');
        
        // Типа на файла
        $row->type = fileman_Mimes::getMimeByExt(fileman_Files::getExt($rec->name));
        
        // Вербалния размер на файла
        $row->size = fileman_Data::getFileSize($rec->dataId);
        
        // Информация за файла
        $row->info = self::getFileInfo($rec->dataId);
        
        // Версиите на файла
        $row->versions = self::getFileVersionsString($rec->id);
    }
    
    
    /**
     * 
     */
    function on_AfterRenderSingle($mvc, &$tpl, &$data)
    {
        // Манипулатора на файла
        $fh = $data->rec->fileHnd;
        
        // Разширението на файла
        $ext = self::getExt($data->rec->name);

        // Проверяваме дали разширението, предлага preview на файла
        if (!in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'bmp'))) {

            return ;
        }
        
        //Вземема конфигурационните константи
        $conf = core_Packs::getConfig('fileman');
        
        // В зависимост от широчината на екрана вземаме размерите на thumbnail изображението
        if (mode::is('screenMode', 'narrow')) {
            $thumbWidth = $conf->FILEMAN_PREVIEW_WIDTH_NARROW;
            $thumbHeight = $conf->FILEMAN_PREVIEW_HEIGHT_NARROW;
        } else {
            $thumbWidth = $conf->FILEMAN_PREVIEW_WIDTH;
            $thumbHeight = $conf->FILEMAN_PREVIEW_HEIGHT;
        }
        
        // Атрибути на thumbnail изображението
        $attr = array('baseName' => 'Preview', 'isAbsolute' => FALSE, 'qt' => '');
            
        //Размера на thumbnail изображението
        $size = array($thumbWidth, $thumbHeight);
        
        //Създаваме тумбнаил с параметрите
        $thumbnailImg = thumbnail_Thumbnail::getImg($fh, $size, $attr);
        
        if ($thumbnailImg) {
            
            // Background' а на preview' то
            $bgImg = sbf('fileman/img/Preview_background.jpg');
        
            // Създаваме шаблон за preview на изображението
            $preview = new ET("<fieldset><legend>Преглед</legend><div style='background-image:url(" . $bgImg . "); padding:10px 0; '><div style='margin: 0 auto; display:table;'>[#THUMB_IMAGE#]</div></div></fieldset>");
            
            // Добавяме към preview' то генерираното изображение
            $preview->append($thumbnailImg, 'THUMB_IMAGE');
            
            // Добаваме preview' то към шаблона
            $tpl->append($preview);    
        }
    }
    
    
    /**
     * Връща разширението на файла, от името му
     */
    static function getExt($name)
    {
        if(($dotPos = mb_strrpos($name, '.')) !== FALSE) {
            $ext = mb_substr($name, $dotPos + 1);
        } else {
            $ext = '';
        }
        
        return $ext;
    }

    
    /**
     * Връща типа на файла
     * 
     * @param string $fileName - Името на файла
     * 
     * @return string - mime типа на файла
     */
    static function getType($fileName)
    {
        if (($dotPos = mb_strrpos($fileName, '.')) !== FALSE) {
            
            // Файл за mime типове
            include(dirname(__FILE__) . '/data/mimes.inc.php');
            
            // Разширение на файла
            $ext = mb_substr($fileName, $dotPos + 1);
        
            return $mimetypes["{$ext}"];
        }
    }
    
    
    /**
     * Връща информация за файла
     * 
     * @param $dataId - id' то на файла, с данните
     * 
     * @return string $fileInfo - Информация за файла
     * @access private
     * @todo Временно решение
     */
    static function getFileInfo($dataId)
    {
        // Пътя до файла
        $path = fileman_Data::getFilePath($dataId);

        // TODO временно решени
        // Ще се промени
        $fileInfo = exec("file {$path}");
        
        $fileInfo = str_ireplace($path . ':', '', $fileInfo);
        
        $fileInfo = str::trim($fileInfo);
        
        return $fileInfo;
    }
    
    
    /**
     * Връща стринг с всички версии на файла, който търсим
     */
    static function getFileVersionsString($id)
    {
        // Масив с всички версии на файла
        $fileVersionsArr = fileman_FileDetails::getFileVersionsArr($id);
        
        foreach ($fileVersionsArr as $fileHnd => $fileInfo) {
            
            // Линк към single' а на файла
            $link = ht::createLink($fileInfo['fileName'], array('fileman_Files', 'single', $fileHnd), FALSE, array('title' => $fileInfo['versionInfo']));
            
            // Всеки линк за файла да е на нов ред
            $text .= ($text) ? '<br />' . $link : $link;
        }
        
        return $text;
    }
    
    
    /**
     * Връща името на файла без разширението му
     * 
     * @param mixed $fh - Манипулатор на файла или пътя до файла
     * 
     * @retun string $name - Името на файла, без разширението
     */
    static function getFileNameWithoutExt($fh)
    {
        // Ако е подаден път до файла
        if (strstr($fh, '/')) {
            
            // Вземаме името на файла
            $fname = basename($fh);
        } else {
            
            // Ако е подаден манипулатор на файл
            // Вземаме името на файла
            $fRec = static::fetchByFh($fh);
            $fname = $fRec->name;
        }
        
        // Ако има разширение
        if(($dotPos = mb_strrpos($fname, '.')) !== FALSE) {
            $name = mb_substr($fname, 0, $dotPos);
        } else {
            $name = $fname;
        }
        
        return $name;
    }
    

	/**
     * 
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        // Добавяме бутон за сваляне
        $downloadUrl = toUrl(array('fileman_Download', 'Download', 'fh' => $data->rec->fileHnd, 'forceDownload' => TRUE), FALSE);
        $data->toolbar->addBtn('Сваляне', $downloadUrl, 'id=btn-download,class=btn-download', array('order=8'));
        
        // Генериране на линк сваляне на файла от sbf директорията
        $createLinkUrl = toUrl(array('fileman_Download', 'GenerateLink', 'fh' => $data->rec->fileHnd, 'ret_url' => TRUE), FALSE);
        $data->toolbar->addBtn('Линк', $createLinkUrl, 'id=btn-createLink,class=btn-createLink', 'order=40');
    }
}
