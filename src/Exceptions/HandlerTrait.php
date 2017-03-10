<?php
/**
 * Created by PhpStorm.
 * User: keal
 * Date: 2017/2/15
 * Time: 上午11:17
 */

namespace Caikeal\Output\Exceptions;

use Exception;
use Illuminate\Http\Response;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

trait HandlerTrait
{
    public function handlerApi(Exception $exception) {
        $e = $this->prepareException($exception);
        $response = $this->prepareReplacements($e);
        return new Response($response, $this->getStatusCode($e), $this->getHeaders($e));
    }

    /**
     * 将错误转换成对应的输出格式.
     *
     * @param Exception $exception
     * @return array
     */
    protected function prepareReplacements(Exception $exception)
    {
        $statusCode = $this->getStatusCode($exception);
        if (! $message = $exception->getMessage()) {
            $message = sprintf('%d %s', $statusCode, Response::$statusTexts[$statusCode]);
        }
        $replacements = [
            'message' => $message,
            'status_code' => $statusCode,
            'code' => $this->convertCode($exception)
        ];

        // 登录验证
        if ($exception instanceof AuthenticationException) {
            $replacements['message'] = '您尚未登录';
        }

        // 格式验证处理
        if ($exception instanceof ValidationException && !$exception->validator->errors()->isEmpty()) {
            $replacements['message'] = $exception->validator->errors()->first();
            $replacements['errors'] = $exception->validator->errors()->getMessages();
        }

        if ($this->runningInDebugMode()) {
            $replacements['debug'] = [
                'line' => $exception->getLine(),
                'file' => $exception->getFile(),
                'class' => get_class($exception),
                'trace' => explode("\n", $exception->getTraceAsString()),
            ];
        }
        return $replacements;
    }

    /**
     * Get the status code from the exception.
     *
     * @param \Exception $exception
     *
     * @return int
     */
    protected function getStatusCode(Exception $exception)
    {
        if ($exception instanceof  AuthenticationException) {
            return 401;
        } elseif ($exception instanceof ValidationException) {
            return 422;
        } elseif ($exception instanceof HttpExceptionInterface) {
            return $exception->getStatusCode();
        } else {
            return 500;
        }
    }

    /**
     * Get the headers from the exception.
     *
     * @param \Exception $exception
     *
     * @return array
     */
    protected function getHeaders(Exception $exception)
    {
        return $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];
    }

    /**
     * 将业务码code转换成对应6位字符串.
     *
     * @param Exception $exception
     * @return int|mixed|string
     */
    protected function convertCode(Exception $exception) {
        // 判断code是否存在，且满足6位限制
        $code = $exception->getCode();
        $statusCode = $this->getStatusCode($exception);
        if ($code) {
            $code = str_pad($code, 6, '0', STR_PAD_RIGHT);
        } else {
            $code = str_pad($statusCode, 6, '0', STR_PAD_RIGHT);
        }

        return $code;
    }

    /**
     * 判断应用环境.
     *
     * @return mixed
     */
    protected function runningInDebugMode()
    {
        return Config('app.debug');
    }
}