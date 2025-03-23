@extends('layouts.master')

@section('heading')
    {{ __('All Roles') }}
@stop

@section('content')
    <div class="col-lg-12 currenttask">
        <div>
            <p>Clear Data</p>
            <form action="{{ route('reset.database') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-danger">Reset Database</button>
            </form>
            <form action="{{ route('reset.database') }}" method="POST">
                @csrf
                <div>
                    <label>
                        <input type="checkbox" id="select-all"> Select All
                    </label>
                </div>
                @if (!empty($tables) && count($tables) > 0)
                    @foreach ($tables as $table)
                        <div>
                            <label>
                                <input type="checkbox" name="tables[]" value="{{ $table }}"> {{ $table }}
                            </label>
                        </div>
                    @endforeach
                @else
                    <p>No tables available to reset.</p>
                @endif
                <button type="submit" class="btn btn-danger" {{ empty($tables) ? 'disabled' : '' }}>
                    Reset Selected Tables and Seed Data
                </button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('input[name="tables[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });
    </script>
@stop
