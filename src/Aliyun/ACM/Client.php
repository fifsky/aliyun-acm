<?php
/**
 * Created by PhpStorm.
 * User: 蔡旭东 caixudong@verystar.cn
 * Date: 2018/7/3 10:28 PM
 */

namespace Aliyun\ACM;

class Client
{

    protected $localDataPath;
    protected $accessKey;
    protected $secretKey;
    protected $endPoint;
    protected $nameSpace;
    protected $timeOut  = 5;//seconds
    protected $request;
    protected $serverIp = [];

    /**
     * Client constructor.
     *
     * @param array $config
     */
    public function __construct($config = [])
    {
        $this->accessKey     = isset($config['accessKey']) ? $config['accessKey'] : "";
        $this->secretKey     = isset($config['secretKey']) ? $config['secretKey'] : "";
        $this->endPoint      = isset($config['endPoint']) ? $config['endPoint'] : "";
        $this->nameSpace     = isset($config['nameSpace']) ? $config['nameSpace'] : "";
        $this->timeOut       = isset($config['timeOut']) ? $config['timeOut'] : 30;
        $this->localDataPath = isset($config['localDataPath']) ? $config['localDataPath'] : "./data/";
        $this->request       = new Request();
        $this->initServer();
    }

    private function initServer()
    {
        $ret = $this->request->get("http://".$this->endPoint.":8080/diamond-server/diamond");

        if (!$ret->getError() && $ret->getStatusCode() == 200) {
            $servers = explode("\n", trim($ret->getBody()));
            foreach ($servers as $v) {
                $server = explode(":", $v);
                if (count($server) == 1) {
                    $server[] = "8080";
                }

                $this->serverIp[] = implode(":", $server);
            }
        }
    }

    /**
     * rand get server
     *
     * @return string
     */
    private function getServer()
    {
        return $this->serverIp[mt_rand(0, count($this->serverIp) - 1)];
    }

    public function getServers()
    {
        return $this->serverIp;
    }

    private function getSign($tenant, $group, $timeStamp)
    {
        if ($group) {
            $signStr = $tenant."+".$group."+".$timeStamp;
        } else {
            $signStr = $tenant."+".$timeStamp;
        }

        return base64_encode(hash_hmac('sha1', $signStr, $this->secretKey, true));
    }

    private function getSubscribeSign($probe)
    {
        return base64_encode(hash_hmac('sha1', $probe, $this->secretKey, true));
    }

    /**
     * @param        $api
     * @param array  $params
     *
     * @param string $method
     *
     * @return string
     * @throws \Aliyun\ACM\RequestException
     */
    private function callApi($api, $params = [], $method = "GET")
    {
        if (!$server = $this->getServer()) {
            throw new RequestException("get server ip error");
        }

        $timeStamp = round(microtime(true) * 1000);
        $header    = [
            "Content-Type:application/x-www-form-urlencoded; charset=utf-8",
            "Spas-AccessKey:".$this->accessKey,
            "timeStamp:".$timeStamp,
        ];

        if (isset($params["Probe-Modify-Request"])) {
            $header[] = "Spas-Signature:".$this->getSubscribeSign($params["Probe-Modify-Request"]);
            $header[] = "longPullingTimeout:".$this->timeOut * 1000;
            //http timeout > long pull timeout
            $this->request->setTimeout($this->timeOut + 30);
        } else {
            $params["tenant"] = $this->nameSpace;
            $group            = isset($params["group"]) ? $params["group"] : "";
            $header[]         = "Spas-Signature:".$this->getSign($this->nameSpace, $group, $timeStamp);
        }

        $this->request->setHeader($header);
        if ($method == "GET") {
            $spec = strpos($api, "?") === false ? "?" : "&";
            $ret  =
                $this->request->get(sprintf("http://%s/%s%s%s", $server, $api, $spec, http_build_query($params)));
        } else {
            $ret =
                $this->request->post(sprintf("http://%s/%s", $server, $api), $params);
        }

        if ($ret->getError()) {
            throw new RequestException("request error:".$ret->getError());
        }

        if ($ret->getStatusCode() != 200) {
            throw new RequestException("response error:".$ret->getBody());
        }

        return Str::toUTF8($ret->getBody());
    }

    /**
     * @param        $dataId
     * @param string $group
     *
     * @return string
     * @throws \Aliyun\ACM\RequestException
     */
    public function getConfig($dataId, $group = "DEFAULT_GROUP")
    {
        return $this->callApi("diamond-server/config.co", [
            "dataId" => $dataId,
            "group"  => $group,
        ]);
    }

    /**
     * @param $pageNo
     * @param $pageSize
     *
     * @return string
     * @throws \Aliyun\ACM\RequestException
     */
    public function getAllConfigs($pageNo, $pageSize)
    {
        return $this->callApi("diamond-server/basestone.do?method=getAllConfigByTenant", [
            "pageNo"   => $pageNo,
            "pageSize" => $pageSize,
        ]);
    }

    /**
     * @param $dataId
     * @param $group
     * @param $content
     *
     * @return string
     * @throws \Aliyun\ACM\RequestException
     */
    public function publish($dataId, $group, $content)
    {
        return $this->callApi("diamond-server/basestone.do?method=syncUpdateAll", [
            "dataId"  => $dataId,
            "group"   => $group,
            "content" => Str::toGBK($content),
        ], "POST");
    }

    /**
     * @param $dataId
     * @param $group
     * @param $contentMd5
     *
     * @return string
     * @throws \Aliyun\ACM\RequestException
     */
    public function subscribe($dataId, $group, $contentMd5 = "")
    {
        if (!file_exists($this->localDataPath)) {
            mkdir($this->localDataPath, 0755, true);
        }

        $filename = rtrim($this->localDataPath, "/")."/".$this->nameSpace."-".$group."-".$dataId.".acm";

        if ($contentMd5 == "" && file_exists($filename)) {
            $contentMd5 = md5_file($filename);
        }

        $probe = implode("\x02", [$dataId, $group, $contentMd5, $this->nameSpace,])."\x01";

        $content = $this->callApi("diamond-server/config.co", ["Probe-Modify-Request" => $probe], "POST");
        $ret     = "";
        if (trim($content) === implode("%02", [$dataId, $group, $this->nameSpace])."%01") {
            $ret = $this->getConfig($dataId, $group);
            file_put_contents($filename, $ret);
        }

        return $ret;
    }

    /**
     * @param $dataId
     * @param $group
     *
     * @return string
     * @throws \Aliyun\ACM\RequestException
     */
    public function delete($dataId, $group)
    {
        return $this->callApi("diamond-server/datum.do?method=deleteAllDatums", [
            "dataId" => $dataId,
            "group"  => $group,
        ], "POST");
    }
}