<?php

namespace App\Livewire;

use App\Jobs\ProcessOcrJob;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\OcrJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocumentVersionCreateForm extends Component
{
    use WithFileUploads;

    public Document $document;

    public $file;
    public $uploadProgress = 0;
    public $previewUrl;
    public $previewMime;
    public $previewName;

    protected $listeners = ['previewFile'];

    protected function rules()
    {
        $maxFileSizeKb    = (int) config('uploads.max_file_size_kb', 50000);
        $allowedExtensions = config('uploads.allowed_extensions', []);

        $mimesRule = !empty($allowedExtensions)
            ? 'mimes:' . implode(',', $allowedExtensions)
            : '';

        $rule = 'required|file|max:' . $maxFileSizeKb;
        if ($mimesRule) {
            $rule .= '|' . $mimesRule;
        }

        return [
            'file' => $rule,
        ];
    }

    public function mount(Document $document)
    {
        $this->document = $document;
    }

    public function previewFile()
    {
        if (!$this->file) return;

        $absolutePath = $this->file->getRealPath();
        $token = \Crypt::encryptString($absolutePath);
        $this->previewUrl = route('preview.temp', ['token' => $token, 'name' => $this->file->getClientOriginalName()]);
        $this->previewMime = $this->file->getMimeType();
        $this->previewName = $this->file->getClientOriginalName();
        $this->dispatch('show-preview-modal');
    }

    public function submit()
    {

        $this->validate();

        $extension = $this->file->getClientOriginalExtension();
        $filename = $this->document->title . now()->format('His') .'.' . $extension;
        $filePath = Storage::disk('local')->putFileAs('', $this->file, $filename);


        try {
            DB::beginTransaction();


            $docVersion = DocumentVersion::create([
                'document_id' => $this->document->id,
                'uploaded_by' => auth()->id(),
                'file_path' => $filePath,
                'file_type' => getFileCategory($extension)
            ]);

            // Only run OCR automatically if the underlying document is already approved.
            $status = $this->document->status instanceof \App\Enums\DocumentStatus
                ? $this->document->status->value
                : $this->document->status;

            if ($status === 'approved') {
                $ocrJob = OcrJob::create([
                    'document_version_id' => $docVersion->id,
                    'status' => 'queued',
                    'queued_at' => now(),
                ]);

                ProcessOcrJob::dispatch($ocrJob);
            }

            DB::commit();



        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Document creation failed', ['error' => $e->getMessage()]);
            $this->addError('file', 'Failed to upload document. Please try again.');
        }

        session()->flash('success', 'Document version created successfully.');
        return redirect()->route('document-versions.by-document', $this->document->id);
    }

    public function render()
    {
        return view('livewire.document-version-create-form');
    }
}
