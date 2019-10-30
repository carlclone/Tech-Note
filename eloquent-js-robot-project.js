// 还缺 ， robotanimation的实现，作业2的实现
/*
今天沮丧的原因
1 复杂度管理(eloquent js :complexity management)没有处理好 ， 版本兼容问题
2 花了好长时间才看懂派送机器人的逻辑
3 好多长期的问题没有考虑，比如createSubTable
4 看代码就知道，做事态度有问题，能用就行的状态
5 题目都只看答案了，都不自己动手做了
6 银行名义利率 实际利率都不会算

收获
1 复杂度管理，persistent data，生成新的状态，而不是修改原来的状态
2 模型抽象，不要直接抽象现实世界的模型，而是先建立最小状态的模型 , 状态和动作
3 测试，假数据生成


 */
const roads = [
  "Alice's House-Bob's House",
  "Alice's House-Cabin",
  "Alice's House-Post Office",
  "Bob's House-Town Hall",
  "Daria's House-Ernie's House", "Daria's House-Town Hall",
  "Ernie's House-Grete's House", "Grete's House-Farm",
  "Grete's House-Shop",
  "Marketplace-Farm",
  "Marketplace-Post Office",
  "Marketplace-Shop",
  "Marketplace-Town Hall",
  "Shop-Town Hall"
];

//生成路径图
function buildGraph(edges) {
  let graph = Object.create(null);

  function addEdge(from, to) {
    if (graph[from] == null) {
      graph[from] = [to];
    } else {
      graph[from].push(to);
    }
  }

  for (let [from, to] of edges.map(r => r.split("-"))) {
    addEdge(from, to);
    addEdge(to, from);
  }
  return graph;
}

const roadGraph = buildGraph(roads);
findRoute(roadGraph,"Alice's House",'Shop')

//定义递送机器人的模型最小状态 ， 村庄状态，包含机器人当前所处位置place属性，所有未派送的包裹，每个包裹持有当前位置和目的地状态
class VillageState {
  constructor(place, parcels) {
    this.place = place;
    this.parcels = parcels;
  }

  // 移动机器人， 路径不存在时，返回原状态
  // 否则map检查每一个包裹，并返回包裹的新状态，filter对派送完成对包裹进行过滤
  // 返回模型的新状态
  move(destination) {
    if (!roadGraph[this.place].includes(destination)) {
      return this;
    } else {
      let parcels = this.parcels.map(p => {
        if (p.place !== this.place) return p;
        return {place: destination, address: p.address};
      }).filter(p => p.place !== p.address);
      return new VillageState(destination, parcels);
    }
  }
}


//运行机器人，直到所有包裹都送达（===0）
function runRobot(state, robot, memory) {
  for (let turn = 0; ; turn++) {
    if (state.parcels.length === 0) {
      console.log(`Done in ${turn} turns`);
      break;
    }
    let action = robot(state, memory);
    state = state.move(action.direction);
    memory = action.memory;
    console.log(`Moved to ${action.direction}`);
  }
}

// 从数组中随机选取元素的辅助函数
function randomPick(array) {
  let choice = Math.floor(Math.random() * array.length);
  return array[choice];
}

// 机器人行走逻辑函数，随机行走 , 机器人的动作
function randomRobot(state) {
  return {direction: randomPick(roadGraph[state.place])};
}

//mailRoute行走逻辑
const mailRoute = [
  "Alice's House", "Cabin", "Alice's House", "Bob's House", "Town Hall", "Daria's House", "Ernie's House",
  "Grete's House", "Shop", "Grete's House", "Farm", "Marketplace", "Post Office"
];

//memory是切割了走过的地点后的数组
function routeRobot(state, memory) {
  if (memory.length === 0) {
    memory = mailRoute;
  }
  return {direction: memory[0], memory: memory.slice(1)};
}

