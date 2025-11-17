<a class="btn btn-default" onclick="window.open(window.location + '&sso=yes', '_blank')">
    {$LANG.aa.service.panelalpha.login_to_panelalpha_as_user}
</a>

<div style="display: inline-block; margin-left: 5px; position: relative;">
    <button type="button" class="btn btn-default" id="panelalpha-hosting-sso-btn" onclick="toggleHostingAccountDropdown(event)">
        {$LANG.aa.service.panelalpha.login_to_hosting_control_panel_as_user}
        <span class="caret"></span>
    </button>
    <ul id="panelalpha-hosting-accounts-dropdown" style="display: none; position: absolute; z-index: 1000; background: white; border: 1px solid #ccc; list-style: none; padding: 5px 0; margin: 2px 0 0 0; min-width: 200px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);"></ul>
</div>

<script>
var panelalphaServerAccounts = {$serverAccountsJson};

function toggleHostingAccountDropdown(e) {
    e.preventDefault();
    e.stopPropagation();
    
    var dropdown = document.getElementById("panelalpha-hosting-accounts-dropdown");
    if (dropdown.style.display === "none") {
        dropdown.innerHTML = "";
        
        if (panelalphaServerAccounts.length === 0) {
            dropdown.innerHTML = "<li style=\"padding: 8px 12px; color: #999;\">{$LANG.aa.service.panelalpha.no_hosting_accounts}</li>";
        } else {
            panelalphaServerAccounts.forEach(function(account) {
                var li = document.createElement("li");
                li.style.padding = "8px 12px";
                li.style.cursor = "pointer";
                li.onmouseover = function() { this.style.background = "#f5f5f5"; };
                li.onmouseout = function() { this.style.background = "white"; };
                
                var displayName = account.username || account.domain || "Account #" + account.id;
                li.textContent = displayName;
                
                li.onclick = function(ev) {
                    ev.preventDefault();
                    window.open(window.location + "&hosting_sso=yes&account_id=" + account.id, "_blank");
                    dropdown.style.display = "none";
                };
                
                dropdown.appendChild(li);
            });
        }
        
        dropdown.style.display = "block";
    } else {
        dropdown.style.display = "none";
    }
}

document.addEventListener("click", function(e) {
    var dropdown = document.getElementById("panelalpha-hosting-accounts-dropdown");
    var btn = document.getElementById("panelalpha-hosting-sso-btn");
    if (dropdown && btn && !btn.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.style.display = "none";
    }
});
</script>
