@extends('layouts.app')


@section('content')
    <div class="w-75 position-relative ">
        <div class="mt-4">
            <h4 class="fw-bold">{{ ui_t('pages.notifications.title') }}</h4>
            <div class="d-flex justify-content-end mt-4 border-bottom pb-3 notification-info">
              {{--  <div class=" d-flex  ">
                    <p>{{ ui_t('pages.notifications.all') }}</p>
                    <p>{{ ui_t('pages.notifications.important') }}</p>
                    <p>{{ ui_t('pages.notifications.action_required') }}</p>
                </div>--}}
                <a href="#" class="text-primary small text-decoration-none">{{ ui_t('pages.notifications.mark_all_as_read') }}</a>
            </div>


        </div>

        <!-- Notification - Upload Successful -->
        <div class="bg-notification pe-3">
            @foreach($notifications as $notification)
                    <div class="notification">
                        <div class="d-flex align-items-center">
                            <img 
                                class="notification-icon me-3" 
                                style="width: 50px; height: 50px;" 
                                src="{{ array_key_exists('icon',$notification->data) ? asset($notification->data['icon']) : asset('assets/created.png') }}" 
                                alt="notif-icon"
                            >
                            <div class="content">
                                <div class="title">{{ $notification->data['title'] ?? ui_t('pages.notifications.notification') }}</div>
                                <div class="time">{{ $notification->created_at->diffForHumans() }}</div>
                                <div class="message">{{ $notification->data['body'] ?? '' }}</div>
                            </div>
                        </div>
                        <div class="actions">
                            @if(array_key_exists('documentLatestVersionId',$notification->data) && !in_array($notification->data['action'],['destroyed','declined','archived']))
                                <a href="{{ route('document-versions.preview',['id' => $notification->data['documentLatestVersionId']]) }}" class="btn-upload text-white">
                                    {{ ui_t('pages.notifications.view_document') }}
                                </a>
                            @endif

                                <a href="#" ><span class="me-2 mark-p" >{{ ui_t('pages.notifications.marked_as_read') }} </span>
                                    <span class="position-relative ">
                                        <i class="fa-solid fa-check "></i>
                                        <i class="fa-solid fa-check position-absolute icon-check"></i>
                                    </span>
                                </a>
                            </div>
                    </div>
            @endforeach




        </div>

    </div>

    <x-pagination :items="$notifications" />

@endsection
