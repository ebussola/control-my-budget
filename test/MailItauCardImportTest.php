<?php
use ebussola\common\datatype\datetime\Date;
use shina\controlmybudget\ImportHandler\MailItauCardImport;

/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 07/02/14
 * Time: 16:38
 */

class MailItauCardImportTest extends MailImportAbstract {

    protected function makeImporter($imap, $purchase_service)
    {
        $config = include 'config.php';

        return new MailItauCardImport($imap, $purchase_service, $config['sender']['email']);
    }

    protected function makeMessage(Date $date, $place, $amount)
    {
        $body = file_get_contents(__DIR__ . '/stubs/itaucard_message.mock');
        $body = str_replace('{{date}}', $date->format('d-m-Y'), $body);
        $body = str_replace('{{place}}', $place, $body);
        $body = str_replace('{{amount}}', 'R$ '.$amount, $body);

        $message = new Swift_Message(
            'Últimas transações realizadas com o cartão',
            $body,
            'text/html',
            'utf-8'
        );

        return $message;
    }

}