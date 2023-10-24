<script type="text/javascript">
	$(document).ready(function () {
		const link = $('a:contains("Visit Website")');
		const manageLink = $('#manageLink');
		link.after(manageLink);
	});
</script>

<a id="manageLink" class="btn btn-primary" href="{$url}" target="_blank" style="margin-left: 8px;">{$MGLANG['ca']['service']['panelalpha']['button']['sso_link']}</a>
