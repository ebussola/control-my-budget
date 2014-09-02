<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 10/02/14
 * Time: 16:46
 */

namespace shina\controlmybudget;

use ebussola\goalr\Event;
use ebussola\goalr\goal\Goal;
use ebussola\goalr\Goalr;

class BudgetControlService
{

    /**
     * @var PurchaseService
     */
    private $purchase_service;

    /**
     * @var Goalr
     */
    private $goalr;

    public function __construct(PurchaseService $purchase_service, Goalr $goalr)
    {
        $this->purchase_service = $purchase_service;
        $this->goalr = $goalr;
    }

    /**
     * @param MonthlyGoal $monthly_goal
     * @param User $user
     * @param float|null $manual_spent
     * Use $manual_spent to add a simulation spent to preview a daily budget with this spent.
     *
     * @return float
     */
    public function getDailyMonthlyBudget(MonthlyGoal $monthly_goal, User $user, $manual_spent = null)
    {
        $date_start = new \DateTime();
        $date_start->setDate($monthly_goal->year, $monthly_goal->month, 1);

        $date_end = clone $date_start;
        $date_end->setDate($date_end->format('Y'), $date_end->format('m'), $date_end->format('t'));

        $goal = new Goal();
        $goal->date_start = $date_start;
        $goal->date_end = $date_end;
        $goal->total_budget = $monthly_goal->amount_goal;

        return $this->processDailyBudget($monthly_goal->events, $user, $manual_spent, $goal);
    }

    /**
     * @param PeriodGoal $period_goal
     * @param User $user
     * @param float | null $manual_spent
     * @return float
     */
    public function getDailyPeriodBudget(PeriodGoal $period_goal, User $user, $manual_spent = null)
    {
        $goal = new Goal();
        $goal->date_start = $period_goal->date_start;
        $goal->date_end = $period_goal->date_end;
        $goal->total_budget = $period_goal->amount_goal;

        return $this->processDailyBudget($period_goal->events, $user, $manual_spent, $goal);
    }

    /**
     * @param Event[] $events
     * @param User $user
     * @param $manual_spent
     * @param $goal
     * @return float
     */
    protected function processDailyBudget(
        $events,
        User $user,
        $manual_spent,
        $goal
    ) {
        $yesterday = clone $this->goalr->current_date;
        $yesterday->modify('-1 day');
        $tomorrow = clone $this->goalr->current_date;
        $tomorrow->modify('+1 day');
        $forecast_amount_today = $this->purchase_service->getForecastAmountByPeriod(
            $this->goalr->current_date,
            $this->goalr->current_date,
            $user
        );

        $spent = $this->purchase_service->getAmountByPeriod($goal->date_start, $yesterday, $user)
            + $this->purchase_service->getAmountByPeriod($tomorrow, $goal->date_end, $user)
            + $forecast_amount_today;

        if ($manual_spent !== null) {
            $spent += $manual_spent;
        }
        $spent_today = $this->purchase_service->getAmountByPeriod(
                $this->goalr->current_date,
                $this->goalr->current_date,
                $user
            ) - $forecast_amount_today;
        $daily_budget = $this->goalr->getDailyBudget($goal, $spent, $events);

        return $daily_budget - $spent_today;
    }

}