```
.
├── App.php
├── CallableResolver.php
├── CallableResolverAwareTrait.php
├── Collection.php
├── Container.php
├── DefaultServicesProvider.php
├── DeferredCallable.php
├── Exception                                            异常类目录
│   ├── ContainerException.php
│   ├── ContainerValueNotFoundException.php
│   ├── InvalidMethodException.php
│   ├── MethodNotAllowedException.php
│   ├── NotFoundException.php
│   └── SlimException.php
├── Handlers                                            请求处理第一层
│   ├── AbstractError.php
│   ├── AbstractHandler.php
│   ├── Error.php
│   ├── NotAllowed.php
│   ├── NotFound.php
│   ├── PhpError.php
│   └── Strategies
│       ├── RequestResponse.php
│       └── RequestResponseArgs.php
├── Http
│   ├── Body.php
│   ├── Cookies.php
│   ├── Environment.php
│   ├── Headers.php
│   ├── Message.php
│   ├── NonBufferedBody.php
│   ├── Request.php
│   ├── RequestBody.php
│   ├── Response.php
│   ├── StatusCode.php
│   ├── Stream.php
│   ├── UploadedFile.php
│   └── Uri.php
├── Interfaces
│   ├── CallableResolverInterface.php
│   ├── CollectionInterface.php
│   ├── Http
│   │   ├── CookiesInterface.php
│   │   ├── EnvironmentInterface.php
│   │   └── HeadersInterface.php
│   ├── InvocationStrategyInterface.php
│   ├── RouteGroupInterface.php
│   ├── RouteInterface.php
│   └── RouterInterface.php
├── MiddlewareAwareTrait.php
├── Routable.php
├── Route.php
├── RouteGroup.php
└── Router.php
```

参考文章：



摘抄一句《Laravel框架关键技术》作者的话 - "如果你没有读懂源码，那一定是还没有足够地简化源码"

先精简一下流程，专注于请求的流向

新建一个app类
配置路由
执行
 -- 从容器中获取response实例
 -- 执行中间件闭包，获得响应
 -- 返回响应
 
 
### 响应的细节

基于psr7的http-message实现 https://www.php-fig.org/psr/psr-7/
返回的$response是一个响应类，实现了ResponseInterface
__toString()方法,因此返回给nginx等web服务器的时候会转换为字符串
基于StreamInterface ， 这个接口的方法可以避免body过大造成的内存溢出

### 中间件闭包生成的细节

中间件有必须用闭包实现的理由吗，像其他一些框架用链表实现不是更加简单易懂，并且也不会造成很深的调用栈吗
