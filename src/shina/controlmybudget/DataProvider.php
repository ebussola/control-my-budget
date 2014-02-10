<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 10/02/14
 * Time: 10:12
 */

namespace shina\controlmybudget;


interface DataProvider {

    /**
     * @param array $data
     *
     * @return int
     */
    public function insert(array $data);

    /**
     * @param int   $id
     * @param array $data
     *
     * @return bool
     */
    public function update($id, array $data);

    /**
     * @param array $data
     *
     * @return int
     */
    public function save(array $data);

}