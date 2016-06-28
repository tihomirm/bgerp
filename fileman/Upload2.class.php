<?php


/**
 * Клас 'fileman_Upload2' - качване на файлове от диалогов прозорец
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_Upload2 extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'Files=fileman_Files,fileman_DialogWrapper';
    
    
    /**
     * Заглавие
     */
    var $title = 'Качвания на файлове';
    
    
    /**
     *
     */
    var $canAdd = 'every_one';
    
    
    /**
     * @todo Чака за документация...
     */
    function act_Dialog()
    {
        // Дали ще качаваме много файлове едновременно
        $allowMultiUpload = FALSE;
        
        Request::setProtected('callback, bucketId');
        
        // Вземаме callBack'а
        if ($callback = Request::get('callback', 'identifier')) {
            
            // Ако файловете ще се добавят в richText
            if (stripos($callback, 'placeFile_') !== FALSE) {
                
                // Позволяваме множествено добавяне
                $allowMultiUpload = TRUE;
            } 
        }
        
        // Вземаме id' то на кофата
        $bucketId = Request::get('bucketId', 'int');
        expect(fileman_Buckets::canAddFileToBucket($bucketId));
        
        // Шаблона с качените файлове и грешките
        $add = new ET('<div id="add-file-info"><div id="add-error-info">[#ERR#]</div><div id="add-success-info">[#ADD#]</div></div>');
        
        $add->push('fileman/simpleUpload/1.0/simpleUpload.min.js', 'JS');
        
        // Ако е стартрино качването
        if (Request::get('Upload')) {
            
            $resEt = new ET();
            
            // Обхождаме качените файлове
            foreach ((array)$_FILES as $inputName => $inputArr) {
                
                $fh = NULL;
                
                // Масив с грешките
                $err = array();
                
                $fRec = new stdClass();
                
                foreach ((array)$inputArr['name'] as $id => $inpName) {
                    
                    // Ако файла е качен успешно
                    if($_FILES[$inputName]['name'][$id] && $_FILES[$inputName]['tmp_name'][$id]) {
                        
                        // Ако има кофа
                        if($bucketId) {
                            
                            // Вземаме инфото на обекта, който ще получи файла
                            $Buckets = cls::get('fileman_Buckets');
                            
                            // Ако файла е валиден по размер и разширение - добавяме го към собственика му
                            if($Buckets->isValid($err, $bucketId, $_FILES[$inputName]['name'][$id], $_FILES[$inputName]['tmp_name'][$id])) {
                                
                                // Създаваме файла
                                $fh = $this->Files->createDraftFile($_FILES[$inputName]['name'][$id], $bucketId);
                                
                                // Записваме му съдържанието
                                $this->Files->setContent($fh, $_FILES[$inputName]['tmp_name'][$id]);
                                
                                $resEt->append($Buckets->getInfoAfterAddingFile($fh));
                                
                                if($callback && !$_FILES[$inputName]['error'][$id]) {
                                    if (isset($fh)) {
                                        $fRec = fileman_Files::fetchByFh($fh);
                                    }
                                    $resEt->append("<script>  if(window.opener.{$callback}('{$fh}','{$fRec->name}') != true) self.close(); else self.focus();</script>");
                                }
                            }
                        } else {
                            $err[] = 'Не е избрана кофа';
                        }
                    }
                    
                    // Ако има грешка в $_FILES за съответния файл
                    if($_FILES[$inputName]['error'][$id]) {
                        // Ако са възникнали грешки при качването - записваме ги в променливата $err
                        switch($_FILES[$inputName]['error'][$id]) {
                            case 1 : $err[] = 'The uploaded file exceeds the upload_max_filesize directive in php.ini'; break;
                            case 2 : $err[] = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'; break;
                            case 3 : $err[] = 'The uploaded file was only partially uploaded.'; break;
                            case 4 : $err[] = 'No file was uploaded.'; break;
                            case 6 : $err[] = 'Missing a temporary folder.'; break;
                            case 7 : $err[] = 'Failed to write file to disk.'; break;
                        }
                    }
                    
                    $success = true;
                    
                    // Ако има грешки, показваме ги в прозореца за качване
                    if(!empty($err)) {
                        
                        $error = new ET("<div class='upload-еrror'><ul>{$_FILES[$inputName]['name'][$id]}[#ERR#]</ul></div>");
                        
                        foreach($err as $e) {
                            $error->append("<li>" . tr($e) . "</li>", 'ERR');
                            fileman_Files::logWarning('Грешка при добавяне на файл: ' . $e);
                            $success = false;
                        }
                        $resEt->append($error);
                    } else {
                        if (isset($fh)) {
                            fileman_Files::logWrite('Качен файл', $fRec->id);
                        }
                    }
                }
            }
            
            if (Request::get('ajax_mode')) {
                core_App::getJson(array("success" => $success, "res" => $resEt->getContent()));
            } else {
                $add->prepend($resEt);
            }
        }
        
        // Ако има id на кофата
        if ($bucketId) {
            
            // Вземаме максималния размер за файл в кофата
            $maxAllowedFileSize = fileman_Buckets::fetchField($bucketId, 'maxSize');
        }
        
        $tpl = $this->getProgressTpl($allowMultiUpload, $maxAllowedFileSize);
        
        $tpl->prepend($add);
        
        return $this->renderDialog($tpl);
    }
    
    
    /**
     * Връща линк към подадения обект
     * 
     * @param integer $objId
     * 
     * @return core_ET
     */
    public static function getLinkForObject($objId)
    {
        
        return ht::createLink(get_called_class(), array());
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function renderDialog_($tpl)
    {
        
        return $tpl;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    function getProgressTpl($allowMultiUpload=FALSE, $maxAllowedFileSize=0)
    {
        
        $uploadStr = tr('Качване') . ':';
        
        $multiple = '';
        if ($allowMultiUpload) {
            $multiple = 'multiple';
        }
        
        $tpl = new ET('
            <style>
        		.uploaded-title{background-image:url(' . sbf('img/16/tick-circle-frame.png', '') . ');}
        		.btn-ulfile{background-image:url(' . sbf('img/16/paper_clip.png', '') . ');}
        	</style>
            
            <div id="uploads"><div id="uploadsTitle" style="display: none;">' . $uploadStr . '</div></div>
            <form id="uploadform" enctype="multipart/form-data" method="post">
                <span class="uploaded-filenames"> </span>
                <div id="inputDiv">
                    <input id="ulfile" class="ulfile" name="ulfile[]" ' . $multiple . ' type="file" size="1" onchange="afterSelectFile(this, ' . (int)$allowMultiUpload . ', ' . (int)$maxAllowedFileSize . ');" [#ACCEPT#]>
                    <button id="btn-ulfile" class="linkWithIcon button btn-ulfile">' . tr('Файл') . '</button>
                    <input type="button" name="Upload" value="' . tr('Качване') . '" class="linkWithIcon button btn-disabled" id="uploadBtn" disabled="disabled"/>
                </div>
            </form>');

        $currUrl = getCurrentUrl();
        $currUrl['Upload'] = '1';
        $currUrl['ajax_mode'] = '1';
        $uploadUrl = toUrl($currUrl);
        $uploadUrl = json_encode($uploadUrl);
        
        $crossImg = sbf('img/16/cross.png', "");
        $crossImg = json_encode($crossImg);
        
        $uploadErrStr = tr('Грешка при качване на файл') . ': ';
        $uploadErrStr = json_encode($uploadErrStr);
        
        $tpl->appendOnce("var uploadUrl = {$uploadUrl}; var crossImgPng = {$crossImg}; var uploadErrStr = {$uploadErrStr};", 'SCRIPTS');
        
        $tpl->push('fileman/js/upload.js', 'JS');
        
        return $tpl;
    }
}
