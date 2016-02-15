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

$("#bt_addorviboAction").on('click', function(event) {
    var _cmd = {type: 'action'};
    addCmdToTable(_cmd);
});

$("#table_cmd").delegate(".listEquipementInfo", 'click', function() {
    var el = $(this);
    jeedom.cmd.getSelectModal({cmd: {type: 'info'}}, function(result) {
        var calcul = el.closest('tr').find('.cmdAttr[data-l1key=configuration][data-l2key=calcul]');
        calcul.atCaret('insert', result.human);
    });
});

$("#table_cmd").delegate(".listEquipementAction", 'click', function() {
    var el = $(this);
    jeedom.cmd.getSelectModal({cmd: {type: 'action'}}, function(result) {
        var calcul = el.closest('tr').find('.cmdAttr[data-l1key=configuration][data-l2key=' + el.attr('data-input') + ']');
        calcul.value(result.human);
    });
});

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});

function printEqLogic(_eqLogic){
    $.ajax({// fonction permettant de faire de l'ajax
        type: "POST", // méthode de transmission des données au fichier php
        url: "plugins/orvibo/core/ajax/orvibo.ajax.php", // url du fichier php
        data: {
            action: "getModuleInfo",
            id: _eqLogic.id,
        },
        dataType: 'json',
        global: false,
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) { // si l'appel a bien fonctionné
            if (data.state != 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }

			if (_eqLogic.configuration.command == '1') {
				$('#bt_mCmd').addClass('btn-success');
				$('#bt_mCmd').removeClass('btn-default');
				$('#bt_mCmd').text('Activé');
			} else {
				$('#bt_mCmd').addClass('btn-default');
				$('#bt_mCmd').removeClass('btn-success');
				$('#bt_mCmd').text('Désactivé');
			}

			if (_eqLogic.configuration.learning == '1') {
				$('#bt_mIncl').addClass('btn-success');
				$('#bt_mIncl').removeClass('btn-default');
				$('#bt_mIncl').text('Activé');
			} else {
				$('#bt_mIncl').addClass('btn-default');
				$('#bt_mIncl').removeClass('btn-success');
				$('#bt_mIncl').text('Désactivé');
			}

			if (_eqLogic.configuration.type == 'allone') {
			    $('#bt_addorviboAction').show();
			    $('#optionAllone').show();
          $('#optionAllone2').show();
				if (data.result['number']['value'] > 1) {
					$('#btn_synOne').show();
					$('#btn_synTwo').show();
				} else {
					$('#btn_synOne').hide();
					$('#btn_synTwo').hide();
				}
			} else {
			    $('#bt_addorviboAction').hide();
			    $('#btn_synOne').hide();
				$('#btn_synTwo').hide();
				$('#optionAllone').hide();
        $('#optionAllone2').hide();
			}
		}
    });
}

$('body').on('orvibo::stackData', function (_event,param) {
       //var _cmd = JSON.parse(param);
       addCmdToTable(param);
       $(".eqLogic").animate({ scrollTop: $(".eqLogic").height() });
   });

function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    if (!isset(_cmd.configuration)) {
        _cmd.configuration = {};
    }

    if (init(_cmd.type) == 'info') {
        var disabled = (init(_cmd.configuration.virtualAction) == '1') ? 'disabled' : '';
        var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
        tr += '<td>';
			tr += '<span class="cmdAttr" data-l1key="id"></span>';
        tr += '</td>';
        tr += '<td>';
			tr += '<span class="cmdAttr" data-l1key="name"></span></td>';
      tr += '<td>';
      tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="info" disabled style="margin-bottom : 5px;" />';
   tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
      tr += '</td>';
        tr += '<td>';
        tr += '<span class="cmdAttr" data-l1key="configuration" data-l2key="value"></span>';
        tr += '</td>';
        tr += '<td>';
        tr += '<span><input type="checkbox" data-size="mini" data-label-text="{{Historiser}}" class="cmdAttr bootstrapSwitch" data-l1key="isHistorized" /></span>';
        tr += '<span><input type="checkbox" data-size="mini" data-label-text="{{Afficher}}" class="cmdAttr bootstrapSwitch" data-l1key="isVisible" /></span>';
        tr += '</td>';
        tr += '<td>';
        if (is_numeric(_cmd.id)) {
            tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        }
        tr += '</td>';
        tr += '</tr>';
        $('#table_cmd tbody').append(tr);
        $('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
        /*if (isset(_cmd.type)) {
            $('#table_cmd tbody tr:last .cmdAttr[data-l1key=type]').value(init(_cmd.type));
        }
        jeedom.cmd.changeType($('#table_cmd tbody tr:last'), init(_cmd.subType));*/
    }

    if (init(_cmd.type) == 'action') {
        var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
        tr += '<td>';
        tr += '<span class="cmdAttr" data-l1key="id"></span>';
        tr += '</td>';
        tr += '<td>';
        tr += '<div class="row">';
        tr += '<div class="col-lg-6">';
        tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fa fa-flag"></i> Icone</a>';
        tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
        tr += '</div>';
        tr += '<div class="col-lg-6">';
        tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">';
        tr += '</div>';
        tr += '</td>';
        tr += '<td>';
    tr += '<input class="cmdAttr form-control type input-sm" data-l1key="type" value="action" disabled style="margin-bottom : 5px;" />';
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    tr += '</td>';
         tr += '<td>';
			tr += '<textarea class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="order" style="height : 33px;" ' + disabled + ' placeholder="{{Code}}"></textarea><br/>';
        tr += '</td><td>';
        tr += '<span><input type="checkbox" data-size="mini" data-label-text="{{Afficher}}" class="cmdAttr bootstrapSwitch" data-l1key="isVisible" /></span>';
        tr += '</td>';
        tr += '<td>';
        if (is_numeric(_cmd.id)) {
            tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
            tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> {{Tester}}</a>';
        }
        tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
        tr += '</tr>';

        $('#table_cmd tbody').append(tr);
        //$('#table_cmd tbody tr:last').setValues(_cmd, '.cmdAttr');
        var tr = $('#table_cmd tbody tr:last');
 	jeedom.eqLogic.builSelectCmd({
	id: $(".li_eqLogic.active").attr('data-eqLogic_id'),
	filter: {type: 'info'},
	error: function (error) {
		$('#div_alert').showAlert({message: error.message, level: 'danger'});
	},
	success: function (result) {
		tr.find('.cmdAttr[data-l1key=value]').append(result);
		tr.setValues(_cmd, '.cmdAttr');
		jeedom.cmd.changeType(tr, init(_cmd.subType));
	}
	});

    }
}
