<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 07/02/14
 * Time: 16:39
 */

namespace shina\controlmybudget\ImportHandler;


use ebussola\common\datatype\datetime\Date;
use ebussola\common\datatype\number\Currency;
use Fetch\Message;
use shina\controlmybudget\Importer;
use shina\controlmybudget\Purchase;
use shina\controlmybudget\PurchaseService;
use shina\controlmybudget\User;

class MailItauPaymentImport extends MailImportAbstract implements Importer
{

    protected $from;

    public function __construct(\Fetch\Server $imap, PurchaseService $purchase_service, $from = null)
    {
        if ($from === null) {
            $from = 'comunicacaodigital@itau-unibanco.com.br';
        }

        $this->from = $from;

        parent::__construct($imap, $purchase_service);
    }

    /**
     * @param Message $message
     *
     * @return Purchase[]
     */
    protected function parseData(Message $message)
    {
        $dom = new \DOMDocument();
        $dom->loadHTML($message->getMessageBody(true));
        $nodes = $dom->getElementsByTagName('p');

        $purchase = new Purchase\Purchase();

        $purchase->date = new Date();
        $purchase->place = 'Pagamento';

        $matches = [];
        preg_match('/no valor de R\$ (.*)/', $nodes->item(2)->nodeValue, $matches);
        $purchase->amount = (new Currency($matches[1]))->getValue();

        return [$purchase];
    }

    /**
     * @return string
     */
    protected function getImapSearch(User $user)
    {
        return 'FROM "' . $this->from . '" TO "'.$user->email.'" SUBJECT "Pagamento realizado"';
    }

}