<?php
/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 04/08/14
 * Time: 18:07
 */

namespace shina\controlmybudget;


class UserService {

    /**
     * @var DataProvider
     */
    protected $data_provider;

    public function __construct(DataProvider $data_provider)
    {
        $this->data_provider = $data_provider;
    }

    /**
     * @param int $user_id
     * @return User
     */
    public function getById($user_id)
    {
        $data = $this->data_provider->findUserById($user_id);
        $user = null;

        if ($data != null) {
            $user = $this->createUser($data);
        }

        return $user;
    }

    /**
     * @param string $email
     * @return User
     */
    public function getByEmail($email)
    {
        $data = $this->data_provider->findUserByEmail($email);
        $user = null;

        if ($data != null) {
            $user = $this->createUser($data);
        }

        return $user;
    }

    /**
     * @param string $access_token
     * @return User
     */
    public function getByAccessToken($access_token)
    {
        $data = $this->data_provider->findUserByAccessToken($access_token);
        $user = null;

        if ($data != null) {
            $user = $this->createUser($data);
        }

        return $user;
    }

    /**
     * @param int $page
     * @param null|int $page_size
     * @return User[]
     */
    public function getAll($page=1, $page_size=null)
    {
        $data = $this->data_provider->findAllUsers($page, $page_size);
        $users = [];
        foreach ($data as $user_data) {
            $users[] = $this->createUser($user_data);
        }

        return $users;
    }

    /**
     * @param User $user
     * @return int
     */
    public function save(User $user)
    {
        if ($user->id === null) {
            $user->id = $this->data_provider->insertUser($this->toArray($user));
        } else {
            $this->data_provider->updateUser($user->id, $this->toArray($user));
        }

        return $user->id;
    }

    /**
     * @param int $user_id
     * @return bool
     */
    public function delete($user_id)
    {
        return $this->data_provider->deleteUser($user_id) > 0;
    }

    /**
     * @param User $user
     * @return bool
     */
    public function validateToken(User $user)
    {
        return $user->facebook_access_token['expires'] > time();
    }

    /**
     * @param array $data
     * @return User
     */
    private function createUser($data)
    {
        $user = new User();
        $user->id = $data['id'];
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->facebook_access_token = unserialize($data['facebook_access_token']);

        return $user;
    }

    /**
     * @param User $user
     * @return array
     */
    private function toArray(User $user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'facebook_access_token' => serialize($user->facebook_access_token)
        ];
    }

} 