<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 07/02/14
 * Time: 16:39
 */

namespace shina\controlmybudget\ImportHandler;


use Fetch\Message;
use shina\controlmybudget\Importer;
use shina\controlmybudget\Purchase;
use shina\controlmybudget\PurchaseService;

class MailImport implements Importer {

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
    private function parseData(Message $message) {
        $dom = new \DOMDocument();
        $dom->loadHTML($message->getMessageBody(true));
        $nodes = $dom->getElementsByTagName('tr');

        $data = array();
        for ($i=5 ; $i<=$nodes->length ; $i++) {
            if (strstr($nodes->item($i)->nodeValue, 'R$')) {
                $date = $nodes->item($i)->childNodes->item(0)->nodeValue;
                $date = new \DateTime(trim($date));

                $place = $nodes->item($i)->childNodes->item(1)->nodeValue;
                $amount = $nodes->item($i)->childNodes->item(2)->nodeValue;

                $purchase = new \shina\controlmybudget\Purchase\Purchase();
                $purchase->date = $date;
                $purchase->place = trim($place);
                $purchase->amount = (float) str_replace('R$', '', str_replace(',', '.', str_replace('.', '', trim($amount))));

                $data[] = $purchase;
            } else {
                break;
            }
        }

        return $data;
    }

}