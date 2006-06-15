<?php
/**
 * Table Definition for event_has_keyword
 */
require_once 'DB/DataObject.php';

class UNL_UCBCN_Event_has_keyword extends DB_DataObject 
{
    ###START_AUTOCODE
    /* the code below is auto generated do not remove the above tag */

    public $__table = 'event_has_keyword';               // table name
    public $event_id;                        // int(10)  not_null unsigned
    public $keyword_id;                      // int(10)  not_null unsigned

    /* Static get */
    function staticGet($k,$v=NULL) { return DB_DataObject::staticGet('UNL_UCBCN_Event_has_keyword',$k,$v); }

    /* the code above is auto generated do not remove the tag below */
    ###END_AUTOCODE
}