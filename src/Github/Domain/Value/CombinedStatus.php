<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Github\Domain\Value;

use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class CombinedStatus
{
    private const FAILURE = 'failure';
    private const PENDING = 'pending';
    private const SUCCESS = 'success';

    private string $state;

    /**
     * @var Status[]
     */
    private array $statuses;

    /**
     * @param Status[] $statuses
     */
    private function __construct(string $state, array $statuses)
    {
        $this->state = $state;
        $this->statuses = $statuses;
    }

    public static function fromResponse(array $response): self
    {
        Assert::notEmpty($response);

        Assert::keyExists($response, 'state');
        Assert::stringNotEmpty($response['state']);
        Assert::oneOf(
            $response['state'],
            [
                self::FAILURE,
                self::PENDING,
                self::SUCCESS,
            ]
        );

        Assert::keyExists($response, 'statuses');
        Assert::notEmpty($response['statuses']);

        $statuses = [];
        foreach ($response['statuses'] as $status) {
            $statuses[] = Status::fromResponse($status);
        }

        return new self(
            $response['state'],
            $statuses
        );
    }

    public function isSuccessful(): bool
    {
        return self::SUCCESS === $this->state;
    }

    public function state(): string
    {
        return $this->state;
    }

    /**
     * @return Status[]
     */
    public function statuses(): array
    {
        return $this->statuses;
    }
}
