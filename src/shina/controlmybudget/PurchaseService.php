<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 10/02/14
 * Time: 14:50
 */

namespace shina\controlmybudget;


class PurchaseService {

    /**
     * @var DataProvider
     */
    private $data_provider;

    public function __construct(DataProvider $data_provider) {
        $this->data_provider = $data_provider;
    }

    /**
     * @param Purchase $purchase
     */
    public function save(Purchase $purchase) {
        $data = $this->toArray($purchase);

        if ($purchase->id == null) {
            $id = $this->data_provider->insertPurchase($data);
            $purchase->id = $id;
        } else {
            $this->data_provider->updatePurchase($purchase->id, $data);
        }
    }

    /**
     * @param \DateTime $date_start
     * @param \DateTime $date_end
     *
     * @return Purchase[]
     */
    public function getPurchasesByPeriod(\DateTime $date_start, \DateTime $date_end) {
        $data = $this->data_provider->findPurchasesByPeriod($date_start, $date_end);

        $purchases = array();
        foreach ($data as $row) {
            $purchases[] = $this->createPurchase($row);
        }

        return $purchases;
    }

    public function getAmountByPeriod(\DateTime $date_start, \DateTime $date_end) {
        return (float) $this->data_provider->calcAmountByPeriod($date_start, $date_end);
    }

    /**
     * @param Purchase $purchase
     *
     * @return array
     */
    private function toArray(Purchase $purchase) {
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
    private function createPurchase($row) {
        $purchase = new Purchase\Purchase();
        $purchase->id = $row['id'];
        $purchase->date = new \DateTime($row['date']);
        $purchase->place = $row['place'];
        $purchase->amount = (float) $row['amount'];

        return $purchase;
    }

}