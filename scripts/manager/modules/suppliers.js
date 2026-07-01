// ============================================================
// MANAGER SUPPLIERS MODULE - Supplier logistics directory
// ============================================================

async function fetchSupplierLogisticsDirectory() {
    const tbody = document.getElementById('supplierProfilesTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="5">Opening supplier directory tables...</td></tr>';

    try {
        const response = await fetch(`${API_BASE_URL}manager/supplier_list`);
        const data = await response.json();

        tbody.innerHTML = (data.items || []).map(sup => `
            <tr>
                <td class="history-order-id">${sup.sup_id}</td>
                <td><strong>${sup.sup_name}</strong></td>
                <td><span class="cart-item-qty">${sup.sup_type.toUpperCase()}</span></td>
                <td>${sup.sup_phone}</td>
                <td><span style="font-weight:600; color:var(--amber);">${sup.delivery_date}</span></td>
            </tr>
        `).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="5">Error syncing contact metrics.</td></tr>';
    }
}

window.fetchSupplierLogisticsDirectory = fetchSupplierLogisticsDirectory;
