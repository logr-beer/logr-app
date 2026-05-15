<?php

namespace App\Livewire;

use App\Services\JsonImportService;
use Livewire\Component;
use Livewire\WithFileUploads;

class JsonImport extends Component
{
    use WithFileUploads;

    public function boot(): void
    {
        abort_if(config('app.demo_mode'), 403);
    }

    public $jsonFile;

    public string $step = 'upload'; // upload, preview, importing, results

    public array $summary = [];

    public array $results = [];

    public array $importErrors = [];

    public function updatedJsonFile(): void
    {
        $this->validate([
            'jsonFile' => 'required|file|max:51200',
        ]);

        $contents = file_get_contents($this->jsonFile->getRealPath());
        $data = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->addError('jsonFile', 'Invalid JSON file.');

            return;
        }

        $this->summary = JsonImportService::preview($data);
        $this->step = 'preview';
    }

    public function runImport(): void
    {
        $this->step = 'importing';

        $contents = file_get_contents($this->jsonFile->getRealPath());
        $data = json_decode($contents, true);

        if (! $data) {
            $this->importErrors[] = 'Failed to parse JSON file.';
            $this->step = 'results';

            return;
        }

        $this->importErrors = [];

        try {
            $service = new JsonImportService(auth()->id());
            $service->import($data);
            $this->results = $service->results;
            $this->importErrors = $service->errors;
        } catch (\Throwable $e) {
            $this->importErrors[] = 'Fatal error: '.$e->getMessage();
        }

        $this->step = 'results';
    }

    public function backToUpload(): void
    {
        $this->step = 'upload';
        $this->reset(['jsonFile', 'summary', 'results', 'importErrors']);
    }

    public function resetImport(): void
    {
        $this->reset();
    }

    public function render()
    {
        return view('livewire.json-import');
    }
}
