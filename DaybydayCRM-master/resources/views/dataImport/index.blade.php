@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Import Data') }}</div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if(!session('suggestedTable'))
                        <form method="POST" action="{{ route('dataImport.analyze') }}" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group mb-3">
                                <label for="file">{{ __('Select CSV File') }}</label>
                                <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file" accept=".csv" required>
                                @error('file')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">{{ __('Analyze File') }}</button>
                        </form>
                    @endif

                    @if(session('suggestedTable'))
                        <div class="alert alert-info">
                            <h5>{{ __('File Analysis Results') }}</h5>
                            <p><strong>{{ __('File') }}:</strong> {{ session('fileName') }}</p>
                            <p><strong>{{ __('Suggested Table') }}:</strong> {{ session('suggestedTable')['table'] }}</p>
                            <p><strong>{{ __('Match Score') }}:</strong> {{ number_format(session('suggestedTable')['score'] * 100, 1) }}%</p>
                        </div>

                        @if(session('consistency'))
                            <div class="card mb-3">
                                <div class="card-body">
                                    <h6>{{ __('Column Mapping') }}</h6>
                                    <ul class="list-unstyled">
                                        @foreach(session('consistency')['matching_columns'] as $fileCol => $tableCol)
                                            <li><code>{{ $fileCol }}</code> → <code>{{ $tableCol }}</code></li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>

                            <form method="POST" action="{{ route('dataImport.import') }}">
                                @csrf
                                <input type="hidden" name="table" value="{{ session('suggestedTable')['table'] }}">
                                <input type="hidden" name="tempFile" value="{{ session('tempFile') }}">
                                <input type="hidden" name="fileName" value="{{ session('fileName') }}">
                                
                                <div class="d-flex justify-content-between">
                                    <a href="{{ route('dataImport.index') }}" class="btn btn-secondary">{{ __('Cancel') }}</a>
                                    <button type="submit" class="btn btn-success">{{ __('Confirm Import') }}</button>
                                </div>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection      
