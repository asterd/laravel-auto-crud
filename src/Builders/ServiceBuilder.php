<?php
declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Builders;

use Mrmarchone\LaravelAutoCrud\Traits\DynamicModelPathTrait;

class ServiceBuilder extends BaseBuilder
{
    use DynamicModelPathTrait;

    public function create(array $modelData, string $repository, bool $overwrite = false): string
    {
        return $this->fileService->createFromStub($modelData, 'service', 'Services', 'Service', $overwrite, function ($modelData) use ($repository) {
            // $model = $modelData['namespace'] ? 'App\\Models\\' . $modelData['namespace'] . '\\' . $modelData['modelName'] : 'App\\Models\\' . $modelData['modelName'];
            $model = $this->getModelNamespace($modelData);
            $repositorySplitting = explode('\\', $repository);
            $repositoryNamespace = $repository;
            $repository = end($repositorySplitting);
            $repositoryVariable = lcfirst($repository);
            return [
                '{{ modelNamespace }}' => $model,
                '{{ model }}' => $modelData['modelName'],
                '{{ modelVariable }}' => lcfirst($modelData['modelName']),
                '{{ repository }}' => $repository,
                '{{ repositoryNamespace }}' => $repositoryNamespace,
                '{{ repositoryVariable }}' => $repositoryVariable,
            ];
        });
    }
}
