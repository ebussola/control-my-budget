<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 10/02/14
 * Time: 14:50
 */

namespace shina\controlmybudget;


class PurchaseService
{

    /**
     * @var DataProvider
     */
    private $data_provider;

    public function __construct(DataProvider $data_provider)
    {
        $this->data_provider = $data_provider;
    }

    /**
     * @param Purchase $purchase
     */
    public function save(Purchase $purchase)
    {
        $data = $this->toArray($purchase);

        $hash = md5(join('.', $data));
        if (!$this->data_provider->findPurchaseByHash($hash)) {

            $data['hash'] = $hash;
            if ($purchase->id == null) {
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
     *
     * @return Purchase[]
     */
    public function getPurchasesByPeriod(\DateTime $date_start, \DateTime $date_end)
    {
        $data = $this->data_provider->findPurchasesByPeriod($date_start, $date_end);

        $purchases = array();
        foreach ($data as $row) {
            $purchases[] = $this->createPurchase($row);
        }

        return $purchases;
    }

    /**
     * @param \DateTime $date_start
     * @param \DateTime $date_end
     * @return float
     */
    public function getAmountByPeriod(\DateTime $date_start, \DateTime $date_end)
    {
        return (float)$this->data_provider->calcAmountByPeriod($date_start, $date_end);
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
    private function toArray(Purchase $purchase)
    {
        return array(
            'id' => $purchase->id,
            'date' => $purchase->date->format('Y-m-d'),
            'place' => $purchase->place,
            'amount' => $purchase->amount
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

}