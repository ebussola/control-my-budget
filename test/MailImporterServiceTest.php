<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 07/02/14
 * Time: 16:38
 */

class MailImporterServiceTest extends PHPUnit_Framework_TestCase {

    /**
     * @var \shina\controlmybudget\MailImporterService
     */
    private $mail_importer_service;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    public function setUp() {
        $config = include 'config.php';

        $imap = new \Fetch\Server($config['server_path'], $config['port']);
        $imap->setAuthentication($config['login'], $config['password']);
        $imap->setMailBox($config['mailbox']);

        $this->conn = \Doctrine\DBAL\DriverManager::getConnection(array(
            'driver' => 'pdo_sqlite',
            'user' => 'root',
            'password' => 'root',
            'memory' => true
        ));

        $data_provider = new DataProviderDoctrine($this->conn);

        $this->mail_importer_service = new \shina\controlmybudget\MailImporterService($imap, $data_provider);
    }

    public function testImport() {
        $this->mail_importer_service->import(5);
        $data = $this->conn->executeQuery('SELECT * FROM purchase')->fetchAll();

        foreach ($data as $row) {
            $this->assertPurchaseData($row);
        }
    }

    private function assertPurchaseData($row) {
        $this->assertNotNull($row['id']);
        $this->assertNotNull($row['date']);

        $date_portion = explode('-', $row['date']);
        $this->assertTrue(checkdate($date_portion[1], $date_portion[2], $date_portion[0]));

        $this->assertNotNull($row['place']);
        $this->assertNotNull($row['amount']);
    }

}