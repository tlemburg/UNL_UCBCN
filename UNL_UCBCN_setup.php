<?php
/**
 * This is the setup file for the UNL UCBCN Calendar System.
 * This file installs/upgrades the database and inserts the default 
 * permissions.
 * 
 * PHP version 5
 * 
 * @category  Events 
 * @package   UNL_UCBCN
 * @author    Brett Bieber <brett.bieber@gmail.com>
 * @copyright 2008 Regents of the University of Nebraska
 * @license   http://www1.unl.edu/wdn/wiki/Software_License BSD License
 * @link      http://code.google.com/p/unl-event-publisher/
 */

/**
 * Require MDB2_Schema for database interactions, and the UNL_UCBCN class
 * for backend interactions.
 */
require_once 'MDB2/Schema.php';
require_once 'UNL/UCBCN.php';

/**
 * Class used by the PEAR installer which is executed after install to do
 * post installation tasks such as database creation/updates as well as
 * replacements and configuration.
 *
 * @category  Events
 * @package   UNL_UCBCN
 * @author    Brett Bieber <brett.bieber@gmail.com>
 * @copyright 2008 Regents of the University of Nebraska
 * @license   http://www1.unl.edu/wdn/wiki/Software_License BSD License
 * @link      http://code.google.com/p/unl-event-publisher/
 */
class UNL_UCBCN_setup_postinstall
{
    var $createDB;
    var $databaseExists;
    var $noDBsetup;
    var $dsn;
    
    /**
     * initialize the post install script
     *
     * @param PEAR_Config  &$config     User's PEAR configuration
     * @param unknown_type &$pkg        Package object
     * @param string       $lastversion Last version installed, if any
     * 
     * @return true
     */
    function init(&$config, &$pkg, $lastversion)
    {
        $this->_config        = &$config;
        $this->_registry      = &$config->getRegistry();
        $this->_ui            = &PEAR_Frontend::singleton();
        $this->_pkg           = &$pkg;
        $this->lastversion    = $lastversion;
        $this->databaseExists = false;
        return true;
    }

    /**
     * Change questions asked if necessary.
     *
     * @param array  $prompts questions to prompt for answers
     * @param string $section section we're asking questions for
     * 
     * @return array revised prompts
     */
    function postProcessPrompts($prompts, $section)
    {
        switch ($section) {
        case 'databaseSetup' :
                
            break;
        }
        return $prompts;
    }
    
    /**
     * Run the postinstall script.
     *
     * @param array  $answers Responses to questions
     * @param string $phase   Which phase of the install we're in
     * 
     * @return bool
     */
    function run($answers, $phase)
    {
        switch ($phase) {
        case 'questionCreate':
            $this->createDB = ($answers['createdb']=='yes') ? true : false;
            return $this->createDB;
        case 'databaseSetup' :
            if ($this->createDB) {
                   $r = $this->handleDatabase($answers);
                   return ($r && $this->setupPermissions($answers));
            } else {
                return true;
            }
        case 'questionEventTypes':
            return $this->setupEventTypes($answers);
        case '_undoOnError' :
            // answers contains paramgroups that succeeded in reverse order
            foreach ($answers as $group) {
                switch ($group) {
                case 'makedatabase' :
                    break;
                case 'databaseSetup' :
                    if ($this->lastversion || $this->databaseExists || $this->noDBsetup) {
                        // don't uninstall the database if it had already existed
                        break;
                    }
                    /* REMOVE THE DATABASE ON FAILURE! */
                    break;
                }
            }
            break;
        }
    }
    
    /**
     * handle database postinstall script
     *
     * @param array $answers responses
     * 
     * @return bool
     */
    function handleDatabase($answers)
    {
        PEAR::staticPushErrorHandling(PEAR_ERROR_RETURN);
        $this->dsn = $answers['dbtype']   . '://'
                   . $answers['user']     . ':' 
                   . $answers['password'] . '@'
                   . $answers['dbhost']   . '/' 
                   . $answers['database'];
        
        if ($this->databaseExists($answers['database'])) {
            $this->outputData('Database exists already. Upgrading.');
            return $this->upgradeDatabase($answers);
        } else {
            $this->outputData('No database exists. Creating.');
            return $this->createDatabase($answers);
        }
        
        PEAR::staticPopErrorHandling();
    }
    
    /**
     * Create an empty database for use.
     *
     * @param array $answers answers to questions
     * 
     * @return bool
     */
    function createDatabase($answers)
    {
        $db = MDB2::factory($this->dsn);
        
        if (PEAR::isError($db)) {
            $this->outputData('Could not create database connection. "'.$db->getMessage().'"');
            $this->noDBsetup = true;
            return false;
        }
        
        $sth       = $db->prepare('CREATE DATABASE IF NOT EXISTS ?', array('text'), MDB2_PREPARE_MANIP);
        $operation = $sth->execute(array($answers['database']));
        if (PEAR::isError($db)) {
            $this->outputData('Could not create database. "'.$db->getMessage().'"');
            $this->noDBsetup = true;
            return false;
        }
        return $this->upgradeDatabase($answers);
    }

