<?php

namespace Icinga\Module\Graphite\Web\Widget;

use Icinga\Module\Graphite\Graphing\Chart;
use Icinga\Web\Widget\AbstractWidget;

class InlineGraphImage extends AbstractWidget
{
    /**
     * The image to be rendered
     *
     * @var GraphImage
     */
    protected $image;

    /**
     * The rendered <img>
     *
     * @var string|null
     */
    protected $rendered;

    /**
     * Constructor
     *
     * @param   Chart   $chart  The chart to be rendered
     */
    public function __construct(Chart $chart)
    {
        $this->image = new GraphImage($chart);
    }

    /**
     * Render the graph lazily
     *
     * @return string
     */
    public function render()
    {
        if ($this->rendered === null) {
            $this->rendered = '<img src="data:image/png;base64,'
                . implode("\n", str_split(base64_encode($this->image->render()), 76))
                . '">';
        }

        return $this->rendered;
    }
}
