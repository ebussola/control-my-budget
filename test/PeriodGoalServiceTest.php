<?php
use ebussola\common\datatype\datetime\Date;
use shina\controlmybudget\User;

/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 10/02/14
 * Time: 17:06
 */
class PeriodGoalServiceTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var \shina\controlmybudget\PeriodGoalService
     */
    private $period_goal_service;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @var User
     */
    private $user;

    public function setUp()
    {
        $this->conn = \Doctrine\DBAL\DriverManager::getConnection(
            array(
                'driver' => 'pdo_sqlite',
                'user' => 'root',
                'password' => 'root',
                'memory' => true
            )
        );
        $data_provider = new DataProviderDoctrine($this->conn);
        $this->period_goal_service = new \shina\controlmybudget\PeriodGoalService($data_provider);

        $this->user = new User();
        $this->user->id = 1;
    }

    public function testSave()
    {
        $period_goal = $this->createExampleGoal();

        $this->period_goal_service->save($period_goal, $this->user);

        $data = $this->conn->executeQuery('SELECT * FROM period_goal')->fetchAll();
        $this->assertCount(1, $data);
        foreach ($data as $row) {
            $this->assertPeriodGoalData($row);
        }

        $data = $this->conn->executeQuery('SELECT * FROM period_event')->fetchAll();
        $this->assertCount(2, $data);
        foreach ($data as $row) {
            $this->assertEventData($row);
        }
    }

    public function testSave_WithoutEvents()
    {
        $period_goal = new \shina\controlmybudget\PeriodGoal();
        $period_goal->name = 'Maio';
        $period_goal->date_start = new Date('2014-05-01');
        $period_goal->date_end = new Date('2014-05-31');
        $period_goal->amount_goal = 1000;

        $this->period_goal_service->save($period_goal, $this->user);

        $data = $this->conn->executeQuery('SELECT * FROM period_goal')->fetchAll();
        $this->assertCount(1, $data);
        foreach ($data as $row) {
            $this->assertPeriodGoalData($row);
        }

        $data = $this->conn->executeQuery('SELECT * FROM event')->fetchAll();
        $this->assertCount(0, $data);
    }

    public function testGetPeriodGoalByMonthAndYear()
    {
        $period_goal = $this->createExampleGoal();
        $this->period_goal_service->save($period_goal, $this->user);
        $period_goal = $this->createExampleGoal2();
        $this->period_goal_service->save($period_goal, $this->user);

        $period_goals = $this->period_goal_service->getPeriodGoalByPeriod(
            new Date('2014-01-01'),
            new Date('2014-01-31'),
            $this->user
        );
        $this->assertCount(1, $period_goals);
        foreach ($period_goals as $period_goal) {
            $this->assertPeriodGoalObj($period_goal);
        }
    }

    public function testGetPeriodGoalById()
    {
        $period_goal1 = $this->createExampleGoal();
        $this->period_goal_service->save($period_goal1, $this->user);
        $period_goal2 = $this->createExampleGoal2();
        $this->period_goal_service->save($period_goal2, $this->user);

        $period_goal = $this->period_goal_service->getPeriodGoalById(2);
        $this->assertEquals($period_goal->id, $period_goal2->id);
        $this->assertNotEquals($period_goal->id, $period_goal1->id);
    }

    public function testGetAll()
    {
        $period_goal1 = $this->createExampleGoal();
        $this->period_goal_service->save($period_goal1, $this->user);
        $period_goal2 = $this->createExampleGoal2();
        $this->period_goal_service->save($period_goal2, $this->user);

        $period_goals = $this->period_goal_service->getAll($this->user);

        $this->assertCount(2, $period_goals);
        foreach ($period_goals as $period_goal) {
            $this->assertPeriodGoalObj($period_goal);
        }
    }

    public function testDelete()
    {
        $period_goal = $this->createExampleGoal();
        $this->period_goal_service->save($period_goal, $this->user);

        $this->period_goal_service->delete($period_goal->id);

        $data = $this->conn->executeQuery('select * from period_goal')->fetchAll();
        $this->assertCount(0, $data);
    }

    private function assertPeriodGoalData($row)
    {
        $this->assertNotNull($row['id']);
        $this->assertNotNull($row['name']);
        $this->assertNotNull($row['date_start']);
        $this->assertNotNull($row['date_end']);
        $this->assertNotNull($row['amount_goal']);
        $this->assertNotNull($row['user_id']);
    }

    private function assertEventData($row)
    {
        $this->assertNotNull($row['id']);
        $this->assertNotNull($row['name']);
        $this->assertNotNull($row['date_start']);
        $this->assertNotNull($row['date_end']);
        $this->assertNotNull($row['variation']);
        $this->assertNotNull($row['category']);
    }

    /**
     * @return \shina\controlmybudget\PeriodGoal
     */
    private function createExampleGoal()
    {
        $events = [];

        $event = new \ebussola\goalr\event\Event();
        $event->name = 'fds';
        $event->date_start = new DateTime('2014-01-01');
        $event->date_end = new DateTime('2014-01-02');
        $event->variation = 50;
        $event->category = 'regular';
        $events[] = $event;

        $event = new \ebussola\goalr\event\Event();
        $event->name = 'fds';
        $event->date_start = new DateTime('2014-01-08');
        $event->date_end = new DateTime('2014-01-09');
        $event->variation = 50;
        $event->category = 'regular';
        $events[] = $event;

        $period_goal = new \shina\controlmybudget\PeriodGoal();
        $period_goal->name = 'Janeiro';
        $period_goal->date_start = new Date('2014-01-01');
        $period_goal->date_end = new Date('2014-01-31');
        $period_goal->amount_goal = 1500;
        $period_goal->events = $events;

        return $period_goal;
    }

    /**
     * @return \shina\controlmybudget\PeriodGoal
     */
    private function createExampleGoal2()
    {
        $events = [];

        $event = new \ebussola\goalr\event\Event();
        $event->name = 'fds';
        $event->date_start = new DateTime('2014-02-01');
        $event->date_end = new DateTime('2014-02-02');
        $event->variation = 50;
        $event->category = 'regular';
        $events[] = $event;

        $event = new \ebussola\goalr\event\Event();
        $event->name = 'fds';
        $event->date_start = new DateTime('2014-02-08');
        $event->date_end = new DateTime('2014-02-09');
        $event->variation = 50;
        $event->category = 'regular';
        $events[] = $event;

        $period_goal = new \shina\controlmybudget\PeriodGoal();
        $period_goal->name = 'Fevereiro';
        $period_goal->date_start = new Date('2014-02-01');
        $period_goal->date_end = new Date('2014-02-28');
        $period_goal->amount_goal = 1500;
        $period_goal->events = $events;

        return $period_goal;
    }

    private function assertPeriodGoalObj(\shina\controlmybudget\PeriodGoal $period_goal)
    {
        $this->assertNotNull($period_goal->id);
        $this->assertNotNull($period_goal->name);
        $this->assertInstanceOf('\ebussola\common\datatype\datetime\Date', $period_goal->date_start);
        $this->assertInstanceOf('\ebussola\common\datatype\datetime\Date', $period_goal->date_end);
        $this->assertNotNull($period_goal->amount_goal);
        $this->assertTrue(is_float($period_goal->amount_goal));
        $this->assertNotNull($period_goal->events);

        foreach ($period_goal->events as $event) {
            $this->assertNotNull($event->id);
            $this->assertNotNull($event->name);
            $this->assertNotNull($event->date_start);
            $this->assertNotNull($event->date_end);
            $this->assertNotNull($event->variation);
            $this->assertNotNull($event->category);
        }
    }

}