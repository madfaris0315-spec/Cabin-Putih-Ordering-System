// ============================================================
// PAST MODULE - Past Historical Archive functionality
// ============================================================
function filterAndRenderPastOrdersArchive() {
    const tableBody = document.getElementById('historicalPastOrdersTableBody');
    if (!tableBody) return;

    const query = document.getElementById('txtSearchPastOrders').value.trim().toLowerCase();
    const calendarDate = document.getElementById('dateSearchPastOrders').value;
    const sortBy = document.getElementById('ddlSortPastOrders').value;
    const direction = document.getElementById('ddlDirectionPastOrders').value;

    let filtered = window.localCachedMasterOrdersArray.filter(t => t.order_status === 'Completed' || t.order_status === 'Cancelled');

    if (query) {
        filtered = filtered.filter(t =>
            String(t.order_id).toLowerCase().includes(query) ||
            String(t.cust_first_name || '').toLowerCase().includes(query) ||
            String(t.cust_last_name || '').toLowerCase().includes(query)
        );
    }

    if (calendarDate) {
        filtered = filtered.filter(t => String(t.order_date).startsWith(calendarDate));
    }

    filtered.sort((a, b) => {
        let valA = a[sortBy];
        let valB = b[sortBy];
        if (valA < valB) return direction === 'ASC' ? -1 : 1;
        if (valA > valB) return direction === 'ASC' ? 1 : -1;
        return 0;
    });

    if (filtered.length === 0) {
        tableBody.innerHTML = `<tr><td colspan="6" style="text-align:center; color:var(--text-muted); padding:24px;">No database rows matched the criteria.</td></tr>`;
        return;
    }

    tableBody.innerHTML = filtered.map(record => `
        <tr>
            <td class="history-order-id">${record.order_id}</td>
            <td><strong>${record.order_date}</strong></td>
            <td>${record.cust_first_name || 'Walk-In'} ${record.cust_last_name || 'Guest'}</td>
            <td><div class="history-items-list" style="max-width:240px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${record.summary || 'N/A'}</div></td>
            <td><span style="font-weight:700; color:var(--text-primary);">RM ${parseFloat(record.total_amount).toFixed(2)}</span></td>
            <td><span class="cart-item-qty">${record.pay_type}</span></td>
        </tr>
    `).join('');
}

// Export for module usage
window.filterAndRenderPastOrdersArchive = filterAndRenderPastOrdersArchive;
