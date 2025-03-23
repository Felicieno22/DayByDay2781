@extends('layouts.master')

@section('heading')
    {{ __('Reset Database') }}
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h4>{{ __('Reset Database') }}</h4>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle"></i>
                <strong>{{ __('Warning') }}:</strong> 
                {{ __('This action will delete all data except administrator accounts. This action cannot be undone.') }}
            </div>

            <form action="{{ route('reset.database') }}" method="POST" class="mt-4">
                @csrf
                <button type="submit" class="btn btn-danger" 
                        onclick="return confirm('{{ __('Are you sure you want to reset all data? This cannot be undone.') }}')">
                    <i class="fa fa-refresh"></i> {{ __('Reset All Data') }}
                </button>
            </form>
        </div>
    </div>
@stop
