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
}

function panelalpha_hideFields() {
	$("#addUsername").parent().parent().hide();
	$("#addPassword").parent().parent().hide();
	$("#inputUsername").parent().parent().hide();
	$("#inputPassword").parent().parent().hide();
}

$(document).ready(function () {
	let selectServerType = $("#inputServerType").val();
	let selectServerTypeAdvanced = $("#addType").val();

	panelalpha_accesshash = $("textarea[name='accesshash']").val();
	if (selectServerType == "panelalpha" || selectServerTypeAdvanced == "panelalpha") {
		panelalpha_hideFields()
		panelalpha_addInput()
	}

	$('#newToken').on('change', function () {
		let token = $(this).val()
		$("input[name='accesshash']").val(token)
	})

	$("#addType").on("change", function () {
		selectServerTypeAdvanced = $(this).val();

		if (panelalpha_InputAdded == true && selectServerTypeAdvanced != "panelalpha") {
			//$('textarea#newHash').parent().parent().show();
			panelalpha_showFields();
			panelalpha_removeInput();
		} else if (!panelalpha_InputAdded && selectServerTypeAdvanced == "panelalpha") {
			//$('textarea#newHash').parent().parent().hide();
			panelalpha_hideFields();
			panelalpha_addInput();
		}

	});

	$("#inputServerType").on("change", function () {
		selectServerType = $(this).val();

		if (panelalpha_InputAdded == true && selectServerType != "panelalpha") {
			panelalpha_showFields();
			panelalpha_removeInput();
		} else if (!panelalpha_InputAdded && selectServerType == "panelalpha") {
			panelalpha_hideFields();
			panelalpha_addInput();
		}

	});
});



