<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 10/02/14
 * Time: 16:58
 */

namespace shina\controlmybudget;


use ebussola\goalr\event\Event;

class MonthlyGoalService {

    /**
     * @var DataProvider
     */
    private $data_provider;

    public function __construct(DataProvider $data_provider) {
        $this->data_provider = $data_provider;
    }

    /**
     * @param MonthlyGoal $monthly_goal
     */
    public function save(MonthlyGoal $monthly_goal) {
        if ($monthly_goal->id == null) {
            $id = $this->data_provider->insertMonthlyGoal($this->toArray($monthly_goal));
            $monthly_goal->id = $id;
        } else {
            $this->data_provider->updateMonthlyGoal($monthly_goal->id, $this->toArray($monthly_goal));
        }
    }

    public function getGoalByMonthAndYear($month, $year) {

    }

    private function toArray(MonthlyGoal $monthly_goal) {
        return array(
            'id' => $monthly_goal->id,
            'month' => $monthly_goal->month,
            'year' => $monthly_goal->year,
            'amount_goal' => $monthly_goal->amount_goal,
            'events' => $this->eventsToArray($monthly_goal->events)
        );
    }

    /**
     * @param Event[] $events
     */
    private function eventsToArray($events) {
        foreach ($events as &$event) {
            $event = array(
                'id' => $event->id,
                'name' => $event->name,
                'date_start' => $event->date_start->format('Y-m-d'),
                'date_end' => $event->date_end->format('Y-m-d'),
                'variation' => $event->variation,
                'category' => $event->category
            );
        }

        return $events;
    }

}