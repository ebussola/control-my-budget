<?php

/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 10/02/14
 * Time: 10:50
 */
class DataProviderDoctrine implements \shina\controlmybudget\DataProvider
{

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $conn;

    private $id_count = 1;

    public function __construct(\Doctrine\DBAL\Connection $conn)
    {
        $this->conn = $conn;

        $this->createTable();
    }

    /**
     * @param array $data
     *
     * @return int
     */
    public function insertPurchase(array $data)
    {
        $data['id'] = $this->id_count;
        $this->conn->insert('purchase', $data);
        $this->id_count++;

        return $this->conn->lastInsertId();
    }

    /**
     * @param int $id
     * @param array $data
     *
     * @return bool
     */
    public function updatePurchase($id, array $data)
    {
        return $this->conn->update('purchase', $data, array('id' => $id)) === 1;
    }

    /**
     * @param array $data
     *
     * @return int
     */
    public function savePurchase(array $data)
    {
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
    public function findPurchasesByPeriod(\DateTime $date_start, \DateTime $date_end, $user_id)
    {
        $data = $this->conn->executeQuery(
            'SELECT * FROM purchase WHERE date >= ? AND date <= ? AND user_id = ?',
            array(
                $date_start->format('Y-m-d'),
                $date_end->format('Y-m-d'),
                $user_id
            )
        )->fetchAll();

        return $data;
    }

    /**
     * @param string $hash
     *
     * @return array
     */
    public function findPurchaseByHash($hash)
    {
        $data = $this->conn->executeQuery(
            'SELECT * FROM purchase WHERE hash=?',
            array(
                $hash
            )
        )->fetch();

        return $data;
    }

    /**
     * @param array $data
     *
     * @return int
     * ID of the added object
     */
    public function insertMonthlyGoal(array $data)
    {
        $events = $data['events'];
        unset($data['events']);

        $data['id'] = $this->id_count;
        $this->conn->insert('monthly_goal', $data);
        $monthly_goal_id = $this->conn->lastInsertId();
        $this->id_count++;

        $this->saveEvents($events, $monthly_goal_id);

        return $monthly_goal_id;
    }

    /**
     * @param int $id
     * @param array $data
     *
     * @return bool
     */
    public function updateMonthlyGoal($id, array $data)
    {
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
    public function findMonthlyGoalsByMonthAndYear($month, $year, $user_id)
    {
        $query = $this->conn->createQueryBuilder()
            ->select('*')
            ->from('monthly_goal', 'mg')
            ->where('mg.month = ?')
            ->andWhere('mg.year = ?')
            ->andWhere('mg.user_id = ?');
        $data = $this->conn->executeQuery(
            $query,
            array(
                $month,
                $year,
                $user_id
            )
        )->fetchAll();

        foreach ($data as &$monthly_goal_data) {
            $events_data = $this->conn->executeQuery(
                'SELECT * FROM event WHERE monthly_goal_id = ?',
                array($monthly_goal_data['id'])
            )
                ->fetchAll();
            $monthly_goal_data['events'] = $events_data;
        }

        return $data;
    }

    public function calcAmountByPeriod(\DateTime $date_start, \DateTime $date_end, $user_id, $only_forecast = false)
    {
        $data = $this->findPurchasesByPeriod($date_start, $date_end, $user_id);
        $amount = 0;
        foreach ($data as $row) {
            if ($only_forecast && !$row['is_forecast']) {
                continue;
            }
            $amount += $row['amount'];
        }

        return $amount;
    }

    /**
     * @param int[] $monthly_goal_ids
     *
     * @return \shina\controlmybudget\MonthlyGoal
     */
    public function findMonthlyGoalByIds($monthly_goal_ids)
    {
        $query = $this->conn->createQueryBuilder()
            ->select('*')
            ->from('monthly_goal', 'mg')
            ->where('mg.id IN (?)');
        $data = $this->conn->executeQuery(
            $query,
            array(
                $monthly_goal_ids
            ),
            array(
                \Doctrine\DBAL\Connection::PARAM_INT_ARRAY
            )
        )->fetchAll();

        foreach ($data as &$monthly_goal_data) {
            $events_data = $this->conn->executeQuery(
                'SELECT * FROM event WHERE monthly_goal_id = ?',
                array($monthly_goal_data['id'])
            )
                ->fetchAll();
            $monthly_goal_data['events'] = $events_data;
        }

        return $data;
    }

    private function createTable()
    {
        $schema = $this->conn->getSchemaManager()->createSchema();

        $table1 = $schema->createTable('purchase');
        $table1->addColumn('id', 'integer');
        $table1->addColumn('date', 'date');
        $table1->addColumn('place', 'string');
        $table1->addColumn('amount', 'float');
        $table1->addColumn('hash', 'string');
        $table1->addColumn('is_forecast', 'boolean');
        $table1->addColumn('user_id', 'integer');

        $table2 = $schema->createTable('monthly_goal');
        $table2->addColumn('id', 'integer');
        $table2->addColumn('month', 'integer');
        $table2->addColumn('year', 'integer');
        $table2->addColumn('amount_goal', 'float');
        $table2->addColumn('user_id', 'integer');

        $table3 = $schema->createTable('event');
        $table3->addColumn('id', 'integer');
        $table3->addColumn('name', 'string');
        $table3->addColumn('date_start', 'date');
        $table3->addColumn('date_end', 'date');
        $table3->addColumn('variation', 'float');
        $table3->addColumn('category', 'string');
        $table3->addColumn('monthly_goal_id', 'integer');

        $table4 = $schema->createTable('user');
        $table4->addColumn('id', 'integer');
        $table4->addColumn('email', 'string');
        $table4->addColumn('name', 'string');
        $table4->addColumn('facebook_user_id', 'string');

        $table5 = $schema->createTable('period_goal');
        $table5->addColumn('id', 'integer');
        $table5->addColumn('name', 'string');
        $table5->addColumn('date_start', 'date');
        $table5->addColumn('date_end', 'date');
        $table5->addColumn('amount_goal', 'float');
        $table5->addColumn('user_id', 'integer');

        $table6 = $schema->createTable('period_event');
        $table6->addColumn('id', 'integer');
        $table6->addColumn('name', 'string');
        $table6->addColumn('date_start', 'date');
        $table6->addColumn('date_end', 'date');
        $table6->addColumn('variation', 'float');
        $table6->addColumn('category', 'string');
        $table6->addColumn('period_goal_id', 'integer');

        $sqls = $schema->toSql($this->conn->getDatabasePlatform());
        foreach ($sqls as $sql) {
            $this->conn->executeQuery($sql);
        }
    }

    /**
     * @param $events
     * @param $monthly_goal_id
     */
    private function saveEvents($events, $monthly_goal_id)
    {
        foreach ($events as $event_data) {
            $event_data['monthly_goal_id'] = $monthly_goal_id;
            if ($event_data['id'] == null) {
                $event_data['id'] = $this->id_count * rand(1, 500);
                $this->conn->insert('event', $event_data);
            } else {
                $this->conn->update('event', $event_data, array('id' => $event_data['id']));
            }
        }
    }

    /**
     * @param $events
     * @param $period_goal_id
     */
    private function savePeriodEvents($events, $period_goal_id)
    {
        foreach ($events as $event_data) {
            $event_data['period_goal_id'] = $period_goal_id;
            if ($event_data['id'] == null) {
                $event_data['id'] = $this->id_count * rand(1, 500);
                $this->conn->insert('period_event', $event_data);
            } else {
                $this->conn->update('period_event', $event_data, array('id' => $event_data['id']));
            }
        }
    }

    /**
     * @param $purchase_id
     * @return bool
     */
    public function deletePurchase($purchase_id)
    {
        return $this->conn->delete('purchase', ['id' => $purchase_id]) > 0;
    }

    /**
     * @return \shina\controlmybudget\MonthlyGoal[]
     */
    public function findAllMonthlyGoals($user_id, $page = 1, $page_size = null)
    {
        $query = $this->conn->createQueryBuilder();
        $query->select('*')
            ->from('monthly_goal', 'mg')
            ->where('mg.user_id = :user_id');

        if ($page_size !== null) {
            $query->setMaxResults($page_size)
                ->setFirstResult(($page - 1) * $page_size);
        }

        $data = $this->conn->executeQuery($query, ['user_id' => $user_id])->fetchAll();

        foreach ($data as &$monthly_goal_data) {
            $events_data = $this->conn->executeQuery(
                'SELECT * FROM event WHERE monthly_goal_id = ?',
                array($monthly_goal_data['id'])
            )
                ->fetchAll();
            $monthly_goal_data['events'] = $events_data;
        }

        return $data;
    }

    /**
     * @param int $monthly_goal_id
     * @return bool
     */
    public function deleteMonthlyGoal($monthly_goal_id)
    {
        return $this->conn->delete('monthly_goal', ['id' => $monthly_goal_id]) > 0;
    }

    /**
     * @param int $purchase_id
     * @return array
     */
    public function findPurchaseById($purchase_id)
    {
        $data = $this->conn->executeQuery(
            'SELECT * FROM purchase WHERE id=?',
            array(
                $purchase_id
            )
        )->fetch();

        return $data;
    }

    /**
     * @param int $user_id
     * @return array
     */
    public function findUserById($user_id)
    {
        return $this->conn->executeQuery('select * from user where id=?', [$user_id])->fetch();
    }

    /**
     * @param string $email
     * @return array
     */
    public function findUserByEmail($email)
    {
        return $this->conn->executeQuery('select * from user where email=?', [$email])->fetch();
    }

    /**
     * @param int $page
     * @param int | null $page_size
     * @return array
     */
    public function findAllUsers($page = 1, $page_size = null)
    {
        $query = $this->conn->createQueryBuilder();
        $query->select('*')
            ->from('user', 'u');

        if ($page_size != null) {
            $query->setMaxResults($page_size)
                ->setFirstResult(($page * $page_size) - 1);
        }

        return $this->conn->executeQuery(
            $query
        )->fetchAll();
    }

    /**
     * @param array $data
     * @return int
     */
    public function insertUser($data)
    {
        $data['id'] = $this->id_count;
        $this->conn->insert('user', $data);
        $this->id_count++;

        return $this->conn->lastInsertId();
    }

    /**
     * @param int $id
     * @param array $data
     */
    public function updateUser($id, $data)
    {
        $this->conn->update('user', $data, ['id' => $id]);
    }

    /**
     * @param int $user_id
     * @return int
     */
    public function deleteUser($user_id)
    {
        return $this->conn->delete('user', ['id'=> $user_id]);
    }

    /**
     * @param string $facebook_user_id
     * @return array
     */
    public function findUserByFacebookId($facebook_user_id)
    {
        return $this->conn->executeQuery('select * from user where facebook_user_id=?', [$facebook_user_id])
            ->fetch();
    }

    /**
     * @param array $data
     * @return int
     */
    public function insertPeriodGoal($data)
    {
        $events = $data['events'];
        unset($data['events']);

        $data['id'] = $this->id_count;
        $this->conn->insert('period_goal', $data);
        $monthly_goal_id = $this->conn->lastInsertId();
        $this->id_count++;

        $this->savePeriodEvents($events, $monthly_goal_id);

        return $monthly_goal_id;
    }

    /**
     * @param int $id
     * @param array $data
     */
    public function updatePeriodGoal($id, $data)
    {
        $events = $data['events'];
        unset($data['events']);

        $this->conn->update('period_goal', $data, array('id' => $data['id']));

        $this->savePeriodEvents($events, $data['id']);
    }

    /**
     * @param int[] $ids
     * @return \shina\controlmybudget\PeriodGoal[]
     */
    public function findPeriodGoalByIds($ids)
    {
        $query = $this->conn->createQueryBuilder()
            ->select('*')
            ->from('period_goal', 'mg')
            ->where('mg.id IN (?)');
        $data = $this->conn->executeQuery(
            $query,
            array(
                $ids
            ),
            array(
                \Doctrine\DBAL\Connection::PARAM_INT_ARRAY
            )
        )->fetchAll();

        foreach ($data as &$period_goal_data) {
            $events_data = $this->conn->executeQuery(
                'SELECT * FROM period_event WHERE period_goal_id = ?',
                array($period_goal_data['id'])
            )
                ->fetchAll();
            $period_goal_data['events'] = $events_data;
        }

        return $data;
    }

    /**
     * @param \ebussola\common\datatype\datetime\Date $date_start
     * @param \ebussola\common\datatype\datetime\Date $date_end
     * @param int $user_id
     * @return \shina\controlmybudget\PeriodGoal[]
     */
    public function findPeriodGoalsByPeriod($date_start, $date_end, $user_id)
    {
        $query = $this->conn->createQueryBuilder()
            ->select('*')
            ->from('period_goal', 'mg')
            ->where('mg.date_start <= :date_end')
            ->andWhere('mg.date_end >= :date_start')
            ->andWhere('mg.user_id = :user_id');
        $data = $this->conn->executeQuery(
            $query,
            array(
                'date_start' => $date_start->format('Y-m-d'),
                'date_end' => $date_end->format('Y-m-d'),
                'user_id' => $user_id
            )
        )->fetchAll();

        foreach ($data as &$period_goal_data) {
            $events_data = $this->conn->executeQuery(
                'SELECT * FROM period_event WHERE period_goal_id = ?',
                array($period_goal_data['id'])
            )
                ->fetchAll();
            $period_goal_data['events'] = $events_data;
        }

        return $data;
    }

    /**
     * @param int $user_id
     * @param int $page
     * @param int $page_size
     * @return \shina\controlmybudget\PeriodGoal[]
     */
    public function findAllPeriodGoals($user_id, $page, $page_size)
    {
        $query = $this->conn->createQueryBuilder();
        $query->select('*')
            ->from('period_goal', 'mg')
            ->where('mg.user_id = :user_id');

        if ($page_size !== null) {
            $query->setMaxResults($page_size)
                ->setFirstResult(($page - 1) * $page_size);
        }

        $data = $this->conn->executeQuery($query, ['user_id' => $user_id])->fetchAll();

        foreach ($data as &$monthly_goal_data) {
            $events_data = $this->conn->executeQuery(
                'SELECT * FROM period_event WHERE period_goal_id = ?',
                array($monthly_goal_data['id'])
            )
                ->fetchAll();
            $monthly_goal_data['events'] = $events_data;
        }

        return $data;
    }

    /**
     * @param int $period_goal_id
     * @return int
     */
    public function deletePeriodGoal($period_goal_id)
    {
        return $this->conn->delete('period_goal', ['id' => $period_goal_id]);
    }
}