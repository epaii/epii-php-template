
install

```json
{
    "require": {
        "epii/template-engine": ">=0.0.1"
    }
}
```


```php
require_once __DIR__ . "/vendor/autoload.php";

$config = ["tpl_dir" => __DIR__."/view","cache_dir"=>__DIR__."/cache"];

\epii\template\View::setEngine($config);



$data = ["name" => "张三", "age" => "222","key_name"=>"name1","info"=>["name1"=>"李四"],
    "list"=>[
        ["name"=>"任0"],
        ["name"=>"任1"],
        ["name"=>"任2"],
        ["name"=>"任3"]
    ]
];
\epii\template\View::display("a/index", $data);

```

在模板文件中 a/index.php

```
{$name} ,{$info.name1},{$info.$key_name}//方法一
<?php echo $name; ?>, <?php echo $info[$key_name]; ?>//方法二
<?=$name?> ////方法三
```

函数支持

```
{$time_int|date,Y-m-d H:i:s,$0} //$0 代表当前值，逗号隔开为参数顺序
```

也可以

```
{:date,Y-m-d H:i:s,$time_int} //
```

遍历与其他

```
{loop $list}

    {$key},{$value.name}

{/loop}


{loop $list $mykey=>$myvalue}
{$mykey},{$myvalue}
{/loop}

{foreach $list}

    {$key},{$value.name}

{/foreach}


{foreach $list $mykey=>$myvalue}
{$mykey},{$myvalue}
{/foreach}


{if  $name=="aaa" }
    1111111111
{elseif $name=="cccc"}
    333333
{else}
   00000000
{/if}

```

> 支持php原生所有语法

支持第三方引擎 

```
View::setEngine($config,支持自定义模板引擎类,默认为\epii\template\engine\EpiiViewEngine::class);
// 如果使用纯php本身语言为模板，只需使用\epii\template\engine\PhpViewEngine::class  即可
//第三方类只需实现 接口 epii\template\i\IEpiiViewEngine

```