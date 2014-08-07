<?php
use shina\controlmybudget\ImportHandler\MailItauPaymentImport;

/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 07/02/14
 * Time: 16:38
 */

class MailItauPaymentImportTest extends MailImportAbstract {

    protected function makeImporter($imap, $purchase_service)
    {
        $config = include 'config.php';

        return new MailItauPaymentImport($imap, $purchase_service, $config['sender']['email']);
    }

    /**
     * @param \ebussola\common\datatype\datetime\Date $date
     * @param $place
     * @param $amount
     * @return Swift_Message
     */
    protected function makeMessage(\ebussola\common\datatype\datetime\Date $date, $place, $amount)
    {
        $body = file_get_contents(__DIR__ . '/stubs/itau_payment_message.mock');
        $body = str_replace('{{place}}', $place, $body);
        $body = str_replace('{{amount}}', 'R$ '.$amount, $body);

        $message = new Swift_Message(
            'Pagamento realizado',
            $body,
            'text/html',
            'utf-8'
        );

        return $message;
    }

}