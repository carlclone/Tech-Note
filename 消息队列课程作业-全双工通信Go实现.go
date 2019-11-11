package main



//解决的问题，使用双工通信，使得俩大爷有边听边说的本事，对话(请求相应)要对上 ， 执行一百万次，记录耗时
// 使用序号保证双工的对话顺序
// 使用分隔符和前置长度解决"断句"问题，前置长度会更好，因为发送和接收分隔符还需要进行转义才能区分，损失性能

/*考察点：
异步设计方法
异步网络IO
序列化，反序列化
设计良好的传输协议
双工通信
*/




import (
"encoding/binary"
"fmt"
"io"
"net"
"sync"
"time"
)

var count = uint32(0)      // 俩大爷已经遇见了多少次
var total = uint32(100000) // 总共需要遇见多少次

var z0 = " 吃了没，您吶?"
var z3 = " 嗨！吃饱了溜溜弯儿。"
var z5 = " 回头去给老太太请安！"
var l1 = " 刚吃。"
var l2 = " 您这，嘛去？"
var l4 = " 有空家里坐坐啊。"

//用锁的普遍场景是什么（保护shard data，协调异步线程） ,这里用发送队列好像也可以 ， 是一个优化 ， 减少加解锁的损耗
//再尝试用CAS替代锁，第18节
var liWriteLock sync.Mutex    // 李大爷的写锁
var zhangWriteLock sync.Mutex // 张大爷的写锁



type RequestResponse struct {
	Serial  uint32 // 序号
	Payload string // 内容
}

// 序列化 RequestResponse，并发送
// 序列化后的结构如下：
// 	长度	4 字节
// 	Serial 4 字节
// 	PayLoad 变长
func writeTo(r *RequestResponse, conn *net.TCPConn, lock *sync.Mutex) {
	lock.Lock()
	defer lock.Unlock()
	payloadBytes := []byte(r.Payload)
	serialBytes := make([]byte, 4)
	binary.BigEndian.PutUint32(serialBytes, r.Serial)   //大端
	length := uint32(len(payloadBytes) + len(serialBytes))
	lengthByte := make([]byte, 4)
	binary.BigEndian.PutUint32(lengthByte, length)

	//这里是按序发送的吗？ //我的猜测，包太小没有超过MTU，所以不会切割，放在同一个包传出去了
	conn.Write(lengthByte)
	conn.Write(serialBytes)
	conn.Write(payloadBytes)
	// fmt.Println(" 发送: " + r.Payload)
}

// 接收数据，反序列化成 RequestResponse
func readFrom(conn *net.TCPConn) (*RequestResponse, error) {
	ret := &RequestResponse{}
	buf := make([]byte, 4)
	//ReadFull如果没读完是会等待一段时间吗?
	/*
	是的，如果没有设置超时，会一直等
	// Read reads data from the connection.
	// Read can be made to time out and return an Error with Timeout() == true
	// after a fixed time limit; see SetDeadline and SetReadDeadline.
	Read(b []byte) (n int, err error)


	*/
	if _, err := io.ReadFull(conn, buf); err != nil {
		return nil, fmt.Errorf(" 读长度故障：%s", err.Error())
	}
	length := binary.BigEndian.Uint32(buf)
	if _, err := io.ReadFull(conn, buf); err != nil {
		return nil, fmt.Errorf(" 读 Serial 故障：%s", err.Error())
	}
	ret.Serial = binary.BigEndian.Uint32(buf)
	payloadBytes := make([]byte, length-4)
	if _, err := io.ReadFull(conn, payloadBytes); err != nil {
		return nil, fmt.Errorf(" 读 Payload 故障：%s", err.Error())
	}
	ret.Payload = string(payloadBytes)
	return ret, nil
}

