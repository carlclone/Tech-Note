### 从数据结构的角度理解中间件中闭包的使用

匿名函数 , 闭包Closure 可以想象成一个名为 Closure 的类 , 传入的变量则是成员变量(因此封装了状态) ,  匿名函数以另一个匿名函数作为参数 , 则类似链表的节点包含另一个节点 , 有子结构,递归的特性 

Java 的一个叫做 Netty 的网络框架的中间件实现, 则直接使用了双向链表 , 显得更直观易懂

许多文章使用装饰器模式去解释,理解起来不够直观,并且实际执行的时候还有前置后置,执行顺序像一棵树,反而复杂了,我更倾向于链表中间件简单明了的设计

参考 Laravel 实现提取出来的一个 closure middleware demo
```
<?php

function main()
{
    $middleWareList = [
        Echo1::class,
        Echo2::class
    ];
    $executionTree = buildMiddleWareList($middleWareList);
    $executionTree();
}

class Echo1 {
    public static function handle(Closure $next)
    {
        $next();
        echo 1; 
    }
}

class Echo2 {
    public static function handle(Closure $next)
    {
        echo 2;
        $next(); 
    }
}

function buildMiddleWareList(array $list):Closure
{

    $initFunc = function () {
    };
    $list = array_reverse($list);
    $buildFunc = function (Closure $last,$class) {
        return function () use ($last,$class){
            return $class::handle($last);
        };

    };
    return array_reduce($list, $buildFunc, $initFunc);
}

main();
```


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

