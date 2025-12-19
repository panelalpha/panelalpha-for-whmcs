let panelalpha_td1;
let panelalpha_td2;
let panelalpha_accesshash;
let panelalpha_InputAdded = false;

function panelalpha_addInput() {

	const accessTd = $("#inputServerType").closest("tbody").find(" tr:eq(3)").find(" td:eq(0)");
	panelalpha_td1 = accessTd.html();
	accessTd.html("API Token");

	const accessInput = $("#inputServerType").closest("tbody").find(" tr:eq(3)").find(" td:eq(1)");
	panelalpha_td2 = accessInput.html();
	accessInput.html("<input name=\"accesshash\" class='form-control'/>");

	$('#newToken').show();
	$('#newToken').removeClass('hidden');
	$('#newToken').removeAttr('disabled');
	$('#newHash').hide();
	$('.api-key').show();
	$('.access-hash').hide();

	panelalpha_accesshash ? $("input[name='accesshash']").val(panelalpha_accesshash) : $("input[name='accesshash']").val("")
	panelalpha_accesshash ? $("input[name='newHash']").val(panelalpha_accesshash) : $("input[name='newHash']").val("")
	panelalpha_InputAdded = true;
}

function panelalpha_removeInput() {
	$("#inputServerType").closest("tbody").find(" tr:eq(3)").find(" td:eq(0)").html(panelalpha_td1);
	$("#inputServerType").closest("tbody").find(" tr:eq(3)").find(" td:eq(1)").html(panelalpha_td2);

	$('#newToken').hide();
	$('#newHash').show();
	$('.api-key').hide();
	$('.access-hash').show();

	panelalpha_InputAdded = false
}

function panelalpha_showFields() {
	$("#addUsername").parent().parent().show();
	$("#addPassword").parent().parent().show();
	$("#inputUsername").parent().parent().show();
	$("#inputPassword").parent().parent().show();

	$("#trPort").show();
}

function panelalpha_hideFields() {
	$("#addUsername").parent().parent().hide();
	$("#addPassword").parent().parent().hide();
	$("#inputUsername").parent().parent().hide();
	$("#inputPassword").parent().parent().hide();
}

function panelalpha_showProtocolSelect()
{
	const hostnameInput = $('#inputHostname');
	let value = hostnameInput.val();
	const protocol = value.split(':')[0];
	console.log(protocol);


	const newRow = $('<tr>').attr('id','trProtocol');
	const labelTd = $('<td>').addClass('fieldlabel').text('Protocol');
	const areaTd = $('<td>').addClass('fieldarea');

	const selectElement = $('<select>');
	if (protocol === 'https') {
		selectElement.append($('<option>').text('http').val('http'));
		selectElement.append($('<option>').text('https').val('https').prop('selected', true));
	} else {
		selectElement.append($('<option>').text('http').val('http'));
		selectElement.append($('<option>').text('https').val('https'));
	}

	selectElement.addClass('form-control');
	selectElement.addClass('select-inline');
	selectElement.attr('name', 'username');

	areaTd.append(selectElement);
	newRow.append(labelTd);
	newRow.append(areaTd);

	const secureCheckbox = $("input[name='accesshash']");
	secureCheckbox.parent().parent().after(newRow)
}

function panelalpha_clearHostname()
{
	const hostnameInput = $('#inputHostname');
	let value = hostnameInput.val();
	hostnameInput.val(value.replace(/^https?:\/\//, ''))
}

function panelalpha_customizeSecureToggle()
{
	const secureInput = $("#inputSecure");
	if (!secureInput.length) {
		return;
	}

	const secureRow = secureInput.closest("tr");
	secureRow.find(".fieldlabel").text("Verify SSL Certificate");

	const helperId = "panelalpha-ssl-helper";
	if (!secureRow.find("#" + helperId).length) {
		secureRow.find(".fieldarea").append(
			'<p id="' + helperId + '" class="text-muted" style="margin-top: 8px;">Keep enabled to validate the certificate. Disable only when connecting to a self-signed certificate.</p>'
		);
	}

	if (!secureInput.prop("checked")) {
		secureInput.prop("checked", true);
	}
}

$(document).ready(function () {
	let selectServerType = $("#inputServerType").val();
	let selectServerTypeAdvanced = $("#addType").val();
	
	panelalpha_accesshash = $("textarea[name='accesshash']").val();
	if (selectServerType == "panelalpha" || selectServerTypeAdvanced == "panelalpha") {
		panelalpha_hideFields();
		panelalpha_addInput();
		panelalpha_showProtocolSelect();
		panelalpha_clearHostname();
		panelalpha_customizeSecureToggle();
	}

	$('#newToken').on('change', function () {
		let token = $(this).val()
		$("input[name='accesshash']").val(token)
	})

	$("#addType").on("change", function () {
		selectServerTypeAdvanced = $(this).val();

		if (panelalpha_InputAdded == true && selectServerTypeAdvanced != "panelalpha") {
			panelalpha_showFields();
			panelalpha_removeInput();
			$('#trProtocol').hide();
		} else if (!panelalpha_InputAdded && selectServerTypeAdvanced == "panelalpha") {
			panelalpha_hideFields();
			panelalpha_addInput();
			panelalpha_showProtocolSelect();
			panelalpha_clearHostname();
			panelalpha_customizeSecureToggle();
		}

	});

	$("#inputServerType").on("change", function () {
		selectServerType = $(this).val();

		if (panelalpha_InputAdded == true && selectServerType != "panelalpha") {
			panelalpha_showFields();
			panelalpha_removeInput();
			$('#trProtocol').hide();
		} else if (!panelalpha_InputAdded && selectServerType == "panelalpha") {
			panelalpha_hideFields();
			panelalpha_addInput();
			panelalpha_showProtocolSelect()
			panelalpha_clearHostname();
			panelalpha_customizeSecureToggle();
		}
	});
});


