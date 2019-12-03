## 对近期知识点的map_reduce (8 语言特性)



### Py 的协程 / JS的协程



#### Coroutine 模型对比

Go的是m:n的协程线程模型 , 并且支持多核并行

Py的是m:1的单线程事件循环模型 ,  只支持并发 , 因此在cpu bound的场景下使用协程没有优势

js的await / async也是协程 , 和py模型一样 , 但是使用方式不太一样, js可以直接执行异步函数 , py生成的是Coroutine object , 要配合其他才能执行



### Coroutine 概念

1.是用户态的并发调度

2.协程类似线程 , 但是线程的控制权是操作系统 , 用户没有控制权限 , 而协程把控制权交给了用户 

3.“协程就是可以人为暂停执行的函数”。如果你觉得，“这听起来像是生成器(generators)”，那么你是对的。



### 模型适用场景

多进程    cpu bound , 并行执行

多线程    io bound , 避免了进程上下文切换的消耗  , CPU空闲/阻塞在IO的时候切换到其他可运行线程 , 当前线程放入等待队列

事件循环 io bound best choice , 避免了线程维护的消耗 , 同上



### 参考资料

[IO是否会一直占用CPU]( https://www.zhihu.com/question/27734728)

[Python 3.5中async/await的工作机制]( https://www.cnblogs.com/harelion/p/8496360.html)

https://en.wikipedia.org/wiki/Coroutine

https://en.wikipedia.org/wiki/Thread_safety

https://en.wikipedia.org/wiki/Race_condition#Computing

[多线程可以同时使用 CPU 的多个核心？](https://v2ex.com/t/285551)

[python并发编程 demo库]()





### Py的并发接口

async 标识可以被调度/异步的操作 (返回Coroutine object)

await标识阻塞的位置 ( 用户的调度控制权)

task 是调度基本单位



### 完

py的协程就是随处可见的事件循环模型 , 挺好理解的 , go的要复杂的多 , 以后再开一章总结



py的协程相比线程 , 没有线程安全问题 , 没有race condition(用户负责调度) , 没有线程维护开销

