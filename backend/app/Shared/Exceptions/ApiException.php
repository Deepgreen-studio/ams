<?php

namespace App\Shared\Exceptions;

use App\Shared\Responses\ApiResponse;
use Exception;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    protected int $statusCode;

    protected mixed $errors;

    protected ?string $errorCode;

    public function __construct(
        string $message = 'Unexpected Error',
        int $statusCode = 500,
        mixed $errors = null,
        ?Exception $previous = null,
        ?string $errorCode = null,
    ) {
        parent::__construct($message, $statusCode, $previous);

        $this->statusCode = $statusCode;
        $this->errors = $errors;
        $this->errorCode = $errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): mixed
    {
        return $this->errors;
    }

    public function render(): JsonResponse
    {
        return ApiResponse::error(
            $this->getMessage(),
            $this->getStatusCode(),
            $this->getErrors(),
            $this->errorCode,
        );
    }
}
