<?php
use shina\controlmybudget\ImportHandler\MailItauDebitImport;

/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 07/02/14
 * Time: 16:38
 */

class MailItauUniclassDebitImportTest extends MailImportAbstract {

    protected function makeImporter($imap, $purchase_service)
    {
        $config = include 'config.php';

        return new MailItauDebitImport($imap, $purchase_service, $config['sender']['email']);
    }

    /**
     * @param \ebussola\common\datatype\datetime\Date $date
     * @param $place
     * @param $amount
     * @return Swift_Message
     */
    protected function makeMessage(\ebussola\common\datatype\datetime\Date $date, $place, $amount)
    {
        $body = file_get_contents(__DIR__ . '/stubs/itau_uniclass_debt_message.mock');
        $body = str_replace('{{date}}', $date->format('d/m/Y'), $body);
        $body = str_replace('{{place}}', $place, $body);
        $body = str_replace('{{amount}}', 'R$ '.$amount, $body);

        $message = new Swift_Message(
            'Compra com cartão débito aprovada',
            $body,
            'text/html',
            'utf-8'
        );

        return $message;
    }

}