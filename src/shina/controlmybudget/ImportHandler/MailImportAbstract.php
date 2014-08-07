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
use shina\controlmybudget\User;

abstract class MailImportAbstract
{

    /**
     * @var \Fetch\Server
     */
    private $imap;

    /**
     * @var PurchaseService
     */
    private $purchase_service;

    public function __construct(\Fetch\Server $imap, PurchaseService $purchase_service)
    {
        $this->imap = $imap;
        $this->purchase_service = $purchase_service;
    }

    /**
     * @param int|null $limit
     * @param User $user
     */
    public function import($limit = 3, User $user)
    {
        // making the work of Fetch package
        $messages = imap_sort($this->imap->getImapStream(), SORTARRIVAL, 1, SE_UID, $this->getImapSearch($user));
        if ($limit != null) {
            $messages = array_slice($messages, 0, $limit);
        }
        foreach ($messages as &$message) {
            $message = new Message($message, $this->imap);
        }
        unset($message);

        foreach ($messages as $message) {
            $data = $this->parseData($message);

            foreach ($data as $purchase) {

                try {
                    $this->purchase_service->save($purchase, $user);
                } catch (\Exception $e) {
                    continue;
                }
            }
        }
    }

    /**
     * Make the first import
     *
     * @param User $user
     */
    public function firstImport(User $user)
    {
        $this->import(null, $user);
    }

    /**
     * @param Message $message
     *
     * @return Purchase[]
     */
    abstract protected function parseData(Message $message);

    /**
     * @param User $user
     * @return string
     */
    abstract protected function getImapSearch(User $user);

}