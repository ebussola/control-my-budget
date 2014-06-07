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

class MailItauCardImport extends MailImportAbstract implements Importer {

    /**
     * @param Message $message
     *
     * @return Purchase[]
     */
    protected function parseData(Message $message) {
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

    /**
     * @return string
     */
    protected function getImapSearch()
    {
        return 'FROM "itaucard@itau-unibanco.com.br" SUBJECT "realizadas"';
    }

}