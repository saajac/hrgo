@extends('layouts.auth')
@php
    // $logo=asset(Storage::url('uploads/logo/'));
    $logo = \App\Models\Utility::get_file('uploads/logo/');
    
@endphp
@section('page-title')
    {{ __('Forgot Password') }}
@endsection

@php
    $segment = !empty(\Request::segment(3)) ? \Request::segment(3) : 'en';
    
@endphp
@section('language-bar')
    <li class="nav-item">
        <select name="language" id="language" class="lang-dropdown btn btn-primary my-1 me-2"
            onchange="this.options[this.selectedIndex].value && (window.location = this.options[this.selectedIndex].value);">
            {{-- @foreach (\App\Models\Utility::languages() as $language)
                <option @if ($segment == $language) selected @endif
                    value="{{ route('password.reset', [$request->route('token'), $language]) }}">
                    {{ Str::upper($language) }}
                </option>
            @endforeach --}}
            @foreach (\App\Models\Utility::languages() as $code => $language)
                <option @if ($segment == $code) selected @endif
                    value="{{ route('password.reset', [$request->route('token'), $code]) }}">
                    {{ Str::ucfirst($language) }}
                </option>
            @endforeach
        </select>
    </li>
@endsection


@section('content')
    <div class="card">
        <div class="row align-items-center text-start">
            <div class="col-xl-6">
                <div class="card-body">
                    <div class="">

                        <h2 class="mb-3 f-w-600">{{ __('Reset Password') }}</h2>
                        @if (session('status'))
                            <div class="alert alert-primary">
                                {{ session('status') }}
                            </div>
                        @endif

                    </div>
                    {{-- <form method="POST" action="{{ route('password.update') }}" id="form_data"> --}}
                    {{ Form::open(['route' => 'password.update', 'method' => 'post', 'id' => 'form_data']) }}
                    <input type="hidden" name="token" value="{{ $request->route('token') }}">
                    <div class="">
                        <div class="form-group mb-3">
                            <label for="email" class="form-label">{{ __('Email') }}</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                            @error('email')
                                <span class="error invalid-email text-danger" role="alert">
                                    <small>{{ $message }}</small>
                                </span>
                            @enderror
                        </div>
                        <div class="form-group mb-3">
                            <label for="password" class="form-label">{{ __('Password') }}</label>
                            <input id="password" type="password"
                                class="form-control @error('password') is-invalid @enderror" name="password"
                                value="{{ old('password') }}" required autocomplete="password" autofocus>
                            @error('password')
                                <span class="error invalid-password text-danger" role="alert">
                                    <small>{{ $message }}</small>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label for="password-confirm" class="form-label">{{ __('Confirm Password') }}</label>
                            <input id="password-confirm" type="password" class="form-control" name="password_confirmation"
                                value="{{ old('password') }}" required autocomplete="password" autofocus>
                            @error('password_confirmation')
                                <span class="invalid-password_confirmation text-danger" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                        <div class="d-grid">
                            <button type="submit"
                                class="btn btn-primary btn-submit btn-block mt-2">{{ __('Reset Password') }}</button>
                        </div>

                    </div>
                    </form>
                </div>
            </div>
            <div class="col-xl-6 img-card-side">
                <div class="auth-img-content">
                    <img src="{{ asset('assets/images/auth/img-auth-3.svg') }}" alt="" class="img-fluid">
                    <h3 class="text-white mb-4 mt-5">“Attention is the new currency”</h3>
                    <p class="text-white">The more effortless the writing looks, the more effort the writer
                        actually put into the process.</p>
                </div>
            </div>
        </div>
    </div>
@endsection
