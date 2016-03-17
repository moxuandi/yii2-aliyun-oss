Yii2 Aliyun OSS
===============
Yii2 阿里云 OSS

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist yiier/yii2-aliyun-oss "*"
```

or add

```
"yiier/yii2-aliyun-oss": "*"
```

to the require section of your `composer.json` file.


Usage
-----

配置文件添加组件  :

```php
components => [
    'oss' => [
        'class' => 'yiier\AliyunOSS\OSS',
        'accessKeyId' => '阿里云OSS AccessKeyID',
        'accessKeySecret' => '阿里云OSS AccessKeySecret',
        'bucket' => '阿里云的bucket空间',
        'lanDomain' => 'OSS内网地址, 如:oss-cn-hangzhou-internal.aliyuncs.com',
        'wanDomain' => 'OSS外网地址, 如:oss-cn-hangzhou.aliyuncs.com'
    ],
]
```

```php
$oss = \Yii::$app->get('oss');
$fh = '/vagrant/php/baseapi/web/storage/image/824edb4e295892aedb8c49e4706606d6.png';
$oss->upload('824edb4e295892aedb8c49e4706606d6.png', $fh);
```