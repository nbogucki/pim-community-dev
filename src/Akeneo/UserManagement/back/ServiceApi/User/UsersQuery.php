<?php

declare(strict_types=1);

/*
 * This file is part of the Akeneo PIM Community Edition.
 *
 * (c) 2022 Akeneo SAS (https://www.akeneo.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Akeneo\UserManagement\ServiceApi\User;

use Akeneo\UserManagement\Domain\Storage\FindUsers;

final class UsersQuery
{
    public function __construct(
        private ?string $search = null,
        private ?int $searchAfterId = null,
        private int $limit = FindUsers::DEFAULT_LIMIT,
    ) {
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }

    public function getSearchAfterId(): ?int
    {
        return $this->searchAfterId;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }
}
