<?php

declare(strict_types=1);

namespace WebDevelovers\ResourceBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WebDevelovers\ResourceBundle\Metadata\MetadataInterface;
use WebDevelovers\ResourceBundle\Metadata\MetadataRegistryInterface;

use function assert;
use function is_array;
use function is_string;
use function iterator_to_array;
use function ksort;

final class DebugResourceCommand extends Command
{
    public function __construct(private MetadataRegistryInterface $registry)
    {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setName('debug:resource');
        $this->setDescription('Debug resource metadata.');
        $this->setHelp(
            <<<'EOT'
List or show resource metadata.

To list run the command without an argument:

    $ php %command.full_name%

To show the metadata for a resource, pass its alias:

    $ php %command.full_name% wd.customer
EOT,
        );
        $this->addArgument('resource', InputArgument::OPTIONAL, 'Resource to debug');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $resource = $input->getArgument('resource');
        assert(is_string($resource) || $resource === null);

        if ($resource === null) {
            $this->listResources($output);

            return 0;
        }

        $metadata = $this->registry->get($resource);

        $this->debugResource($metadata, $output);

        return 0;
    }

    private function listResources(OutputInterface $output): void
    {
        /** @var iterable<MetadataInterface> $resources */
        $resources = $this->registry->getAll();
        $resources = is_array($resources) ? $resources : iterator_to_array($resources);
        ksort($resources);

        $table = new Table($output);
        $table->setHeaders(['Alias']);

        foreach ($resources as $resource) {
            $table->addRow([$resource->getAlias()]);
        }

        $table->render();
    }

    private function debugResource(MetadataInterface $metadata, OutputInterface $output): void
    {
        $table = new Table($output);
        $information = [
            'alias' => $metadata->getAlias(),
            'name' => $metadata->name,
            'humanized_name' => $metadata->getHumanizedName(),
            'plural_name' => $metadata->getPluralName(),
            'application' => $metadata->applicationName,
            'driver' => $metadata->driver,
            'templates_namespace' => $metadata->templatesNamespace ?? '-',
        ];

        $parameters = $this->flattenParameters($metadata->parameters);

        foreach ($parameters as $key => $value) {
            $information[$key] = $value;
        }

        foreach ($information as $key => $value) {
            $table->addRow([$key, $value]);
        }

        $table->render();
    }

    /**
     * @param array<string,mixed> $parameters
     * @param array<string,mixed> $flattened
     *
     * @return array<string,mixed>
     */
    private function flattenParameters(array $parameters, array $flattened = [], string $prefix = ''): array
    {
        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                $flattened = $this->flattenParameters($value, $flattened, $prefix . $key . '.');

                continue;
            }

            $flattened[$prefix . $key] = $value;
        }

        return $flattened;
    }
}
