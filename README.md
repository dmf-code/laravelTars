# laravel-tars 集合常用业务组件

### 代码编写按照这个架构进行

![架构图](./static/structure.png)

### 微服务

1. 允许使用 `join` , 只能在同一个库使用。因为公司体量较小，很多基础服务不足，然后需求却
非常复杂。比如： 三个表中每个表都要提供一个字段进行查询，不是用 `join` 那么就是要自己在逻辑层
实现这个逻辑。这样子效率是非常低下的，这里的需求其实都是同一个库的表，不同的库只能是在逻辑层处理
了。

2. `DAO` 层在 `Laravel` 中其实就是 `Model` , 所以不需要特意去编写，多写了反而笨重。
 

### 代码规范

1. 类使用大驼峰命名与文件名保持一致，类函数使用小驼峰 (PlayGame, playGame)

2. 非类函数命名统一采用蛇形命名 （play_game）

### 项目搭建

`src/config/tars.php` 文件中要对上在 `tarsweb` 后台的部署名称 

![协议](./static/proto.png)

`tarsweb` 后台

![tarsweb](./static/tarsweb_add_service.png)

这里直接使用 `php artisan tars:deploy` 生成压缩包，然后上传就可以了

