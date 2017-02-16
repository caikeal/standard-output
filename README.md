# 关于`Laravel`项目规范输出和错误提示（测试版本）
# 安装

# 使用
## 需要在`Exceptions/Handler.php`中引入`Caikeal\Exceptions\HandlerTrait`该方法，
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
## 在所有`Controller`的基类中引入该方法，如下：
```
    protected function response()
    {
        return app('output.response');
    }
```

## 在返回时可以用该方法代替response返回：`$this->reponse()`的方法

所有方法如下：
+ 正确：
    - model层，且带有transformer验证的单列数据：`$this->response()->item();`
    - model层，且带有transformer验证的多列数据：`$this->response()->collection();`
    - model层，且带有transformer验证的分页数据：`$this->response()->paginator();`
    - 任意：`$this->response()->array();`

+ 错误：
    - 404错误：`$this->response()->errorNotFound()`
    - 400错误：`$this->response()->errorBadRequest()`
    - 403错误：`$this->response()->errorForbidden()`
    - 500错误：`$this->response()->errorInternal()`
    - 401错误：`$this->response()->errorUnauthorized()`
    - 405错误：`$this->response()->errorMethodNotAllowed()`
    - 任意错误：`$this->response()->error()`
 
## `Transformer`的数据格式转换
在所有`Transformer`类中必须继承`League\Fractal\TransformerAbstract`，使用如下：
```
    namespace App\Transformer;
    
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

# 感谢
主要借鉴和引用了[Dingo/api](https://github.com/dingo/api.git)和[league/fractal](https://github.com/thephpleague/fractal.git)
