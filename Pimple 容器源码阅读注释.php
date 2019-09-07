<?php

/*
 * This file is part of Pimple.
 *
 * Copyright (c) 2009 Fabien Potencier
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Pimple;

use Pimple\Exception\ExpectedInvokableException; // 没有__invoke方法异常
use Pimple\Exception\FrozenServiceException; //  冻结服务异常 , 不能被多次实例化
use Pimple\Exception\InvalidServiceIdentifierException; // 非法ID异常
use Pimple\Exception\UnknownIdentifierException; //未知ID异常

/*
 * 参考资料:
 * https://segmentfault.com/a/1190000014480078
 * https://segmentfault.com/a/1190000014487490
 * https://segmentfault.com/a/1190000014471794
 * https://segmentfault.com/a/1190000010018086
 */

/**
 * Container main class.
 *
 * @author Fabien Potencier
 */
class Container implements \ArrayAccess //实现了ArrayAccess , $this->abc 可以通过$this['abc']访问
{
    private $values = array(); //存放key=>value , value可能是任意值也可能是Closure , 也可能是执行Closure后的object (单例的实现)
    private $factories; //如果设为了factory方法, 则value不会被替换为object , 而是每次都调用Closure返回一个新object (工厂模式实现相关)
    private $protected; //防止容器中设为protect的Closure属性被当做service使用(调用返回object) , 而应该当做一个Closure变量 
    private $frozen = array(); //Closure被执行后就会frozen (单例实现相关)
    private $raw = array();  // 存放原始Closure (因为value已变成object)
    private $keys = array(); //存放key=>bool , 标记service是否存在

