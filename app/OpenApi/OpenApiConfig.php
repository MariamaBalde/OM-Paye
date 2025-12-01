<?php

namespace App\OpenApi;

/**
 * @OA\Info(
 *     title="Orange Money API",
 *     version="1.0.0",
 *     description="API for Orange Money payment system - Version 1"
 * )
 * @OA\Server(
 *     url="http://127.0.0.1:8000",
 *     description="Local Development Server"
 * )
 * @OA\Server(
 *     url="https://om-paye.onrender.com",
 *     description="Production API Server"
 * )
 * @OA\SecurityScheme(
 *     securityScheme="passport",
 *     type="apiKey",
 *     name="Authorization",
 *     in="header",
 *     description="Enter token in format: Bearer {token}"
 * )
 */
class OpenApiConfig {}
