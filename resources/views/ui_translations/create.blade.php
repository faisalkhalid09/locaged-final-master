@extends('layouts.app')

@section('content')
    <div class="mt-5 position-relative">

        {{-- Filters / Search --}}
        <form method="GET" class="d-flex w-75 align-items-center mb-3">
            <input type="text" name="q" value="{{ $q }}" class="form-control me-2" placeholder="{{ ui_t('pages.translations.search_placeholder') }}">
            <div class="form-check me-3">
                <input class="form-check-input" type="checkbox" name="missing" value="1" id="missingOnly" {{ $missingOnly ? 'checked' : '' }}>
                <label class="form-check-label" for="missingOnly">{{ ui_t('pages.translations.show_missing_only') }}</label>
            </div>
            <select name="perPage" class="form-select w-auto me-2">
                <option value="25" {{ ($perPage ?? 25) == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ ($perPage ?? 25) == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ ($perPage ?? 25) == 100 ? 'selected' : '' }}>100</option>
            </select>
            <button class="btn btn-outline-secondary">{{ ui_t('pages.translations.filter') }}</button>
        </form>

        {{-- Pagination (top) & summary --}}


        {{-- Always-visible help note --}}
        <div class="w-75 mb-3">
            <div class="alert alert-info py-2 mb-0" role="alert" style="font-size:.95rem;">
                {{ ui_t('pages.translations.help_note') }}
            </div>
        </div>

        {{-- Help / Grouping --}}
        <details class="w-75 mb-3">
            <summary>{{ ui_t('pages.translations.groups_title') }}</summary>
            <div class="text-muted mt-2" style="font-size:.9rem;">
                <ul>
                    <li><strong>nav</strong>: {{ ui_t('pages.translations.groups_description.nav') }}</li>
                    <li><strong>pages</strong>: {{ ui_t('pages.translations.groups_description.pages') }}</li>
                    <li><strong>tables</strong>: {{ ui_t('pages.translations.groups_description.tables') }}</li>
                    <li><strong>filters</strong>: {{ ui_t('pages.translations.groups_description.filters') }}</li>
                    <li><strong>actions</strong>: {{ ui_t('pages.translations.groups_description.actions') }}</li>
                    <li><strong>header</strong>: {{ ui_t('pages.translations.groups_description.header') }}</li>
                    <li><strong>auth</strong>: {{ ui_t('pages.translations.groups_description.auth') }}</li>
                    <li><strong>validation</strong>: {{ ui_t('pages.translations.groups_description.validation') }}</li>
                </ul>
            </div>
        </details>

        {{-- Pagination (top) & summary --}}
        <div class="w-75 d-flex justify-content-between align-items-center mb-2">

            <x-pagination :items="$items" />
        </div>

        {{-- Advanced create removed: keys are defined in code; admin edits overrides only --}}

        {{-- Translations Table --}}
        <div class="w-75 mt-5">
            <table class="table table-hover table-bordered align-middle border-1">
                <thead>
                <tr>
                    <th>{{ ui_t('pages.translations.key') }}</th>
                    <th>{{ ui_t('pages.translations.english') }}</th>
                    <th>{{ ui_t('pages.translations.arabic') }}</th>
                    <th>{{ ui_t('pages.translations.french') }}</th>
                    <th>{{ ui_t('pages.translations.status') }}</th>
                    <th>{{ ui_t('pages.translations.actions') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($items as $row)
                    <tr>
                        @if($row['id'])
                        <form method="POST" action="{{ route('ui-translations.update', $row['id']) }}">
                        @else
                        <form method="POST" action="{{ route('ui-translations.store') }}">
                        @endif
                            @csrf
                            @if($row['id']) @method('PUT') @endif
                            <td>
                                <input type="text" name="key" class="form-control" value="{{ $row['key'] }}" readonly title="{{ ui_t('pages.translations.key_defined_by_developers') }}">
                            </td>
                            <td>
                                <input type="text" name="en_text" class="form-control" value="{{ $row['en_text'] ?? '' }}" placeholder="{{ $row['default_en'] }}" onchange="var f=this.form||this.closest('form'); if(f){ if(f.requestSubmit){ f.requestSubmit(); } else { f.submit(); } }">
                            </td>
                            <td>
                                <input type="text" name="ar_text" class="form-control" value="{{ $row['ar_text'] ?? '' }}" placeholder="{{ $row['default_ar'] }}" onchange="var f=this.form||this.closest('form'); if(f){ if(f.requestSubmit){ f.requestSubmit(); } else { f.submit(); } }">
                            </td>
                            <td>
                                <input type="text" name="fr_text" class="form-control" value="{{ $row['fr_text'] ?? '' }}" placeholder="{{ $row['default_fr'] }}" onchange="var f=this.form||this.closest('form'); if(f){ if(f.requestSubmit){ f.requestSubmit(); } else { f.submit(); } }">
                            </td>
                            <td>
                                @php $overridden = !empty($row['en_text']) || !empty($row['ar_text']) || !empty($row['fr_text']); @endphp
                                @if($overridden)
                                    <span class="badge bg-primary">{{ ui_t('pages.translations.overridden') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ ui_t('pages.translations.using_default') }}</span>
                                @endif
                            </td>
                            <td class="d-flex">
                                <button type="submit" class="btn btn-primary me-2" title="{{ ui_t('pages.translations.save_override_hint') }}">{{ ui_t('pages.translations.save_override') }}</button>
                        </form>
                        @if($row['id'] && (!empty($row['en_text']) || !empty($row['ar_text']) || !empty($row['fr_text'])))
                        <form method="POST" action="{{ route('ui-translations.destroy', $row['id']) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-secondary" onclick="return confirm('{{ ui_t('pages.translations.revert_confirm') }}')">{{ ui_t('pages.translations.revert_to_default') }}</button>
                        </form>
                        @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pagination (bottom) --}}
    <div class="w-75 d-flex justify-content-end">
        <x-pagination :items="$items" />
    </div>
@endsection
