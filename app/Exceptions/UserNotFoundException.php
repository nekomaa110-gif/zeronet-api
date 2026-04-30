<?php

namespace App\Exceptions;

use RuntimeException;

class UserNotFoundException extends RuntimeException
{
    public function __construct(string $username)
    {
        parent::__construct("User '{$username}' tidak ditemukan.");
    }
}
