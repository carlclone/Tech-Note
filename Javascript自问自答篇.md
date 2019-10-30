# Javascript自问自答篇



### 如何理解JS的作用域，作用域的底层实现

为什么这段代码输出为1 ？
```
let a = 1
function foo() {
    console.log(a)
}
function too() {
    let a = 2
    foo()
}
too() // 1  为什么这里输出为1 ？

```

答：Javascript有作用域链，在当前作用域找不到的时候会往上层作用域继续查找 , 并且作用域链在定义（编码阶段）的时候就已经固定，而不是运行时才确定，因此foo的上一层作用域是全局作用域 。于此相反的是this是在运行时才确定。




参考：

[讲清楚之javascript作用域](https://segmentfault.com/a/1190000014980841)

[javascript作用域底层作用分析](https://blog.csdn.net/weixin_39877717/article/details/80448975)



### Promise的出现背景，设计思想

回调函数实现异步 ， 有回调地狱的问题

观察者模式 / 发布订阅模式. VS   Promise

promise的reslove就是观察者模式中的 事件分发 ， then就是正常执行结果的Listener ，catch就是reject的Listener

由于只能触发一次，还增加了一个status状态字段来避免重发触发的情况(pending , fulfilled , rejected)

链式调用功能，则使用队列来保存不同阶段执行的结果 ， 分别有正常执行的promise结果队列，和错误执行的promise结果队列

每个promise中的data字段保存异步结果

rejected状态用于错误处理 ， 不同的状态相当于事件分类

参考：

[从设计模式角度分析Promise：手撕Promise并不难](https://juejin.im/post/5cc7d8f56fb9a031f61d838e)

[解读Promise内部实现原理](https://juejin.im/post/5a30193051882503dc53af3c)

### this的值到底是谁？

和作用域不同，this是运行时才确定的

在开启严格模式进行js编程时 ，当函数作为方法被对象拥有并调用时 this 指向该对象，否则 this 为 undefind

当函数是以箭头函数方式创建的，此时的 this 指向箭头函数执行时的宿主函数的上下文

```
function foo () {
    let that = this
    let too = () => {
        console.log(this === that) // true
    }
    too()
}
foo()
```



参考:

[讲清楚之 javascript中的this](https://segmentfault.com/a/1190000015038826)






## 扩展学习
	
 闭包可以理解为缓存了当前作用域的一个函数变量

 [讲清楚之 javascript 参数传值](https://segmentfault.com/a/1190000015105086)

 [讲清楚之 javascript 对象继承](https://segmentfault.com/a/1190000015171937)

 [讲清楚之 javascript 变量对象](https://segmentfault.com/a/1190000015077971)