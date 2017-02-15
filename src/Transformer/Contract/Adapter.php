<?php
/**
 * Created by PhpStorm.
 * User: keal
 * Date: 2017/2/15
 * Time: 上午11:41
 */

namespace Caikeal\Transformer\Contract;

use Caikeal\Transformer\Binding;
use Illuminate\Http\Request;

interface Adapter
{
    /**
     * Transform a response with a transformer.
     *
     * @param mixed                               $response
     * @param object                              $transformer
     * @param \Caikeal\Transformer\Binding $binding   $binding
     * @param \Illuminate\Http\Request            $request
     *
     * @return array
     */
    public function transform($response, $transformer, Binding $binding, Request $request);
}