    /**
     * Creates or updates the calendar system database.
     *
     * @param array $answers Responses to the questsions asked.
     * 
     * @return bool true or false on success or error.
     */
    function upgradeDatabase($answers)
    {
        
        $db = MDB2::factory($this->dsn);
        
        if (PEAR::isError($db)) {
            $this->outputData('Could not create database connection. "'.$db->getMessage().'"');
            $this->noDBsetup = true;
            return false;
        }
        
        if ($answers['database'] != 'eventcal') {
            $a = self::file_str_replace('<name>eventcal</name>',
                                        '<name>'.$answers['database'].'</name>',
                                        '@DATA_DIR@/UNL_UCBCN/UNL_UCBCN_db.xml');
            if ($a != true) {
                $this->noDBsetup = true;
                return $a;
            }
            $ds = DIRECTORY_SEPARATOR;
            $this->outputData('Copying DB_DataObject config file to "@DATA_DIR@' .
                $ds.'UNL_UCBCN'.$ds.'UCBCN'.$ds.$answers['database'].'.ini"'."\n");
            copy('@DATA_DIR@' . $ds . 'UNL_UCBCN' . $ds . 'UCBCN' . $ds .
                    'eventcal.ini',
                '@DATA_DIR@' . $ds . 'UNL_UCBCN' . $ds . 'UCBCN' . $ds .
                    $answers['database'] . '.ini');
            copy('@DATA_DIR@' . $ds . 'UNL_UCBCN' . $ds . 'UCBCN' . $ds .
                    'eventcal.links.ini',
                '@DATA_DIR@' . $ds . 'UNL_UCBCN' . $ds . 'UCBCN' . $ds .
                    $answers['database'] . '.links.ini');
        }
        
        $manager =& MDB2_Schema::factory($db);
        if (PEAR::isError($manager)) {
            $this->outputData($manager->getMessage() . ' ' . $manager->getUserInfo());
            $this->noDBsetup = true;
            return false;
        } else {
            $new_definition_file = '@DATA_DIR@/UNL_UCBCN/UNL_UCBCN_db.xml';
            $old_definition_file = '@DATA_DIR@/UNL_UCBCN/UNL_UCBCN_db_'.$answers['database'].'.old';
            
            if (file_exists($old_definition_file)) {
                $operation = $manager->updateDatabase($new_definition_file, $old_definition_file);
            } else {
                $previous_definition = $manager->getDefinitionFromDatabase();
                $operation = $manager->updateDatabase($new_definition_file, $previous_definition);
            }
            
            if (PEAR::isError($operation)) {
                $this->outputData('There was an error updating the database.');
                $this->outputData($operation->getMessage() . ' ' . $operation->getDebugInfo());
                $this->noDBsetup = true;
                return false;
            } else {
                copy($new_definition_file, $old_definition_file);
                $this->outputData('Successfully connected and created '.$this->dsn."\n");
                return true;
            }
        }
    }
    
    /**
     * checks if the database exists already, or not
     *
     * @param string $db_name Database name
     * 
     * @return bool
     */
    function databaseExists($db_name)
    {
        $this->outputData('Checking for existing database, '.$db_name.'. . .');
        
        $db =& MDB2::factory($this->dsn);

        if (PEAR::isError($db)) {
            $this->outputData('There was an error connecting, you must resolve this issue before installation can complete.');
            $this->outputData($db->getUserinfo());
            die();
        }
        
        $exists = $db->databaseExists($db_name);
        if (PEAR::isError($exists)) {
            if ($exists->getMessage() == "MDB2 Error: no such database") {
                return false;
            }
            $this->outputData('There was an error checking the database, you must resolve this issue before installation can complete.');
            $this->outputData($exists->getUserinfo());
            die();
        } else {
            if ($exists) {
                return true;
            } else {
                return false;
            }
        }
    }
    
    /**
     * Replace text within a file
     *
     * @param string $search  Text to search for
     * @param string $replace What to replace with
     * @param string $file    Filename to make replacements in
     * 
     * @return void
     */
    function file_str_replace($search, $replace, $file)
    {
        $a = true;
        if (is_array($file)) {
            foreach ($file as $f) {
                $a = self::file_str_replace($search, $replace, $f);
                if ($a != true) {
                    return $a;
                }
            }
        } else {
            if (file_exists($file)) {
                $contents = file_get_contents($file);
                $contents = str_replace($search, $replace, $contents);
    
                $fp = fopen($file, 'w');
                $a = fwrite($fp, $contents, strlen($contents));
                fclose($fp);
                if ($a) {
                    $this->outputData('Replacements made in '.$file."\n");
                    return true;
                } else {
                    $this->outputData('Could not update '.$file."\n");
                    return false;
                }
            } else {
                $this->outputData($file.' does not exist!');
            }
        }
    }
    
