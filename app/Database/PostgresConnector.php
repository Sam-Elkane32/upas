<?php

namespace App\Database;

use Illuminate\Database\Connectors\PostgresConnector as BasePostgresConnector;

class PostgresConnector extends BasePostgresConnector
{
    protected function getDsn(array $config)
    {
        $dsn = parent::getDsn($config);

        if (! empty($config['connect_timeout'])) {
            $dsn .= ';connect_timeout='.(int) $config['connect_timeout'];
        }

        return $dsn;
    }
}
