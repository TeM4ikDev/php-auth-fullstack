<?php

declare(strict_types=1);

namespace App\Database;

final class Sql
{
    public const CREATE_USERS_TABLE = <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(32) NOT NULL DEFAULT 'USER',
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
)
SQL;

    public const INSERT_USER = 'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)';

    public const FIND_USER_BY_EMAIL = 'SELECT id, name, email, password_hash, role, created_at FROM users WHERE email = :email LIMIT 1';

    public const FIND_USER_BY_ID = 'SELECT id, name, email, password_hash, role, created_at FROM users WHERE id = :id LIMIT 1';
}
