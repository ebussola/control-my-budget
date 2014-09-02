<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 11/02/14
 * Time: 14:07
 */

use ebussola\common\datatype\datetime\Date;
use ebussola\goalr\Goalr;

class BudgetControlServiceTest extends PHPUnit_Framework_TestCase {

    /**
     * @var \shina\controlmybudget\BudgetControlService
     */
    private $budget_control_service;

    /**
     * @var \shina\controlmybudget\PurchaseService
     */
    private $purchase_service;

    /**
     * @var \shina\controlmybudget\DataProvider
     */
    private $data_provider;

    /**
     * @var \shina\controlmybudget\User
     */
    protected $user;

    public function setUp() {
        $goalr = new Goalr();
        $conn = \Doctrine\DBAL\DriverManager::getConnection(array(
            'driver' => 'pdo_sqlite',
            'user' => 'root',
            'password' => 'root',
            'memory' => true
        ));
        $this->data_provider = new DataProviderDoctrine($conn);
        $purchase_service = new \shina\controlmybudget\PurchaseService($this->data_provider);
        $this->purchase_service = new \shina\controlmybudget\PurchaseService($this->data_provider);
        $this->budget_control_service = new \shina\controlmybudget\BudgetControlService($purchase_service, $goalr);

        $this->user = new \shina\controlmybudget\User();
        $this->user->id = 1;
    }

    public function testGetDailyBudget() {
        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('2014-01-15');
        $purchase->place = 'Zona Sul';
        $purchase->amount = 2.1;
        $this->purchase_service->save($purchase, $this->user);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('2013-12-25');
        $purchase->place = 'Natalandia';
        $purchase->amount = 300;
        $this->purchase_service->save($purchase, $this->user);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('2014-01-20');
        $purchase->place = 'Casa do carnaval';
        $purchase->amount = 54.70;
        $this->purchase_service->save($purchase, $this->user);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('2014-01-21');
        $purchase->place = 'Bigbi';
        $purchase->amount = 11.00;
        $this->purchase_service->save($purchase, $this->user);


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


        echo $this->budget_control_service->getDailyMonthlyBudget($monthly_goal, $this->user) . PHP_EOL;
    }

    public function testDecreaseTodaysPurchases()
    {
        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new Date('2014-06-01');
        $purchase->place = 'foo';
        $purchase->amount = 700;
        $this->purchase_service->save($purchase, $this->user);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new Date('2014-06-15');
        $purchase->place = 'foo';
        $purchase->amount = 50;
        $this->purchase_service->save($purchase, $this->user);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new Date('2014-06-16');
        $purchase->place = 'foo';
        $purchase->amount = 25;
        $this->purchase_service->save($purchase, $this->user);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new Date('2014-06-16');
        $purchase->place = 'foobar';
        $purchase->amount = 25;
        $this->purchase_service->save($purchase, $this->user);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new Date('2014-06-17');
        $purchase->place = 'bar';
        $purchase->amount = 30;
        $this->purchase_service->save($purchase, $this->user);

        $monthly_goal = new \shina\controlmybudget\MonthlyGoal\MonthlyGoal();
        $monthly_goal->month = 6;
        $monthly_goal->year = 2014;
        $monthly_goal->amount_goal = 1500;
        $monthly_goal->events = [];

        $goalr = new Goalr(new Date('2014-06-17'));
        $budget_control_service = new \shina\controlmybudget\BudgetControlService($this->purchase_service, $goalr);

        $this->assertEquals(20, $budget_control_service->getDailyMonthlyBudget($monthly_goal, $this->user));
    }

