<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');

if (!isConnect('admin')) {
    throw new Exception('{{401 - Acces non autorise}}');
}
?>

<form class="form-horizontal">
    <fieldset>
        <legend><i class="fas fa-cloud"></i> {{Configuration du compte Somfy}}</legend>

        <div class="form-group">
            <label class="col-sm-3 control-label">{{Serveur Overkiz}}</label>
            <div class="col-sm-3">
                <select class="configKey form-control" data-l1key="server">
                    <option value="ha101-1.overkiz.com">{{Somfy Europe}}</option>
                    <option value="ha201-1.overkiz.com">{{Somfy Australie}}</option>
                    <option value="ha401-1.overkiz.com">{{Somfy Amerique du Nord}}</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-3 control-label">{{Email (compte Somfy Connect)}}</label>
            <div class="col-sm-5">
                <input type="email" class="configKey form-control" data-l1key="email" placeholder="{{votre@email.com}}" />
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-3 control-label">{{Mot de passe}}</label>
            <div class="col-sm-5">
                <input type="password" class="configKey form-control" data-l1key="password" placeholder="{{Mot de passe Somfy Connect}}" />
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-3 control-label"></label>
            <div class="col-sm-5">
                <a class="btn btn-success" id="bt_testConnection">
                    <i class="fas fa-plug"></i> {{Tester la connexion}}
                </a>
                <a class="btn btn-primary" id="bt_syncDevices" style="margin-left: 10px;">
                    <i class="fas fa-sync"></i> {{Synchroniser les equipements}}
                </a>
            </div>
        </div>

        <div class="form-group">
            <label class="col-sm-3 control-label"></label>
            <div class="col-sm-5">
                <span id="somfycloud_connectionResult"></span>
            </div>
        </div>
    </fieldset>
</form>

<script>
function somfycloud_saveConfig(callback) {
    jeedom.config.save({
        plugin: 'somfycloud',
        configuration: $('#div_plugin_configuration').getValues('.configKey')[0],
        error: function(error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function() {
            if (typeof callback === 'function') {
                callback();
            }
        }
    });
}

$('#bt_testConnection').on('click', function() {
    var btn = $(this);
    btn.prop('disabled', true);
    $('#somfycloud_connectionResult').html('<i class="fas fa-spinner fa-spin"></i> {{Sauvegarde puis test en cours...}}');
    somfycloud_saveConfig(function() {
        $.ajax({
            type: 'POST',
            url: 'plugins/somfycloud/core/ajax/somfycloud.ajax.php',
            data: {
                action: 'testConnection'
            },
            dataType: 'json',
            global: false,
            error: function(request, status, error) {
                btn.prop('disabled', false);
                $('#somfycloud_connectionResult').html('<span class="label label-danger"><i class="fas fa-times"></i> ' + error + '</span>');
            },
            success: function(data) {
                btn.prop('disabled', false);
                if (data.state !== 'ok') {
                    $('#somfycloud_connectionResult').html('<span class="label label-danger"><i class="fas fa-times"></i> ' + data.result + '</span>');
                } else {
                    $('#somfycloud_connectionResult').html('<span class="label label-success"><i class="fas fa-check"></i> ' + data.result + '</span>');
                }
            }
        });
    });
});

$('#bt_syncDevices').on('click', function() {
    var btn = $(this);
    btn.prop('disabled', true);
    $('#somfycloud_connectionResult').html('<i class="fas fa-spinner fa-spin"></i> {{Sauvegarde puis synchronisation en cours...}}');
    somfycloud_saveConfig(function() {
        $.ajax({
            type: 'POST',
            url: 'plugins/somfycloud/core/ajax/somfycloud.ajax.php',
            data: {
                action: 'syncDevices'
            },
            dataType: 'json',
            global: false,
            error: function(request, status, error) {
                btn.prop('disabled', false);
                $('#somfycloud_connectionResult').html('<span class="label label-danger"><i class="fas fa-times"></i> ' + error + '</span>');
            },
            success: function(data) {
                btn.prop('disabled', false);
                if (data.state !== 'ok') {
                    $('#somfycloud_connectionResult').html('<span class="label label-danger"><i class="fas fa-times"></i> ' + data.result + '</span>');
                } else {
                    $('#somfycloud_connectionResult').html('<span class="label label-success"><i class="fas fa-check"></i> ' + data.result + '</span>');
                }
            }
        });
    });
});
</script>
