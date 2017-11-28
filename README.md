# ZxpDatabase
compact, lightweight, in common use. PHP, MySQL, SQLite, PDO, database

数据库常用操作类  
简洁，轻量，灵活

## 示例

### 基本操作

#### 1. 实例化数据库对象
```php
<?php
$host = 'localhost';  
$dbname = 'zxp';
$username = 'dbuser';
$password = 'dbpass';`

$dsn = "mysql:host=$host;dbname=$dbname";

$mysql = new ZxpPDO($dsn, $username, $password);
```

#### 2. 建立数据库
```php
<?php
$sql = 'CREATE SCHEMA IF NOT EXISTS `zxp` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ;';
$mysql->exec($sql);
$sql = 'USE `zxp` ;';
$mysql->exec($sql);
```

#### 3. 建表

```php
<?php
$sql = "CREATE TABLE IF NOT EXISTS `zxp` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `name` VARCHAR(16),
        `sex` TINYINT,
        `age` TINYINT,
        `content` TEXT,
        `addtime` DATETIME,
        `modified` DATETIME,
        PRIMARY KEY (`id`)
);";
$mysql->exec($sql);
```

#### 4. 插入数据
```php
<?php
$data = [
    'name' => 'zxp',
    'age' => '33',
    'sex' => '0',
    'content' => 'hello ZxpPDO',
    'addtime' => date('Y-m-d H:i:s')
];
$insertId = $mysql->insert('zxp', $data);
// $insertId => 1
```

#### 5. 更新数据
```php
<?php
$data = [
    'name' => 'zhangxianpeng',
    'age' => '35',
    'sex' => '0',
    'content' => '你好',
    'modified' => date('Y-m-d H:i:s')
];
$where = ['id' => 1];
$mysql->update('zxp', $data, $where);
```

#### 6. 删除数据
```php
<?php
$where = ['id' => 1];
$mysql->delete('zxp', $where);
```

#### 7. 清空数据
```php
<?php
$mysql->delete('zxp', 'all');
```

#### 8. 批量插入数据
```php
<?php
$now = date('Y-m-d H:i:s');
$data = [
    [
        'name' => 'zhangxp',
        'age' => '25',
        'sex' => '0',
        'content' => 'hello',
        'addtime' => $now
    ],
    [
        'name' => 'zhangxianpeng',
        'age' => '27',
        'sex' => '0',
        'content' => '你好',
        'addtime' => $now
    ],
    [
        'name' => 'xianpeng',
        'age' => '28',
        'sex' => '0',
        'content' => 'hello world',
        'addtime' => $now
    ]
];
$insertIds = $mysql->insertBatch('zxp', $data);
// $insertIds => [2, 3, 4]
```

#### 9. 批量更新数据
```php
<?php
$data = [
    [
        [
            'name' => 'zhangxp_mdf',
            'age' => 26
        ],
        [
            'id' => 2
        ]
    ],
    [
        [
            'name' => 'zxp_mdf',
            'age' => 25
        ],
        [
            'id' => 3
        ]
    ],
    [
        [
            'name' => 'xianpeng_mdf',
            'age' => 29
        ],
        [
            'id' => 4
        ]
    ]
];
$mysql->updateBatch('zxp', $data);
```

### 查询记录

#### 1. 简单查询
```php
<?php
$rs = $mysql->select('zxp', '*');

// 生成的 SQL 语句 : select * from `zxp`

// $rs =>
// [
//     [
//         'name' => 'zhangxp_mdf',
//         'age' => 26,
//         'sex' => '0',
//         'content' => 'hello',
//         'addtime' => '2017-11-01 13:27:22',
//         'modified' => '2017-11-01 13:36:05'
//     ],
//     [
//         'name' => 'zxp_mdf',
//         'age' => '25',
//         'sex' => '0',
//         'content' => '你好',
//         'addtime' => '2017-11-01 13:27:22',
//         'modified' => '2017-11-01 13:36:05'
//     ],
//     [
//         'name' => 'xianpeng_mdf',
//         'age' => '29',
//         'sex' => '0',
//         'content' => 'hello world',
//         'addtime' => '2017-11-01 13:27:22',
//         'modified' => '2017-11-01 13:36:05'
//     ]
// ]
```

#### 2. 条件查询
```php
<?php
$where = [
    'name' => 'zxp_mdf'
];
$rs = $mysql->select('zxp', '*', $where);

