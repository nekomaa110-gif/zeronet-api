<?php

namespace App\Exceptions;

use RuntimeException;

class UserAlreadyExistsException extends RuntimeException
{
    public function __construct(string $username)
    {
        parent::__construct("User '{$username}' sudah ada.");
    }
}
