<?php

/**
 * Created by PhpStorm.
 * User: Leonardo Shinagawa
 * Date: 05/08/14
 * Time: 13:48
 */
class UserServiceTest extends PHPUnit_Framework_TestCase
{

    /**
     * @var \shina\controlmybudget\UserService
     */
    protected $user_service;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $conn;

    /**
     * @var \Guzzle\Http\ClientInterface
     */
    protected $http;

    public function setup()
    {
        $this->conn = \Doctrine\DBAL\DriverManager::getConnection(
            array(
                'driver' => 'pdo_sqlite',
                'user' => 'root',
                'password' => 'root',
                'memory' => true
            )
        );
        $data_provider = new DataProviderDoctrine($this->conn);
        $this->http = new \Guzzle\Http\Client();
        $this->user_service = new \shina\controlmybudget\UserService($data_provider, $this->http);
    }

    public function testSave()
    {
        $user = $this->createUser();

        $this->user_service->save($user);
        $this->assertNotNull($user->id);

        $data = $this->conn->executeQuery('select * from user')->fetch();
        $this->assertEquals($data['id'], $user->id);
        $this->assertEquals($data['name'], $user->name);
        $this->assertEquals($data['email'], $user->email);
        $this->assertEquals($data['facebook_user_id'], $user->facebook_user_id);

        // UPDATE
        $user->name = 'foobar';

        $this->user_service->save($user);

        $data = $this->conn->executeQuery('select * from user')->fetchAll();
        $this->assertCount(1, $data);
        $this->assertEquals($data[0]['name'], $user->name);
    }

    public function testGetById()
    {
        $user = $this->createUser();
        $this->user_service->save($user);

        $user = $this->user_service->getById($user->id);
        $this->assertUserInstance($user);
    }

    public function testGetById_Invalid()
    {
        $user = $this->createUser();
        $this->user_service->save($user);

        $this->assertNull($this->user_service->getById(69));
    }

    public function testGetByEmail()
    {
        $user = $this->createUser();
        $this->user_service->save($user);

        $user = $this->user_service->getByEmail($user->email);
        $this->assertUserInstance($user);
    }

    public function testGetByEmail_Invalid()
    {
        $user = $this->createUser();
        $this->user_service->save($user);

        $this->assertNull($this->user_service->getByEmail('invalid@email.com'));
    }

    public function testGetByAccessToken()
    {
        $user = $this->createUser();
        $this->user_service->save($user);

        $mock_plugin = new \Guzzle\Plugin\Mock\MockPlugin();
        $mock_plugin->addResponse(
            new \Guzzle\Http\Message\Response(200, [], json_encode(
                    [
                        'id' => $user->facebook_user_id
                    ]
                )
            ));
        $this->http->addSubscriber($mock_plugin);

        $user = $this->user_service->getByAccessToken('access token simulation');
        $this->assertUserInstance($user);
    }

    public function testGetByAccessToken_Invalid()
    {
        $user = $this->createUser();
        $this->user_service->save($user);

        $mock_plugin = new \Guzzle\Plugin\Mock\MockPlugin();
        $mock_plugin->addResponse(
            new \Guzzle\Http\Message\Response(200, [], json_encode(
                    [
                        'id' => 2 // user do not exists
                    ]
                )
            ));
        $this->http->addSubscriber($mock_plugin);

        $this->assertNull($this->user_service->getByAccessToken('invalid_token'));
    }

    public function testGetAll()
    {
        $user1 = $this->createRandomUser();
        $this->user_service->save($user1);
        $user2 = $this->createRandomUser();
        $this->user_service->save($user2);
        $user3 = $this->createRandomUser();
        $this->user_service->save($user3);

        $users = $this->user_service->getAll();
        $this->assertCount(3, $users);

        foreach ($users as $user) {
            $this->assertUserInstance($user);
        }
    }

    public function testGetAll_Empty()
    {
        $users = $this->user_service->getAll();
        $this->assertCount(0, $users);
    }

    public function testDelete()
    {
        $user = $this->createUser();
        $this->user_service->save($user);

        $this->assertTrue($this->user_service->delete($user->id));
    }

    public function testDelete_Invalid()
    {
        $this->assertFalse($this->user_service->delete(0));
    }

    /**
     * @return \shina\controlmybudget\User
     */
    protected function createUser()
    {
        $user = new \shina\controlmybudget\User();
        $user->name = 'Foo';
        $user->email = 'foo@bar.com';
        $user->facebook_user_id = 'myuseridonfacebook';

        return $user;
    }

    /**
     * @param $user
     */
    protected function assertUserInstance($user)
    {
        $this->assertInstanceOf('\shina\controlmybudget\User', $user);
        $this->assertNotNull($user->id);
        $this->assertNotNull($user->name);
        $this->assertNotNull($user->email);
        $this->assertNotNull($user->facebook_user_id);
    }

    protected function createRandomUser()
    {
        $user = new \shina\controlmybudget\User();
        $user->name = md5(rand(0, 1000) * rand(0, 1000));
        $user->email = md5(rand(0, 1000) * rand(0, 1000)) . '@' . md5(rand(0, 1000) * rand(0, 1000)) . '.com';
        $user->facebook_user_id = md5(rand(0, 1000) * rand(0, 1000));

        return $user;
    }

}
 