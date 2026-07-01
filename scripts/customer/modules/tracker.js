// ============================================================
// TRACKER MODULE - Order tracking functionality
// ============================================================

async function renderTrackerSection() {
    const liveTrackerContainer = document.getElementById('liveTrackerBoxWrapper');
    if (!liveTrackerContainer) return;

    liveTrackerContainer.innerHTML = `<div class="loading">Fetching active order metrics from server...</div>`;

    try {
        const response = await fetch(`${window.API_BASE_URL}customer/active_orders?cust_id=${window.currentCustomerID}`, { method: 'GET' });
        if (!response.ok) throw new Error('Failed to synchronize tracker frames.');

        const data = await response.json();
        const activeOrdersList = data.items || [];

        if (activeOrdersList.length === 0) {
            liveTrackerContainer.innerHTML = `
                <div class="tracker-empty-state-card">
                    <div class="tracker-empty-icon">🍽️</div>
                    <h3>No Active Orders Found</h3>
                    <p style="font-size:0.9rem; color:var(--text-muted);">You have no active orders running right now.</p>
                </div>`;
            return;
        }

        liveTrackerContainer.innerHTML = activeOrdersList.map(order => {
            const status = order.order_status ? order.order_status.trim().toLowerCase() : 'pending';

            let progressClass = 'fill-received';
            let receivedActive = 'done', preparingActive = '', readyActive = '', completedActive = '';

            if (status === 'preparing') {
                progressClass = 'fill-preparing';
                preparingActive = 'active';
            } else if (status === 'ready') {
                progressClass = 'fill-completed';
                preparingActive = 'done';
                readyActive = 'active';
            } else if (status === 'completed') {
                progressClass = 'fill-completed';
                preparingActive = 'done';
                readyActive = 'done';
                completedActive = 'done';
            }

            return `
                <div class="tracker-card" id="order-card-${order.order_id}">
                    <div class="tracker-header">
                        <div>
                            <div class="order-id-badge">${order.order_id}</div>
                            <div class="order-date-meta">${order.order_date} &bull; ${order.dining_type}</div>
                        </div>
                        <div>
                            <div class="order-price-badge">RM ${parseFloat(order.total_amount).toFixed(2)}</div>
                            <div class="order-payment-type">${order.pay_type}</div>
                        </div>
                    </div>
                    <div class="progress-timeline">
                        <div class="progress-connector-line ${progressClass}"></div>
                        <div class="timeline-node ${receivedActive}"><div class="node-dot"></div><span class="node-label">Received</span></div>
                        <div class="timeline-node ${preparingActive}"><div class="node-dot"></div><span class="node-label">Preparing</span></div>
                        <div class="timeline-node ${readyActive}"><div class="node-dot"></div><span class="node-label">Ready</span></div>
                        <div class="timeline-node ${completedActive}"><div class="node-dot"></div><span class="node-label">Completed</span></div>
                    </div>
                    <div class="order-summary-items">${order.summary || 'Items processing...'}</div>
                    <div class="tracker-footer-actions">
                        <span style="font-size:0.85rem; color:var(--text-muted);">Status tracking linked to live terminal...</span>
                        ${status === 'pending' ? `<button type="button" class="btn-cancel-order" onclick="cancelTrackerOrder('${order.order_id}')">Cancel Order</button>` : ''}
                    </div>
                </div>`;
        }).join('');

    } catch (err) {
        console.error("Tracker sync fault:", err);
        liveTrackerContainer.innerHTML = `<div class="tracker-empty-state-card"><h3>Tracking Sync Offline</h3><p>Unable to connect to database parameters.</p></div>`;
    }
}

async function cancelTrackerOrder(orderId) {
    if (confirm('Cancel this active order?')) {
        try {
            const response = await fetch(`${window.API_BASE_URL}customer/cancel_order`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ order_id: orderId })
            });

            if (!response.ok) throw new Error('Cancellation request broken.');
            const res = await response.json();

            if (res.status === 'success') {
                renderTrackerSection();

                const alertSlot = document.getElementById('trackerAlertNotificationSlot');
                alertSlot.innerHTML = `
                    <div class="alert-toast danger" id="cancellationToastNotification">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        <span><strong>Order Cancelled!</strong> Transaction reference ${orderId} was removed from database queues.</span>
                    </div>`;

                setTimeout(() => {
                    const cancelToast = document.getElementById('cancellationToastNotification');
                    if (cancelToast) {
                        cancelToast.style.opacity = '0';
                        cancelToast.style.transform = 'translateY(-10px)';
                        setTimeout(() => cancelToast.remove(), 300);
                    }
                }, 4000);
            } else {
                alert(res.message || "Failed to cancel order.");
            }
        } catch (err) {
            alert("Error sending cancellation request to DB backend module.");
        }
    }
}

// Export for module usage
window.renderTrackerSection = renderTrackerSection;
window.cancelTrackerOrder = cancelTrackerOrder;
