<?php
/**
 * Table Definition for performertype
 */
require_once 'DB/DataObject.php';

class UNL_UCBCN_Performertype extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'performertype';                   // table name
    public $id;                              // int(11)  not_null primary_key auto_increment
    public $name;                            // string(100)  
    public $description;                     // string(255)  

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('UNL_UCBCN_Performertype',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}