//双方都有一个协程不停发出主动请求，都有一个协程回应，总共4个
//回应的时候返回一样的序号保证一次（请求响应） , tcp是怎么做的？tcp只是支持同时收发，对话还是要自己实现
//这里如果没有锁，会发生什么，画个图 (say和listen同时写入，数据流混在一起
//还有哪些空间可以优化
// 张大爷的耳朵
func zhangDaYeListen(conn *net.TCPConn) {
	for count < total {
		r, err := readFrom(conn)
		if err != nil {
			fmt.Println(err.Error())
			break
		}
		// fmt.Println(" 张大爷收到：" + r.Payload)
		if r.Payload == l2 { // 如果收到：您这，嘛去？
			//为什么有的地方异步发送，有的地方同步 ， 这里改成同步会发生什么 ， 响应变慢
			go writeTo(&RequestResponse{r.Serial, z3}, conn, &zhangWriteLock) // 回复：嗨！吃饱了溜溜弯儿。
		} else if r.Payload == l4 { // 如果收到：有空家里坐坐啊。
			go writeTo(&RequestResponse{r.Serial, z5}, conn, &zhangWriteLock) // 回复：回头去给老太太请安！
		} else if r.Payload == l1 { // 如果收到：刚吃。
			// 不用回复
		} else {
			fmt.Println(" 张大爷听不懂：" + r.Payload)
			break
		}
	}
}

// 张大爷的嘴
func zhangDaYeSay(conn *net.TCPConn) {
	nextSerial := uint32(0)
	for i := uint32(0); i < total; i++ {
		writeTo(&RequestResponse{nextSerial, z0}, conn, &zhangWriteLock)
		nextSerial++
	}
}

// 李大爷的耳朵，实现是和张大爷类似的
func liDaYeListen(conn *net.TCPConn, wg *sync.WaitGroup) {
	// waitGroup，等待所有协程结束才结束 , 为什么张大爷的实现里不需要wg
	// waitGroup的使用方法？
	defer wg.Done()
	for count < total {
		r, err := readFrom(conn)
		if err != nil {
			fmt.Println(err.Error())
			break
		}
		// fmt.Println(" 李大爷收到：" + r.Payload)
		if r.Payload == z0 { // 如果收到：吃了没，您吶?
			writeTo(&RequestResponse{r.Serial, l1}, conn, &liWriteLock) // 回复：刚吃。
		} else if r.Payload == z3 {
			// do nothing
		} else if r.Payload == z5 {
			//fmt.Println(" 俩人说完走了 ")
			count++
		} else {
			fmt.Println(" 李大爷听不懂：" + r.Payload)
			break
		}
	}
}

// 李大爷的嘴
func liDaYeSay(conn *net.TCPConn) {
	//李发出了两种请求
	nextSerial := uint32(0)
	for i := uint32(0); i < total; i++ {
		writeTo(&RequestResponse{nextSerial, l2}, conn, &liWriteLock)
		nextSerial++
		writeTo(&RequestResponse{nextSerial, l4}, conn, &liWriteLock)
		nextSerial++
	}
}

func startServer() {

	//开启一个socket监听地址 ， 张是server
	tcpAddr, _ := net.ResolveTCPAddr("tcp", "127.0.0.1:9999")
	tcpListener, _ := net.ListenTCP("tcp", tcpAddr)
	defer tcpListener.Close()
	fmt.Println(" 张大爷在胡同口等着 ...")
	for {
		conn, err := tcpListener.AcceptTCP()
		if err != nil {
			fmt.Println(err)
			break
		}
		//如果连接成功，开始请求和响应
		fmt.Println(" 碰见一个李大爷:" + conn.RemoteAddr().String())
		//需要同时听说，所以用了go
		go zhangDaYeListen(conn)
		go zhangDaYeSay(conn)
	}

}

// 李，同上，客户端
func startClient() {
	var tcpAddr *net.TCPAddr
	tcpAddr, _ = net.ResolveTCPAddr("tcp", "127.0.0.1:9999")
	conn, _ := net.DialTCP("tcp", nil, tcpAddr)

	defer conn.Close()
	var wg sync.WaitGroup
	//因为是在主线程上执行的，不wait的话就直接print了
	wg.Add(1)
	//同上，同时听说
	go liDaYeListen(conn, &wg)
	go liDaYeSay(conn)
	wg.Wait()
}

func main() {
	//不go的话，server会阻塞住，client无法启动 ， 如果放到两个进程去开的话就不用
	go startServer()
	time.Sleep(time.Second)
	t1 := time.Now()
	startClient()
	elapsed := time.Since(t1)
	fmt.Println(" 耗时: ", elapsed)
}
