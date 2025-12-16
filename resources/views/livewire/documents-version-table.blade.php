<div class="recent-files-section">
        <div class="table-controls">
            <div class="search-files">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="{{ ui_t('pages.versions.search_by_name') }}" wire:model.live="search" />
            </div>
            <div class="table-filters">
                <select class="form-select" wire:model.change="status">
                    <option value="">{{ ui_t('pages.versions.filters.all_status') }}</option>
                    @foreach(\App\Enums\DocumentStatus::activeCases() as $status)
                        <option value="{{ $status->value }}">{{ ui_t('pages.documents.status.' . $status->value) }}</option>
                    @endforeach
                </select>

                <select class="form-select" wire:model.change="fileType">
                    <option value="">{{ ui_t('pages.versions.filters.file_type') }}</option>
                    <option value="pdf">{{ ui_t('filters.types.pdf') }}</option>
                    <option value="doc">{{ ui_t('filters.types.word') ?? ui_t('filters.types.doc') }}</option>
                    <option value="image">{{ ui_t('filters.types.image') }}</option>
                    <option value="excel">{{ ui_t('filters.types.excel') }}</option>
                    <option value="video">{{ ui_t('filters.types.video') }}</option>
                    <option value="audio">{{ ui_t('filters.types.audio') }}</option>
                </select>

                <div class="d-flex align-items-center gap-1">
                    <label>{{ ui_t('pages.versions.filters.from') }}: </label>
                    <input type="date" class="form-control" wire:model.change="dateFrom" placeholder="{{ ui_t('pages.versions.filters.from') }}" />
                </div>

                <div class="d-flex align-items-center gap-1">
                    <label>{{ ui_t('pages.versions.filters.to') }}: </label>
                    <input type="date" class="form-control" wire:model.change="dateTo" placeholder="{{ ui_t('pages.versions.filters.to') }}" />
                </div>

                <!-- Reset button -->
                <button type="button" wire:click="resetFilters" class="btn btn-sm btn-outline-danger text-nowrap">
                    {{ ui_t('pages.versions.filters.reset_filters') }}
                </button>

            </div>
        </div>

        <table class="files-table doc-table bg-white ">
            <thead>
            <tr class="bg-white">
                <th>{{ ui_t('pages.versions.table.file_name') }}</th>
                <th>{{ ui_t('pages.versions.table.submitted_by') }}</th>
                <th>{{ ui_t('pages.versions.table.date') }}</th>
                <th>{{ ui_t('pages.versions.table.last_update') }}</th>
                <th>{{ ui_t('pages.versions.table.subcategory') }}</th>
                <th class="text-center">{{ ui_t('pages.versions.table.version') }}</th>
                <th class="text-center">{{ ui_t('pages.versions.table.tags') }}</th>
                <th class="text-center">{{ ui_t('pages.versions.table.status') }}</th>
                <th class="text-center">{{ ui_t('pages.versions.table.actions') }}</th>
            </tr>
            </thead>
            <tbody>
            @foreach($documentVersions as $documentVersion)
                <tr>
                    <td>
                        <div>
                            <div>
                                
                                @php
                                    $extension = strtolower(pathinfo($documentVersion->file_path, PATHINFO_EXTENSION));
                                    $iconClass = getFileIcon($extension);
                                @endphp
                                <i class="{{ $iconClass }}" style="font-size: 20px;" title="{{ strtoupper($extension) }}"></i>
                            {{ $documentVersion->document?->title }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="img-history d-flex align-items-center">
                            <div class="me-2">
                                <img
                                    src="{{ asset('storage/'.$documentVersion?->document?->createdBy?->image) }}"
                                    alt="user"
                                />
                            </div>
                            <div>{{ $documentVersion->document?->createdBy?->full_name }}</div>
                        </div>
                    </td>
                    <td>
                        <div>{{ $documentVersion->created_at?->format('j/n/Y') }} <br />{{ $documentVersion->created_at?->format('g:ia') }}</div>
                    </td>
                    <td>
                        <div>{{ $documentVersion->updated_at?->format('j/n/Y') }} <br />{{ $documentVersion->updated_at?->format('g:ia') }}</div>
                    </td>

                    <td>
                        <div>{{ $documentVersion->document?->subcategory?->name }}</div>
                    </td>
                    <td class="text-center">
                        {{ $documentVersion->version_number }}
                    </td>
                    <td class="text-center">
                        @if ($documentVersion->document?->tags->isNotEmpty())
                            {{ $documentVersion->document?->tags->pluck('name')->implode(', ') }}
                        @endif
                    </td>

                    <td class="text-center">
                        <div class="d-flex justify-content-center align-items-center">
                            <button class="status-badge approved border-0">
                                {{ ui_t('pages.documents.status.' . ($documentVersion->document?->status ?? '')) }}
                            </button>
                        </div>
                    </td>
                    <td class="text-center">
                        @can('view',$documentVersion->document)
                            <a href="{{ route('document-versions.preview',['id' => $documentVersion->id]) }}" class="btn-action text-decoration-none">
                                {{ ui_t('pages.versions.preview') }}
                            </a>
                            <span class="mx-1"></span>
                            @canany(['view any ocr job', 'view department ocr job', 'view own ocr job'])
                                <a href="{{ route('document-versions.ocr',['id' => $documentVersion->id]) }}" class="btn-action text-decoration-none">
                                    {{ ui_t('pages.versions.ocr') }}
                                </a>
                            @endcanany
                        @endcan
                    </td>
                </tr>
            @endforeach


            </tbody>
        </table>

        <x-pagination :items="$documentVersions"></x-pagination>
</div>


