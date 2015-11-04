<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 10/02/14
 * Time: 14:50
 */

namespace shina\controlmybudget;


use ebussola\common\datatype\datetime\Date;

class PurchaseService
{

    /**
     * @var DataProvider
     */
    private $data_provider;

    /**
     * @var Date
     */
    private $current_date;

    public function __construct(DataProvider $data_provider, Date $current_date = null)
    {
        if ($current_date === null) {
            $current_date = new Date('today');
        }

        $this->data_provider = $data_provider;
        $this->current_date = $current_date;
    }

    /**
     * @param Purchase $purchase
     * @param User $user
     */
    public function save(Purchase $purchase, $user)
    {
        $data = $this->toArray($purchase, $user);

        $hash = $this->hash($data, $user);
        if (!$this->data_provider->findPurchaseByHash($hash)) {

            if ($purchase->date > $this->current_date) {
                $data['is_forecast'] = 1;
            } else {
                $data['is_forecast'] = 0;
            }

            if ($purchase->id == null) {
                $data['hash'] = $hash;
                $id = $this->data_provider->insertPurchase($data);
                $purchase->id = $id;
            } else {
                $this->data_provider->updatePurchase($purchase->id, $data);
            }

        } else {
            throw new \Exception('Purchase already registered');
        }
    }

    /**
     * @param int $purchase_id
     * @return Purchase
     */
    public function getById($purchase_id)
    {
        $data = $this->data_provider->findPurchaseById($purchase_id);
        $purchase = $this->createPurchase($data);

        return $purchase;
    }

    /**
     * @param \DateTime $date_start
     * @param \DateTime $date_end
     * @param User $user
     *
     * @return Purchase[]
     */
    public function getPurchasesByPeriod(\DateTime $date_start, \DateTime $date_end, $user)
    {
        $data = $this->data_provider->findPurchasesByPeriod($date_start, $date_end, $user->id);

        $purchases = array();
        foreach ($data as $row) {
            $purchases[] = $this->createPurchase($row);
        }

        return $purchases;
    }

    /**
     * @param \DateTime $date_start
     * @param \DateTime $date_end
     * @param User $user
     * @return float
     */
    public function getAmountByPeriod(\DateTime $date_start, \DateTime $date_end, $user)
    {
        return (float)$this->data_provider->calcAmountByPeriod($date_start, $date_end, $user->id);
    }

    /**
     * @param \DateTime $date_start
     * @param \DateTime $date_end
     * @param User $user
     * @return float
     */
    public function getForecastAmountByPeriod(\DateTime $date_start, \DateTime $date_end, $user)
    {
        return (float)$this->data_provider->calcAmountByPeriod($date_start, $date_end, $user->id, true);
    }

    /**
     * @param string $purchase_id
     * @return bool
     */
    public function delete($purchase_id)
    {
        return $this->data_provider->deletePurchase($purchase_id);
    }

    /**
     * @param Purchase $purchase
     *
     * @return array
     */
    private function toArray(Purchase $purchase, $user)
    {
        return array(
            'id' => $purchase->id,
            'date' => $purchase->date->format('Y-m-d'),
            'place' => $purchase->place,
            'amount' => $purchase->amount,
            'user_id' => $user->id
        );
    }

    /**
     * @param array $row
     *
     * @return Purchase
     */
    private function createPurchase($row)
    {
        $purchase = new Purchase\Purchase();
        $purchase->id = $row['id'];
        $purchase->date = new \DateTime($row['date']);
        $purchase->place = $row['place'];
        $purchase->amount = (float)$row['amount'];

        return $purchase;
    }

    /**
     * @param $data
     * @param User $user
     * @return string
     */
    protected function hash($data, $user)
    {
        return md5(
            join('.', [$data['date'], $data['place'], $data['amount'], $user->id])
        );
    }

}