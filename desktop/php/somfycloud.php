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

if (!isConnect('admin')) {
    throw new Exception('{{401 - Acces non autorise}}');
}

$plugin = plugin::byId('somfycloud');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
?>

<div class="row row-overflow">
    <!-- Equipment list -->
    <div class="col-xs-12 eqLogicThumbnailDisplay">
        <legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
        <div class="eqLogicThumbnailContainer">
            <div class="cursor eqLogicAction logoPrimary" data-action="add">
                <i class="fas fa-plus-circle"></i>
                <br />
                <span>{{Ajouter}}</span>
            </div>
            <div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
                <i class="fas fa-wrench"></i>
                <br />
                <span>{{Configuration}}</span>
            </div>
        </div>

        <legend><i class="fas fa-home"></i> {{Mes equipements Somfy}}</legend>
        <input class="form-control" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
        <div class="eqLogicThumbnailContainer">
            <?php
            foreach ($eqLogics as $eqLogic) {
                $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
                $uiClass = $eqLogic->getConfiguration('uiClass', '');
                // Map uiClass to an icon
                $icon = 'fas fa-cube';
                $iconColor = '#9b59b6';
                switch ($uiClass) {
                    case 'RollerShutter':
                    case 'Shutter':
                    case 'ExteriorScreen':
                    case 'Screen':
                        $icon = 'fas fa-bars';
                        $iconColor = '#3498db';
                        break;
                    case 'Light':
                        $icon = 'fas fa-lightbulb';
                        $iconColor = '#f1c40f';
                        break;
                    case 'GarageDoor':
                        $icon = 'fas fa-warehouse';
                        $iconColor = '#e67e22';
                        break;
                    case 'Gate':
                        $icon = 'fas fa-door-open';
                        $iconColor = '#e67e22';
                        break;
                    case 'Window':
                        $icon = 'fas fa-window-maximize';
                        $iconColor = '#1abc9c';
                        break;
                    case 'Awning':
                    case 'Pergola':
                        $icon = 'fas fa-umbrella-beach';
                        $iconColor = '#2ecc71';
                        break;
                    case 'VenetianBlind':
                        $icon = 'fas fa-align-justify';
                        $iconColor = '#3498db';
                        break;
                }
                echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
                echo '<i class="' . $icon . '" style="font-size:3em;color:' . $iconColor . ';margin-top:15px;"></i>';
                echo '<br />';
                $displayName = $eqLogic->getName();
                $object = $eqLogic->getObject();
                if (is_object($object)) {
                    $displayName = $object->getName() . ' - ' . $displayName;
                }
                echo '<span class="name">' . htmlspecialchars($displayName, ENT_QUOTES) . '</span>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Equipment detail -->
    <div class="col-xs-12 eqLogic" style="display: none;">
        <div class="input-group pull-right" style="display:inline-flex">
            <span class="input-group-btn">
                <a class="btn btn-default btn-sm eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancee}}</a>
                <a class="btn btn-default btn-sm eqLogicAction" data-action="copy"><i class="fas fa-copy"></i> {{Dupliquer}}</a>
                <a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
                <a class="btn btn-danger btn-sm eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
            </span>
        </div>

        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
            <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
            <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
        </ul>

        <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
            <!-- Equipment tab -->
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <br />
                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Nom de l'equipement}}</label>
                            <div class="col-sm-3">
                                <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display: none;" />
                                <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'equipement}}" />
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Objet parent}}</label>
                            <div class="col-sm-3">
                                <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                                    <option value="">{{Aucun}}</option>
                                    <?php
                                    $options = '';
                                    foreach ((jeeObject::buildTree(null, false)) as $object) {
                                        $options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
                                    }
                                    echo $options;
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Categorie}}</label>
                            <div class="col-sm-9">
                                <?php
                                foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                    echo '<label class="checkbox-inline">';
                                    echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                                    echo '</label>';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Options}}</label>
                            <div class="col-sm-9">
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked />{{Activer}}</label>
                                <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked />{{Visible}}</label>
                            </div>
                        </div>
                    </fieldset>
                </form>

                <legend><i class="fas fa-info-circle"></i> {{Informations}}</legend>
                <form class="form-horizontal">
                    <fieldset>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Device URL}}</label>
                            <div class="col-sm-5">
                                <span class="eqLogicAttr" data-l1key="configuration" data-l2key="deviceURL"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Type (uiClass)}}</label>
                            <div class="col-sm-5">
                                <span class="eqLogicAttr" data-l1key="configuration" data-l2key="uiClass"></span>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 control-label">{{Controllable Name}}</label>
                            <div class="col-sm-5">
                                <span class="eqLogicAttr" data-l1key="configuration" data-l2key="controllableName"></span>
                            </div>
                        </div>
                    </fieldset>
                </form>
            </div>

            <!-- Commands tab -->
            <div role="tabpanel" class="tab-pane" id="commandtab">
                <a class="btn btn-success btn-sm cmdAction pull-right" data-action="add" style="margin-top:5px;">
                    <i class="fas fa-plus-circle"></i> {{Ajouter une commande}}
                </a>
                <br /><br />
                <table id="table_cmd" class="table table-bordered table-condensed">
                    <thead>
                        <tr>
                            <th>{{Nom}}</th>
                            <th>{{Type}}</th>
                            <th>{{Logical ID}}</th>
                            <th>{{Options}}</th>
                            <th>{{Actions}}</th>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_file('desktop', 'somfycloud', 'js', 'somfycloud'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
