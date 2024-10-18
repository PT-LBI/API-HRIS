<?php
protected $routeMiddleware = [
  // middleware lainnya
  'token.expired' => \App\Http\Middleware\TokenExpiredMiddleware::class,
];
