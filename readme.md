
```php
require_once __DIR__ . "/vendor/autoload.php";

$config = ["tpl_dir" => __DIR__."/view","cache_dir"=>__DIR__."/cache"];

\epii\template\View::setEngine($config,\epii\template\engine\PhpViewEngine::class);



$data = ["name" => "张三", "age" => "222"];
\epii\template\View::display("a/index", $data);

```