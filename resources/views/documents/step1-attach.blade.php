@if($step === 1)
    <div class="upload-section p-3 mt-2"
         x-data="{ 
            isUploading: false, 
            progress: 0,
            showError: false,
            errorMessage: '',
            maxFileSizeMB: {{ floor(config('uploads.max_file_size_kb', 51200) / 1024) }},
            maxBatchFiles: {{ config('uploads.max_batch_files', 50) }},
            
            validateFiles(files) {
                const filesArray = Array.from(files || []);
                if (!filesArray.length) return { valid: true };

                // Validate individual file sizes
                const maxBytes = this.maxFileSizeMB * 1024 * 1024;
                const oversizedFiles = filesArray.filter(f => f.size > maxBytes);
                
                if (oversizedFiles.length > 0) {
                    const fileNames = oversizedFiles.map(f => f.name).slice(0, 3).join(', ');
                    const sizeMB = (oversizedFiles[0].size / 1024 / 1024).toFixed(1);
                    const moreCount = oversizedFiles.length > 3 ? ` (+${oversizedFiles.length - 3} more)` : '';
                    return {
                        valid: false,
                        message: `File(s) too large: ${fileNames}${moreCount}. Size: ${sizeMB} MB (Maximum allowed: ${this.maxFileSizeMB} MB per file)`
                    };
                }

                // Validate number of files
                if (filesArray.length > this.maxBatchFiles) {
                    return {
                        valid: false,
                        message: `Too many files selected. You selected ${filesArray.length} files. Maximum allowed: ${this.maxBatchFiles} files per upload.`
                    };
                }

                return { valid: true };
            },
            
            validateAndUpload(files, isFolder = false) {
                // Use validateFiles function from same scope
                const validation = this.validateFiles(files);
                
                if (!validation.valid) {
                    this.errorMessage = validation.message;
                    this.showError = true;
                    return false;
                }

                // If folder upload, preserve relative paths
                if (isFolder) {
                    const filesArray = Array.from(files || []);
                    const paths = filesArray.map(f => f.webkitRelativePath || f.name);
                    $wire.set('relativePaths', paths);
                }

                // Manually trigger Livewire upload after validation passes
                // We use explicit callbacks to ensure the progress bar updates correctly
                $wire.uploadMultiple(
                    'newDocuments', 
                    files, 
                    () => { 
                        this.isUploading = false; 
                        this.progress = 0; 
                    }, 
                    () => { 
                        this.isUploading = false; 
                        this.progress = 0;
                    }, 
                    (event) => { 
                        this.isUploading = true; 
                        this.progress = event.detail.progress; 
                    }
                );
                return true;
            },
            
            handleFolderChange(event) {
                const files = event.target.files;
                if (files && files.length > 0) {
                    if (!this.validateAndUpload(files, true)) {
                        // Clear the input on validation failure
                        event.target.value = '';
                    }
                }
            },
            
            handleFileChange(event) {
                const files = event.target.files;
                if (files && files.length > 0) {
                    if (!this.validateAndUpload(files, false)) {
                        // Clear the input on validation failure
                        event.target.value = '';
                    }
                }
            },

            handleDrop(event) {
                event.preventDefault();
                const files = event.dataTransfer?.files;
                const validation = this.validateFiles(files);
                
                if (!validation.valid) {
                    this.errorMessage = validation.message;
                    this.showError = true;
                    return false;
                }
                
                // Manually trigger upload for drop files too
                this.validateAndUpload(files, false);
                return true;
            }
         }"
         x-on:livewire-upload-start.window="isUploading = true"
         x-on:livewire-upload-finish.window="isUploading = false; progress = 0"
         x-on:livewire-upload-error.window="isUploading = false"
         x-on:livewire-upload-progress.window="progress = $event.detail.progress"
    >
        <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label fw-semibold text-muted mb-0">
                <img src="{{ asset('assets/Vector (25).svg') }}" alt=""> {{ ui_t('pages.upload.upload_file') }}
            </label>
            @if(count($documents) > 1)
                <div class="form-check form-switch small">
                    <input class="form-check-input" type="checkbox" id="useSharedMetadataSwitch" wire:model="useSharedMetadata">
                    <label class="form-check-label" for="useSharedMetadataSwitch">
                        {{ $useSharedMetadata ? 'Same metadata for all files' : 'Different metadata for each file' }}
                    </label>
                </div>
            @endif
        </div>

        {{-- Simple Custom Error Modal (No Bootstrap JS dependency) --}}
        <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5); z-index: 9999;" 
             x-show="showError" 
             x-transition.opacity
             x-cloak>
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-exclamation-triangle me-2"></i>
                            {{ ui_t('pages.upload.upload_blocked') }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" @click="showError = false" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-0" x-text="errorMessage" style="white-space: pre-wrap;"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" @click="showError = false">
                            {{ ui_t('actions.ok') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="upload-box" 
             class="upload-box border border-dashed rounded-3 text-center p-5" 
             style="cursor: pointer;"
             x-on:drop.prevent="if (!handleDrop($event)) { $event.stopPropagation(); }"
             x-on:dragover.prevent>
            
            <div class="mb-3">
                <img src="{{ asset('assets/Vector (24).svg') }}" alt="upload">
            </div>
            <p class="text-drag">{{ ui_t('pages.upload.drag_drop_here') }}</p>
            <div class="d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-dark mt-2" @click.stop="$refs.fileInput.click()">
                    <i class="fa-solid fa-file-arrow-up me-2"></i> {{ ui_t('pages.upload.browse_file') }}
                </button>
                <button type="button" class="btn btn-dark mt-2" @click.stop="$refs.folderInput.click()">
                    <i class="fa-solid fa-folder-open me-2"></i> {{ ui_t('pages.upload.select_folder') }}
                </button>
            </div>

            {{-- File Size Limits Information --}}
            @php
                $maxFileSizeMB = floor(config('uploads.max_file_size_kb', 51200) / 1024);
                $maxBatchFiles = config('uploads.max_batch_files', 50);
                $maxBatchSizeMB = floor(config('uploads.max_batch_size_kb', 512000) / 1024);
            @endphp
            <div class="mt-3">
                <small class="text-muted d-block">
                    <i class="fa-solid fa-info-circle me-1"></i>
                    {{ ui_t('pages.upload.max_file_size', ['size' => $maxFileSizeMB]) }}
                </small>
                <small class="text-muted d-block">
                    <i class="fa-solid fa-info-circle me-1"></i>
                    {{ ui_t('pages.upload.max_batch_info', ['count' => $maxBatchFiles, 'size' => $maxBatchSizeMB]) }}
                </small>
            </div>

            {{-- File selection without wire:model to prevent auto-upload --}}
            <input type="file" x-ref="fileInput" class="d-none" multiple 
                   x-on:change="handleFileChange($event)" />

            {{-- Folder selection without wire:model to prevent auto-upload --}}
            <input type="file" x-ref="folderInput" class="d-none" multiple
                   webkitdirectory directory mozdirectory
                   x-on:change="handleFolderChange($event)" />

            <template x-if="isUploading">
                <div class="progress mt-3" style="height: 8px; background-color: #2A2A2E;">
                    <div class="progress-bar bg-success" role="progressbar" :style="`width: ${progress}%`"></div>
                </div>
            </template>

        </div>

        {{-- Display upload errors --}}
        @error('documents')
            <div class="alert alert-danger mt-3" role="alert">
                <i class="fa-solid fa-exclamation-triangle me-2"></i>
                <strong>Upload Error:</strong> {{ $message }}
            </div>
        @enderror
        
        @error('documents.*')
            <div class="alert alert-danger mt-3" role="alert">
                <i class="fa-solid fa-exclamation-triangle me-2"></i>
                <strong>File Validation Error:</strong> {{ $message }}
            </div>
        @enderror
        
        @if (session()->has('error'))
            <div class="alert alert-danger mt-3" role="alert">
                <i class="fa-solid fa-exclamation-triangle me-2"></i>
                {{ session('error') }}
            </div>
        @endif

        <div class="container-fluid doc-file p-0 mt-4">
            @foreach($documents as $index => $document)
                <div class="border rounded p-3 mb-3 py-4">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="d-flex align-items-start gap-3">
                            @php
                                $extension = strtolower(pathinfo($document->getClientOriginalName(), PATHINFO_EXTENSION));
                                $iconClass = getFileIcon($extension);
                            @endphp
                            <i class="{{ $iconClass }} mt-2" style="font-size: 24px;"></i>
                            <div>
                                <strong>{{ pathinfo($document->getClientOriginalName(), PATHINFO_FILENAME) }}</strong><br />
                                <small class="text-muted">({{ round($document->getSize() / 1024) }} KB)</small>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <button class="border-0 bg-transparent me-3" title="{{ ui_t('pages.upload.preview') }}" type="button"
                                    wire:click="previewFile({{ $index }})">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                            <button class="border-0 bg-transparent btn-delete" title="{{ ui_t('pages.upload.delete') }}" type="button"
                                    wire:click="removeDocument({{ $index }})">
                                <i class="fa-solid fa-trash text-danger"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @include('livewire.preview-modal')

    </div>
@endif
