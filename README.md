# Aliyun-ACM SDK for PHP

<a href="https://travis-ci.org/verystar/aliyun-acm"><img src="https://api.travis-ci.org/verystar/aliyun-acm.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/verystar/aliyun-acm"><img src="https://poser.pugx.org/verystar/aliyun-acm/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/verystar/aliyun-acm"><img src="https://poser.pugx.org/verystar/aliyun-acm/v/stable.svg" alt="Latest Stable Version"></a>

Aliyun ACM SDK for PHP, Multi-ip polling and local file cache is supported

## Install

```
composer require verystar/aliyun-acm
```

Or add a dependency to the composer.json

```
"require": {
    "verystar/aliyun-acm": "1.0.*"
}
```

Run
```
composer update
```

## Usage

```php
use Aliyun\ACM\Client;

$client = new Client([
    "accessKey"=>"***********",
    "secretKey"=>"***********",
    "endPoint"=>"acm.aliyun.com",
    "nameSpace"=>"***********",
    "timeOut"=>30, //long pull timeout default 30s
]);


//get config
$ret = $client->getConfig("test","DEFAULT_GROUP");
print_r($ret);

//subscribe 
$ret = $client->subscribe("test","DEFAULT_GROUP");
print_r($ret);

//pulish
$ret = $client->publish("test","DEFAULT_GROUP","config content");
print_r($ret);


//remove config
$ret = $client->delete("test","DEFAULT_GROUP");
print_r($ret);

//get all config by tenant
$ret = $client->getAllConfigs(1,1);
print_r($ret);
```

## Exception
If the API request fails, an throw exception is RequestException

```php
use Aliyun\ACM\RequestException;

try{
    $ret = $client->getConfig("test","DEFAULT_GROUP");
    print_r($ret);    
}catch (RequestException $e){
    print_r($e);   
}
```

## License
The SDK is open-sourced software licensed under the MIT license.