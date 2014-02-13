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

    /**
     * @param int $monthly_goal_id
     *
     * @return MonthlyGoal
     */
    public function getMonthlyGoalById($monthly_goal_id) {
        $data = $this->data_provider->findMonthlyGoalByIds([$monthly_goal_id]);

        $monthly_goals = array();
        foreach ($data as $row) {
            $monthly_goals[] = $this->createMonthlyGoal($row);
        }

        return reset($monthly_goals);
    }

    /**
     * @param int $month
     * @param int $year
     *
     * @return MonthlyGoal[]
     */
    public function getMonthlyGoalByMonthAndYear($month, $year) {
        $data = $this->data_provider->findMonthlyGoalsByMonthAndYear($month, $year);

        $monthly_goals = array();
        foreach ($data as $row) {
            $monthly_goals[] = $this->createMonthlyGoal($row);
        }

        return $monthly_goals;
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

    /**
     * @param $row
     */
    private function createMonthlyGoal($row) {
        $monthly_goal = new MonthlyGoal\MonthlyGoal();
        $monthly_goal->id = $row['id'];
        $monthly_goal->month = $row['month'];
        $monthly_goal->year = $row['year'];
        $monthly_goal->amount_goal = (float) $row['amount_goal'];
        $monthly_goal->events = $this->createEvents($row['events']);

        return $monthly_goal;
    }

    private function createEvents($events) {
        foreach ($events as &$event_data) {
            $obj = new Event();
            $obj->id = $event_data['id'];
            $obj->date_start = new \DateTime($event_data['date_start']);
            $obj->date_end = new \DateTime($event_data['date_end']);
            $obj->name = $event_data['name'];
            $obj->variation = (float) $event_data['variation'];
            $obj->category = $event_data['category'];

            $event_data = $obj;
        }

        return $events;
    }

}