### 对近期知识点的map_reduce (6 网络编程框架源码阅读)



项目来自于 网络编程课老师用C写的网络编程框架



基于 第五篇学到的源码阅读方法 , 我先搭建起了项目, 跑了之前的几个小demo



1.quick start

使用CLion的Build Project编译了项目

使用chap-17的` ./reliable_client01 127.0.0.1`和chap-27的`./poll-server-onethread` 跑了框架demo



```
服务端输出

[msg] set poll as dispatcher, main thread
[msg] add channel fd == 4, main thread
[msg] poll added channel fd==4, main thread
[msg] add channel fd == 5, main thread
[msg] poll added channel fd==5, main thread
[msg] event loop run, main thread
[msg] get message channel i==1, fd==5, main thread
[msg] activate channel fd == 5, revents=2, main thread
[msg] new connection established, socket == 6
connection completed
[msg] add channel fd == 6, main thread
[msg] poll added channel fd==6, main thread
[msg] get message channel i==2, fd==6, main thread
[msg] activate channel fd == 6, revents=2, main thread
get message from tcp connection connection-6
dsad
[msg] get message channel i==2, fd==6, main thread
[msg] activate channel fd == 6, revents=2, main thread
get message from tcp connection connection-6
dsad
jkjkjk

```





然后阅读"文档" , 也就是搭建HTTP服务器的三篇



模型的基本概念,名词术语:

Eventloop , 事件驱动模型 , 别名反应堆模型(Reactor)

reactor/event loop线程 , 负责无限循环,分发事件 , 

acceptor负责连接建立成功事件

所有IO操作都被抽象成事件 , 每个事件都必须有回调函数进行处理



read , decode , compute (业务逻辑) , encode , send

