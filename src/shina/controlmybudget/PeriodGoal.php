<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 01/09/14
 * Time: 22:17
 */

namespace shina\controlmybudget;


use ebussola\common\datatype\datetime\Date;
use ebussola\goalr\Event;

class PeriodGoal
{

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $name;

    /**
     * @var Date
     */
    public $date_start;

    /**
     * @var Date
     */
    public $date_end;

    /**
     * @var float
     */
    public $amount_goal;

    /**
     * @var Event[]
     */
    public $events;

    public function __construct()
    {
        $this->events = [];
    }

} 