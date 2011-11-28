<?php

/**
 * Клас 'doc_Containers' - Контейнери за документи
 *
 * @category   Experta Framework
 * @package    doc
 * @author     Milen Georgiev <milen@download.bg>
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class doc_Containers extends core_Manager
{   
    var $loadList = 'plg_Created, plg_Modified,plg_RowTools,doc_Wrapper,plg_State';

    var $title    = "Документи в нишките";

    var $listFields = 'created=Създаване,document=Документи,createdOn=';
    
    
    /**
     * За конвертиране на съществуащи MySQL таблици от предишни версии
     */
    var $oldClassName = 'doc_ThreadDocuments';

    function description()
    {
        // Мастери - нишка и папка
        $this->FLD('folderId' ,  'key(mvc=doc_Folders)', 'caption=Папки');
        $this->FLD('threadId' ,  'key(mvc=doc_Threads)', 'caption=Нишка');
        
        // Документ
        $this->FLD('docClass' , 'class(interface=doc_DocumentIntf)', 'caption=Документ->Клас');
        $this->FLD('docId' , 'int', 'caption=Документ->Обект');
 
        $this->FLD('title' ,  'varchar(128)', 'caption=Заглавие');
        $this->FLD('status' ,  'varchar(128)', 'caption=Статус');
        $this->FLD('amount' ,  'double', 'caption=Сума');
     }


    /**
     * Филтрира по папка
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $threadId = Request::get('threadId', 'int');
 
        $data->query->where("#threadId = {$threadId}");
    }

    
    /**
     * Изпълнява се след подготовката на филтъра за листовия изглед
     * Обикновено тук се въвеждат филтриращите променливи от Request
     */
    function on_AfterPrepareListFilter($mvc, $res, $data)
    {
        expect($data->threadId  = Request::get('threadId', 'int'));
        expect($data->threadRec = doc_Threads::fetch($data->threadId));

        $data->folderId = $data->threadRec->folderId;

        doc_Threads::requireRightFor('read', $data->threadRec);
    }


    /**
     * Подготвя титлата за единичния изглед на една нишка от документи
     */
    function on_AfterPrepareListTitle($mvc, $res, $data)
    {
        $title = new ET("[#user#] » [#folder#] » [#threadTitle#]");
         
        $document = $mvc->getDocument($data->threadRec->firstContainerId);

        $docRow = $document->getDocumentRow();

        $docTitle = $docRow->title;

        $title->replace($docTitle, 'threadTitle');

        $folder = doc_Folders::getTitleById($data->folderId);

        $folderRec = doc_Folders::fetch($data->folderId);

        $title->replace(ht::createLink($folder, array('doc_Threads', 'list', 'folderId' => $data->folderId)), 'folder');

        $user = core_Users::fetchField($folderRec->inCharge, 'nick');

        $title->replace($user, 'user');

        $data->title = $title;
    }



    /**
     *
     */
    function on_AfterRecToVerbal($mvc, $row, $rec, $fields = NULL)
    {
        $document = $mvc->getDocument($rec->id);
        
        $docRow   = $document->getDocumentRow();

        $row->created = new ET( "<center><div style='font-size:0.8em'>[#1#]</div><div style='margin:10px;'>[#2#]</div>[#3#]<div></div></center>",
                                dt::addVerbal($row->createdOn),
                                avatar_Plugin::getImg($docRow->authorId,  $docRow->authorEmail),
                                $docRow->author );


        // Създаваме обекта $data
        $data = new stdClass();
         
        // Трябва да има $rec за това $id
        expect($data->rec = $document->fetch());
        
        // Подготвяме данните за единичния изглед
        $document->instance->prepareSingle($data);

        // Рендираме изгледа
        $row->document = $document->instance->renderSingle($data);

    }
    
    
    static function move($id, $new, $old = null)
    {
    	$rec = (object)array(
    		'id' => $id,
    		'folderId' => $new->folderId,
    		'threadId' => $new->threadId	
    	);
    	
    	$bSuccess = self::save($rec, 'id, folderId, threadId');
    	
    	if ($old->threadId) {
    		doc_Threads::updateThread($old->threadId);
    	}
    	
    	return (boolean)$bSuccess;
    	
    }
    

    /**
     * Създава нов контейнер за документ от посочения клас
     * Връща $id на новосъздадения контейнер
     */
    function create($class, $threadId, $folderId)
    {
        $className = cls::getClassName($class);
        $rec->docClass = core_Classes::fetchByName($className)->id;
        $rec->threadId = $threadId;
        $rec->folderId = $folderId;

        self::save($rec);

        return $rec->id;
    }


    /**
     * Обновява информацията в контейнера според информацията в документа
     * Ако в контейнера няма връзка към документ, а само мениджър на документи - съзсава я
     */
    function update_($id)
    {
        expect($rec = doc_Containers::fetch($id), $id);
 
        $docMvc = cls::get($rec->docClass);

        if(!$rec->docId) {
            expect($rec->docId = $docMvc->fetchField("#containerId = {$id}", 'id'));
            $mustSave = TRUE;
        }
        $fields = 'state,folderId,threadId,containerId';

        $docRec = $docMvc->fetch($rec->docId, $fields);
        
        foreach( arr::make($fields) as $field) {
            if($rec->{$field} != $docRec->{$field}) {
                $rec->{$field} = $docRec->{$field};
                $mustSave = TRUE;
            }
        } 

 
        if($mustSave) {
            doc_Containers::save($rec);
        }
    }


    /**
     * Предизвиква обновяване на треда, след всяко обновяване на контейнера
     */
    function on_AfterSave($mvc, $id, $rec, $fields = NULL)
    {
        if($rec->threadId) {
    	    doc_Threads::updateThread($rec->threadId);
        }
    }


    /**
<<<<<<< HEAD
<<<<<<< HEAD
     * Връща инстанция на класа на документа
     */
    function getDocMvc($id)
    {
        $rec = doc_Containers::fetch($id, 'docClass');
        $DocMvc = cls::get($rec->docClass);
        
        return $DocMvc;
    }


    /**
     * Връща id-то на документа в неговия мениджър
     */
    function getDocId($id)
    {
        $rec = doc_Containers::fetch($id, 'docId');
         
        return $rec->docId;
    }
    
    
    /**
     * Връща обект-пълномощник приведен към зададен интерфейс
     *
     * @param int $id key(mvc=doc_Containers)
     * @param string $intf
     * @return object
     */
    static function getDocument($id, $intf = NULL)
    {
        $rec = doc_Containers::fetch($id, 'docId, docClass');
        
        expect($rec);
        
    	return new core_ObjectReference($rec->docClass, $rec->docId, $intf);
    }

}
