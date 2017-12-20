<?php

namespace Icinga\Module\Graphite\Web;

use Icinga\Web\Request;

/**
 * Rationale:
 *
 * {@link Url::fromPath()} doesn't preserve URLs which seem to be internal as they are.
 */
class FakeSchemeRequest extends Request
{
    public function getScheme()
    {
        return 'a random url scheme which always differs from the current request\'s one';
    }
}
