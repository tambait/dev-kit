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

namespace App\Command\Dispatcher;

use App\Command\AbstractNeedApplyCommand;
use App\Config\Projects;
use App\Domain\Value\Project;
use App\Util\Util;
use Github\Client as GithubClient;
use Github\Exception\ExceptionInterface;
use Packagist\Api\Result\Package;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Sullivan Senechal <soullivaneuh@gmail.com>
 * @author Oskar Stark <oskarstark@googlemail.com>
 */
final class DispatchSettingsCommand extends AbstractNeedApplyCommand
{
    private Projects $projects;
    private GithubClient $github;

    public function __construct(Projects $projects, GithubClient $github)
    {
        parent::__construct();

        $this->projects = $projects;
        $this->github = $github;
    }

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('dispatch:settings')
            ->setDescription('Dispatches repository information and general settings for all sonata projects.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io->title('Dispatch repository information and general settings for all sonata projects');

        /** @var Project $project */
        foreach ($this->projects->all() as $project) {
            try {
                $this->io->title($project->name());

                $this->updateRepositories(
                    $project->package(),
                    $project->rawConfig()
                );
            } catch (ExceptionInterface $e) {
                $this->io->error(sprintf(
                    'Failed with message: %s',
                    $e->getMessage()
                ));
            }
        }

        return 0;
    }

    private function updateRepositories(Package $package, array $projectConfig): void
    {
        $repositoryName = Util::getRepositoryNameWithoutVendorPrefix($package);
        $branches = array_keys($projectConfig['branches']);

        $repositoryInfo = $this->github->repo()->show(static::GITHUB_GROUP, $repositoryName);
        $infoToUpdate = [
            'homepage' => 'https://sonata-project.org/',
            'has_issues' => true,
            'has_projects' => true,
            'has_wiki' => false,
            'default_branch' => end($branches),
            'allow_squash_merge' => true,
            'allow_merge_commit' => false,
            'allow_rebase_merge' => true,
        ];

        foreach ($infoToUpdate as $info => $value) {
            if ($value === $repositoryInfo[$info]) {
                unset($infoToUpdate[$info]);
            }
        }

        if (\count($infoToUpdate)) {
            $this->io->comment(sprintf(
                'Following info have to be changed: %s.',
                implode(', ', array_keys($infoToUpdate))
            ));

            if ($this->apply) {
                $this->github->repo()->update(static::GITHUB_GROUP, $repositoryName, array_merge($infoToUpdate, [
                    'name' => $repositoryName,
                ]));
            }
        } else {
            $this->io->comment(static::LABEL_NOTHING_CHANGED);
        }
    }
}