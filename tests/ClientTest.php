<?php
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 2018/7/4 3:43 PM
 */

namespace Very\Tests\Support;

use PHPUnit\Framework\TestCase;
use Aliyun\ACM\Client;

class ClientTest extends TestCase
{
    public function testNewClient()
    {
        $client = new Client([
            "accessKey" => "********",
            "secretKey" => "********",
            "endPoint"  => "acm.aliyun.com",
            "nameSpace" => "test",
            "timeOut"   => 30,
        ]);

        $this->assertTrue(count($client->getServers()) >0);
    }
}