<?php
use ebussola\common\datatype\datetime\Date;
use shina\controlmybudget\User;

/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 07/02/14
 * Time: 16:38
 */

abstract class MailImportAbstract extends PHPUnit_Framework_TestCase {

    /**
     * @var \shina\controlmybudget\Importer
     */
    private $mail_importer;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    /**
     * @var User
     */
    protected $user;

    /**
     * @var Swift_Mailer
     */
    protected $mailer;

    /**
     * @var \Fetch\Server
     */
    protected $imap;

    protected $to;

    public function setUp() {
        $config = include 'config.php';

        $this->user = new User();
        $this->user->id = 1;
        $this->user->email = $config['email'];
        $this->user->name = 'CMB Test';

        $this->imap = new \Fetch\Server($config['server_path'], $config['port']);
        $this->imap->setAuthentication($config['login'], $config['password']);
        $this->imap->setMailBox($config['mailbox']);

        $this->conn = \Doctrine\DBAL\DriverManager::getConnection(array(
            'driver' => 'pdo_sqlite',
            'user' => 'root',
            'password' => 'root',
            'memory' => true
        ));

        $data_provider = new DataProviderDoctrine($this->conn);
        $purchase_service = new \shina\controlmybudget\PurchaseService($data_provider);

        $this->mail_importer = $this->makeImporter($this->imap, $purchase_service);
    }

    public function tearDown() {
        $this->conn->close();
        imap_close($this->imap->getImapStream());
    }

    // Actually it test nothing, only remove all messages and send some emails
    public function testOnlyToSendSomeEmails()
    {
        $messages = $this->imap->getMessages();
        foreach ($messages as $message) {
            $message->delete();
        }

        $this->sendMockMails();
    }

    public function testImport()
    {
        $this->mail_importer->import(2, $this->user);
        $data = $this->conn->executeQuery('SELECT * FROM purchase')->fetchAll();
        $this->assertCount(2, $data);

        foreach ($data as $row) {
            $this->assertPurchaseData($row);
        }
    }

    /**
     * @depends testImport
     */
    public function testImportOnlyFromRightUser()
    {
        $this->mail_importer->import(4, $this->user);
        $data = $this->conn->executeQuery('SELECT * FROM purchase')->fetchAll();
        $this->assertCount(2, $data);

        foreach ($data as $row) {
            $this->assertPurchaseData($row);
        }
    }

    public function testImportDuplicated() {
        $this->mail_importer->import(1, $this->user);
        $data = $this->conn->executeQuery('SELECT * FROM purchase')->fetchAll();
        $data_count = count($data);

        $this->mail_importer->import(1, $this->user);
        $data = $this->conn->executeQuery('SELECT * FROM purchase')->fetchAll();

        $this->assertCount($data_count, $data);
    }

    private function assertPurchaseData($row) {
        $this->assertNotNull($row['id']);
        $this->assertNotNull($row['date']);

        $date_portion = explode('-', $row['date']);
        $this->assertTrue(checkdate($date_portion[1], $date_portion[2], $date_portion[0]));

        $this->assertNotNull($row['place']);
        $this->assertNotNull($row['amount']);
        $this->assertTrue(is_numeric($row['amount']) && floor($row['amount']) != $row['amount']); // all amount should be decimal according to generated emails sent by sendMockMails
        $this->assertNotNull($row['user_id']);
        $this->assertEquals($this->user->id, $row['user_id']);
    }

    /**
     * @param $config
     */
    protected function sendMockMails()
    {
        $config = include 'config.php';

        $to = ['email' => $config['email'], 'name' => 'CMB Container'];

        $transport = new Swift_SmtpTransport($config['sender']['server'], $config['sender']['port'], $config['sender']['security']);
        $transport->setUsername($config['sender']['login'])
            ->setPassword($config['sender']['password']);
        $this->mailer = new Swift_Mailer($transport);

        // Send some valid emails
        for ($i = 1; $i <= 2; $i++) {
            $message = $this->makeMessage(new Date('-' . $i . ' days'), md5(rand(0, 1000)), rand(0, 1000).','.rand(1, 99));
            $message->setFrom($config['sender']['email'])
                ->setTo($to['email'], $to['name']);
            $this->mailer->send($message);
        }

        $transport = new Swift_SmtpTransport($config['invalid_sender']['server'], $config['invalid_sender']['port'], $config['invalid_sender']['security']);
        $transport->setUsername($config['invalid_sender']['login'])
            ->setPassword($config['invalid_sender']['password']);
        $this->mailer = new Swift_Mailer($transport);

        // Send some invalid emails
        for ($i = 1; $i <= 2; $i++) {
            $message = $this->makeMessage(new Date('-' . $i . ' days'), md5(rand(0, 1000)), rand(0, 1000));
            $message->setFrom($config['invalid_sender']['email'])
                ->setTo($to['email'], $to['name']);
            $this->mailer->send($message);
        }
    }

    /**
     * @param $imap
     * @param $purchase_service
     * @return \shina\controlmybudget\Importer
     */
    abstract protected function makeImporter($imap, $purchase_service);

    /**
     * @param Date $date
     * @param $place
     * @param $amount
     * @return Swift_Message
     */
    abstract protected function makeMessage(Date $date, $place, $amount);

}