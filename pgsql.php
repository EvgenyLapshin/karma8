<?php

function query(string $query, array $params): array
{
    $pgsql = mysqli_connect('localhost', 'user', 'password', 'database');
    $values = $params ? ' VALUES ($' . implode(', $', $params) . ')' : '';
    $result = mysqli_query($pgsql, mysqli_real_escape_string($pgsql, $query . $values));

    return mysqli_fetch_all($result);
}
