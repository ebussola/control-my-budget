<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 10/02/14
 * Time: 10:12
 */

namespace shina\controlmybudget;


interface DataProvider {

    /**
     * @param array $data
     *
     * @return int
     */
    public function insertPurchase(array $data);

    /**
     * @param int   $id
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
     * @param array $data
     *
     * @return int
     * ID of the added object
     */
    public function insertMonthlyGoal(array $data);

    /**
     * @param int   $id
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

}