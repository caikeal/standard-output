<?php
/**
 * Created by PhpStorm.
 * User: keal
 * Date: 2017/2/15
 * Time: 下午2:47
 */

namespace Caikeal\Response;

use ArrayObject;
use UnexpectedValueException;
use Illuminate\Http\JsonResponse;
use Caikeal\Transformer\Binding;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Caikeal\Transformer\Factory as TransformerFactory;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException;

class Response extends IlluminateResponse
{
    /**
     * The exception that triggered the error response.
     *
     * @var \Exception
     */
    public $exception;

    /**
     * Transformer binding instance.
     *
     * @var \Caikeal\Transformer\Binding
     */
    protected $binding;

    /**
     * Array of registered formatters.
     *
     * @var array
     */
    protected static $formatters = [];

    /**
     * Transformer factory instance.
     *
     * @var \Caikeal\Transformer\Factory
     */
    protected static $transformer;

    /**
     * Create a new response instance.
     *
     * @param mixed                          $content
     * @param int                            $status
     * @param array                          $headers
     * @param \Caikeal\Transformer\Binding $binding
     *
     * @return void
     */
    public function __construct($content, $status = 200, $headers = [], Binding $binding = null)
    {
        parent::__construct($content, $status, $headers);
        $this->binding = $binding;
    }

    /**
     * Make an API response from an existing Illuminate response.
     *
     * @param \Illuminate\Http\Response $old
     *
     * @return \Caikeal\Response\Response
     */
    public static function makeFromExisting(IlluminateResponse $old)
    {
        $new = static::create($old->getOriginalContent(), $old->getStatusCode());
        $new->headers = $old->headers;
        return $new;
    }

    /**
     * Make an API response from an existing JSON response.
     *
     * @param \Illuminate\Http\JsonResponse $json
     *
     * @return \Caikeal\Response\Response
     */
    public static function makeFromJson(JsonResponse $json)
    {
        $new = static::create(json_decode($json->getContent(), true), $json->getStatusCode());
        $new->headers = $json->headers;
        return $new;
    }

    /**
     * Morph the API response to the appropriate format.
     *
     * @param string $format
     *
     * @return \Caikeal\Response\Response
     */
    public function morph($format = 'json')
    {
        $this->content = $this->getOriginalContent();

        if (isset(static::$transformer) && static::$transformer->transformableResponse($this->content)) {
            $this->content = static::$transformer->transform($this->content);
        }
        $formatter = static::getFormatter($format);
        $defaultContentType = $this->headers->get('Content-Type');
        $this->headers->set('Content-Type', $formatter->getContentType());

        if ($this->content instanceof EloquentModel) {
            $this->content = $formatter->formatEloquentModel($this->content);
        } elseif ($this->content instanceof EloquentCollection) {
            $this->content = $formatter->formatEloquentCollection($this->content);
        } elseif (is_array($this->content) || $this->content instanceof ArrayObject || $this->content instanceof Arrayable) {
            $this->content = $formatter->formatArray($this->content);
        } else {
            $this->headers->set('Content-Type', $defaultContentType);
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setContent($content)
    {
        // Attempt to set the content string, if we encounter an unexpected value
        // then we most likely have an object that cannot be type cast. In that
        // case we'll simply leave the content as null and set the original
        // content value and continue.
        try {
            return parent::setContent($content);
        } catch (UnexpectedValueException $exception) {
            $this->original = $content;
            return $this;
        }
    }

    /**
     * Get the formatter based on the requested format type.
     *
     * @param string $format
     *
     * @throws \RuntimeException
     *
     * @return \Caikeal\Response\Format\Format
     */
    public static function getFormatter($format)
    {
        if (! static::hasFormatter($format)) {
            throw new NotAcceptableHttpException('Unable to format response according to Accept header.');
        }
        return static::$formatters[$format];
    }

    /**
     * Determine if a response formatter has been registered.
     *
     * @param string $format
     *
     * @return bool
     */
    public static function hasFormatter($format)
    {
        return isset(static::$formatters[$format]);
    }

    /**
     * Set the response formatters.
     *
     * @param array $formatters
     *
     * @return void
     */
    public static function setFormatters(array $formatters)
    {
        static::$formatters = $formatters;
    }

    /**
     * Add a response formatter.
     *
     * @param string                                 $key
     * @param \Caikeal\Response\Format\Format $formatter
     *
     * @return void
     */
    public static function addFormatter($key, $formatter)
    {
        static::$formatters[$key] = $formatter;
    }

    /**
     * Set the transformer factory instance.
     *
     * @param \Caikeal\Transformer\Factory $transformer
     *
     * @return void
     */
    public static function setTransformer(TransformerFactory $transformer)
    {
        static::$transformer = $transformer;
    }

    /**
     * Get the transformer instance.
     *
     * @return \Caikeal\Transformer\Factory
     */
    public static function getTransformer()
    {
        return static::$transformer;
    }

    /**
     * Add a meta key and value pair.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return \Caikeal\Response\Response
     */
    public function addMeta($key, $value)
    {
        $this->binding->addMeta($key, $value);
        return $this;
    }

    /**
     * Add a meta key and value pair.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return \Caikeal\Response\Response
     */
    public function meta($key, $value)
    {
        return $this->addMeta($key, $value);
    }

    /**
     * Set the meta data for the response.
     *
     * @param array $meta
     *
     * @return \Caikeal\Response\Response
     */
    public function setMeta(array $meta)
    {
        $this->binding->setMeta($meta);
        return $this;
    }

    /**
     * Get the meta data for the response.
     *
     * @return array
     */
    public function getMeta()
    {
        return $this->binding->getMeta();
    }

    /**
     * Add a cookie to the response.
     *
     * @param \Symfony\Component\HttpFoundation\Cookie|mixed $cookie
     *
     * @return \Caikeal\Response\Response
     */
    public function cookie($cookie)
    {
        return $this->withCookie($cookie);
    }

    /**
     * Add a header to the response.
     *
     * @param string $key
     * @param string $value
     * @param bool   $replace
     *
     * @return \Caikeal\Response\Response
     */
    public function withHeader($key, $value, $replace = true)
    {
        return $this->header($key, $value, $replace);
    }

    /**
     * Set the response status code.
     *
     * @param int $statusCode
     *
     * @return \Caikeal\Response\Response
     */
    public function statusCode($statusCode)
    {
        return $this->setStatusCode($statusCode);
    }

    /**
     * Add morph.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function prepare(\Symfony\Component\HttpFoundation\Request $request)
    {
        $response = self::morph();
        return parent::prepare($request);
    }

    /**
     * Add Headers.
     *
     * @param array $headers
     * @return $this
     */
    public function addHeaders(array $headers=[])
    {
        if (count($headers)) {
            foreach ($headers as $key => $value) {
                $this->headers->set(strtoupper($key), $value);
            }
        }

        return $this;
    }
}