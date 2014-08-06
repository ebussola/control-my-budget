<?php
use ebussola\common\datatype\datetime\Date;
use shina\controlmybudget\User;

/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 10/02/14
 * Time: 16:28
 */
class PurchaseServiceTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var \shina\controlmybudget\PurchaseService
     */
    private $purchase_service;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @var \shina\controlmybudget\DataProvider
     */
    private $data_provider;

    protected $user;

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

        $this->data_provider = new DataProviderDoctrine($this->conn);

        $this->purchase_service = new \shina\controlmybudget\PurchaseService($this->data_provider);

        $this->user = new User();
        $this->user->id = 1;
    }

    public function testSave()
    {
        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('2014-01-15');
        $purchase->place = 'Zona Sul';
        $purchase->amount = 2.1;

        $this->purchase_service->save($purchase, $this->user);
        $this->assertNotNull($purchase->id);

        $data = $this->conn->executeQuery('SELECT * FROM purchase')->fetchAll();
        $this->assertCount(1, $data);
        $this->assertEquals(0, reset($data)['is_forecast']);
        foreach ($data as $row) {
            $this->assertPurchaseData($row);
        }

        // If the purchase is edited, its hash can't change
        $current_hash = reset($data)['hash'];
        $purchase->amount = 2;
        $this->purchase_service->save($purchase, $this->user);

        $data = $this->conn->executeQuery('SELECT * FROM purchase where id=?', [$purchase->id])->fetch();
        $this->assertPurchaseData($data);
        $this->assertEquals($current_hash, $data['hash']);
    }

    public function testSaveFuturePurchase()
    {
        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('+5 days');
        $purchase->place = 'Zona Sul';
        $purchase->amount = 2.1;

        $this->purchase_service->save($purchase, $this->user);

        $data = $this->conn->executeQuery('SELECT * FROM purchase')->fetchAll();
        $this->assertEquals(1, reset($data)['is_forecast']);
    }

    public function testGetPurchasesByPeriod()
    {
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

        $purchases = $this->purchase_service->getPurchasesByPeriod(
            new DateTime('2014-01-01'),
            new DateTime('2014-01-31'),
            $this->user
        );
        $this->assertCount(3, $purchases);
        foreach ($purchases as $purchase) {
            $this->assertPurchaseObject($purchase);
        }
    }

    public function testGetForecastAmountByPeriod()
    {
        $purchase_service = new \shina\controlmybudget\PurchaseService($this->data_provider, new Date('2014-08-15'));

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('2014-08-10');
        $purchase->place = 'Zona Sul';
        $purchase->amount = 2.1;
        $purchase_service->save($purchase, $this->user);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('2014-08-15');
        $purchase->place = 'Natalandia';
        $purchase->amount = 300;
        $purchase_service->save($purchase, $this->user);

        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('2014-08-18');
        $purchase->place = 'Casa do carnaval';
        $purchase->amount = 54.70;
        $purchase_service->save($purchase, $this->user);

        $amount = $purchase_service->getForecastAmountByPeriod(
            new DateTime('2014-08-01'),
            new DateTime('2014-08-31'),
            $this->user
        );
        $this->assertEquals(54.70, $amount);
    }

    public function testGetAmountByPeriod()
    {
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

        $this->assertEquals(
            67.8,
            $this->purchase_service->getAmountByPeriod(
                new DateTime('2014-01-01'),
                new DateTime('2014-01-31'),
                $this->user
            )
        );
    }

    public function testDelete()
    {
        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('2013-12-25');
        $purchase->place = 'Natalandia';
        $purchase->amount = 300;
        $this->purchase_service->save($purchase, $this->user);

        $this->purchase_service->delete($purchase->id);

        $data = $this->conn->executeQuery('select * from purchase')->fetchAll();
        $this->assertCount(0, $data);
    }

    public function testGetById()
    {
        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('2013-12-25');
        $purchase->place = 'Natalandia';
        $purchase->amount = 300;
        $this->purchase_service->save($purchase, $this->user);

        $purchase = $this->purchase_service->getById($purchase->id);

        $this->assertInstanceOf('\shina\controlmybudget\Purchase', $purchase);
    }

    private function assertPurchaseData($row)
    {
        $this->assertNotNull($row['id']);
        $this->assertNotNull($row['date']);
        $this->assertNotNull($row['place']);
        $this->assertNotNull($row['amount']);
        $this->assertNotNull($row['user_id']);
    }

    /**
     * @param $purchase
     */
    private function assertPurchaseObject($purchase)
    {
        $this->assertNotNull($purchase->id);
        $this->assertNotNull($purchase->date);
        $this->assertInstanceOf('\\DateTime', $purchase->date);
        $this->assertNotNull($purchase->place);
        $this->assertNotNull($purchase->amount);
        $this->assertTrue(is_float($purchase->amount));
    }

}