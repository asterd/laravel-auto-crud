<?php
declare(strict_types=1);

namespace Mrmarchone\LaravelAutoCrud\Console\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Mrmarchone\LaravelAutoCrud\Services\CRUDGenerator;
use Mrmarchone\LaravelAutoCrud\Services\DatabaseValidatorService;
use Mrmarchone\LaravelAutoCrud\Services\HelperService;
use Mrmarchone\LaravelAutoCrud\Services\ModelService;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\alert;

class GenerateAutoCrudCommand extends Command
{
    private DatabaseValidatorService $databaseValidatorService;
    private CRUDGenerator $CRUDGenerator;
    // protected $signature = 'auto-crud:generate {--M|model=* : Select one or more of your models.} {--T|type= : Select weather api or web.} {--R|repository : Working with repository design pattern} {--O|overwrite : Overwrite the files if already exists.} {--P|pattern= : Supports Spatie-Data Pattern.} {--C|curl : Generate CURL Requests for API.} {--PM|postman : Generate Postman Collection for API.}';
    protected $signature = 'auto-crud:generate 
        {--M|model=* : Select one or more of your models.}
        {--T|type=api : Select whether api or web (default: api)}
        {--R|repository : Working with repository design pattern}
        {--O|overwrite : Overwrite existing files}
        {--P|pattern= : Supports Spatie-Data Pattern}
        {--C|curl : Generate CURL Requests for API}
        {--PM|postman : Generate Postman Collection for API}
        {--MP|models-path= : Custom path for models directory}
        {--MN|models-namespace= : Custom namespace for models}
        {--F|force : Skip all confirmations}
        {--S|skip-validation : Skip database validation}
        {--NC|no-confirmations : Skip all confirmations}';

    protected $description = 'A command to create auto CRUD for your models.';

    public function __construct()
    {
        parent::__construct();
        $this->databaseValidatorService = new DatabaseValidatorService();
        $this->CRUDGenerator = new CRUDGenerator();
    }

    public function handle(): void
    {
        // Initialize custom model path and namespace if provided
        if ($this->option('models-path') || $this->option('models-namespace')) {
            ModelService::initialize(
                $this->option('models-path'),
                $this->option('models-namespace')
            );
        }

        if (!$this->validateOptions()) {
            return;
        }
        HelperService::displaySignature();
        
        $models = $this->resolveModels();
        if (empty($models)) {
            alert('No valid models found.');
            return;
        }

        $this->generate($models);
    }

    private function validateOptions(): bool
    {
        if ($this->option('type') && !in_array($this->option('type'), ['api', 'web'])) {
            alert('Type must be either "api" or "web".');
            return false;
        }

        if ($this->option('pattern') === 'spatie-data' && !class_exists(\Spatie\LaravelData\Data::class)) {
            alert('Spatie Data package is required but not installed.');
            return false;
        }

        return true;
    }

    private function resolveModels(): array
    {
        $models = [];
        $specifiedModels = $this->option('model');

        if (!empty($specifiedModels)) {
            foreach ($specifiedModels as $model) {
                if (ModelService::isModelExists($model)) {
                    $models[] = $model;
                } else {
                    $this->warn("Model '{$model}' not found, skipping...");
                }
            }
        } else {
            $models = ModelService::showModels();
        }

        return $models;
    }

    private function generate(array $models): void
    {
        if (!$this->option('skip-validation') && !$this->databaseValidatorService->checkDataBaseConnection()) {
            $this->error('Database connection error.');
            return;
        }

        foreach ($models as $model) {
            $this->generateForModel($model);
        }
    }

    private function generateForModel(string $model): void
    {
        $modelData = ModelService::resolveModelName($model);
        $table = ModelService::getFullModelNamespace($modelData);

        if (!$this->option('skip-validation') && 
            !$this->databaseValidatorService->checkTableExists($table) && 
            !$this->option('force')) {
            
            if (!$this->confirmEmptyGeneration($table)) {
                alert("Skipping CRUD generation for model '{$model}'.");
                return;
            }
        }

        $options = array_merge($this->options(), [
            'skip_confirmations' => $this->option('no-confirmations') || $this->option('force'),
        ]);

        $this->CRUDGenerator->generate($modelData, $options);
        $this->info("CRUD operations generated successfully for model '{$model}'.");
    }

    private function confirmEmptyGeneration(string $table): bool
    {
        if ($this->option('force') || $this->option('no-confirmations')) {
            return true;
        }

        return confirm(
            label: "Table '{$table}' not found. Generate empty CRUD files?",
            default: false
        );
    }
}

