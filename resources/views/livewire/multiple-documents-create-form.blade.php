<div>
    {{-- Loading overlay for transitions --}}
    <x-loading-overlay 
        target="nextStep" 
        message="Preparing metadata form for {{ count($documents) }} file(s)..." 
    />
    <x-loading-overlay 
        target="submit,performSubmit" 
        message="Uploading {{ count($documents) }} document(s)..." 
    />

    <div class="form-section">
        <div class="form-title">{{ ui_t('pages.upload.upload_documents') }}</div>

        <div class="step-indicator justify-content-center d-flex gap-3 w-50">
            <button type="button" class="step-tab text-decoration-none border-0 bg-transparent" data-step="1">
                <p class="step {{ $step === 1 ? 'active' : '' }}">
                    <i class="fa-solid fa-file me-2"></i> {{ ui_t('pages.upload.attach_document') }}
                    <i class="fa-solid fa-chevron-right ms-3"></i>
                </p>
            </button>

            <button type="button" id="step2Btn" class="step-tab text-decoration-none border-0 bg-transparent" data-step="2" {{ $step < 2 ? 'disabled' : '' }}>
                <p class="step {{ $step === 2 ? 'active' : '' }}">
                    <i class="fa-solid fa-pen-to-square mx-2"></i> {{ ui_t('pages.upload.document_info') }}
                    @php
                        $multiUpload = count($documentInfos) > 1;
                    @endphp
                    @if($step === 2 && count($documentInfos) > 0 && (!$multiUpload || !$useSharedMetadata))
                        ({{ $currentDocumentIndex + 1 }}/{{ count($documentInfos) }})
                    @endif
                    <i class="fa-solid fa-chevron-right ms-3"></i>
                </p>
            </button>
        </div>

        {{-- Step 1 --}}
        <div class="step-content mt-4 {{ $step !== 1 ? 'd-none' : '' }}">
            @include('documents.step1-attach')
            <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-outline-secondary btn-nav doc-history next-step" wire:click="nextStep" wire:loading.attr="disabled" wire:loading.class="opacity-50">
                    <span wire:loading.remove wire:target="nextStep">
                        {{ ui_t('actions.next') }} &gt;
                    </span>
                    <span wire:loading wire:target="nextStep">
                        <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                        Processing {{ count($documents) }} file(s)...
                    </span>
                </button>
            </div>
        </div>

        {{-- Step 2 --}}
        <div class="step-content mt-4 {{ $step !== 2 ? 'd-none' : '' }}">
            @include('documents.step2-documentinfo')
        </div>


    </div>
</div>
