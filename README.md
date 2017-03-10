# 关于`Laravel`项目规范输出和错误提示（测试版本）

# 安装
试用Laravel >= 5.1.*

`composer require keal/laravel-output`

# 使用
## 注册ServiceProvider
在`app.php`中加入`Caikeal\Output\Provider\OutputServiceProvider::class`

## 引入错误处理
需要在`Exceptions/Handler.php`中引入`Caikeal\Output\Exceptions\HandlerTrait`该方法，
然后在改写下`render`方法，如下：
```
    public function render($request, Exception $exception)
    {
        if ($request->expectsJson()) {
            return $this->handlerApi($exception);
        }

        return parent::render($request, $exception);
    }
```
## 引入返回值基类
在所有`Controller`的基类中引入该方法，如下：
```
    protected function response()
    {
        return app('output.response');
    }
```

## 返回方法
在返回时可以用该方法代替response返回：`$this->reponse()`的方法
所有方法如下：
+ 正确值返回：
    - model层，且带有transformer验证的单列数据：`$this->response()->item([Model], [Transformer]);`
    - model层，且带有transformer验证的多列数据：`$this->response()->collection([Model], [Transformer]);`
    - model层，且带有transformer验证的分页数据：`$this->response()->paginator([Model], [Transformer]);`
    - 任意：`$this->response()->array([Model/Array]);`
    
    返回值样式：
    ```
      {
        "data": {
          "name": "keal"
        },
        "meta": {
          "type": "seller"
        }
      }
    ```

+ 错误值返回：
    - 404错误：`$this->response()->errorNotFound([$message], [$code])`
    - 400错误：`$this->response()->errorBadRequest([$message], [$code])`
    - 403错误：`$this->response()->errorForbidden([$message], [$code])`
    - 500错误：`$this->response()->errorInternal([$message], [$code])`
    - 401错误：`$this->response()->errorUnauthorized([$message], [$code])`
    - 405错误：`$this->response()->errorMethodNotAllowed([$message], [$code])`
    - 任意错误：`$this->response()->error([$message], [$statusCode], [$code])`
    
    返回值样式：
    ```
      {
        "code": "401000",
        "status_code": "401",
        "message": "Wrong."
      }
    ```
 
## `Transformer`的数据格式转换
在所有`Transformer`类中必须继承`League\Fractal\TransformerAbstract`，使用如下：
```
    namespace App\Transformers;
    
    use App\User;
    use League\Fractal\TransformerAbstract;
    
    class UserTransformer extends TransformerAbstract
    {
        public function transform(User $data){
            return [
                'id'=>intval($data['id']),
                'name'=>ucfirst($data['name'])
            ];
        }
    }
```

## `Artsian`创建`Transformer`类
创建Transformer：`php artisan make:transformer User`

附带Model名：`php artisan make:transformer User -m User`

或者`php artisan make:transformer User --model=User`

或者`php artisan make:transformer User --model=Model/User` 带位置，命令会自动转化成空间名，并在开头添加`App`

# 感谢
主要借鉴和引用了[Dingo/api](https://github.com/dingo/api.git)和[league/fractal](https://github.com/thephpleague/fractal.git)
