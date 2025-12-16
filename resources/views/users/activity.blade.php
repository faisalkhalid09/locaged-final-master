@extends('layouts.app')

@section('content')

    <div class="activity-log px-4 px-md-0">


        <div class="d-md-flex justify-content-between my-3">
            <h5><i class="fa-solid fa-briefcase me-2"></i>{{ ui_t('pages.user_activity.title') }}</h5>

        </div>

        <table class="files-table">
            <thead>
            <tr>
                <th>
                    <div class="img-history d-flex align-items-center">
                        <div class="me-2">
                            <img
                                src="{{ asset('assets/1627f3a870e9b56d751d07f53392d7a84aa55817.jpg') }}"
                                alt="user"
                            />
                        </div>
                        <div>{{ $user->full_name }} </div>
                    </div>
                </th>
                <th >
                    <div >
                        {{ $user->role }}
                    </div>
                </th>

                @can('view',$user)
                <th class="text-muted">
                    <a href="{{ route('users.show',['user' => $user->id]) }}" class="btn">
                        <i class="fa-solid fa-circle-exclamation me-2 "></i>{{ ui_t('pages.user_activity.profile') }}
                    </a>
                </th>
                @endcan
            </tr>
            </thead>
        </table>

        @foreach($auditLogs as $log)
            <!-- Approved -->
            <div class="px-5 pt-2">
                <div class="activity-step">
                    <div class="icon-box border rounded-5">
                        <i class="fas fa-check fa-2xl"></i>
                    </div>
                    <div class="ms-4">
                        <strong>{{ $log->action }} {{ $log->document?->title }}</strong>
                        <div class="activity-meta">
                            <i class="fa-solid fa-clock fa-sm me-2"></i>{{ optional($log->occurred_at)->format('Y-m-d H:i') }}
                        </div>
                        <div class="d-flex align-items-center mt-2">
                            <img
                                src="{{ asset('assets/Group 8 (2).svg') }}"

                                alt="te"
                            />
                            <div class="ms-2 " >
                                <div >{{ $log->document?->title ?? ui_t('pages.user_activity.document_unavailable') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        <x-pagination  :items="$auditLogs" />
    </div>



@endsection
