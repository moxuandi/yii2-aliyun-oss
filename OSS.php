<?php

namespace yiier\AliyunOSS;

use OSS\Core\OssException;
use OSS\Http\ResponseCore;
use OSS\Model\ObjectListInfo;
use OSS\OssClient;
use yii\base\Component;
use yii\base\InvalidConfigException;

/**
 * Class OSS
 * @package yiier\AliyunOSS
 */
class OSS extends Component
{
    /**
     * @var string 阿里云OSS AccessKeyID
     */
    public $accessKeyId;
    /**
     * @var string 阿里云OSS AccessKeySecret
     */
    public $accessKeySecret;
    /**
     * @var string 阿里云OSS 数据中心的域名, eg: oss-cn-hangzhou.aliyuncs.com
     */
    public $endpoint;
    /**
     * @var string 阿里云OSS的bucket空间
     */
    public $bucket;
    /**
     * @var int 临时URL的超时时间, 单位:秒.
     */
    public $timeout = 3600;
    /**
     * @var bool 是否私有空间, 默认公开空间
     */
    public $isPrivate = false;
    /**
     * @var OssClient
     */
    private $_ossClient;


    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        if (empty($this->accessKeyId)) {
            throw new InvalidConfigException('The "accessKeyId" property must be set.');
        }
        if (empty($this->accessKeySecret)) {
            throw new InvalidConfigException('The "accessKeySecret" property must be set.');
        }
        if (empty($this->endpoint)) {
            throw new InvalidConfigException('The "endpoint" property must be set.');
        }
        if (empty($this->bucket)) {
            throw new InvalidConfigException('The "bucket" property must be set.');
        }
    }

    /**
     * @return OssClient
     * @throws OssException
     */
    public function getClient()
    {
        if ($this->_ossClient === null) {
            $this->setClient(new OssClient($this->accessKeyId, $this->accessKeySecret, $this->endpoint));
        }
        return $this->_ossClient;
    }

    /**
     * @param OssClient $ossClient
     */
    public function setClient(OssClient $ossClient)
    {
        $this->_ossClient = $ossClient;
    }

    /**
     * 检查文件对象是否存在.
     * @param string $path 文件对象的路径.
     * @return bool
     * @throws OssException
     */
    public function has(string $path)
    {
        return $this->getClient()->doesObjectExist($this->bucket, $path);
    }

    /**
     * 将一个本地文件上传到 OSS.
     * @param string $ossFile OSS 上的文件路径.
     * @param string $localFile 本地文件路径.
     * @return string
     * @throws OssException
     */
    public function upload(string $ossFile, string $localFile)
    {
        $result = $this->getClient()->uploadFile($this->bucket, $ossFile, $localFile);
        // todo: 此处要对失败情况进行处理
        //if ($result['oss-redirects'] === 0) {}
        return $result['oss-request-url'];
    }

    /**
     * 对 URL 进行签名, 获取一个临时 URL.
     * @param string $ossFile OSS 上的文件路径.
     * @return ResponseCore|string
     * @throws OssException
     */
    public function signUrl(string $ossFile)
    {
        return $this->getClient()->signUrl($this->bucket, $ossFile, $this->timeout);
    }

    /**
     * 删除文件
     * @param string $ossFile OSS 上的文件路径
     * @return bool
     * @throws OssException
     */
    public function delete(string $ossFile)
    {
        return $this->getClient()->deleteObject($this->bucket, $ossFile) === null;
    }

    /**
     * 在 OSS 中创建一个虚拟文件夹
     * @param string $dirName 文件夹名称
     * @return bool
     * @throws OssException
     */
    public function createDir(string $dirName)
    {
        $result = $this->getClient()->createObjectDir($this->bucket, rtrim($dirName, '/'));
        // todo: 此处要对失败情况进行处理
        //if ($result['oss-redirects'] === 0) {}
        return $result['oss-redirects'] === 0;
    }

    /**
     * 获取 Bucket 中所有文件的文件名, 返回 Array.
     * @param array $options = [
     *      'max-keys'  => max-keys用于限定此次返回object的最大数, 如果不设定, 默认为100, max-keys取值不能大于1000.
     *      'prefix'    => 限定返回的object key必须以prefix作为前缀.注意使用prefix查询时, 返回的key中仍会包含prefix.
     *      'delimiter' => 是一个用于对Object名字进行分组的字符.所有名字包含指定的前缀且第一次出现delimiter字符之间的object作为一组元素.
     *      'marker'    => 用户设定结果从marker之后按字母排序的第一个开始返回.
     * ]
     * @param false $returnRawObject 是否返回原生 ObjectListInfo 对象.
     * @return array[]|ObjectListInfo
     * @throws OssException
     */
    public function getAllObject(array $options = [], $returnRawObject = false)
    {
        $objectListing = $this->getClient()->listObjects($this->bucket, $options);
        if ($returnRawObject) {
            return $objectListing;
        }
        $result = [
            'files' => [],
            'dirs' => [],
        ];
        foreach ($objectListing->getObjectList() as $objectInfo) {
            $result['files'][] = $objectInfo->getKey();
        }
        foreach ($objectListing->getPrefixList() as $prefixInfo) {
            $result['dirs'][] = $prefixInfo->getPrefix();
        }
        return $result;
    }

    /**
     * 读取 OSS 上的文件资源
     * @param string $ossFile OSS 上的文件路径
     * @return bool
     * @throws OssException
     */
    public function read(string $ossFile)
    {
        if (!($resource = $this->readStream($ossFile))) {
            return false;
        }
        $resource['contents'] = stream_get_contents($resource['stream']);
        fclose($resource['stream']);
        unset($resource['stream']);
        return $resource;
    }

    /**
     * 以流的方式读取 OSS 上的文件
     * @param string $ossFile OSS 上的文件路径
     * @return array|bool
     * @throws OssException
     */
    public function readStream(string $ossFile)
    {
        $url = $this->getClient()->signUrl($this->bucket, $ossFile, 3600);
        $stream = fopen($url, 'r');
        if (!$stream) {
            return false;
        }
        return compact('stream', 'path');
    }
}
