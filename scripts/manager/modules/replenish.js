// ============================================================
// MANAGER REPLENISH MODULE - Stock replenishment requests
// ============================================================

async function fetchReplenishAlertRequests() {
    const tbody = document.getElementById('replenishRequestsTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6">Checking menu inventory levels...</td></tr>';

    try {
        const response = await fetch(`${API_BASE_URL}manager/replenish_alerts`);
        const data = await response.json();
        const items = data.items || [];

        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; color:var(--success); padding:20px;">All stock thresholds healthy. No alert flags.</td></tr>';
            return;
        }

        tbody.innerHTML = items.map(item => `
            <tr>
                <td class="history-order-id">${item.item_id}</td>
                <td><strong>${item.item_name}</strong></td>
                <td>${item.item_type.toUpperCase()}</td>
                <td><span style="color:var(--danger); font-weight:700;">${item.item_qty} units</span></td>
                <td><span class="status-pill-static danger">LOW STOCK</span></td>
                <td style="text-align:right;">
                    <button type="button" class="btn-reorder" onclick="approveRestockDispatches('${item.item_id}')">Approve +100 Units</button>
                </td>
            </tr>
        `).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="6">Error loading logs.</td></tr>';
    }
}

async function approveRestockDispatches(itemId) {
    try {
        const response = await fetch(`${API_BASE_URL}manager/restock_item`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ item_id: itemId, batch_amount: 100 })
        });
        if (response.ok) {
            alert("Inventory transaction processed! Row increment updated.");
            fetchReplenishAlertRequests();
        }
    } catch (err) {
        alert("Failed to write adjustment to database item schema.");
    }
}

window.fetchReplenishAlertRequests = fetchReplenishAlertRequests;
window.approveRestockDispatches = approveRestockDispatches;
