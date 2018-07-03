<?php

/**
 * Мениджър на отчети за отсъствия по служители
 *
 * @category  bgerp
 * @package   hr
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Персонал » Отсъствия по служители
 */
class hr_reports_AbsencesPerEmployee extends frame2_driver_TableData
{

    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo,hr,acc';

    /**
     * Брой записи на страница
     *
     * @var int
     */
    protected $listItemsPerPage = 30;

    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField;

    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields;

    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('from', 'date', 'caption=От,after=title,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');
        // $fieldset->FLD ( 'employee', 'users(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal,allowEmpty)', 'caption=Служител,after=to,single=none');
        // $fieldset->FLD('employee', 'key(mvc=crm_Persons,select=name,allowEmpty)', 'caption=Служител,placeholder=Всички,after=to,');
        $fieldset->FLD('employee', 'userList(roles=powerUser)', 'caption=Избери екип или служител,single=none,after=to,autohide');
    }

    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     *            $Driver
     * @param embed_Manager $Embedder
     * @param stdClass $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
    }

    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     * @return array
     */
    protected function prepareRecs($rec, &$data = NULL)
    {
        $recs = array();
        $sickdaysArr = array();
        $tripsesArr = array();
        $leavesArr = array();
        
        $sickdaysQuery = hr_Sickdays::getQuery();
        
        $leavesQuery = hr_Leaves::getQuery();
        
        $tripsQuery = hr_Trips::getQuery();
        
        $sickdaysQuery->where("(#startDate >= '{$rec->from}' AND #startDate <= '{$rec->to}') OR (#toDate >= '{$rec->from}' AND #toDate <= '{$rec->to}')");
        
        $sickdaysQuery->where("#state != 'rejected'");
        
        $leavesQuery->where("#leaveFrom >= '{$rec->from}' OR #leaveTo <= '{$rec->to}'");
        
        $leavesQuery->where("#state != 'rejected'");
        
        $tripsQuery->where("#startDate >= '{$rec->from}' OR #toDate <= '{$rec->to}'");
        
        $tripsQuery->where("#state != 'rejected'");
        
        if ($rec->employee) {
            
            $employees = type_Keylist::toArray($rec->employee);
            
            foreach ($employees as $v) {
                
                $employees[$v] = crm_Profiles::getProfile($v)->id;
            }
            
            $sickdaysQuery->in('personId', $employees);
            
            $leavesQuery->in('personId', $employees);
            
            $tripsQuery->in('personId', $employees);
        }
        
        // Болнични
        
        $doc = array();
        $docPeriod = array();
        
        while ($sickdays = $sickdaysQuery->fetch()) {
            
            $doc['startDate'] = ($sickdays->startDate);
            $doc['endDate'] = $sickdays->toDate;
            
            $docPeriod = self::getPeriod($rec, $doc);
            
            $numberOfSickdays = $docPeriod['workingDays'];
            
            $sickdaysArr[$sickdays->personId] += $numberOfSickdays;
            
            if (!array_key_exists($sickdays->productId, $recs)) {
                
                $recs[$sickdays->personId] = (object) array(
                    
                    'personId' => $sickdays->personId,
                    
                    'numberOfSickdays' => $numberOfSickdays
                
                );
            } else {
                
                $obj = &$recs[$sickdays->productId];
                
                $obj->numberOfSickdays += $numberOfSickdays;
            }
        }
        
        // Отпуски
        
        $doc = array();
        $docPeriod = array();
        
        while ($leaves = $leavesQuery->fetch()) {
            
            $doc['startDate'] = dt::addDays(0, $leaves->leaveFrom, false);
            $doc['endDate'] = dt::addDays(0, $leaves->leaveTo, false);
            
            $docPeriod = self::getPeriod($rec, $doc);
            
            $numberOfLeavesDays = $docPeriod['workingDays'];
            
            if (!array_key_exists($leaves->personId, $recs)) {
                
                $recs[$leaves->personId] = (object) array(
                    
                    'personId' => $leaves->personId,
                    
                    'numberOfLeavesDays' => $numberOfLeavesDays
                
                );
            } else {
                
                $obj = &$recs[$leaves->personId];
                
                $obj->numberOfLeavesDays += $numberOfLeavesDays;
            }
        }
        
        // Kомандировъчни
        
        $doc = array();
        $docPeriod = array();
        
        while ($trips = $tripsQuery->fetch()) {
            
            $doc['startDate'] = ($trips->startDate);
            $doc['endDate'] = $trips->toDate;
            
            $docPeriod = self::getPeriod($rec, $doc);
            
            $numberOfTripsesDays = $docPeriod['numberOfDays'];
            
            $tripsesArr[$trips->personId] += $numberOfTripsesDays;
            
            if (!array_key_exists($trips->personId, $recs)) {
                
                $recs[$trips->personId] = (object) array(
                    
                    'personId' => $trips->personId,
                    
                    'numberOfTripsesDays' => $numberOfTripsesDays
                );
            } else {
                
                $obj = &$recs[$trips->personId];
                
                $obj->numberOfTripsesDays += $numberOfTripsesDays;
            }
        }
        
        return $recs;
    }

    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *            - записа
     * @param boolean $export
     *            - таблицата за експорт ли е
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = FALSE)
    {
        $fld = cls::get('core_FieldSet');
        
        if ($export === FALSE) {
            
            $fld->FLD('employee', 'varchar', 'caption=Потребител');
            $fld->FLD('numberOfLeavesDays', 'varchar', 'caption=Дни->Отпуска,tdClass=centered');
            $fld->FLD('numberOfSickdays', 'varchar', 'caption=Дни->Болнични,tdClass=centered');
            $fld->FLD('numberOfTripsesDays', 'varchar', 'caption=Дни->Командировъчни,tdClass=centered');
            $fld->FLD('absencesDays', 'varchar', 'caption=Общо отсъствия,tdClass=centered');
        } else {
            
            $fld->FLD('employee', 'varchar', 'caption=Потребител,smartCenter');
            $fld->FLD('numberOfLeavesDays', 'varchar', 'caption=Дни->Отпуска');
            $fld->FLD('numberOfSickdays', 'varchar', 'caption=Дни->Болнични');
            $fld->FLD('numberOfTripsesDays', 'varchar', 'caption=Дни->Командировъчни');
            $fld->FLD('absencesDays', 'varchar', 'caption=Общо отсъствия,tdClass=centered');
        }
        return $fld;
    }

    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *            - записа
     * @param stdClass $dRec
     *            - чистия запис
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $isPlain = Mode::is('text', 'plain');
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        $row = new stdClass();
        
        $row->employee = crm_Persons::getContragentData($dRec->personId)->person;
        
        $row->numberOfLeavesDays = $Int->toVerbal($dRec->numberOfLeavesDays);
        
        $row->numberOfSickdays = $Int->toVerbal($dRec->numberOfSickdays);
        
        $row->numberOfTripsesDays = $Int->toVerbal($dRec->numberOfTripsesDays);
        
        $row->absencesDays = $Int->toVerbal($dRec->numberOfTripsesDays + $dRec->numberOfSickdays + $dRec->numberOfLeavesDays);
        
        return $row;
    }

    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager $Embedder
     * @param core_ET $tpl
     * @param stdClass $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $Date = cls::get('type_Date');
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
        		                <fieldset class='detail-info'><legend class=red><small><b>|СПРАВКАТА Е В ПРОЦЕС НА РАЗРАБОТКА.ВЪЗМОЖНО Е ДА ИМА НЕТОЧНИ РЕЗУЛТАТИ|*</b></small></legend>
                                <small><div><!--ET_BEGIN from-->|От|*: [#from#]<!--ET_END from--></div></small>
                                <small><div><!--ET_BEGIN to-->|До|*: [#to#]<!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN employee-->|Служители|*: [#employee#]<!--ET_END employee--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
        
        if (isset($data->rec->from)) {
            $fieldTpl->append("<b>" . $data->rec->from . "</b>", 'from');
        }
        
        if (isset($data->rec->to)) {
            $fieldTpl->append("<b>" . $data->rec->to . "</b>", 'to');
        }
        
        if ((isset($data->rec->employee)) && ((min(array_keys(keylist::toArray($data->rec->employee))) >= 1))) {
            
            foreach (type_Keylist::toArray($data->rec->employee) as $employee) {
                
                $employeeVerb .= (core_Users::getTitleById($employee) . ', ');
            }
            
            $fieldTpl->append("<b>" . trim($employeeVerb, ',  ') . "</b>", 'employee');
        } else {
            $fieldTpl->append("<b>" . 'Всички' . "</b>", 'employee');
        }
        
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }

    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass $res
     * @param stdClass $rec
     * @param stdClass $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $res->paidAmount = (self::getPaidAmount($dRec));
        
        $res->paidDates = self::getPaidDates($dRec, FALSE);
        
        $res->dueDate = self::getDueDate($dRec, FALSE, $rec);
        
        if ($dRec->invoiceCurrentSumm < 0) {
            $invoiceOverSumm = -1 * $dRec->invoiceCurrentSumm;
            $res->invoiceCurrentSumm = '';
            $res->invoiceOverSumm = ($invoiceOverSumm);
        }
        
        if ($dRec->dueDate && $dRec->invoiceCurrentSumm > 0 && $dRec->dueDate < $rec->checkDate) {
            
            $res->dueDateStatus = 'Просрочен';
        }
        
        $invoiceNo = str_pad($dRec->invoiceNo, 10, "0", STR_PAD_LEFT);
        
        $res->invoiceNo = $invoiceNo;
        
        $contragentName = crm_Companies::getTitleById($dRec->contragentId);
        
        $res->contragentId = $contragentName;
    }

    /**
     * Връща масив с данни за сечението на проверявания период и периода на документа
     *
     * @param stdClass $rec
     *            - запис
     * @param array $doc
     *            - начална и крайна дата на документа
     * @return array - масив с начална и крайна дата на периода за проверка,
     *         брой календарни дни, брий работни дни.
     */
    public function getPeriod($rec, $doc)
    {
        $period = array();
        if (($rec->from <= $doc['startDate']) && ($rec->to >= $doc['endDate'])) {
            
            $period['startDate'] = $doc['startDate'];
            $period['endDate'] = $doc['endDate'];
        }
        
        if ($rec->from > $doc['startDate']) {
            
            if (($rec->to < $doc['endDate'])) {
                
                $period['startDate'] = $rec->from;
                $period['endDate'] = $rec->to;
            }
            
            if (($rec->to >= $doc['endDate'])) {
                
                $period['startDate'] = $rec->from;
                $period['endDate'] = $doc['endDate'];
            }
        }
        
        if ($rec->to < $doc['endtDate']) {
            
            if ($rec->from <= $doc['startDate']) {
                
                $period['startDate'] = $doc['startDate'];
                $period['endDate'] = $rec->to;
            }
        }
        
        $period[workingDays] = 0;
        $period['numberOfDays'] = 0;
        
        $checkDate = $period['startDate'];
        
        do {
            
            if (!cal_Calendar::isHoliday($checkDate, 'bg')) {
                
                $period[workingDays]++;
            }
            
            $checkDate = dt::addDays(1, $checkDate, FALSE);
        } while ($checkDate <= $period['endDate']);
        
        $period[numberOfDays] = dt::daysBetween($period['endDate'], $period['startDate']) + 1;
        
        return $period;
    }

    /**
     * Връща следващите три дати, когато да се актуализира справката
     *
     * @param stdClass $rec
     *            - запис
     * @return array|FALSE - масив с три дати или FALSE ако не може да се обновява
     */
    public function getNextRefreshDates($rec)
    {
        $date = new DateTime(dt::now());
        $toAdd = 25 - $date->format(H);
        $interval = 'PT' . $toAdd . 'H';
        $date->add(new DateInterval($interval));
        $d1 = $date->format('Y-m-d H:i:s');
        $date->add(new DateInterval($interval));
        $d2 = $date->format('Y-m-d H:i:s');
        $date->add(new DateInterval($interval));
        $d3 = $date->format('Y-m-d H:i:s');
        
        return array(
            $d1,
            $d2,
            $d3
        );
    }
}



