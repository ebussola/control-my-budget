<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 10/02/14
 * Time: 10:50
 */

class DataProviderDoctrine implements \shina\controlmybudget\DataProvider {

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    static public $id_count = 1;

    public function __construct(\Doctrine\DBAL\Connection $conn) {
        $this->conn = $conn;

        $this->createTable();
    }

    /**
     * @param array $data
     *
     * @return int
     */
    public function insertPurchase(array $data) {
        $data['id'] = self::$id_count;
        $this->conn->insert('purchase', $data);
        self::$id_count++;

        return $this->conn->lastInsertId();
    }

    /**
     * @param int   $id
     * @param array $data
     *
     * @return bool
     */
    public function updatePurchase($id, array $data) {
        return $this->conn->update('purchase', $data, array('id' => $id)) === 1;
    }

    /**
     * @param array $data
     *
     * @return int
     */
    public function savePurchase(array $data) {
        if (isset($data['id']) && $data['id'] != null) {
            $this->updatePurchase($data['id'], $data);

            return $data['id'];
        } else {
            $id = $this->insertPurchase($data);

            return $id;
        }
    }

    /**
     * @param \DateTime $date_start
     * @param \DateTime $date_end
     *
     * @return array
     */
    public function findPurchasesByPeriod(\DateTime $date_start, \DateTime $date_end) {
        $data = $this->conn->executeQuery('SELECT * FROM purchase WHERE date >= ? AND date <= ?', array(
            $date_start->format('Y-m-d'),
            $date_end->format('Y-m-d')
        ))->fetchAll();

        return $data;
    }

    private function createTable() {
        $schema = $this->conn->getSchemaManager()->createSchema();

        $table = $schema->createTable('purchase');
        $table->addColumn('id', 'integer');
        $table->addColumn('date', 'date');
        $table->addColumn('place', 'string');
        $table->addColumn('amount', 'float');

        $sqls = $schema->toSql($this->conn->getDatabasePlatform());
        foreach ($sqls as $sql) {
            $this->conn->executeQuery($sql);
        }
    }

}