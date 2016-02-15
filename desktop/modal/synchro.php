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
	throw new Exception('{{401 - Accès non autorisé}}');
}

$id = init('id');
$eqLogics = eqLogic::byType('orvibo');

?>

<div style="display: none;width : 100%" id="div_modal"></div>
            <div class="alert alert-info">Attention, cette fonctionnalité va copier l'intégralité des commandes de cet Orvibo Allone vers un autre Orvibo Allone. Cela détruit les commandes existantes sur le second Orvibo.
            </div>
            
            <div>
			<label>{{Cible de la synchro}}</label>
                        <select class="form-control eqLogicAttr" id="target_ob">
                            <?php
                            foreach ($eqLogics as $eqLogic) {
                                if ($eqLogic->getConfiguration('mac') != $id) {
                                    echo '<option value="' . $eqLogic->getConfiguration('mac') . '">' . $eqLogic->getHumanName(true) . '</option>';
                                }
                            }
                            ?>
                        </select>
			</div>
			<br />                  
                
                 <a class="btn btn-success" id="bt_sync"><i class="fa fa-check-circle"></i> {{Copier les commandes}}</a>
                 
                 
<script>
$('#bt_sync').on('click', function () {
					var source = '<?php echo $id; ?>';
					var target = $('#target_ob').value();
					$.ajax({// fonction permettant de faire de l'ajax
						type: "POST", // methode de transmission des données au fichier php
						url: "plugins/orvibo/core/ajax/orvibo.ajax.php", // url du fichier php
						data: {
							action: "synchronise",
							source: source,
							target: target,
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
						$('#div_modal').showAlert({message: 'Commandes copiées'});
						}
					});
				});
</script>				