    public function testDontDecreaseTodays_ButForecast_Purchases()
    {
        $purchase_service = new \shina\controlmybudget\PurchaseService($this->data_provider, new Date('2014-08-01'));

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new Date('2014-08-02');
        $purchase->place = 'foo';
        $purchase->amount = 10;
        $purchase_service->save($purchase, $this->user);

        $monthly_goal = new \shina\controlmybudget\MonthlyGoal\MonthlyGoal();
        $monthly_goal->month = 8;
        $monthly_goal->year = 2014;
        $monthly_goal->amount_goal = 1510;
        $monthly_goal->events = [];

        $goalr = new Goalr(new Date('2014-08-02'));
        $budget_control_service = new \shina\controlmybudget\BudgetControlService($this->purchase_service, $goalr);

        $this->assertEquals(50, $budget_control_service->getDailyMonthlyBudget($monthly_goal, $this->user));
    }

    public function testSpentSimulation()
    {
        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new Date('2014-06-01');
        $purchase->place = 'foo';
        $purchase->amount = 700;
        $this->purchase_service->save($purchase, $this->user);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new Date('2014-06-15');
        $purchase->place = 'foo';
        $purchase->amount = 50;
        $this->purchase_service->save($purchase, $this->user);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new Date('2014-06-16');
        $purchase->place = 'foo';
        $purchase->amount = 25;
        $this->purchase_service->save($purchase, $this->user);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new Date('2014-06-16');
        $purchase->place = 'foobar';
        $purchase->amount = 25;
        $this->purchase_service->save($purchase, $this->user);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new Date('2014-06-17');
        $purchase->place = 'bar';
        $purchase->amount = 30;
        $this->purchase_service->save($purchase, $this->user);

        $monthly_goal = new \shina\controlmybudget\MonthlyGoal\MonthlyGoal();
        $monthly_goal->month = 6;
        $monthly_goal->year = 2014;
        $monthly_goal->amount_goal = 1500;
        $monthly_goal->events = [];

        $goalr = new Goalr(new Date('2014-06-17'));
        $budget_control_service = new \shina\controlmybudget\BudgetControlService($this->purchase_service, $goalr);

        $this->assertEquals(2, $budget_control_service->getDailyMonthlyBudget($monthly_goal, $this->user, 252));
    }

    public function testForecastPurchase()
    {
        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new Date('2014-08-10');
        $purchase->place = 'foo';
        $purchase->amount = 500;
        $this->purchase_service->save($purchase, $this->user);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new Date('2014-08-15');
        $purchase->place = 'bar';
        $purchase->amount = 70;
        $this->purchase_service->save($purchase, $this->user);

        $monthly_goal = new \shina\controlmybudget\MonthlyGoal\MonthlyGoal();
        $monthly_goal->month = 8;
        $monthly_goal->year = 2014;
        $monthly_goal->amount_goal = 1500;
        $monthly_goal->events = [];

        $goalr = new Goalr(new Date('2014-08-01'));
        $budget_control_service = new \shina\controlmybudget\BudgetControlService($this->purchase_service, $goalr);

        $this->assertEquals(30, $budget_control_service->getDailyMonthlyBudget($monthly_goal, $this->user));
    }

    public function testGetDailyPeriodBudget() {
        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('2014-01-15');
        $purchase->place = 'Zona Sul';
        $purchase->amount = 2.1;
        $this->purchase_service->save($purchase, $this->user);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('2013-12-25');
        $purchase->place = 'Natalandia';
        $purchase->amount = 300;
        $this->purchase_service->save($purchase, $this->user);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('2014-01-20');
        $purchase->place = 'Casa do carnaval';
        $purchase->amount = 54.70;
        $this->purchase_service->save($purchase, $this->user);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('2014-01-21');
        $purchase->place = 'Bigbi';
        $purchase->amount = 11.00;
        $this->purchase_service->save($purchase, $this->user);


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
        $period_goal->date_start = new Date('2014-01-01');
        $period_goal->date_end = new Date('2014-01-31');
        $period_goal->amount_goal = 1500;
        $period_goal->events = $events;


        echo $this->budget_control_service->getDailyPeriodBudget($period_goal, $this->user) . PHP_EOL;
    }

}
 