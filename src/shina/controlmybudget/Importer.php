<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 07/06/14
 * Time: 12:48
 */

namespace shina\controlmybudget;


interface Importer {

    /**
     * @param int|null $limit
     */
    public function import($limit=3);

    /**
     * Make the first import
     */
    public function firstImport();

}