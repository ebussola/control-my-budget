<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 07/02/14
 * Time: 16:39
 */

namespace shina\controlmybudget;


use Fetch\Message;

class MailImporterService {

    /**
     * @var \Fetch\Server
     */
    private $imap;

    /**
     * @var DataProvider
     */
    private $data_provider;

    public function __construct(\Fetch\Server $imap, DataProvider $data_provider) {
        $this->imap = $imap;
        $this->data_provider = $data_provider;
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

            foreach ($data as $row) {
                $this->data_provider->save($row);
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
     * @return array
     */
    private function parseData(Message $message) {
        $dom = new \DOMDocument();
        $dom->loadHTML($message->getMessageBody(true));
        $nodes = $dom->getElementsByTagName('tr');

        $data = array();
        for ($i=5 ; $i<=$nodes->length ; $i++) {
            if (strstr($nodes->item($i)->nodeValue, 'R$')) {
                $date = $nodes->item($i)->childNodes->item(0)->nodeValue;
                $place = $nodes->item($i)->childNodes->item(1)->nodeValue;
                $amount = $nodes->item($i)->childNodes->item(2)->nodeValue;

                $data[] = array(
                    'date' => trim($date),
                    'place' => trim($place),
                    'amount' => (float) str_replace('R$', '', str_replace(',', '.', str_replace('.', '', trim($amount))))
                );
            } else {
                break;
            }
        }

        return $data;
    }

}