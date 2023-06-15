<?php

/* Icinga Graphite Web | (c) 2022 Icinga GmbH | GPLv2 */

namespace Icinga\Module\Graphite\Web\Widget;

use Icinga\Module\Graphite\Web\Widget\Graphs\Icingadb\IcingadbHost;
use Icinga\Module\Graphite\Web\Widget\Graphs\Icingadb\IcingadbService;
use Icinga\Module\Icingadb\Common\Links;
use Icinga\Module\Icingadb\Widget\EmptyState;
use Icinga\Module\Icingadb\Model\Host;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlString;
use ipl\Orm\ResultSet;
use ipl\Stdlib\BaseFilter;
use ipl\Web\Filter\QueryString;
use ipl\Web\Widget\Link;

/**
* Class for creating graphs of icingadb objects
*/
class IcingadbGraphs extends BaseHtmlElement
{
    use BaseFilter;

    protected $defaultAttributes = ['class' => 'grid'];

    /** @var Iterable */
    protected $objects;

    protected $tag = 'div';

    /**
     * Create a new Graph item
     *
     * @param ResultSet $objects
     */
    public function __construct(ResultSet $objects)
    {
        $this->objects = $objects;
    }

    protected function assemble()
    {
        if (! $this->objects->hasResult()) {
            $this->add(new EmptyState(t('No items found.')));
        }

        foreach ($this->objects as $object) {
            $this->add($this->createGridItem($object));
        }

        $document = new HtmlDocument();
        $document->addHtml(Html::tag('div', ['class' => 'graphite-graph-color-registry']), $this);
        $this->prependWrapper($document);
    }

    protected function createGridItem($object)
    {
        if ($object instanceof Host) {
            $graph = new IcingadbHost($object);
            $hostObj = $object;
        } else {
            $graph = new IcingadbService($object);
            $hostObj = $object->host;
        }

        $hostUrl = Links::host($hostObj);

        if ($this->hasBaseFilter()) {
            $hostUrlParams = $hostUrl->getParams()->toArray(false);
            $hostUrl->setQueryString(QueryString::render($this->getBaseFilter()))
                ->addParams($hostUrlParams);
        }

        $hostLink =  new Link(
            $graph->createHostTitle(),
            $hostUrl,
            ['data-base-target' => '_next']
        );

        $serviceLink = null;
        if ($graph->getObjectType() === 'service') {
            $serviceUrl = Links::service($object, $hostObj);

            if ($this->hasBaseFilter()) {
                $serviceUrlParams = $serviceUrl->getParams()->toArray(false);
                $serviceUrl->setQueryString(QueryString::render($this->getBaseFilter()))
                    ->addParams($serviceUrlParams);
            }

            $serviceLink = new Link(
                $graph->createServiceTitle(),
                $serviceUrl,
                ['data-base-target' => '_next']
            );
        }

        $gridItem = Html::tag('div', ['class' => 'grid-item']);
        $header = Html::tag('h2');

        $header->add([$hostLink, $serviceLink]);
        $gridItem->add($header);

        return $gridItem->add(HtmlString::create($graph->setPreloadDummy()->handleRequest()));
    }
}
