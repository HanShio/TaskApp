// Live search for the "My Work" tabs on the profile page
function filterWork() {
    var q = document.getElementById("workSearch").value.trim().toLowerCase();
    var panes = document.querySelectorAll("#workTabContent .tab-pane");

    panes.forEach(function (pane) {
        var rows = pane.querySelectorAll("tbody tr");
        var visible = 0;

        rows.forEach(function (row) {
            // Compare the search text with everything shown in the row
            var match = row.textContent.toLowerCase().indexOf(q) !== -1;
            row.style.display = match ? "" : "none";
            if (match) { visible++; }
        });

        // Show "no match" only when the tab has rows but none match
        var msg = pane.querySelector(".no-match");
        if (msg) {
            msg.classList.toggle("d-none", !(rows.length > 0 && visible === 0));
        }

        // Update the number on the tab button, e.g. "Current (2)"
        var btn = document.querySelector('[data-bs-target="#' + pane.id + '"]');
        if (btn) {
            btn.textContent = btn.dataset.label + " (" + visible + ")";
        }
    });
}