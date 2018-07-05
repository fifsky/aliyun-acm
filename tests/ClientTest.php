<?php
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 2018/7/4 3:43 PM
 */

namespace Very\Tests\Support;

use Aliyun\ACM\RequestException;
use PHPUnit\Framework\TestCase;
use Aliyun\ACM\Client;

class ClientTest extends TestCase
{

    /**
     * @return \Aliyun\ACM\Client
     */
    private function getClient()
    {
        return new Client([
            "accessKey" => getenv("AccessKey"),
            "secretKey" => getenv("SecretKey"),
            "endPoint"  => "acm.aliyun.com",
            "nameSpace" => getenv("NameSpace"),
            "timeOut"   => 30,
        ]);
    }

    public function testNewClient()
    {
        $client = $this->getClient();
        $this->assertTrue(count($client->getServers()) > 0);
    }

    private function withTest($dateId, $fn)
    {
        $client = $this->getClient();
        $ret    = $client->publish($dateId, "test", "test");
        $fn($client);
        $client->delete($dateId, "test");
    }

    public function testGetConfig()
    {
        $this->withTest("test1",function ($client) {
            $ret = $client->getConfig("test1", "test");
            $this->assertEquals($ret, "test");
        });
    }

    public function testSubscribe()
    {
        $this->withTest("test2",function ($client) {
            $client->subscribe("test2", "test");
        });
    }
}