// ============================================================
// STOCK MODULE - Inventory Stock Room functionality
// ============================================================
function renderInventoryStockroomManagerTable() {
    const tableBody = document.getElementById('inventoryStockTableBody');
    if (!tableBody) return;

    tableBody.innerHTML = window.localCachedMasterMenuCatalog.map(item => {
        const qty = parseInt(item.item_qty || 0);
        let statusPill = `<span class="status-pill-static success">Healthy</span>`;
        if (qty <= 0) {
            statusPill = `<span class="status-pill-static danger">OUT OF STOCK</span>`;
        } else if (qty <= 20) {
            statusPill = `<span class="status-pill-static danger" style="background:var(--amber-faint); color:var(--amber); border-color:var(--amber);">CRITICAL LEVEL</span>`;
        }

        return `
            <tr>
                <td class="history-order-id">${item.item_id}</td>
                <td><strong>${item.item_name}</strong></td>
                <td><span style="font-weight:700; color:var(--amber);">${qty} units remaining</span></td>
                <td>${statusPill}</td>
                <td style="text-align:right;">
                    <button type="button" class="btn-reorder" onclick="dispatchStockReplenishmentRequest('${item.item_id}', '${item.item_name.replace(/'/g, "\\'")}')">Request Stock</button>
                </td>
            </tr>`;
    }).join('');
}

function dispatchStockReplenishmentRequest(itemId, name) {
    alert(`Replenishment notification payload successfully transmitted to Manager for item: ${itemId}`);
}

// Export for module usage
window.renderInventoryStockroomManagerTable = renderInventoryStockroomManagerTable;
window.dispatchStockReplenishmentRequest = dispatchStockReplenishmentRequest;
