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

class MailItauWithdrawImport extends MailImportAbstract implements Importer
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

        $matches = [];
        preg_match(
            '/((?:(?:[0-2]?\\d{1})|(?:[3][01]{1}))[-:\\/.](?:[0]?[1-9]|[1][012])[-:\\/.](?:(?:[1]{1}\\d{1}\\d{1}\\d{1})|(?:[2]{1}\\d{3})))(?![\\d])/',
            $nodes->item(2)->nodeValue,
            $matches
        );
        $purchase->date = new Date(join('-', array_reverse(explode('/', $matches[1]))));

        preg_match('/local: (.*?),/', str_replace('.', ',', str_replace("\n", ' ', $nodes->item(2)->nodeValue)), $matches);
        $purchase->place = trim($matches[1]);

        preg_match('/R\$ (.*), no dia/', $nodes->item(2)->nodeValue, $matches);
        $purchase->amount = (new Currency($matches[1]))->getValue();

        return [$purchase];
    }

    /**
     * @return string
     */
    protected function getImapSearch(User $user)
    {
        return 'FROM "' . $this->from . '" TO "'.$user->email.'" SUBJECT "Saque realizado"';
    }

}