<?php
declare(strict_types=1);

namespace Excel;

class Load
{
    /**
     * Get Instance.
     *
     * @param string $type
     *
     * @return Load
     */
    public static function getInstance($type): Load
    {
        $class = 'Excel\\' . ucfirst($type);

        return new $class();
    }
}