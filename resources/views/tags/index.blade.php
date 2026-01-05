@extends('layouts.app')

@section('content')
    <div class="addtag w-75 mt-5 position-relative">
        <h3>{{ ui_t('pages.tags_page.manage') }}</h3>

        @can('create',\App\Models\Tag::class)
            <div class="mt-4">
                <form method="post" action="{{ route('tags.store') }}">
                    @csrf
                    <h6>{{ ui_t('pages.tags_page.create_new') }}</h6>
                    <div class="d-flex justify-content-between">
                        <input type="text" id="tagInput" name="name" class="form-control me-3 w-50 py-2"
                               placeholder="{{ ui_t('pages.tags_page.tag_name') }}" value="{{ old('name') }}">
                        <button id="addTagBtn" type="submit" class="btn-add">{{ ui_t('pages.tags_page.add_tag') }}</button>
                    </div>
                </form>

            </div>
        @endcan
        <div class="mt-5">
            <h6>{{ ui_t('pages.tags_page.existing') }}</h6>
            <div id="tagsList" class="mt-3">
                @foreach($tags as $tag)
                    <div class="tag-item d-flex justify-content-between align-items-center mb-2" data-id="{{ $tag->id }}">
                        <span>{{ $tag->name }}</span>
                        <div class="d-inline-flex gap-1">
                            @can('update',$tag)
                                <button class="btn-delete btn btn-sm" type="button" data-bs-toggle="modal" data-bs-target="#editModal{{ $tag->id }}">
                                    {{ ui_t('pages.tags_page.edit') }}
                                </button>
                                <!-- Modal -->
                                @include('components.modals.edit-tag-modal',['tag' => $tag])
                            @endcan
                            @can('delete',$tag)
                                    <button type="button"
                                            data-id="{{ $tag->id }}"
                                            data-name="{{ $tag->name }}"
                                            data-url="{{ route('tags.destroy', $tag->id) }}"
                                            class="btn btn-sm btn-delete  trigger-action"
                                            data-method="DELETE"
                                            data-button-text="{{ ui_t('pages.tags_page.confirm') }}"
                                            data-title="{{ ui_t('pages.tags_page.delete_title', ['name' => $tag->name]) }}"
                                            data-body="{{ ui_t('pages.tags_page.delete_body') }}">
                                        {{ ui_t('pages.tags_page.delete') }}
                                    </button>
                                @endcan

                        </div>

                    </div>

                @endforeach
            </div>
        </div>
    </div>
    @include('components.modals.confirm-modal')
@endsection


