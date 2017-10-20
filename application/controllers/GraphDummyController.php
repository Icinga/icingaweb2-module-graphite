<?php

namespace Icinga\Module\Graphite\Controllers;

use Icinga\Module\Graphite\Web\Controller\MonitoringAwareController;

class GraphDummyController extends MonitoringAwareController
{
    public function hostAction()
    {
        $this->supplyImage();
    }

    public function serviceAction()
    {
        $this->supplyImage();
    }

    /**
     * Do all monitored object type independend actions
     */
    protected function supplyImage()
    {
        $this->getResponse()
            ->setHeader('Content-Type', 'image/png', true)
            ->setHeader('Content-Disposition', 'inline; filename="dummy.png"', true)
            ->setBody(
                "\x89PNG\x0d\x0a\x1a\x0a\x00\x00\x00\x0dIHDR\x00\x00\x00\x01"
                    . "\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15\xc4\x89\x00\x00\x00\x06bKG"
                    . "D\x00\xff\x00\xff\x00\xff\xa0\xbd\xa7\x93\x00\x00\x00\x09pHYs\x00"
                    . "\x00\x0b\x13\x00\x00\x0b\x13\x01\x00\x9a\x9c\x18\x00\x00\x00\x0bIDAT"
                    . "\x08\xd7c`\x00\x02\x00\x00\x05\x00\x01\xe2&\x05\x9b\x00\x00\x00\x00I"
                    . "END\xaeB`\x82"
            )
            ->sendResponse();

        exit;
    }
}
