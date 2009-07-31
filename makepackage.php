<?php
/**
 * Make package file for the UNL_UCBCN package.
 * 
 * PHP version 5
 * 
 * @category  Events 
 * @package   UNL_UCBCN
 * @author    Brett Bieber <brett.bieber@gmail.com>
 * @copyright 2009 Regents of the University of Nebraska
 * @license   http://www1.unl.edu/wdn/wiki/Software_License BSD License
 * @link      http://code.google.com/p/unl-event-publisher/
 */

ini_set('display_errors', true);

/**
 * Require the PEAR_PackageFileManager2 classes, and other
 * necessary classes for package.xml file creation.
 */
require_once 'PEAR/PackageFileManager2.php';
require_once 'PEAR/PackageFileManager/File.php';
require_once 'PEAR/Task/Postinstallscript/rw.php';
require_once 'PEAR/Config.php';
require_once 'PEAR/Frontend.php';

/**
 * @var PEAR_PackageFileManager
 */
PEAR::setErrorHandling(PEAR_ERROR_DIE);
chdir(dirname(__FILE__));
$pfm = PEAR_PackageFileManager2::importOptions('package.xml', array(
//$pfm = new PEAR_PackageFileManager2();
//$pfm->setOptions(array(
    'packagedirectory' => dirname(__FILE__),
    'baseinstalldir' => '/',
    'filelistgenerator' => 'svn',
    'ignore' => array(  'package.xml',
                        '.project',
                        '*.tgz',
                        'makepackage.php',
                        '*CVS/*',
                        '*.sh',
                        '*.svg',
                        '.cache',
                        'dataobject.ini',
                        'DBDataObjects',
                        'insert_sample_data.php',
                        'install.sh',
                        '*tests*',
                        '*scripts*'),
    'simpleoutput' => true,
    'roles'=>array('php'=>'php'),
    'exceptions'=>array()
));
$pfm->setPackage('UNL_UCBCN');
$pfm->setPackageType('php'); // this is a PEAR-style php script package
$pfm->setSummary('This package provides the database interactions for a UC Berkeley Calendar system.');
$pfm->setDescription('This package creates and upgrades a relational database used to store event publishing details
                    formatted using the University of California Berkeley Calendar Network schema. The backend provides
                    basic functions for an event management frontend, as well as a public frontend.');
$pfm->setChannel('pear.unl.edu');
$pfm->setAPIStability('beta');
$pfm->setReleaseStability('beta');
$pfm->setAPIVersion('0.8.0');
$pfm->setReleaseVersion('0.8.1');
$pfm->setNotes('
0.8.1 Changes:
Restore details required by forms used in the manager, until the manager
rewrite is complete.
');

//$pfm->addMaintainer('lead','saltybeagle','Brett Bieber','brett.bieber@gmail.com');
//$pfm->addMaintainer('helper','bsteere','Brian Steere','bsteere@cornellcollege.edu');
$pfm->setLicense('BSD License', 'http://www1.unl.edu/wdn/wiki/Software_License');
$pfm->clearDeps();
$pfm->setPhpDep('5.0.0');
$pfm->setPearinstallerDep('1.5.4');
$pfm->addPackageDepWithChannel('required', 'Cache_Lite', 'pear.php.net', '1.0');
$pfm->addPackageDepWithChannel('required', 'DB_DataObject', 'pear.php.net', '0.8');
$pfm->addPackageDepWithChannel('required', 'Savant3', 'phpsavant.com', '3.0.0');
$pfm->addPackageDepWithChannel('required', 'NET_URL', 'pear.php.net', '1.0');
$pfm->addPackageDepWithChannel('required', 'MDB2', 'pear.php.net', '2.5.0b1');
$pfm->addPackageDepWithChannel('required', 'MDB2_Driver_mysqli', 'pear.php.net', '1.5.0b1');
$pfm->addPackageDepWithChannel('required', 'MDB2_Schema', 'pear.php.net', '0.5.0');
foreach (array('UNL/UCBCN.php','UNL/UCBCN_setup.php','UNL_UCBCN_db.xml') as $file) {
    $pfm->addReplacement($file, 'pear-config', '@PHP_BIN@', 'php_bin');
    $pfm->addReplacement($file, 'pear-config', '@DATA_DIR@', 'data_dir');
    $pfm->addReplacement($file, 'pear-config', '@DOC_DIR@', 'doc_dir');
}

$config = PEAR_Config::singleton();
$log = PEAR_Frontend::singleton();
$task = new PEAR_Task_Postinstallscript_rw($pfm, $config, $log,
    array('name' => 'UNL/UCBCN_setup.php', 'role' => 'php'));
$task->addParamGroup('questionCreate', array(
    $task->getParam('createdb', 'Create/Upgrade database for UNL Event Publisher?', 'string', 'yes'),
    ));
$task->addParamGroup('databaseSetup', array(
    $task->getParam('dbtype',   'Database/connection type', 'string', 'mysqli'),
    $task->getParam('database', 'Calendar database',        'string', 'eventcal'),
    $task->getParam('user',     'Username (must have CREATE TABLE permission)', 'string', 'eventcal'),
    $task->getParam('password', 'Mysql password',           'string', 'eventcal'),
    $task->getParam('dbhost',   'Database Host',            'string', 'localhost')
    ));
$task->addParamGroup('questionEventTypes', array(
    $task->getParam('addeventtypes', 'Add sample default event types to the calendar database?', 'string', 'yes'),
    ));

$task->addParamGroup('questionSponsors', array(
    $task->getParam('addsponsors', 'Add sample sponsors to the calendar database?', 'string', 'yes'),
    ));
$pfm->addPostinstallTask($task, 'UNL/UCBCN_setup.php');
$pfm->generateContents();
if (isset($_SERVER['argv']) && $_SERVER['argv'][1] == 'make') {
    $pfm->writePackageFile();
} else {
    $pfm->debugPackageFile();
}
?>