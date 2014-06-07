<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 07/02/14
 * Time: 16:38
 */

class MailItauDebitImportTest extends MailImportAbstract {

    protected function makeImporter($imap, $purchase_service)
    {
        return new \shina\controlmybudget\ImportHandler\MailItauDebitImport($imap, $purchase_service);
    }

}