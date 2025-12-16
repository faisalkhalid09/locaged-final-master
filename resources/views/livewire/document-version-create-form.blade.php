<div>
    <div class="form-section">
        <div class="form-title">{{ ui_t('pages.upload.upload_new_version_for', ['title' => $document->title]) }}</div>

        <div class="step-indicator justify-content-center d-flex gap-3 w-50">
            <button type="button" class="step-tab text-decoration-none border-0 bg-transparent">
                <p class="step active">
                    <i class="fa-solid fa-file me-2"></i> {{ ui_t('pages.upload.attach_document') }}
                </p>
            </button>
        </div>

        <x-errors/>

        <div class="step-content mt-4">
            <form wire:submit.prevent="submit">
                <div class="upload-section p-3 mt-2"
                     x-data="{ isUploading: false, progress: 0 }"
                     x-on:livewire-upload-start="isUploading = true"
                     x-on:livewire-upload-finish="isUploading = false; progress = 0"
                     x-on:livewire-upload-error="isUploading = false"
                     x-on:livewire-upload-progress="progress = $event.detail.progress"
                >
                    <label class="form-label fw-semibold text-muted">
                        <img src="{{ asset('assets/Vector (25).svg') }}" alt=""> {{ ui_t('pages.upload.upload_file') }}
                    </label>

                    <div id="upload-box" class="upload-box border border-dashed rounded-3 text-center p-5" style="cursor: pointer;"
                         @click="$refs.fileInput.click()">

                        <div class="mb-3">
                            <img src="{{ asset('assets/Vector (24).svg') }}" alt="upload">
                        </div>
                        <p class="text-drag">{{ ui_t('pages.upload.drag_drop_here') }}</p>
                        <button type="button" class="btn btn-dark mt-2" @click.stop="$refs.fileInput.click()">
                            <i class="fa-solid fa-folder-open me-2"></i> {{ ui_t('pages.upload.browse_file') }}
                        </button>

                        <input type="file" wire:model="file" x-ref="fileInput" class="d-none"
                               accept=".png,.jpg,.jpeg,.pdf,.webp,.doc,.docx,.xls,.xlsx,.csv,.mp4,.avi,.mov,.wmv,.flv,.webm,.mkv,.mp3,.wav,.flac,.aac,.ogg,.m4a" />

                        <template x-if="isUploading">
                            <div class="progress mt-3" style="height: 8px; background-color: #2A2A2E;">
                                <div class="progress-bar bg-success" role="progressbar" :style="`width: ${progress}%`"></div>
                            </div>
                        </template>

                        @error('file')
                        <div class="text-danger mt-2">{{ $message }}</div>
                        @enderror
                    </div>

                    @if ($file)
                        <div class="container-fluid doc-file p-0 mt-4">
                            <div class="border rounded p-3 mb-3 py-4">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="d-flex align-items-start gap-3">
                                        @php
                                            $extension = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
                                            $iconClass = getFileIcon($extension);
                                        @endphp
                                        <i class="{{ $iconClass }} mt-2" style="font-size: 24px;"></i>
                                        <div>
                                            <strong>{{ $file->getClientOriginalName() }}</strong><br />
                                            <small class="text-muted">({{ round($file->getSize() / 1024) }} KB)</small>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2 mt-3">
                                        <button class="border-0 bg-transparent me-3" title="{{ ui_t('pages.upload.preview') }}" type="button"
                                                wire:click="previewFile">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <button class="border-0 bg-transparent btn-delete" title="{{ ui_t('pages.upload.remove') }}" type="button"
                                                wire:click="$set('file', null)">
                                            <i class="fa-solid fa-trash text-danger"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @include('livewire.preview-modal')

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-dark">
                            <i class="fa-solid fa-upload me-2"></i> {{ ui_t('pages.upload.upload_new_version') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
