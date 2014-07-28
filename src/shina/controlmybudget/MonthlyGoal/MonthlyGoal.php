<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 10/02/14
 * Time: 16:56
 */

namespace shina\controlmybudget\MonthlyGoal;


class MonthlyGoal implements \shina\controlmybudget\MonthlyGoal
{

    public $id;
    public $month;
    public $year;
    public $amount_goal;
    public $events;

    public function __construct()
    {
        $this->events = [];
    }

}