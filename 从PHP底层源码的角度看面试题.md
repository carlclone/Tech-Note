### 为什么foreach比for快

因为php数组的底层是哈希结构 , bucket之间是一条双向链表 , foreach的时候直接取next的值即可 , 同时foreach是根据插入顺序遍历的 , 如果需要有序遍历 , 只能用for循环

for比foreach慢的原因 , 可能是多出的一个条件判断造成的 , 因此能用foreach就尽量用foreach



### foreach中的一个小陷阱

```
$arr = array('1','2','3');
foreach($arr as &$v){
}
foreach($arr as $v){
}
var_dump($arr);

//结果
array
  0 => string '1' (length=1)
  1 => string '2' (length=1)
  2 => &string '2' (length=1)
```



预期结果应该是1 2 3 , 为什么是1 2 2 呢 ?

```
因为在第一个循环的最后一次执行中 , 由于$v为引用变量，所以$v 与 $arr[ 2 ] 指向了同一个地址空间 , 于是在第二次循环的时候 , 不断将值赋给arr[2]的zval结构体,  arr的变化如下:
```

[ 1 , 2 , 1 ]

[ 1 , 2 , 2 ]

[ 1, 2 , 2 ]



### unset

unset只删除php层引用变量的映射关系 , 并不会清空底层zval变量的内存空间



### 为什么要小心使用PHP的引用

PHP采用的复制机制是“引用计数，写时复制”，也就是说，即便在PHP里复制一个变量，最初的形式从根本上说其实仍然是引用的形式，只有当变量的内容发生变化时，才会出现真正的复制。





### 引用和指针的区别



在C中指针是一个存储内存地址的变量

在PHP中引用则是在存储变量键值对的哈希表中增加一对键值对 , 值指向同一个结构体 , 因此几乎不产生额外空间 



写时复制

写时改变



https://blog.csdn.net/JathamJ/article/details/73189194

https://blog.csdn.net/chen1083376511/article/details/82721749



#### [C语言里的三种参数传递 ](nowamagic.net/academy/detail/1205552) - 现代魔法学院

在开始之前，请务必看这篇文章： [漫谈C指针：参数传递的三道题目](http://www.nowamagic.net/librarys/veda/detail/2126)

我们都知道：C语言中函数参数的传递有：[值传递](http://www.nowamagic.net/academy/tag/值传递)、地址传递、引用传递这三种形式。题一为值传递，题二为地址传递，题三为引用传递。

值传递大家都应该很清楚，作为参数的变量，传递给函数执行后，自己的变量值是不变的。它（实参）仅仅只是把值赋给了形参，自己实际上是没有参与函数运算的，参与的是形参，这个就是参数的值传递。

地址传递呢？地址传递跟值传递也没有什么不同，值传递是把变量的值传递给形参去参与函数运算，而地址传递则把变量的地址传递给形参去参与函数运算。当然，如果函数改变了变量地址的值，实参的值也会变化的。

最后是[引用传递](http://www.nowamagic.net/academy/tag/引用传递)，也是我们本小节的重点。引用传递的调用方式与值传递一样的，而形参则引用了实参，在函数里头操作的是实参，而不是像上面两种操作形参那样。也就是函数是直接修改实参的值了。



### 变量和变量名是如何映射的

变量是一个zval结构体 , 通过哈希表键值对将变量名和变量映射起来 



参考: 

[如何获取一个变量的名字](http://www.laruence.com/2010/12/08/1716.html)
https://segmentfault.com/a/1190000018535960

# [许铮的技术成长之路](https://segmentfault.com/blog/xuzheng_tech_growth)

https://www.jianshu.com/p/53fcf6128dcd





```

> 变量组成
每一个php变量都会由变量类型、value值、引用计数次数和是否是引用变量四部分组成

> 变量名和变量容器关联
而变量name是如何与变量容器关联起来的呢？其实也是使用了php的一个内部机制，即哈希表。每个变量的变量名和指向zval结构的指针被存储在哈希表内，以此实现了变量名到变量容器的映射

> 变量作用域
其实不是的，变量存储也有作用域的概念。全局变量被存储到了全局符号表内，而局部变量也就是指函数或对象内的变量，则被存储到了活动符号表内（每个函数或对象都单独维护了自己的活动符号表。活动符号表的生命周期，从函数或对象被调用时开始，到调用完成时结束）

> 变量销毁
我们这次主要讲一下手动销毁，即unset，每次销毁时都会将符号表内的变量名和对应的zval结构进行销毁，并将对应的内存归还到php所维护的内存池内（按内存大小划分到对应内存列表中）
```



### 请求从开始到结束  , 底层



浏览器 与 nginx(master/worker模型)的一个worker建立tcp连接 , 传递http格式的请求 , nginx与php-fpm (master/worker 和 leader/follower模型) 的一个等待accept的worker建立连接 ,传递fast-cgi格式的packet , worker通过(是cgi sapi吗 ?)解析, 编译 , 执行php脚本



### php 脚本编译 , 执行过程

需要看编译原理

简言之；PHP动态语言执行过程：拿到一段代码后，经过词法解析、语法解析等阶段后，源程序会被翻译成一个个指令（opcodes）,然后ZEND虚拟机顺次执行这些指令完成操作。PHP本身是用C实现的，因此最终调用的也是C的函数，实际上，我们可以把PHP看做一个C开发的软件。

http://blog.yzmcms.com/html/php/173.html

https://www.cnblogs.com/wanglijun/p/8830932.html

[风雪之隅](http://www.laruence.com/) 

http://www.laruence.com/php-internal

[现代魔法学院](http://www.nowamagic.net/academy/category/12)




### OPCODE 底层实现



### SAPI 底层实现





### php 所有类型的底层实现

四种标量类型：

1. string（字符串）
2. integer（整型）
3. float（浮点型，也作 double ）
4. boolean（布尔型）

两种复合类型：

1. array（数组）
2. object（对象）

两种特殊类型：

1. resource（资源）
2. NULL（空）



