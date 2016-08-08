<?php


/**
 * Коя да е основната мярка на универсалните артикули
 */
defIfNot('VTOTAL_AVAST_COMMAND', "scan");
defIfNot('VTOTAL_API_KEY', '');
defIfNot('VTOTAL_NUMBER_OF_ITEMS_TO_SCAN_BY_VIRUSTOTAL', '3');
defIfNot('VTOTAL_BETWEEN_TIME_SCANS', '864000'); // Десет дена



/**
 * class vtotal_Setup
 * Setup VitusTotal
 *
 * @category  bgerp
 * @package   cond
 * @author    Kristiyan Serafimov <kristian.plamenov@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class vtotal_Setup extends core_ProtoSetup
{

    function install()
    {
        $Plugins = cls::get('core_Plugins');
        $html = parent::install();

        $html .= $Plugins->forcePlugin('Ръчна проверка със VirusTotal', 'vtotal_Plugin', 'fileman_Files', 'private');

        return $html;
    }

    function deinstall()
    {
        $html = parent::deinstall();

        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');

        $html .= "<li>Премахнати са всички инсталации на 'vtotal_Plugin'";

        return $html;
    }

    var $managers = array(
        'vtotal_Checks'
    );
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        'VTOTAL_API_KEY' => array("varchar", 'caption=Ключ за API системата на '),
        'VTOTAL_BETWEEN_TIME_SCANS' => array ('time(suggestions=5 дена|10 дена)', 'caption=Времете между което ще се пуска VirusTotal за неопределените, миналото
        сканирване файлове'),
        'VTOTAL_NUMBER_OF_ITEMS_TO_SCAN_BY_VIRUSTOTAL' => array("int", 'caption=По колко файла да се вземат от VirusTotal за сканирване'),
    );

    /**
     * Настройки за Cron
     */
    var $cronSettings = array(

        array(
            'systemId' => "MoveFilesFromFilemanLog",
            'description' => "Преместване на съмнителните файлове в vtotal_Checks",
            'controller' => "vtotal_Checks",
            'action' => "MoveFilesFromFilemanLog",
            'period' => 1,
            'timeLimit' => 50
        ),

        array(
            'systemId' => "VTCheck",
            'description' => "Проверка на файловете с virustotal",
            'controller' => "vtotal_Checks",
            'action' => "VTCheck",
            'period' => 1,
            'delay' => 15,
            'timeLimit' => 40
        ),
    );



    /**
     * Проверява дали програмата е инсталирана в сървъра
     *
     * @return NULL|string
     */
    /**
     * Проверява дали програмата е инсталирана в сървъра
     *
     * @return NULL|string
     */
    public function checkConfig()
    {
        $command = escapeshellcmd(self::get('AVAST_COMMAND'));
        exec($command . ' --help', $output, $code);
        
        if ($code == 127) {
            
            return "Програмата Avast за Linux не е инсталирана. За да инсталирате, моля посетете https://www.avast.com/";
        }
    }
}
