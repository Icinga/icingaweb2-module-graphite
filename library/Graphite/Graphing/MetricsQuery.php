<?php

namespace Icinga\Module\Graphite\Graphing;

use Icinga\Data\Fetchable;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Filterable;
use Icinga\Data\Queryable;
use Icinga\Exception\NotImplementedError;
use Icinga\Module\Graphite\GraphiteUtil;
use Icinga\Module\Graphite\Util\MacroTemplate;
use Icinga\Module\Graphite\Util\InternalProcessTracker as IPT;
use Icinga\Module\Monitoring\Object\Macro;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Util\Json;
use Icinga\Web\Url;
use InvalidArgumentException;

/**
 * Queries a {@link MetricsDataSource}
 */
class MetricsQuery implements Queryable, Filterable, Fetchable
{
    /**
     * @var MetricsDataSource
     */
    protected $dataSource;

    /**
     * The base metrics pattern
     *
     * @var MacroTemplate
     */
    protected $base;

    /**
     * Extension of {@link base}
     *
     * @var string[]
     */
    protected $filter = [];

    /**
     * The monitored object to render the graphs of
     *
     * @var MonitoredObject
     */
    protected $monitoredObject;

    /**
     * Constructor
     *
     * @param   MetricsDataSource   $dataSource
     */
    public function __construct(MetricsDataSource $dataSource)
    {
        $this->dataSource = $dataSource;
    }

    public function from($target, array $fields = null)
    {
        if ($fields !== null) {
            throw new InvalidArgumentException('Fields are not applicable to this kind of query');
        }

        try {
            $this->base = $target instanceof MacroTemplate ? $target : new MacroTemplate((string) $target);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException('Bad target', $e);
        }

        return $this;
    }

    public function applyFilter(Filter $filter)
    {
        throw new NotImplementedError(__METHOD__);
    }

    public function setFilter(Filter $filter)
    {
        throw new NotImplementedError(__METHOD__);
    }

    public function getFilter()
    {
        throw new NotImplementedError(__METHOD__);
    }

    public function addFilter(Filter $filter)
    {
        throw new NotImplementedError(__METHOD__);
    }

    public function where($condition, $value = null)
    {
        $this->filter[$condition] = $this->escapeMetricStep($value);

        return $this;
    }

    public function fetchAll()
    {
        $result = [];
        foreach ($this->fetchColumn() as $metric) {
            $result[] = (object) ['metric' => $metric];
        }

        return $result;
    }

    public function fetchRow()
    {
        $result = $this->fetchColumn();
        return empty($result) ? false : (object) ['metric' => $result[0]];
    }

    public function fetchColumn()
    {
        $filter = [];
        foreach ($this->base->getMacros() as $macro) {
            if (isset($this->filter[$macro])) {
                $filter[$macro] = $this->filter[$macro];
                continue;
            }

            if (strpos($macro, '.') === false) {
                continue;
            }

            $workaroundMacro = str_replace('.', '_', $macro);
            if ($workaroundMacro === 'service_name') {
                $workaroundMacro = 'service_description';
            }

            $result = Macro::resolveMacro($workaroundMacro, $this->monitoredObject);

            if ($result !== $workaroundMacro) {
                $filter[$macro] = $this->escapeMetricStep($result);
            }
        }

        $client = $this->dataSource->getClient();
        $url = Url::fromPath('metrics/expand', [
            'query' => $client->escapeMetricPath($this->base->resolve($filter, '*'))
        ]);
        $res = Json::decode($client->request($url));
        natsort($res->results);

        IPT::recordf('Fetched %s metric(s) from %s', count($res->results), (string) $client->completeUrl($url));

        return array_values($res->results);
    }

    public function fetchOne()
    {
        $result = $this->fetchColumn();
        return empty($result) ? false : $result[0];
    }

    public function fetchPairs()
    {
        throw new NotImplementedError(__METHOD__);
    }

    /**
     * Set the monitored object to render the graphs of
     *
     * @param MonitoredObject $monitoredObject
     *
     * @return $this
     */
    public function setMonitoredObject($monitoredObject)
    {
        $this->monitoredObject = $monitoredObject;

        return $this;
    }

    /**
     * Escapes a string for usage in a Graphite metric path between two dots
     *
     * @param   string  $step
     *
     * @return  string
     */
    protected function escapeMetricStep($step)
    {
        return preg_replace('/[^a-zA-Z0-9\*\-:[\]$\']/', '_', $step);
    }
}
