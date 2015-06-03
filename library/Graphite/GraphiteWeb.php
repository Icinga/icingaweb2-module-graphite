<?php

namespace Icinga\Module\Graphite;

use Icinga\Module\Graphite\GraphTemplate;
use Icinga\Module\Graphite\GraphiteQuery;

class GraphiteWeb
{
    protected $baseUrl;

    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    public function select()
    {
        return new GraphiteQuery($this);
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function listMetrics($filter)
    {
        $res = json_decode(
            file_get_contents(
                $this->baseUrl . '/metrics/expand?query=' . $filter
            )
        );
        natsort($res->results);
        return array_values($res->results);
    }
}