    /**
     * Instantiates the container.
     *
     * Objects and parameters can be passed as argument to the constructor.
     *
     * @param array $values The parameters or objects
     */
    public function __construct(array $values = array())
    {
        //SplObjectStorage是object集合,意味着object不能重复  (闭包就是一个Closure object , 带有__invoke方法)
        $this->factories = new \SplObjectStorage();
        $this->protected = new \SplObjectStorage();

        //批量设置service
        foreach ($values as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    /**
     * Sets a parameter or an object.
     *
     * Objects must be defined as Closures.
     *
     * Allowing any PHP callable leads to difficult to debug problems
     * as function names (strings) are callable (creating a function with
     * the same name as an existing parameter would break your container).
     *
     * @param string $id    The unique identifier for the parameter or object
     * @param mixed  $value The value of the parameter or a closure to define an object
     *
     * @throws FrozenServiceException Prevent override of a frozen service
     */
    public function offsetSet($id, $value)
    {
        //单例限制 , frozen的service不能被重新set
        if (isset($this->frozen[$id])) {
            throw new FrozenServiceException($id);
        }

        $this->values[$id] = $value;
        $this->keys[$id] = true;
    }

    /**
     * Gets a parameter or an object.
     *
     * @param string $id The unique identifier for the parameter or object
     *
     * @return mixed The value of the parameter or an object
     *
     * @throws UnknownIdentifierException If the identifier is not defined
     */
    public function offsetGet($id)
    {
        //基本检查
        if (!isset($this->keys[$id])) {
            throw new UnknownIdentifierException($id);
        }

        if (
            isset($this->raw[$id]) // 在raw字典中 , 意味着已经被实例化且是单例 , 直接返回
            || !\is_object($this->values[$id])  //  属性不是object , 是其他类型的值 , 直接返回
            || isset($this->protected[$this->values[$id]])   //  被设为protected的参数 , 直接返回
            || !\method_exists($this->values[$id], '__invoke') // 不能被当做Closure方法调用的 , 直接返回
        ) {
            return $this->values[$id];
        }

        if (isset($this->factories[$this->values[$id]])) {
            return $this->values[$id]($this);    //是工厂 , 每次调用生成新的
        }

        //单例 , 调用生成后冻结 , 修改value

        //获取Closure类
        $raw = $this->values[$id];
        //执行Closure类的__invoke获取object
        $val = $this->values[$id] = $raw($this);
        //将Closure类保存到raw字典中
        $this->raw[$id] = $raw;
        //将该service的id保存到frozen字典中,true
        $this->frozen[$id] = true;
        //返回object
        return $val;
    }

    /**
     * Checks if a parameter or an object is set.
     *
     * @param string $id The unique identifier for the parameter or object
     *
     * @return bool
     */
    public function offsetExists($id)
    {
        //使用keys字典判断service是否存在,避免设置的service的值不可判断(如null)
        return isset($this->keys[$id]);
    }

    /**
     * Unsets a parameter or an object.
     *
     * @param string $id The unique identifier for the parameter or object
     */
    public function offsetUnset($id)
    {
        //好吧 , 可以unset一个不存在的key , 不会报错 , 所以写成这样也可以,出于性能考虑?
        /*
        unset($this->values[$id], 
              $this->frozen[$id], 
              $this->raw[$id], 
              $this->keys[$id],
              $this->factories[$this->values[$id]],
              $this->protected[$this->values[$id]]
        );
        */

        if (isset($this->keys[$id])) {
            // 是object就可能是通过protect或factory方法设置的? (因为这两个方法只能设置callable的)
            // 吐槽,没有类型声明的源码读起来真累,go和c的源码读起来就很舒服
            if (\is_object($this->values[$id])) {
                unset($this->factories[$this->values[$id]], $this->protected[$this->values[$id]]);
            }

            //将服务从其余字典中删除
            unset($this->values[$id], $this->frozen[$id], $this->raw[$id], $this->keys[$id]);
        }
    }

    /**
     * Marks a callable as being a factory service.
     *
     * @param callable $callable A service definition to be used as a factory
     *
     * @return callable The passed callable
     *
     * @throws ExpectedInvokableException Service definition has to be a closure or an invokable object
     */
    public function factory($callable)
    {
        //检测是否带有__invoke方法
        if (!\method_exists($callable, '__invoke')) {
            throw new ExpectedInvokableException('Service definition is not a Closure or invokable object.');
        }

        //调用SplObjectStorage的attach方法保存
        $this->factories->attach($callable);


        return $callable;
    }

    /**
     * Protects a callable from being interpreted as a service.
     *
     * This is useful when you want to store a callable as a parameter.
     *
     * @param callable $callable A callable to protect from being evaluated
     *
     * @return callable The passed callable
     *
     * @throws ExpectedInvokableException Service definition has to be a closure or an invokable object
     */
    public function protect($callable)
    {
        //检测是否带有__invoke方法
        if (!\method_exists($callable, '__invoke')) {
            throw new ExpectedInvokableException('Callable is not a Closure or invokable object.');
        }

        //调用SplObjectStorage的attach方法保存
        $this->protected->attach($callable);

        return $callable;
    }

    /**
     * Gets a parameter or the closure defining an object.
     *
     * @param string $id The unique identifier for the parameter or object
     *
     * @return mixed The value of the parameter or the closure defining an object
     *
     * @throws UnknownIdentifierException If the identifier is not defined
     */
    public function raw($id)
    {
        //基本检查
        if (!isset($this->keys[$id])) {
            throw new UnknownIdentifierException($id);
        }


        if (isset($this->raw[$id])) {
            return $this->raw[$id];
        }

        return $this->values[$id];
    }

    /**
     * Extends an object definition.
     *
     * Useful when you want to extend an existing object definition,
     * without necessarily loading that object.
     *
     * @param string   $id       The unique identifier for the object
     * @param callable $callable A service definition to extend the original
     *
     * @return callable The wrapped callable
     *
     * @throws UnknownIdentifierException        If the identifier is not defined
     * @throws FrozenServiceException            If the service is frozen
     * @throws InvalidServiceIdentifierException If the identifier belongs to a parameter
     * @throws ExpectedInvokableException        If the extension callable is not a closure or an invokable object
     */
    public function extend($id, $callable)
    {
        // 基本检查
        if (!isset($this->keys[$id])) {
            throw new UnknownIdentifierException($id);
        }

        //frozen的不能被extend
        if (isset($this->frozen[$id])) {
            throw new FrozenServiceException($id);
        }

        //检查旧的是不是callable
        if (!\is_object($this->values[$id]) || !\method_exists($this->values[$id], '__invoke')) {
            throw new InvalidServiceIdentifierException($id);
        }

        // protected的不能被extend
        if (isset($this->protected[$this->values[$id]])) {
            @\trigger_error(\sprintf('How Pimple behaves when extending protected closures will be fixed in Pimple 4. Are you sure "%s" should be protected?', $id), \E_USER_DEPRECATED);
        }

        //检查新的是不是callable
        if (!\is_object($callable) || !\method_exists($callable, '__invoke')) {
            throw new ExpectedInvokableException('Extension service definition is not a Closure or invokable object.');
        }

        $factory = $this->values[$id];

        //(生成一个新闭包)先执行原来的闭包,然后将原来闭包返回的object作为参数传入extend的闭包
        $extended = function ($c) use ($callable, $factory) {
            return $callable($factory($c), $c);
        };

        //删除原来的callable , 插入新的
        if (isset($this->factories[$factory])) {
            $this->factories->detach($factory);
            $this->factories->attach($extended);
        }

        //设置为callable容器的属性
        return $this[$id] = $extended;
    }

    /**
     * Returns all defined value names.
     *
     * @return array An array of value names
     */
    public function keys()
    {
        //返回所有已设置的service id
        return \array_keys($this->values);
    }

    /**
     * Registers a service provider.
     *
     * @param ServiceProviderInterface $provider A ServiceProviderInterface instance
     * @param array                    $values   An array of values that customizes the provider
     *
     * @return static
     */
    public function register(ServiceProviderInterface $provider, array $values = array())
    {
        //调用服务提供类的register方法注册到容器属性中
        $provider->register($this);

        //设置其他属性
        foreach ($values as $key => $value) {
            $this[$key] = $value;
        }

        return $this;
    }
}
