<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 07/02/14
 * Time: 16:39
 */

namespace shina\controlmybudget\ImportHandler;


use Fetch\Message;
use shina\controlmybudget\Purchase;
use shina\controlmybudget\PurchaseService;

abstract class MailImportAbstract {

    /**
     * @var \Fetch\Server
     */
    private $imap;

    /**
     * @var PurchaseService
     */
    private $purchase_service;

    public function __construct(\Fetch\Server $imap, PurchaseService $purchase_service) {
        $this->imap = $imap;
        $this->purchase_service = $purchase_service;
    }

    /**
     * @param int|null $limit
     */
    public function import($limit=3) {
        // making the work of Fetch package
        $messages = imap_sort($this->imap->getImapStream(), SORTARRIVAL, 1, SE_UID, 'FROM "itaucard@itau-unibanco.com.br" SUBJECT "realizadas"');
        if ($limit != null) {
            $messages = array_slice($messages, 0, $limit);
        }
        foreach ($messages as &$message) {
            $message = new Message($message, $this->imap);
        }

        foreach ($messages as $message) {
            $data = $this->parseData($message);

            foreach ($data as $purchase) {

                try {
                    $this->purchase_service->save($purchase);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
    }

    /**
     * Make the first import
     */
    public function firstImport() {
        $this->import(null);
    }

    /**
     * @param Message $message
     *
     * @return Purchase[]
     */
    abstract protected function parseData(Message $message);

}