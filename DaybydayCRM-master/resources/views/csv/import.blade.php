@extends('layouts.master')
@section('heading')
    <h1>Gestion des Données</h1>
@stop

@section('content')
<div class="row">
    <!-- Section Import CSV -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h3>Import de Données CSV</h3>
            </div>
            <div class="card-body">
                <form id="csvUploadForm" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group">
                        <label for="csv_file">Fichier CSV</label>
                        <div class="input-group">
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="csv_file" name="csv_file" accept=".csv">
                                <label class="custom-file-label" for="csv_file">Choisir un fichier</label>
                            </div>
                            <div class="input-group-append">
                                <button type="button" class="btn btn-info" id="analyzeBtn">
                                    <i class="fa fa-search"></i> Analyser
                                </button>
                                <button type="button" class="btn btn-success" id="importBtn" disabled>
                                    <i class="fa fa-upload"></i> Importer
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Résultats de l'analyse -->
                <div id="analysisResults" class="mt-4" style="display: none;">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Résultats de l'Analyse</h6>
                        </div>
                        <div class="card-body p-0">
                            <div id="tableMatches" class="table-responsive"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Réinitialisation et Stats -->
    <div class="col-md-4">
        <!-- Réinitialisation -->
        <div class="card">
            <div class="card-header bg-warning">
                <h3 class="text-white">Réinitialisation</h3>
            </div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle"></i>
                    <strong>Attention!</strong>
                    <p class="mb-0">Cette action va réinitialiser toutes les données tout en préservant les utilisateurs admin.</p>
                </div>
                <button type="button" class="btn btn-danger btn-block" id="resetBtn">
                    <i class="fa fa-refresh"></i> Réinitialiser
                </button>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="card mt-3">
            <div class="card-header bg-info">
                <h3 class="text-white">Statistiques</h3>
            </div>
            <div class="card-body p-0">
                <div id="dbStats">
                    <div class="text-center py-3">
                        <p class="text-muted mb-0">Chargement des statistiques...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation -->
<div class="modal fade" id="confirmModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmation</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="confirmMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fa fa-times"></i> Annuler
                </button>
                <button type="button" class="btn btn-danger" id="confirmBtn">
                    <i class="fa fa-check"></i> Confirmer
                </button>
            </div>
        </div>
    </div>
</div>
@stop

@push('scripts')
<script>
$(document).ready(function() {
    // Mise à jour du nom du fichier
    $('.custom-file-input').on('change', function() {
        let fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName || 'Choisir un fichier');
    });

    // Analyse du fichier
    $('#analyzeBtn').click(function() {
        if (!$('#csv_file').val()) {
            toastr.error('Veuillez sélectionner un fichier CSV');
            return;
        }

        var formData = new FormData($('#csvUploadForm')[0]);
        
        $.ajax({
            url: '/csv/analyze',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $('#analyzeBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
            },
            success: function(response) {
                if (response.success) {
                    displayAnalysis(response.data);
                    $('#importBtn').prop('disabled', false);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error('Erreur lors de l\'analyse: ' + xhr.responseText);
            },
            complete: function() {
                $('#analyzeBtn').prop('disabled', false).html('<i class="fa fa-search"></i> Analyser');
            }
        });
    });

    // Import des données
    $('#importBtn').click(function() {
        showConfirmation(
            'Êtes-vous sûr de vouloir importer ces données ?',
            function() {
                var formData = new FormData($('#csvUploadForm')[0]);
                
                $.ajax({
                    url: '/csv',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    beforeSend: function() {
                        $('#importBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Import réussi!');
                            $('#analysisResults').hide();
                            $('#importBtn').prop('disabled', true);
                            updateStats();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Erreur lors de l\'import: ' + xhr.responseText);
                    },
                    complete: function() {
                        $('#importBtn').prop('disabled', false).html('<i class="fa fa-upload"></i> Importer');
                    }
                });
            }
        );
    });

    // Réinitialisation
    $('#resetBtn').click(function() {
        showConfirmation(
            '<div class="text-center mb-3"><i class="fa fa-exclamation-triangle fa-3x text-warning"></i></div>' +
            '<p class="text-center">ATTENTION: Cette action va réinitialiser toutes les données.<br>' +
            'Seuls les utilisateurs admin seront préservés.</p>' +
            '<p class="text-center text-danger"><strong>Cette action est irréversible!</strong></p>' +
            '<p class="text-center">Êtes-vous sûr de vouloir continuer ?</p>',
            function() {
                $.ajax({
                    url: '/csv/reset',
                    type: 'POST',
                    data: {_token: $('meta[name="csrf-token"]').attr('content')},
                    beforeSend: function() {
                        $('#resetBtn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i>');
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success('Base de données réinitialisée avec succès!');
                            updateStats();
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Erreur lors de la réinitialisation: ' + xhr.responseText);
                    },
                    complete: function() {
                        $('#resetBtn').prop('disabled', false).html('<i class="fa fa-refresh"></i> Réinitialiser');
                    }
                });
            }
        );
    });

    // Affichage de l'analyse
    function displayAnalysis(data) {
        var html = '<table class="table table-bordered table-hover mb-0">';
        html += '<thead class="thead-light"><tr><th>Table</th><th>Score</th><th>Correspondances</th></tr></thead><tbody>';
        
        Object.keys(data.suggestions).forEach(function(table) {
            var suggestion = data.suggestions[table];
            html += '<tr>';
            html += '<td><strong>' + table + '</strong></td>';
            html += '<td><div class="progress" style="height: 20px;">' +
                   '<div class="progress-bar bg-success" role="progressbar" style="width: ' + suggestion.score + '%">' +
                   suggestion.score + '%</div></div></td>';
            html += '<td>';
            
            ['exact', 'similar', 'pattern', 'type'].forEach(function(matchType) {
                if (suggestion.matches[matchType].length > 0) {
                    var badgeClass = matchType === 'exact' ? 'success' :
                                   matchType === 'similar' ? 'info' :
                                   matchType === 'pattern' ? 'warning' : 'secondary';
                    
                    html += '<div class="mb-2">';
                    html += '<span class="badge badge-' + badgeClass + '">' + matchType + '</span><br>';
                    suggestion.matches[matchType].forEach(function(match) {
                        html += '<small>' + match.csv_column + ' → ' + match.table_column + '</small><br>';
                    });
                    html += '</div>';
                }
            });
            
            html += '</td></tr>';
        });
        
        html += '</tbody></table>';
        
        $('#tableMatches').html(html);
        $('#analysisResults').slideDown();
    }

    // Mise à jour des stats
    function updateStats() {
        $.get('/csv/stats', function(data) {
            var html = '<ul class="list-group list-group-flush">';
            data.tables.forEach(function(table) {
                html += '<li class="list-group-item d-flex justify-content-between align-items-center">' +
                        table.name +
                        '<span class="badge badge-primary badge-pill">' + table.count + '</span></li>';
            });
            html += '</ul>';
            $('#dbStats').html(html);
        });
    }

    // Confirmation
    function showConfirmation(message, callback) {
        $('#confirmMessage').html(message);
        $('#confirmBtn').off('click').on('click', function() {
            $('#confirmModal').modal('hide');
            callback();
        });
        $('#confirmModal').modal('show');
    }

    // Charger les stats initiales
    updateStats();
});
</script>
@endpush
