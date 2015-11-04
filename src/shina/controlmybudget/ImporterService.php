<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 07/06/14
 * Time: 12:50
 */

namespace shina\controlmybudget;


class ImporterService
{

    /**
     * @var Importer[]
     */
    public $importers;

    public function __construct($importers = [])
    {
        $this->importers = $importers;
    }

    /**
     * @param Importer $importer
     */
    public function addImporter(Importer $importer)
    {
        $this->importers[] = $importer;
    }

    /**
     * @param int $limit
     */
    public function import($limit = 3, $user)
    {
        foreach ($this->importers as $import) {
            $import->import($limit, $user);
        }
    }

}