<?php
/**
 * Created by PhpStorm.
 * User: keal
 * Date: 2017/2/15
 * Time: 上午11:41
 */

namespace Caikeal\Output\Transformer\Contract;

use Caikeal\Output\Transformer\Binding;
use Illuminate\Http\Request;

interface Adapter
{
    /**
     * Transform a response with a transformer.
     *
     * @param mixed                               $response
     * @param object                              $transformer
     * @param \Caikeal\Output\Transformer\Binding $binding   $binding
     * @param \Illuminate\Http\Request            $request
     *
     * @return array
     */
    public function transform($response, $transformer, Binding $binding, Request $request);
}