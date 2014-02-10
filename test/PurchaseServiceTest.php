<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 10/02/14
 * Time: 16:28
 */

class PurchaseServiceTest extends PHPUnit_Framework_TestCase {

    /**
     * @var \shina\controlmybudget\PurchaseService
     */
    private $purchase_service;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    public function setUp() {
        $this->conn = \Doctrine\DBAL\DriverManager::getConnection(array(
            'driver' => 'pdo_sqlite',
            'user' => 'root',
            'password' => 'root',
            'memory' => true
        ));

        $data_provider = new DataProviderDoctrine($this->conn);

        $this->purchase_service = new \shina\controlmybudget\PurchaseService($data_provider);
    }

    public function testSave() {
        $purchase = new \shina\controlmybudget\Purchase\Purchase();
        $purchase->date = new DateTime('2014-01-15');
        $purchase->place = 'Zona Sul';
        $purchase->amount = 2.1;

        $this->purchase_service->save($purchase);
        $this->assertNotNull($purchase->id);

        $data = $this->conn->executeQuery('SELECT * FROM purchase');
        foreach ($data as $row) {
            $this->assertPurchaseData($row);
        }
    }

    public function testGetPurchasesByPeriod() {
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

        $purchases = $this->purchase_service->getPurchasesByPeriod(new DateTime('2014-01-01'), new DateTime('2014-01-31'));
        $this->assertCount(3, $purchases);
        foreach ($purchases as $purchase) {
            $this->assertPurchaseObject($purchase);
        }
    }

    private function assertPurchaseData($row) {
        $this->assertNotNull($row['id']);
        $this->assertNotNull($row['date']);
        $this->assertNotNull($row['place']);
        $this->assertNotNull($row['amount']);
    }

    /**
     * @param $purchase
     */
    private function assertPurchaseObject($purchase) {
        $this->assertNotNull($purchase->id);
        $this->assertNotNull($purchase->date);
        $this->assertInstanceOf('\\DateTime', $purchase->date);
        $this->assertNotNull($purchase->place);
        $this->assertNotNull($purchase->amount);
        $this->assertTrue(is_float($purchase->amount));
    }

}