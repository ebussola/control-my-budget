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

    /**
     * @param array $data
     *
     * @return int
     * ID of the added object
     */
    public function insertMonthlyGoal(array $data) {
        $events = $data['events'];
        unset($data['events']);

        $data['id'] = self::$id_count;
        $this->conn->insert('monthly_goal', $data);
        $monthly_goal_id = $this->conn->lastInsertId();
        self::$id_count++;

        $this->saveEvents($events, $monthly_goal_id);

        return $monthly_goal_id;
    }

    /**
     * @param int   $id
     * @param array $data
     *
     * @return bool
     */
    public function updateMonthlyGoal($id, array $data) {
        $events = $data['events'];
        unset($data['events']);

        $this->conn->update('monthly_goal', $data, array('id' => $data['id']));

        $this->saveEvents($events, $data['id']);
    }

    /**
     * @param int $month
     * @param int $year
     *
     * @return \shina\controlmybudget\MonthlyGoal[]
     */
    public function findMonthlyGoalsByMonthAndYear($month, $year) {
        $query = $this->conn->createQueryBuilder()
            ->select('*')
            ->from('monthly_goal', 'mg')
            ->where('mg.month = ?')
            ->andWhere('mg.year = ?');
        $data = $this->conn->executeQuery($query, array(
            $month, $year
        ))->fetchAll();

        foreach ($data as &$monthly_goal_data) {
            $events_data = $this->conn->executeQuery('SELECT * FROM event WHERE monthly_goal_id = ?', array($monthly_goal_data['id']))
                ->fetchAll();
            $monthly_goal_data['events'] = $events_data;
        }

        return $data;
    }

    private function createTable() {
        $schema = $this->conn->getSchemaManager()->createSchema();

        $table1 = $schema->createTable('purchase');
        $table1->addColumn('id', 'integer');
        $table1->addColumn('date', 'date');
        $table1->addColumn('place', 'string');
        $table1->addColumn('amount', 'float');

        $table2 = $schema->createTable('monthly_goal');
        $table2->addColumn('id', 'integer');
        $table2->addColumn('month', 'integer');
        $table2->addColumn('year', 'integer');
        $table2->addColumn('amount_goal', 'float');

        $table3 = $schema->createTable('event');
        $table3->addColumn('id', 'integer');
        $table3->addColumn('name', 'string');
        $table3->addColumn('date_start', 'date');
        $table3->addColumn('date_end', 'date');
        $table3->addColumn('variation', 'float');
        $table3->addColumn('category', 'string');
        $table3->addColumn('monthly_goal_id', 'integer');

        $sqls = $schema->toSql($this->conn->getDatabasePlatform());
        foreach ($sqls as $sql) {
            $this->conn->executeQuery($sql);
        }
    }

    /**
     * @param $events
     * @param $monthly_goal_id
     */
    private function saveEvents($events, $monthly_goal_id) {
        foreach ($events as $event_data) {
            $event_data['monthly_goal_id'] = $monthly_goal_id;
            if ($event_data['id'] == null) {
                $event_data['id'] = self::$id_count*rand(1, 500);
                $this->conn->insert('event', $event_data);
            } else {
                $this->conn->update('event', $event_data, array('id' => $event_data['id']));
            }
        }
    }

}