<?php

use App\Classes\Container\Params;

$request = $this->getContainer()->get('app.request');

$params = $request->query->get('params');

if (false !== $params) {
    if (in_array($params, Params::getParams())) {
        $request->cookies->add('p'.$params, !$request->cookies->get('p'.$params, $params), true);
    }
}
