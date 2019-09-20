### ORM部分的整体结构

![image-20190920152935877](/Users/mojave/Tech-Note/imgs/image-20190920152935877.png)

结构树状图 http://naotu.baidu.com/file/8a40af7cdc898a3887099bb62ab8a173?token=ef47c4dca40cf0e2





### 如何使用数据库连接池来避免重复建立连接



1 使用SMProxy 等数据库中间件代理

https://learnku.com/articles/20793

https://github.com/louislivi/SMProxy



2 使用PDO长连接

```
$dbh = new PDO(PDO_DSN, USERNAME, PASSWORD, [
        PDO::ATTR_PERSISTENT => true,
    ]);
    
// config/database.php

return [
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            // ...
            'prefix'    => '',
            'options'   => [
                PDO::ATTR_PERSISTENT => true,
            ],
        ],
    ],
];
```

