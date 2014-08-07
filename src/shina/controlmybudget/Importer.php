<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 07/06/14
 * Time: 12:48
 */

namespace shina\controlmybudget;


interface Importer
{

    /**
     * @param int|null $limit
     * @param User $user
     */
    public function import($limit = 3, User $user);

    /**
     * Make the first import
     *
     * @param User $user
     */
    public function firstImport(User $user);

}