// 生成的 SQL 语句 :
// select * from `zxp` where `name` = ?
// 被执行的 SQL 语句绑定的参数：
// ["zxp_mdf"]

// $rs =>
// [
//     [
//         'id' => 3,
//         'name' => 'zxp_mdf ',
//         'sex' => 0,
//         'age' => 25,
//         'content' => '你好',
//         'addtime' => '2017-11-01 13:27:22',
//         'modified' => '2017-11-01 13:36:05'
//     ]
// ]
```

#### 3. 指定字段
```php
<?php
$where = [
    'id' => 3
];
$rs = $mysql->select('zxp', ['name', 'sex', 'age'], $where);

// 生成的 SQL 语句 :
// select `name`, `sex`, `age` from `zxp` where `id` = ?
// 被执行的 SQL 语句绑定的参数：
// [3]

// $rs =>
// [
//     'name' => 'zxp_mdf',
//     'sex' => 0,
//     'age' => 25
// ]
```

#### 4. !=, <>, like
```php
<?php
$where = [
    'age' => ['!=', 26]
]
$rs = $mysql->select('zxp', ['name', 'sex', 'age'], $where);

// 生成的 SQL 语句 :
// select `name`, `sex`, `age` from `zxp` where `age` != ?
// 被执行的 SQL 语句绑定的参数：
// [26]

// $rs =>
// [
//     [
//         'name' => 'zxp_mdf',
//         'sex' => 0,
//         'age' => 25
//     ],
//     [
//         'name' => 'xianpeng_mdf',
//         'sex' => 0,
//         'age' => 29
//     ]
// ]

$where = [
    'age' => ['<>', 26]
];
$rs = $mysql->select('zxp', ['name', 'sex', 'age'], $where);
// 生成的 SQL 语句 :
// select `name`, `sex`, `age` from `zxp` where `age` <> ?
// 被执行的 SQL 语句绑定的参数：
// [26]

$where = [
    'name' => ['like', 'zxp%']
];
$rs = $mysql->select('zxp', ['name', 'sex', 'age'], $where);
// 生成的 SQL 语句 :
// select `name`, `sex`, `age` from `zxp` where `name` like ?
// 被执行的 SQL 语句绑定的参数：
// ["zxp%"]
```

#### 5. 单字段 or
```php
<?php
$where = [
    'name' => ['=', 'zhangxp_mdf', 'or' => ['zxp_mdf', 'xianpeng_mdf']]
];
$rs = $mysql->select('zxp', ['name', 'sex', 'age'], $where);

// 生成的 SQL 语句 :
// select `name`, `sex`, `age` from `zxp` where (`name` = ? or `name` = ? or `name` = ?)
// 被执行的 SQL 语句绑定的参数：
// ["zhangxp_mdf","zxp_mdf","xianpeng_mdf"]

// $rs =>
// [
//     [
//         'name' => 'zhangxp_mdf',
//         'sex' => 0,
//         'age' => 26
//     ],
//     [
//         'name' => 'zxp_mdf',
//         'sex' => 0,
//         'age' => 25
//     ],
//     [
//         'name' => 'xianpeng_mdf',
//         'sex' => 0,
//         'age' => 29
//     ]
// ]

