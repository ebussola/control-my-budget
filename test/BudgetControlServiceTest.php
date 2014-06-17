<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 11/02/14
 * Time: 14:07
 */

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

    public function setUp() {
        $goalr = new Goalr();
        $conn = \Doctrine\DBAL\DriverManager::getConnection(array(
            'driver' => 'pdo_sqlite',
            'user' => 'root',
            'password' => 'root',
            'memory' => true
        ));
        $data_provider = new DataProviderDoctrine($conn);
        $purchase_service = new \shina\controlmybudget\PurchaseService($data_provider);
        $this->purchase_service = new \shina\controlmybudget\PurchaseService($data_provider);
        $this->budget_control_service = new \shina\controlmybudget\BudgetControlService($purchase_service, $goalr);
    }

    public function testGetDailyBudget() {
        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('2014-01-15');
        $purchase->place = 'Zona Sul';
        $purchase->amount = 2.1;
        $this->purchase_service->save($purchase);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('2013-12-25');
        $purchase->place = 'Natalandia';
        $purchase->amount = 300;
        $this->purchase_service->save($purchase);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('2014-01-20');
        $purchase->place = 'Casa do carnaval';
        $purchase->amount = 54.70;
        $this->purchase_service->save($purchase);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('2014-01-21');
        $purchase->place = 'Bigbi';
        $purchase->amount = 11.00;
        $this->purchase_service->save($purchase);


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


        echo $this->budget_control_service->getDailyBudget($monthly_goal);
    }

    public function testDecreaseTodaysPurchases()
    {
        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new \ebussola\common\datatype\datetime\Date('2014-06-01');
        $purchase->place = 'foo';
        $purchase->amount = 700;
        $this->purchase_service->save($purchase);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new \ebussola\common\datatype\datetime\Date('2014-06-15');
        $purchase->place = 'foo';
        $purchase->amount = 50;
        $this->purchase_service->save($purchase);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new \ebussola\common\datatype\datetime\Date('2014-06-16');
        $purchase->place = 'foo';
        $purchase->amount = 25;
        $this->purchase_service->save($purchase);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new \ebussola\common\datatype\datetime\Date('2014-06-16');
        $purchase->place = 'foobar';
        $purchase->amount = 25;
        $this->purchase_service->save($purchase);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new \ebussola\common\datatype\datetime\Date('2014-06-17');
        $purchase->place = 'bar';
        $purchase->amount = 30;
        $this->purchase_service->save($purchase);

        $monthly_goal = new \shina\controlmybudget\MonthlyGoal\MonthlyGoal();
        $monthly_goal->month = 6;
        $monthly_goal->year = 2014;
        $monthly_goal->amount_goal = 1500;
        $monthly_goal->events = [];

        $goalr = new Goalr(new \ebussola\common\datatype\datetime\Date('2014-06-17'));
        $budget_control_service = new \shina\controlmybudget\BudgetControlService($this->purchase_service, $goalr);

        $this->assertEquals(20, $budget_control_service->getDailyBudget($monthly_goal));
    }

}
 