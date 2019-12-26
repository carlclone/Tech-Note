<?php

//只用链表的第一版实现

class LruNode {
    public $key;
    public $value;
    /**
     * @var LruNode|null
     */
    public $next;
    /**
     * @var LruNode|null
     */
    public $prev;
}

// head最新 , tail最旧
class LruCache1 {
    /**
     * @var LruNode|null
     */
    public $linkHead;
    /**
     * @var LruNode|null
     */
    public $linkTail;
    /**
     * @var int
     */
    public $linkLen;
    /**
     * @var int
     */
    public $cap;
    
    /*情况分析
    数据不在cache中  , 未满 , 直接添加到链表头节点中
    数据不在cache中, 已满 , 删除尾结点 , 并添加到链表头节点
    数据在cache中,移动到头节点
    */
    public function put($key,$value)
    {
        $node = $this->get($key);
        if ($node) {
            $this->linkHead = $this->moveToHead($node);
            return;
        }
        if ($this->linkLen >= $this->cap) {
            $newTail = $this->delNode($this->linkTail);
            $this->linkTail = $newTail;
            $this->linkHead = $this->addToHead($key, $value);
        } else {
            $this->linkHead = $this->addToHead($key, $value);
        }
        //TODO; value变化时更新value
    }

    public function get($key)
    {
        $node = $this->searchNode($key);
        if ($node) {
            $this->linkHead = $this->moveToHead($node);
        } 
        return $node;
    }
    
}