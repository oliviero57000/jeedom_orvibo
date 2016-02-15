<?php

if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
sendVarToJS('eqType', 'orvibo');
$eqLogics = eqLogic::byType('orvibo');

?>

<div class="row row-overflow">
    <div class="col-lg-2 col-md-3 col-sm-4">
        <div class="bs-sidebar">
            <ul id="ul_eqLogic" class="nav nav-list bs-sidenav">
                <a class="btn btn-default eqLogicAction" style="width : 100%;margin-top : 5px;margin-bottom: 5px;" data-action="add"><i class="fa fa-plus-circle"></i> {{Ajouter un équipement}}</a>
                <li class="filter" style="margin-bottom: 5px;"><input class="filter form-control input-sm" placeholder="{{Rechercher}}" style="width: 100%"/></li>
                <?php
                foreach ($eqLogics as $eqLogic) {
                    echo '<li class="cursor li_eqLogic" data-eqLogic_id="' . $eqLogic->getId() . '"><a>' . $eqLogic->getHumanName(true) . '</a></li>';
                }
                ?>
            </ul>
        </div>
    </div>

    <div class="col-lg-10 col-md-9 col-sm-8 eqLogicThumbnailDisplay" style="border-left: solid 1px #EEE; padding-left: 25px;">
        <legend>{{Mes Orvibo}}
        </legend>
            <div class="eqLogicThumbnailContainer">
                      <div class="cursor eqLogicAction" data-action="add" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >
           <center>
            <i class="fa fa-plus-circle" style="font-size : 7em;color:#00979c;"></i>
        </center>
        <span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>Ajouter</center></span>
    </div>
                <?php
                foreach ($eqLogics as $eqLogic) {
                  $opacity = ($eqLogic->getIsEnable()) ? '' : jeedom::getConfiguration('eqLogic:style:noactive');
                    echo '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' . $eqLogic->getId() . '" style="background-color : #ffffff ; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;' . $opacity . '" >';
                  echo "<center>";
                    $path = $eqLogic->getConfiguration('type');
                    echo '<img src="plugins/orvibo/doc/images/' . $path . '.png" height="105" width="95" />';
                    echo "</center>";
                    echo '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' . $eqLogic->getHumanName(true, true) . '</center></span>';
                    echo '</div>';
                }
                ?>
            </div>
    </div>


    <div class="col-lg-10 col-md-9 col-sm-8 eqLogic" style="border-left: solid 1px #EEE; padding-left: 25px;display: none;">
        <div class="row">
            <div class="col-sm-6">
                <form class="form-horizontal">
            <fieldset>
                <legend><i class="fa fa-arrow-circle-left eqLogicAction cursor" data-action="returnToThumbnailDisplay"></i>  {{Général}}
                <i class='fa fa-cogs eqLogicAction pull-right cursor expertModeVisible' data-action='configure'></i>
                </legend>
                <div class="form-group">
                    <label class="col-md-2 control-label">{{Nom Orvibo}}</label>
                    <div class="col-md-3">
                        <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                        <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement Orvibo}}"/>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label" >{{Objet parent}}</label>
                    <div class="col-md-3">
                        <select class="form-control eqLogicAttr" data-l1key="object_id">
                            <option value="">{{Aucun}}</option>
                            <?php
                            foreach (object::all() as $object) {
                                echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-md-2 control-label">{{Catégorie}}</label>
                    <div class="col-md-8">
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
                <label class="col-sm-2 control-label" ></label>
                <div class="col-sm-9">
                 <input type="checkbox" class="eqLogicAttr bootstrapSwitch" data-label-text="{{Activer}}" data-l1key="isEnable" checked/>
                  <input type="checkbox" class="eqLogicAttr bootstrapSwitch" data-label-text="{{Visible}}" data-l1key="isVisible" checked/>
                </div>
                </div>

                            <div class="form-group">
                    <label class="col-sm-2 control-label">{{Commentaire}}</label>
                    <div class="col-md-8">
                        <textarea class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="commentaire" ></textarea>
                    </div>
                </div>

            </fieldset>

        </form>
        </div>

<div id="infoNode" class="col-sm-6">
                <form class="form-horizontal">
                    <fieldset>
                        <legend>{{Configuration}}</legend>

                        <div class="form-group">
                    		<label class="col-md-2 control-label">{{Addr MAC}}</label>
                    		<div class="col-md-3">
                    		 <span class="form-control eqLogicAttr" id="mac" data-l1key="configuration" data-l2key="mac"></span>
                    		</div>

                    		<label class="col-md-2 control-label">{{Addr IP}}</label>
                    		<div class="col-md-3">
                        	<span class="form-control eqLogicAttr" data-l1key="configuration" data-l2key="addr"></span>
                    		</div>

                	</div>

                        <div class="form-group">
                    		<label class="col-md-2 control-label">{{Type}}</label>
                		 <div class="col-md-3">
                		  <span class="form-control eqLogicAttr" data-l1key="configuration" data-l2key="type"></span>
                    		</div>

                    	        <label class="col-md-2 control-label">{{Der. Activité}}</label>
                    		<div class="col-md-3">
                        	<span class="form-control eqLogicAttr" data-l1key="configuration" data-l2key="updatetime"></span>
                    		</div>

                	</div>

                	<div class="form-group" id="optionAllone">
                    <legend>{{Commandes Infrarouges}}</legend>
                	<label class="col-md-2 control-label">{{Création auto.}}</label>
                    		<div class="col-md-3">
                        	<a class="btn btn-success" id="bt_mCmd">{{Activé}}</a>
                    		</div>

                    		<label class="col-md-2 control-label">{{Apprentissage}}</label>
                    		<div class="col-md-3">
				<a class="btn btn-default" id="bt_mIncl">{{Désactivé}}</a>
            </div>
          </div><div class="form-group" id="optionAllone2">
        <legend>{{Interrupteurs RF433}}</legend>
        <label class="col-md-2 control-label">{{Apprentissage}}</label>
        <div class="col-md-3">
<a class="btn btn-default" id="bt_mRf433">{{Lancer}}</a>

				</div>
                	</div>

                    </fieldset>
                </form>
            </div>
        </div>

	<legend>{{Orvibo}}</legend>

        <form class="form-horizontal">
            <fieldset>
                <div class="form-actions">
                    <a class="btn btn-default btn-sm" id="bt_addorviboAction"><i class="fa fa-plus-circle"></i> {{Ajouter une commande}}</a><br/><br/>
                    <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
                    <a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
                    <a class="btn btn-primary eqLogicAction" id="btn_synOne" onclick="loadModal()"><i class="fa fa-spinner"></i> {{Synchroniser}}</a>
                </div>
            </fieldset>
        </form>

        <br>

        <table id="table_cmd" class="table table-bordered table-condensed" style="word-break: break-all;">
            <thead>
                <tr>
                    <th style="width: 50px;">#</th>
                    <th style="width: 150px;">{{Nom}}</th>
                    <th style="width: 150px;">{{Type}}</th>
                    <th style="width: 250px;">{{Valeur}}</th>
                    <th style="width: 200px;">{{Paramètres}}</th>
                    <th style="width: 100px;"></th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>

        <form class="form-horizontal">
            <fieldset>
                <div class="form-actions">
                    <a class="btn btn-danger eqLogicAction" data-action="remove"><i class="fa fa-minus-circle"></i> {{Supprimer}}</a>
                    <a class="btn btn-success eqLogicAction" data-action="save"><i class="fa fa-check-circle"></i> {{Sauvegarder}}</a>
                    <a class="btn btn-primary eqLogicAction" id="btn_synTwo" onclick="loadModal()"><i class="fa fa-spinner"></i> {{Synchroniser}}</a>
                </div>
            </fieldset>
        </form>

    </div>
</div>

<?php include_file('desktop', 'orvibo', 'js', 'orvibo'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>

<script>
				$('#bt_mIncl').on('click', function () {
					var nodeId = $('#mac').text();
					$.ajax({// fonction permettant de faire de l'ajax
						type: "POST", // methode de transmission des données au fichier php
						url: "plugins/orvibo/core/ajax/orvibo.ajax.php", // url du fichier php
						data: {
							action: "learning",
							node: nodeId,
						},
						dataType: 'json',
						error: function (request, status, error) {
							handleAjaxError(request, status, error);
						},
						success: function (data) { // si l'appel a bien fonctionné
							if (data.state != 'ok') {
								$('#div_alert').showAlert({message: data.result, level: 'danger'});
								return;
							}
						$('.orviboInfo[data-l1key=learning]').value(data.result);
						if (data.result == '1') {
						  $('#bt_mIncl').addClass('btn-success');
						  $('#bt_mIncl').removeClass('btn-default');
						  $('#bt_mIncl').text('Activé');
						  var message = 'activé';
						} else {
							$('#bt_mIncl').addClass('btn-default');
						  $('#bt_mIncl').removeClass('btn-success');
						  $('#bt_mIncl').text('Désactivé');
						    var message = 'désactivé';
						}
						$('#div_alert').showAlert({message: 'Mode apprentissage modifé : ' + message, level: 'success'});
						}
					});
				});

        $('#bt_mRf433').on('click', function () {
					var nodeId = $('#mac').text();
					$.ajax({// fonction permettant de faire de l'ajax
						type: "POST", // methode de transmission des données au fichier php
						url: "plugins/orvibo/core/ajax/orvibo.ajax.php", // url du fichier php
						data: {
							action: "rflearning",
							node: nodeId,
						},
						dataType: 'json',
						error: function (request, status, error) {
							handleAjaxError(request, status, error);
						},
						success: function (data) { // si l'appel a bien fonctionné
							if (data.state != 'ok') {
								$('#div_alert').showAlert({message: data.result, level: 'danger'});
								return;
							}
						}
					});
				});

				$('#bt_mCmd').on('click', function () {
					var nodeId = $('#mac').text();
					$.ajax({// fonction permettant de faire de l'ajax
						type: "POST", // methode de transmission des données au fichier php
						url: "plugins/orvibo/core/ajax/orvibo.ajax.php", // url du fichier php
						data: {
							action: "command",
							node: nodeId,
						},
						dataType: 'json',
						error: function (request, status, error) {
							handleAjaxError(request, status, error);
						},
						success: function (data) { // si l'appel a bien fonctionné
							if (data.state != 'ok') {
								$('#div_alert').showAlert({message: data.result, level: 'danger'});
								return;
							}
						$('.orviboInfo[data-l1key=command]').value(data.result);
						if (data.result == '1') {
						  $('#bt_mCmd').addClass('btn-success');
						  $('#bt_mCmd').removeClass('btn-default');
						  $('#bt_mCmd').text('Activé');
						  var message = 'activée';
						} else {
							$('#bt_mCmd').addClass('btn-default');
						  $('#bt_mCmd').removeClass('btn-success');
						  $('#bt_mCmd').text('Désactivé');
						    var message = 'désactivée';
						}
						$('#div_alert').showAlert({message: 'Création des commandes automatique modifée : ' + message, level: 'success'});
						}
					});
				});

		function loadModal() {
			var nodeId = $('#mac').text();
			$('#md_modal2').dialog({
				title: "Synchronisation"
			});

			$('#md_modal2').load('index.php?v=d&plugin=orvibo&modal=synchro&id=' + nodeId).dialog('open');
			}
			</script>
