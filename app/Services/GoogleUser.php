<?php

namespace App\Services;

class GoogleUser
{
    public function __construct(
        public readonly string $id,
        public readonly string $email,
        public readonly string $name,
    ) {}
}
