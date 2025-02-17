<?php
declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Traits;

use Mrmarchone\LaravelAutoCrud\Services\ModelService;

trait DynamicModelPathTrait
{
    protected function getModelNamespace(array $modelData): string
    {
        $baseNamespace = ModelService::getBaseNamespace();
        return $modelData['namespace'] 
            ? $baseNamespace . '\\' . $modelData['namespace'] . '\\' . $modelData['modelName']
            : $baseNamespace . '\\' . $modelData['modelName'];
    }
}