$where = [
    'name' => ['like', '%xp%', 'or' => ['zxp%', '%_mdf']]
];
$rs = $mysql->select('zxp', ['name', 'sex', 'age'], $where);
// 生成的 SQL 语句 :
// select `name`, `sex`, `age` from `zxp` where (`name` like ? or `name` like ? or `name` like ?)
// 被执行的 SQL 语句绑定参数：
// ["%xp%","zxp%","%_mdf"]
```

#### 6. >, >=, <, <=
```php
<?php
$where = [
    'age' => ['>', 25]
];
$rs = $mysql->select('zxp', ['name', 'sex', 'age'], $where);
// 生成的 SQL 语句 :
// select `name`, `sex`, `age` from `zxp` where `age` > ?
// 被执行的 SQL 语句绑定参数：
// [25]
```

#### 7. 单字段 and
```php
<?php
$where = [
    'age' => ['>', 25, 'and' => ['<', 29]]
];
$rs = $mysql->select('zxp', ['name', 'sex', 'age'], $where);
// 生成的 SQL 语句 :
// select `name`, `sex`, `age` from `zxp` where ( `age` > ? and `age` < ?)
// 被执行的 SQL 语句绑定参数：
// [25,29]
```

#### 8. 多字段
```php
<?php
$where = [
    'name' => ['like', 'zxp%'],
    'age' => ['>=', 25],
    'date(`addtime`)' => '2017-11-01'
];
$rs = $mysql->select('zxp', ['name', 'sex', 'age'], $where);
// 生成的 SQL 语句 :
// select `name`, `sex`, `age` from `zxp` where `name` like ? and `age` >= ? and date(`addtime`) = ?
// 被执行的 SQL 语句绑定参数：
// ["zxp%",25,"2017-11-01"]
```

#### 9. 多字段 or
```php
<?php
$where = [
    'name' => ['like', 'zxp%', 'joint' => 'or'],
    'age' => ['>', 26]
];
$rs = $mysql->select('zxp', ['name', 'sex', 'age'], $where);
// 生成的 SQL 语句 :
// select `name`, `sex`, `age` from `zxp` where `name` like ? or `age` > ?
// 被执行的 SQL 语句绑定参数：
// ["zxp%",26]
```

#### 10. 括号的使用
```php
<?php
$where = [
    'name' => ['like', 'zxp%', 'joint' => 'or', 'prefix' => '('],
    'age' => ['>', 26, 'suffix' => ')'],
    'sex' => 0
];
$rs = $mysql->select('zxp', ['name', 'sex', 'age'], $where);
// 生成的 SQL 语句 :
// select `name`, `sex`, `age` from `zxp` where (`name` like ? or `age` > ?) and `sex` = ?
// 被执行的 SQL 语句绑定参数：
// ["zxp%",26,0]
```

#### 11. between, not between
```php
<?php
$where = [
    'age' => ['between', 26, 35]
];
$rs = $mysql->select('zxp', ['name', 'sex', 'age'], $where);
// 生成的 SQL 语句 :
// select `name`, `sex`, `age` from `zxp` where `age` between ? and ?
// 被执行的 SQL 语句绑定参数：
// [26,35]
```

#### 12. in, not in
```php
<?php
$where = [
    'age' => ['in', [25, 29]]
];
$rs = $mysql->select('zxp', ['name', 'sex', 'age'], $where);
// 生成的 SQL 语句 :
// select `name`, `sex`, `age` from `zxp` where `age` in (?, ?)
// 被执行的 SQL 语句绑定参数：
// [25,29]
```

#### 13. 自定义 SQL 语句
```php
<?php
$sql = 'select `name`, `sex`, `age` from `zxp` where `id` in (?, ?, ?) group by `sex` order by age limit 0,3';
$vals = [2, 3, 4];
$rs = $mysql->query($sql, $vals);
```

### 更多查询条件

#### 1. order by
```php
<?php
$where = [
    'id' => ['in', [2, 3, 4]]
];
$more = [
    'order by' => '`age` desc'
];
$rs = $mysql->select('zxp', ['name', 'sex', 'age'], $where, $more);
// 生成的 SQL 语句 :
// select `name`, `sex`, `age` from `zxp` where `id` in (?, ?, ?) order by `age` desc
// 被执行的 SQL 语句绑定参数：
// [2,3,4]
```

#### 2. limit
```php
<?php
$where = [
    'id' => ['in', [2, 3, 4]]
];
$more = [
    'limit' => [1, 2]
];
$rs = $mysql->select('zxp', ['name', 'sex', 'age'], $where, $more);
// 生成的 SQL 语句 :
// select `name`, `sex`, `age` from `zxp` where `id` in (?, ?, ?) limit 1,2
// 被执行的 SQL 语句绑定参数：
// [2,3,4]
```

#### 3. group by
```php
<?php
$where = [
    'id' => ['in', [2, 3, 4]]
];
$more = [
    'group by' => 'age'
];
$rs = $mysql->select('zxp', ['name', 'sex', 'age'], $where, $more);
// 生成的 SQL 语句 :
// select `name`, `sex`, `age` from `zxp` where `id` in (?, ?, ?) group by `age`
// 被执行的 SQL 语句绑定参数：
// [2,3,4]
```

#### 4. 联合
```php
<?php
$where = [
    'id' => ['in', [2, 3, 4]]
];
$more = [
    'group by' => 'sex',
    'order by' => 'age',
    'limit' => [0, 3]
];
$rs = $mysql->select('zxp', ['name', 'sex', 'age'], $where, $more);
// 生成的 SQL 语句 :
// select `name`, `sex`, `age` from `zxp` where `id` in (?, ?, ?) group by `sex` order by age limit 0,3
// 被执行的 SQL 语句绑定参数：
// [2,3,4]
```
