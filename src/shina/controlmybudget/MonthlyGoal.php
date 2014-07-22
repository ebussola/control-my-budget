<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 10/02/14
 * Time: 16:51
 */

namespace shina\controlmybudget;

use ebussola\goalr\Event;

/**
 * Interface MonthlyGoal
 * @package shina\controlmybudget
 *
 * @property int     $id
 * @property int     $month
 * @property int     $year
 * @property float   $amount_goal
 * @property Event[] $events
 */
interface MonthlyGoal
{

}