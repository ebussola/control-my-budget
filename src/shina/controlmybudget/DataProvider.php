<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 10/02/14
 * Time: 10:12
 */

namespace shina\controlmybudget;


interface DataProvider
{

    /**
     * @param array $data
     *
     * @return int
     */
    public function insertPurchase(array $data);

    /**
     * @param int $id
     * @param array $data
     *
     * @return bool
     */
    public function updatePurchase($id, array $data);

    /**
     * @param array $data
     *
     * @return int
     */
    public function savePurchase(array $data);

    /**
     * @param \DateTime $date_start
     * @param \DateTime $date_end
     *
     * @return array
     */
    public function findPurchasesByPeriod(\DateTime $date_start, \DateTime $date_end);

    /**
     * @param string $hash
     *
     * @return array
     */
    public function findPurchaseByHash($hash);

    /**
     * @param $purchase_id
     * @return bool
     */
    public function deletePurchase($purchase_id);

    /**
     * @param int $purchase_id
     * @return array
     */
    public function findPurchaseById($purchase_id);

    /**
     * @param array $data
     *
     * @return int
     * ID of the added object
     */
    public function insertMonthlyGoal(array $data);

    /**
     * @param int $id
     * @param array $data
     *
     * @return bool
     */
    public function updateMonthlyGoal($id, array $data);

    /**
     * @param int $month
     * @param int $year
     *
     * @return MonthlyGoal[]
     */
    public function findMonthlyGoalsByMonthAndYear($month, $year);

    /**
     * @return array
     */
    public function findAllMonthlyGoals($page = 1, $page_size = null);

    /**
     * @param \DateTime $date_start
     * @param \DateTime $date_end
     * @param boolean   $only_forecast
     *
     * @return float
     */
    public function calcAmountByPeriod(\DateTime $date_start, \DateTime $date_end, $only_forecast=false);

    /**
     * @param int[] $monthly_goal_ids
     *
     * @return MonthlyGoal[]
     */
    public function findMonthlyGoalByIds($monthly_goal_ids);

    /**
     * @param int $monthly_goal_id
     * @return bool
     */
    public function deleteMonthlyGoal($monthly_goal_id);

}