//pathfinding逻辑 , 路径查找算法     leetcode的一道算法题 从一点到另一点到最短距离 , 当然可以用Dijkstra，但是我不会
//画图！ 递归+记忆化搜索
function findRoute(graph, from, to) { //用route记录路径
  let work = [{at: from, route: []}];
  for (let i = 0; i < work.length; i++) {
    let {at, route} = work[i];
    for (let place of graph[at]) {
      if (place === to) return route.concat(place);
      if (!work.some(w => w.at === place)) {
        work.push({at: place, route: route.concat(place)});
      }
    }
  }
}

function goalOrientedRobot({place, parcels}, route) {
  if (route.length === 0) {
    let parcel = parcels[0];
    if (parcel.place !== place) {
      //去送包裹
      route = findRoute(roadGraph, place, parcel.place);    //address : 包裹所处位置， place：目的地
    } else {
      //去拿包裹
      route = findRoute(roadGraph, place, parcel.address);
    }
  }
  return {direction: route[0], memory: route.slice(1)};
}

// 生成包裹们
VillageState.random = function (parcelCount = 5) {
  let parcels = [];
  for (let i = 0; i < parcelCount; i++) {
    let address = randomPick(Object.keys(roadGraph));
    let place;
    do {
      place = randomPick(Object.keys(roadGraph));
    } while (place === address);
    parcels.push({place, address});
  }
  return new VillageState("Post Office", parcels);
};

runRobot(VillageState.random(), randomRobot);


//作业1  Measuring a robot , 测试，对比几个机器人逻辑
function countSteps(state, robot, memory) {
  for (let steps = 0;; steps++) {
    if (state.parcels.length === 0) return steps;
    let action = robot(state, memory);
    state = state.move(action.direction);
    memory = action.memory;
  }
}

function compareRobots(robot1, memory1, robot2, memory2) {
  let total1 = 0, total2 = 0;
  for (let i = 0; i < 100; i++) {
    let state = VillageState.random();
    total1 += countSteps(state, robot1, memory1);
    total2 += countSteps(state, robot2, memory2);
  }
  console.log(`Robot 1 needed ${total1 / 100} steps per task`);
  console.log(`Robot 2 needed ${total2 / 100}`)
}

compareRobots(routeRobot, [], goalOrientedRobot, []);

//作业2 Robot Efficiency  增加了权重， 优化方案基于：
//感觉牵涉到图论算法了
function lazyRobot({place, parcels}, route) {
  if (route.length === 0) {
    // Describe a route for every parcel
    let routes = parcels.map(parcel => {
      if (parcel.place !== place) {
        return {route: findRoute(roadGraph, place, parcel.place),
          pickUp: true};
      } else {
        return {route: findRoute(roadGraph, place, parcel.address),
          pickUp: false};
      }
    });

    // This determines the precedence a route gets when choosing.
    // Route length counts negatively, routes that pick up a package
    // get a small bonus.
    function score({route, pickUp}) {
      return (pickUp ? 0.5 : 0) - route.length;
    }
    route = routes.reduce((a, b) => score(a) > score(b) ? a : b).route;
  }

  return {direction: route[0], memory: route.slice(1)};
}

runRobotAnimation(VillageState.random(), lazyRobot, []);

//需求3 Persistent Group , 模拟js的set，但不修改原数据，而是生成新的group
class PGroup {
  constructor(members) {
    this.members = members;
  }

  add(value) {
    if (this.has(value)) return this;
    return new PGroup(this.members.concat([value]));
  }

  delete(value) {
    if (!this.has(value)) return this;
    return new PGroup(this.members.filter(m => m !== value));
  }

  has(value) {
    return this.members.includes(value);
  }
}

PGroup.empty = new PGroup([]);

let a = PGroup.empty.add("a");
let ab = a.add("b");
let b = ab.delete("a");

console.log(b.has("b"));
// → true
console.log(a.has("b"));
// → false
console.log(b.has("a"));
// → false