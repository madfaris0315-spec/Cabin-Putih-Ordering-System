// ============================================================
// HUB MODULE - Active Kitchen Channels Hub
// ============================================================
function filterAndRenderActiveOrdersHub() {
    const grid = document.getElementById('kitchenOrdersContainerGrid');
    if (!grid) return;

    const query = document.getElementById('txtSearchActiveOrders').value.trim().toLowerCase();
    const sortBy = document.getElementById('ddlSortActiveOrders').value;
    const direction = document.getElementById('ddlDirectionActiveOrders').value;

    let filtered = window.localCachedMasterOrdersArray.filter(t =>
        t.order_status !== 'Cancelled' && t.order_status !== 'Completed'
    );

    if (query) {
        filtered = filtered.filter(t =>
            String(t.order_id).toLowerCase().includes(query) ||
            String(t.cust_first_name || '').toLowerCase().includes(query) ||
            String(t.cust_last_name || '').toLowerCase().includes(query)
        );
    }

    filtered.sort((a, b) => {
        let valA = a[sortBy];
        let valB = b[sortBy];
        if (valA < valB) return direction === 'ASC' ? -1 : 1;
        if (valA > valB) return direction === 'ASC' ? 1 : -1;
        return 0;
    });

    if (filtered.length === 0) {
        grid.innerHTML = `<div class="tracker-empty-state-card"><h3>No Active Tickets Located</h3></div>`;
        return;
    }

    grid.innerHTML = filtered.map(ticket => {
        const status = ticket.order_status ? ticket.order_status.trim().toLowerCase() : 'pending';
        let progressClass = 'fill-received';
        let receivedActive = 'done', preparingActive = '', readyActive = '', completedActive = '';
        let actionButtonHtml = '';

        if (status === 'pending') {
            actionButtonHtml = `<button type="button" class="btn-cart-action primary" onclick="executeOrderStatusUpdate('${ticket.order_id}', 'Preparing')">Start Preparing &rarr;</button>`;
        } else if (status === 'preparing') {
            progressClass = 'fill-preparing';
            preparingActive = 'active';
            actionButtonHtml = `<button type="button" class="btn-cart-action primary" style="background:var(--amber);" onclick="executeOrderStatusUpdate('${ticket.order_id}', 'Ready')">Mark as Ready &rarr;</button>`;
        } else if (status === 'ready') {
            progressClass = 'fill-completed';
            preparingActive = 'done';
            readyActive = 'active';
            actionButtonHtml = `<button type="button" class="btn-cart-action primary" style="background:var(--success);" onclick="executeOrderStatusUpdate('${ticket.order_id}', 'Completed')">Complete Handover &check;</button>`;
        }

        return `
            <div class="tracker-card">
                <div class="tracker-header">
                    <div>
                        <div class="order-id-badge">${ticket.order_id}</div>
                        <div class="order-date-meta"><strong>Guest:</strong> ${ticket.cust_first_name || 'Walk-In'} ${ticket.cust_last_name || ''} &bull; ${ticket.dining_type}</div>
                    </div>
                    <div>
                        <div class="order-price-badge">RM ${parseFloat(ticket.total_amount).toFixed(2)}</div>
                        <div class="order-payment-type">${ticket.pay_type}</div>
                    </div>
                </div>
                <div class="progress-timeline">
                    <div class="progress-connector-line ${progressClass}"></div>
                    <div class="timeline-node ${receivedActive}"><div class="node-dot"></div><span class="node-label">Received</span></div>
                    <div class="timeline-node ${preparingActive}"><div class="node-dot"></div><span class="node-label">Preparing</span></div>
                    <div class="timeline-node ${readyActive}"><div class="node-dot"></div><span class="node-label">Ready</span></div>
                    <div class="timeline-node ${completedActive}"><div class="node-dot"></div><span class="node-label">Done</span></div>
                </div>
                <div class="order-summary-items" style="margin-bottom:12px;">${ticket.summary || 'Summary mapping processing...'}</div>
                <div>${actionButtonHtml}</div>
            </div>`;
    }).join('');
}

async function executeOrderStatusUpdate(orderId, nextStatusTarget) {
    const payload = {
        order_id: orderId,
        status: nextStatusTarget,
        employee_num: window.currentStaffId || 'EMP002'
    };

    try {
        const response = await fetch(`${window.API_BASE_URL}staff/update_status`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(payload)
        });
        if (response.ok) {
            await window.switchView('hub');
        }
    } catch (err) {
        alert("Transaction processing framework timeout advancing status attributes.");
    }
}

// Export for module usage
window.filterAndRenderActiveOrdersHub = filterAndRenderActiveOrdersHub;
window.executeOrderStatusUpdate = executeOrderStatusUpdate;
