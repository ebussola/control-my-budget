<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 10/02/14
 * Time: 16:46
 */

namespace shina\controlmybudget;

use ebussola\goalr\goal\Goal;
use ebussola\goalr\Goalr;

class BudgetControlService {

    /**
     * @var PurchaseService
     */
    private $purchase_service;

    /**
     * @var Goalr
     */
    private $goalr;

    public function __construct(PurchaseService $purchase_service, Goalr $goalr) {
        $this->purchase_service = $purchase_service;
        $this->goalr = $goalr;
    }

    /**
     * @param MonthlyGoal $monthly_goal
     *
     * @return float
     */
    public function getDailyBudget(MonthlyGoal $monthly_goal) {
        $date_start = new \DateTime();
        $date_start->setDate($monthly_goal->year, $monthly_goal->month, 1);

        $date_end = clone $date_start;
        $date_end->setDate($date_end->format('Y'), $date_end->format('m'), $date_end->format('t'));

        $goal = new Goal();
        $goal->date_start = $date_start;
        $goal->date_end = $date_end;
        $goal->total_budget = $monthly_goal->amount_goal;

        $amount = $this->purchase_service->getAmountByPeriod($date_start, $date_end);

        return $this->goalr->getDailyBudget($goal, $amount, $monthly_goal->events);
    }

}