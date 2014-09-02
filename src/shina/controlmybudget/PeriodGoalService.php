<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 01/09/14
 * Time: 22:00
 */

namespace shina\controlmybudget;


use ebussola\common\datatype\datetime\Date;
use ebussola\goalr\Event;

class PeriodGoalService
{

    /**
     * @var DataProvider
     */
    private $data_provider;

    public function __construct(DataProvider $data_provider)
    {
        $this->data_provider = $data_provider;
    }

    /**
     * @param PeriodGoal $period_goal
     * @param User $user
     */
    public function save(PeriodGoal $period_goal, User $user)
    {
        if ($period_goal->id == null) {
            $id = $this->data_provider->insertPeriodGoal($this->toArray($period_goal, $user));
            $period_goal->id = $id;
        } else {
            $this->data_provider->updatePeriodGoal($period_goal->id, $this->toArray($period_goal, $user));
        }
    }

    /**
     * @param int $period_goal_id
     *
     * @return PeriodGoal
     */
    public function getPeriodGoalById($period_goal_id)
    {
        $data = $this->data_provider->findPeriodGoalByIds([$period_goal_id]);

        $period_goals = array();
        foreach ($data as $row) {
            $period_goals[] = $this->createPeriodGoal($row);
        }

        return reset($period_goals);
    }

    /**
     * @param Date $date_start
     * @param Date $date_end
     * @param User $user
     *
     * @return PeriodGoal[]
     */
    public function getPeriodGoalByPeriod(Date $date_start, Date $date_end, User $user)
    {
        $data = $this->data_provider->findPeriodGoalsByPeriod($date_start, $date_end, $user->id);

        $period_goals = array();
        foreach ($data as $row) {
            $period_goals[] = $this->createPeriodGoal($row);
        }

        return $period_goals;
    }

    /**
     * @param User $user
     * @param int $page
     * @param null|int $page_size
     *
     * @return PeriodGoal[]
     */
    public function getAll(User $user, $page = 1, $page_size = null)
    {
        $data = $this->data_provider->findAllPeriodGoals($user->id, $page, $page_size);

        $period_goals = array();
        foreach ($data as $row) {
            $period_goals[] = $this->createPeriodGoal($row);
        }

        return $period_goals;
    }

    /**
     * @param int $period_goal_id
     * @return bool
     */
    public function delete($period_goal_id)
    {
        return $this->data_provider->deletePeriodGoal($period_goal_id);
    }

    private function toArray(PeriodGoal $period_goal, User $user)
    {
        return array(
            'id' => $period_goal->id,
            'name' => $period_goal->name,
            'date_start' => $period_goal->date_start->format('Y-m-d'),
            'date_end' => $period_goal->date_end->format('Y-m-d'),
            'amount_goal' => $period_goal->amount_goal,
            'events' => $this->eventsToArray($period_goal->events),
            'user_id' => $user->id
        );
    }

    /**
     * @param Event[] $events
     * @return array
     */
    private function eventsToArray($events)
    {
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
     * @return PeriodGoal
     */
    private function createPeriodGoal($row)
    {
        $period_goal = new PeriodGoal();
        $period_goal->id = $row['id'];
        $period_goal->name = $row['name'];
        $period_goal->date_start = new Date($row['date_start']);
        $period_goal->date_end = new Date($row['date_end']);
        $period_goal->amount_goal = (float)$row['amount_goal'];
        $period_goal->events = $this->createEvents($row['events']);

        return $period_goal;
    }

    private function createEvents($events)
    {
        foreach ($events as &$event_data) {
            $obj = new Event\Event();
            $obj->id = $event_data['id'];
            $obj->date_start = new \DateTime($event_data['date_start']);
            $obj->date_end = new \DateTime($event_data['date_end']);
            $obj->name = $event_data['name'];
            $obj->variation = (float)$event_data['variation'];
            $obj->category = $event_data['category'];

            $event_data = $obj;
        }

        return $events;
    }

}