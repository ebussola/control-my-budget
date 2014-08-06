<?php
use shina\controlmybudget\User;

/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 10/02/14
 * Time: 17:06
 */

class MonthlyGoalServiceTest extends PHPUnit_Framework_TestCase {

    /**
     * @var \shina\controlmybudget\MonthlyGoalService
     */
    private $monthly_goal_service;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @var User
     */
    private $user;

    public function setUp() {
        $this->conn = \Doctrine\DBAL\DriverManager::getConnection(array(
            'driver' => 'pdo_sqlite',
            'user' => 'root',
            'password' => 'root',
            'memory' => true
        ));
        $data_provider = new DataProviderDoctrine($this->conn);
        $this->monthly_goal_service = new \shina\controlmybudget\MonthlyGoalService($data_provider);

        $this->user = new User();
        $this->user->id = 1;
    }

    public function testSave() {
        $monthly_goal = $this->createExampleGoal();

        $this->monthly_goal_service->save($monthly_goal, $this->user);

        $data = $this->conn->executeQuery('SELECT * FROM monthly_goal')->fetchAll();
        $this->assertCount(1, $data);
        foreach ($data as $row) {
            $this->assertMonthlyGoalData($row);
        }

        $data = $this->conn->executeQuery('SELECT * FROM event')->fetchAll();
        $this->assertCount(2, $data);
        foreach ($data as $row) {
            $this->assertEventData($row);
        }
    }

    public function testSave_WithoutEvents()
    {
        $monthly_goal = new \shina\controlmybudget\MonthlyGoal\MonthlyGoal();
        $monthly_goal->month = 5;
        $monthly_goal->year = 2014;
        $monthly_goal->amount_goal = 1000;

        $this->monthly_goal_service->save($monthly_goal, $this->user);

        $data = $this->conn->executeQuery('SELECT * FROM monthly_goal')->fetchAll();
        $this->assertCount(1, $data);
        foreach ($data as $row) {
            $this->assertMonthlyGoalData($row);
        }

        $data = $this->conn->executeQuery('SELECT * FROM event')->fetchAll();
        $this->assertCount(0, $data);
    }

    public function testGetMonthlyGoalByMonthAndYear() {
        $monthly_goal = $this->createExampleGoal();
        $this->monthly_goal_service->save($monthly_goal, $this->user);
        $monthly_goal = $this->createExampleGoal2();
        $this->monthly_goal_service->save($monthly_goal, $this->user);

        $monthly_goals = $this->monthly_goal_service->getMonthlyGoalByMonthAndYear(1, 2014, $this->user);
        $this->assertCount(1, $monthly_goals);
        foreach ($monthly_goals as $monthly_goal) {
            $this->assertMonthlyGoalObj($monthly_goal);
        }
    }

    public function testGetMonthlyGoalById() {
        $monthly_goal1 = $this->createExampleGoal();
        $this->monthly_goal_service->save($monthly_goal1, $this->user);
        $monthly_goal2 = $this->createExampleGoal2();
        $this->monthly_goal_service->save($monthly_goal2, $this->user);

        $monthly_goal = $this->monthly_goal_service->getMonthlyGoalById(2);
        $this->assertEquals($monthly_goal->id, $monthly_goal2->id);
        $this->assertNotEquals($monthly_goal->id, $monthly_goal1->id);
    }

    public function testGetAll()
    {
        $monthly_goal1 = $this->createExampleGoal();
        $this->monthly_goal_service->save($monthly_goal1, $this->user);
        $monthly_goal2 = $this->createExampleGoal2();
        $this->monthly_goal_service->save($monthly_goal2, $this->user);

        $monthly_goals = $this->monthly_goal_service->getAll($this->user);

        $this->assertCount(2, $monthly_goals);
        foreach ($monthly_goals as $monthly_goal) {
            $this->assertMonthlyGoalObj($monthly_goal);
        }
    }

    public function testDelete()
    {
        $monthly_goal = $this->createExampleGoal();
        $this->monthly_goal_service->save($monthly_goal, $this->user);

        $this->monthly_goal_service->delete($monthly_goal->id);

        $data = $this->conn->executeQuery('select * from monthly_goal')->fetchAll();
        $this->assertCount(0, $data);
    }

    private function assertMonthlyGoalData($row) {
        $this->assertNotNull($row['id']);
        $this->assertNotNull($row['month']);
        $this->assertNotNull($row['year']);
        $this->assertNotNull($row['amount_goal']);
        $this->assertNotNull($row['user_id']);
    }

    private function assertEventData($row) {
        $this->assertNotNull($row['id']);
        $this->assertNotNull($row['name']);
        $this->assertNotNull($row['date_start']);
        $this->assertNotNull($row['date_end']);
        $this->assertNotNull($row['variation']);
        $this->assertNotNull($row['category']);
    }

    /**
     * @return \shina\controlmybudget\MonthlyGoal\MonthlyGoal
     */
    private function createExampleGoal() {
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

        $monthly_goal = new \shina\controlmybudget\MonthlyGoal\MonthlyGoal();
        $monthly_goal->month = 1;
        $monthly_goal->year = 2014;
        $monthly_goal->amount_goal = 1500;
        $monthly_goal->events = $events;

        return $monthly_goal;
    }

    /**
     * @return \shina\controlmybudget\MonthlyGoal\MonthlyGoal
     */
    private function createExampleGoal2() {
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

        $monthly_goal = new \shina\controlmybudget\MonthlyGoal\MonthlyGoal();
        $monthly_goal->month = 2;
        $monthly_goal->year = 2014;
        $monthly_goal->amount_goal = 1500;
        $monthly_goal->events = $events;

        return $monthly_goal;
    }

    private function assertMonthlyGoalObj(\shina\controlmybudget\MonthlyGoal $monthly_goal) {
        $this->assertNotNull($monthly_goal->id);
        $this->assertNotNull($monthly_goal->month);
        $this->assertNotNull($monthly_goal->year);
        $this->assertNotNull($monthly_goal->amount_goal);
        $this->assertTrue(is_float($monthly_goal->amount_goal));
        $this->assertNotNull($monthly_goal->events);

        foreach ($monthly_goal->events as $event) {
            $this->assertNotNull($event->id);
            $this->assertNotNull($event->name);
            $this->assertNotNull($event->date_start);
            $this->assertNotNull($event->date_end);
            $this->assertNotNull($event->variation);
            $this->assertNotNull($event->category);
        }
    }

}