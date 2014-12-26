目录结构：
---
---
ZMC----界面主代码目录

script----开发配合脚本


一.install setups:
---
---

### 1.克隆代码
首先clone代码到需要部署的地方

### 2.数据库配置
首先需要先修改默认mysql密码：

    /opt/zmanda/amanda/bin/reset_mysql_password.sh  newpassword  newpassword
    
然后进入ZMC自带的mysql,密码用前面修改好的：

    /opt/zmanda/amanda/mysql/bin/mysql -uzmc -pnewpassword
新建需要的数据表
```

 CREATE TABLE `gci_drives_owner` (
`id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'id',
`owner` varchar(100) DEFAULT NULL COMMENT 'owner',
`drives` varchar(200) DEFAULT NULL COMMENT 'drives id',
`create_date` timestamp NULL DEFAULT NULL COMMENT 'create date',
PRIMARY KEY (`id`),
unique(`drives`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8；
```


######2014safdsfdsgfdgfdgfdgfd


####测试加入sshkey


