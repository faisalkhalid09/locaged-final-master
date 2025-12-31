@if($step === 1)
    <div class="upload-section p-3 mt-2"
         x-data="{ isUploading: false, progress: 0 }"
         x-on:livewire-upload-start="isUploading = true"
         x-on:livewire-upload-finish="isUploading = false; progress = 0"
         x-on:livewire-upload-error="isUploading = false"
         x-on:livewire-upload-progress="progress = $event.detail.progress"
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

        <div id="upload-box" class="upload-box border border-dashed rounded-3 text-center p-5" style="cursor: pointer;"
             x-data="{ handleFolderChange(event) {
                 const files = Array.from(event.target.files || []);
                 if (!files.length) return;

                 // Preserve relative paths from the selected folder so the backend
                 // can properly create nested folders for all file types.
                 const paths = files.map(f => f.webkitRelativePath || f.name);
                 $wire.set('relativePaths', paths);
             }}">

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

            {{-- File selection (allow multiple files, same behaviour as folder selection). No accept filter so any file type can be selected. --}}
            <input type="file" wire:model="newDocuments" x-ref="fileInput" class="d-none" multiple />

            {{-- Folder selection (all files inside folder). Bound to newDocuments so Livewire
                 can upload all selected files; handleFolderChange() only records relative paths. --}}
            <input type="file" wire:model="newDocuments" x-ref="folderInput" class="d-none" multiple
                   webkitdirectory directory mozdirectory
                   x-on:change="handleFolderChange($event)" />

            <template x-if="isUploading">
                <div class="progress mt-3" style="height: 8px; background-color: #2A2A2E;">
                    <div class="progress-bar bg-success" role="progressbar" :style="`width: ${progress}%`"></div>
                </div>
            </template>

        </div>

        {{-- Display upload errors --}}
        @error('upload')
            <div class="alert alert-danger mt-3" role="alert">
                <i class="fa-solid fa-exclamation-triangle me-2"></i>{{ $message }}
            </div>
        @enderror

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