    /**
     * Add some event types to the system so they have a starting point.
     * 
     * @param array $answers Responses to questions
     *
     * @return true
     */
    function setupEventTypes($answers)
    {
        if ($answers['addeventtypes']=='yes') {
            $this->outputData('Adding sample event types. . .');
            $backend = new UNL_UCBCN(array('dsn'=>$this->dsn));
            /** Add some event types to the database */
            $eventtype = $backend->factory('eventtype');
            $types = array( 'Career Fair',
                            'Colloquium',
                            'Conference/Symposium',
                            'Course',
                            'Deadline',
                            'Debate/Panel Discussion',
                            'Exhibit - Artifacts',
                            'Exhibit - Multimedia',
                            'Exhibit - Painting',
                            'Exhibit - Photography',
                            'Exhibit - Sculpture',
                            'Film - Animated',
                            'Film - Documentary',
                            'Film - Feature',
                            'Film - Series',
                            'Film - Short',
                            'Holiday',
                            'Information Session',
                            'Lecture',
                            'Meeting',
                            'Memorial',
                            'Other',
                            'Performing Arts - Dance',
                            'Performing Arts - Music',
                            'Performing Arts - Musical Theatre',
                            'Performing Arts - Opera',
                            'Performing Arts - Other',
                            'Performing Arts - Theater',
                            'Presentation',
                            'Reading - Fiction/poetry',
                            'Reading - Nonfiction',
                            'Reception',
                            'Sale',
                            'Seminar',
                            'Social Event',
                            'Special Event',
                            'Sport - Club',
                            'Sport - Intercollegiate - Baseball/Softball',
                            'Sport - Intercollegiate - Basketball',
                            'Sport - Intercollegiate - Crew',
                            'Sport - Intercollegiate - Cross Country',
                            'Sport - Intercollegiate - Football',
                            'Sport - Intercollegiate - Golf',
                            'Sport - Intercollegiate - Gymnastics',
                            'Sport - Intercollegiate - Rifle',
                            'Sport - Intercollegiate - Rugby',
                            'Sport - Intercollegiate - Soccer',
                            'Sport - Intercollegiate - Swimming & Diving',
                            'Sport - Intercollegiate - Tennis',
                            'Sport - Intercollegiate - Track & Field',
                            'Sport - Intercollegiate - Volleyball',
                            'Sport - Intercollegiate - Wrestling',
                            'Sport - Intramural',
                            'Sport - Recreational',
                            'Tour/Open House',
                            'Workshop');
            
            foreach ($types as $type) {
                $eventtype->name = $type;
                if (!$eventtype->find()) {
                    $eventtype->name = $type;
                    $eventtype->description = $type;
                    $eventtype->insert();
                }
            }
        }
        return true;
    }
    
    /**
     * This function calls the backend and inserts the default permissions
     * for the system.
     * 
     * @param array $answers User provided responses to questions
     * 
     * @return true
     */
    function setupPermissions($answers)
    {
        $this->outputData('Adding default permissions. . .');
        $backend = new UNL_UCBCN(array('dsn'=>$this->dsn));

        $permissions = array(
                            'Event Create',
                            'Event Delete',
                            'Event Post',
                            'Event Send Event to Pending Queue',
                            'Event Edit',
                            'Event Recommend',
                            'Event Remove from Pending',
                            'Event Remove from Posted',
                            'Event Remove from System ',
                            'Event View Queue',
                            'Event Export',
                            'Event Upload',
                            'Calendar Add User',
                            'Calendar Add Subscription',
                            'Calendar Delete Subscription',
                            'Calendar Delete User',
                            'Calendar Edit Subscription',
                            'Calendar Change User Permissions',
                            'Calendar Format',
                            'Calendar Edit',
                            'Calendar Change Format',
                            'Calendar Delete');
        foreach ($permissions as $p_type) {
            $p       = $backend->factory('permission');
            $p->name = $p_type;
            if (!$p->find()) {
                $p->name        = $p_type;
                $p->description = $p_type;
                $p->insert();
            } else {
                $this->outputData("The permission $p_type already exists.");
            }
        }
        return true;
    }
    
    /**
     * takes in a string and sends it to the client.
     * 
     * @param string $msg Text of message
     * 
     * @return void
     */
    function outputData($msg)
    {
        if (isset($this->_ui)) {
            $this->_ui->outputData($msg.PHP_EOL);
        } else {
            echo $msg.PHP_EOL;
        }
    }
}
?>