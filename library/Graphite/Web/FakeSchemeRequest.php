<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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
