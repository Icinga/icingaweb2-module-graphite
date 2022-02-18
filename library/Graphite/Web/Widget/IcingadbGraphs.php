<?php

/* Icinga Graphite Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Graphite\Web\Widget;

use Icinga\Module\Graphite\Web\Widget\Graphs\Icingadb\IcingadbHost;
use Icinga\Module\Graphite\Web\Widget\Graphs\Icingadb\IcingadbService;
use Icinga\Module\Icingadb\Widget\EmptyState;
use Icinga\Web\Url;
use Icinga\Module\Icingadb\Model\Host;
use InvalidArgumentException;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use ipl\Web\Widget\Link;

/**
* Base class for list items
*/
class IcingadbGraphs extends BaseHtmlElement
{
    protected $defaultAttributes = ['class' => 'grid'];

    /** @var Iterable */
    protected $objects;

    protected $tag = 'div';

    /** @var Url */
    protected $hostBaseUrl;

    /** @var Url */
    protected $serviceBaseUrl;

    /** Create a new Graph item
     *
     * @param Iterable $objects
     *
     * @param Url $hostBaseUrl Base url for hosts
     *
     * @param Url|null $serviceBaseUrl Base url for services
     *
     */
    public function __construct($objects, $hostBaseUrl, $serviceBaseUrl = null)
    {
        if (! is_iterable($objects)) {
            throw new InvalidArgumentException('Data must be an array or an instance of Traversable');
        }

        $this->objects = $objects;
        $this->hostBaseUrl = $hostBaseUrl;
        $this->serviceBaseUrl = $serviceBaseUrl;
    }

    protected function assemble()
    {
        if (! $this->objects->hasResult()) {
            $this->add(new EmptyState(t('No items found.')));
        }

        foreach ($this->objects as $object) {
            $this->add($this->createGridItem($object));
        }
    }

    protected function createGridItem($object)
    {
        if ($object instanceof Host) {
            $graph = new IcingadbHost($object);
        } else {
            $graph = new IcingadbService($object);
        }

        $gridItem = Html::tag('div', ['class' => 'grid-item']);
        $header = Html::tag('h2');

        $hostLink =  new Link(
            $graph->createHostTitle(),
            $graph->createHostLink($this->hostBaseUrl),
            ['data-base-target' => '_next']
        );

        $serviceLink = $graph->getObjectType() === 'service'
            ? new Link(
                $graph->createServiceTitle(),
                $graph->createServiceLink($this->serviceBaseUrl),
                ['data-base-target' => '_next']
            )
            : null;

        $header->add([$hostLink, $serviceLink]);
        $gridItem->add($header);

        return $gridItem->add(HtmlString::create($graph->handleRequest()));
    }
}
