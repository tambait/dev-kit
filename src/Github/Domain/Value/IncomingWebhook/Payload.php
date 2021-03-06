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

namespace App\Github\Domain\Value\IncomingWebhook;

use App\Github\Domain\Value\Issue;
use App\Github\Domain\Value\Repository;
use Webmozart\Assert\Assert;

/**
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class Payload
{
    private Action $action;
    private Issue $issue;
    private int $issueAuthorId;
    private ?int $commentAuthorId;
    private Repository $repository;

    private function __construct(Action $action, Issue $issue, int $issueAuthorId, ?int $commentAuthorId, Repository $repository)
    {
        Assert::greaterThan($issueAuthorId, 0);
        if (null !== $commentAuthorId) {
            Assert::greaterThan($commentAuthorId, 0);
        }

        $this->action = $action;
        $this->issue = $issue;
        $this->issueAuthorId = $issueAuthorId;
        $this->commentAuthorId = $commentAuthorId;
        $this->repository = $repository;
    }

    public static function fromArray(array $payload, Event $event): self
    {
        Assert::notEmpty($payload);

        Assert::keyExists($payload, 'action');
        $action = Action::fromString($payload['action']);

        $issueKey = 'issue_comment' === $event->toString() ? 'issue' : 'pull_request';

        Assert::keyExists($payload, $issueKey);
        Assert::keyExists($payload[$issueKey], 'number');
        $issue = Issue::fromInt($payload[$issueKey]['number']);

        Assert::keyExists($payload[$issueKey], 'user');
        Assert::keyExists($payload[$issueKey]['user'], 'id');
        $issueAuthorId = $payload[$issueKey]['user']['id'];

        $commentAuthorId = null;
        if (\array_key_exists('comment', $payload)) {
            Assert::keyExists($payload['comment'], 'user');
            Assert::keyExists($payload['comment']['user'], 'id');
            $commentAuthorId = $payload['comment']['user']['id'];
        }

        Assert::keyExists($payload, 'repository');
        Assert::keyExists($payload['repository'], 'full_name');

        return new self(
            $action,
            $issue,
            $issueAuthorId,
            $commentAuthorId,
            Repository::fromString($payload['repository']['full_name'])
        );
    }

    public static function fromJsonString(string $payload, Event $event): self
    {
        return self::fromArray(
            json_decode($payload, true),
            $event
        );
    }

    public function action(): Action
    {
        return $this->action;
    }

    public function issue(): Issue
    {
        return $this->issue;
    }

    public function issueAuthorId(): int
    {
        return $this->issueAuthorId;
    }

    public function isTheCommentFromTheAuthor(): bool
    {
        // If it's a PR synchronization, it's obviously done from the author.
        if ($this->action->equals(Action::SYNCHRONIZE())) {
            return true;
        }

        return $this->issueAuthorId === $this->commentAuthorId;
    }

    public function repository(): Repository
    {
        return $this->repository;
    }
}
