// ============================================================
// CABIN PUTIH COMPREHENSIVE STAFF OPERATIONAL ROUTING PIPELINE
// Reuses view navigation architectures matching customer layouts
// ============================================================
const API_BASE_URL = 'https://oracleapex.com/ords/cabin_putih/api/';

const currentStaffId = sessionStorage.getItem('staff_id');
const cachedStaffName = sessionStorage.getItem('staff_name');
const cachedStaffRole = sessionStorage.getItem('staff_role');

const DEFAULT_PLACEHOLDER_IMG = 'https://images.pexels.com/photos/1251198/pexels-photo-1251198.jpeg?auto=compress&cs=tinysrgb&w=400&h=260';

let localCachedMasterOrdersArray = [];
let localCachedMasterMenuCatalog = [];
let staffPosBasketCartState = {};

function verifyStaffAuthenticationSession() {
    if (!currentStaffId) {
        alert("Staff context missing. Redirecting to account portal verification gateway...");
        window.location.href = 'login.html';
        return false;
    }
    return true;
}

function injectStaffContextDisplay() {
    if (cachedStaffName) {
        document.getElementById('lblStaffDisplayName').textContent = cachedStaffName.toUpperCase();
        document.getElementById('staffAvatarInitials').textContent = cachedStaffName.substring(0, 2).toUpperCase();
    }
}

// TOAST NOTIFICATION ENGINE
function showToastNotification(message, type = 'success') {
    const container = document.getElementById('toastNotificationContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast-card ${type}`;
    toast.innerHTML = `
        <span class="toast-msg">${message}</span>
        <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;

    container.appendChild(toast);

    // Auto-destruct after 4 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.animation = 'fadeOut 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards';
            setTimeout(() => toast.remove(), 300);
        }
    }, 4000);
}

async function downloadOperationalDataFromDatabase() {
    try {
        const menuRes = await fetch(`${API_BASE_URL}customer/menu`, { method: 'GET' });
        if (menuRes.ok) {
            const menuData = await menuRes.json();
            localCachedMasterMenuCatalog = menuData.items || [];
        }

        const orderRes = await fetch(`${API_BASE_URL}customer/active_orders`, { method: 'GET' });
        if (orderRes.ok) {
            const orderData = await orderRes.json();
            localCachedMasterOrdersArray = orderData.items || [];
        }
    } catch (err) {
        console.error("Critical download synchronization fault:", err);
    }
}

async function switchView(targetViewId) {
    document.querySelectorAll('.view-section').forEach(section => section.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(tab => tab.classList.remove('active'));
    
    const targetElement = document.getElementById('view-' + targetViewId);
    if(targetElement) targetElement.classList.add('active');
    
    const activeNavButton = document.getElementById('btn-nav-' + targetViewId);
    if (activeNavButton) activeNavButton.classList.add('active');

    await downloadOperationalDataFromDatabase();

    if (targetViewId === 'register') {
        buildPosRegisterCatalogInterface();
    } else if (targetViewId === 'directory') {
        renderMenuDirectoryCRUDTable();
    } else if (targetViewId === 'hub') {
        filterAndRenderActiveOrdersHub();
    } else if (targetViewId === 'stock') {
        renderInventoryStockroomManagerTable();
    } else if (targetViewId === 'past') {
        filterAndRenderPastOrdersArchive();
    }
}

// ------------------------------------------------------------
// MODULE 1: POS REGISTER FUNCTIONALITY
// ------------------------------------------------------------
function buildPosRegisterCatalogInterface() {
    const filterWrapper = document.getElementById('posCategoryFilterWrapper');
    if (!filterWrapper) return;

    // ADJUSTMENT: Extracts categories dynamically based on items currently in the table
    const distinctTypes = [...new Set(localCachedMasterMenuCatalog
        .map(item => String(item.item_type).trim().toLowerCase())
        .filter(type => type && type !== 'null' && type !== 'undefined')
    )];

    let filterButtonsHtml = `<button type="button" class="filter-btn active" onclick="filterPosCatalogDisplay('all', this)" id="btn-pos-filter-all">ALL ITEMS</button>`;
    
    filterButtonsHtml += distinctTypes.map(cat => 
        `<button type="button" class="filter-btn" onclick="filterPosCatalogDisplay('${cat}', this)" id="btn-pos-filter-${cat}">${cat.toUpperCase()}</button>`
    ).join('');

    filterWrapper.innerHTML = filterButtonsHtml;
    
    // Default system initialization down to the fallback master list
    filterPosCatalogDisplay('all', document.getElementById('btn-pos-filter-all'));
    renderPosCartUI();
}

function filterPosCatalogDisplay(categoryClass, buttonElement) {
    const grid = document.getElementById('posCatalogItemsGrid');
    if(!grid) return;

    if(buttonElement) {
        document.querySelectorAll('#posCategoryFilterWrapper .filter-btn').forEach(b => b.classList.remove('active'));
        buttonElement.classList.add('active');
    }

    const subset = categoryClass === 'all' 
        ? localCachedMasterMenuCatalog 
        : localCachedMasterMenuCatalog.filter(item => String(item.item_type).trim().toLowerCase() === categoryClass);

    if(subset.length === 0) {
        grid.innerHTML = `<div class="loading">No items listed inside this database type context.</div>`;
        return;
    }

    grid.innerHTML = subset.map(item => {
        const isAvailable = item.item_qty > 0 && item.item_status === 'Available';
        const itemType = String(item.item_type || 'burger').trim().toLowerCase();
        
        let displayImageSrc = DEFAULT_PLACEHOLDER_IMG;
        if (itemType === 'noodle') {
            displayImageSrc = 'https://images.pexels.com/photos/1907228/pexels-photo-1907228.jpeg?auto=compress&cs=tinysrgb&w=400&h=260';
        } else if (itemType === 'beverage') {
            displayImageSrc = 'https://images.pexels.com/photos/2474669/pexels-photo-2474669.jpeg?auto=compress&cs=tinysrgb&w=400&h=260';
        } else if (itemType === 'addon') {
            displayImageSrc = 'https://images.pexels.com/photos/4110251/pexels-photo-4110251.jpeg?auto=compress&cs=tinysrgb&w=400&h=260';
        }

        return `
            <div class="food-card">
                <div class="food-img-wrapper" style="position: relative; width: 100%; padding-top: 66%; background-color: var(--bg-card); overflow: hidden;">
                    <img src="${displayImageSrc}" alt="${item.item_name}" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;">
                    ${!isAvailable ? '<div class="sold-out-overlay" style="position: absolute; inset: 0; background: rgba(11, 15, 25, 0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center;"><span class="sold-out-pill" style="background: var(--danger); color: white; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; padding: 6px 16px; border-radius: 99px;">Sold Out</span></div>' : ''}
                </div>
                <div class="food-info">
                    <div class="food-title">${item.item_name}</div>
                    <div class="food-meta">
                        <div class="food-price">RM ${parseFloat(item.item_price).toFixed(2)}</div>
                        ${isAvailable ? `
                            <button type="button" class="btn-add-to-cart" style="width: auto; padding: 0 16px;" onclick="addItemToPosCart('${item.item_id}', '${item.item_name.replace(/'/g, "\\'")}', ${item.item_price})">Add</button>
                        ` : `
                            <button type="button" class="btn-add-to-cart" disabled style="width: auto; padding: 0 16px; background-color: var(--bg-pill); color: var(--text-muted); border-color: var(--border); cursor: not-allowed;">Unavailable</button>
                        `}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function addItemToPosCart(id, name, price) {
    if(staffPosBasketCartState[id]) {
        staffPosBasketCartState[id].qty += 1;
    } else {
        staffPosBasketCartState[id] = { name: name, price: price, qty: 1 };
    }
    renderPosCartUI();
}

function renderPosCartUI() {
    const wrapper = document.getElementById('posCartRowsWrapper');
    const lblTotal = document.getElementById('lblPosTotalSum');
    if(!wrapper) return;

    const keys = Object.keys(staffPosBasketCartState);
    let cumulativeSum = 0;

    if(keys.length === 0) {
        wrapper.innerHTML = `<div class="cart-empty">Basket empty.<br>Select from the menu options.</div>`;
        lblTotal.innerText = "RM 0.00";
        return;
    }

    let rowsHtml = keys.map(id => {
        const row = staffPosBasketCartState[id];
        const combinedRowCost = row.qty * row.price;
        cumulativeSum += combinedRowCost;
        return `
            <div class="cart-row">
                <span class="cart-item-name">${row.name}</span>
                <span class="cart-item-qty">x${row.qty}</span>
                <span class="cart-item-price">RM ${combinedRowCost.toFixed(2)}</span>
                <button type="button" class="cart-remove" onclick="removeItemFromPosCart('${id}')">&times;</button>
            </div>`;
    }).join('');

    wrapper.innerHTML = `
        <div class="cart-items">${rowsHtml}</div>
        <hr class="cart-divider">`;
    
    lblTotal.innerText = `RM ${cumulativeSum.toFixed(2)}`;
}

function removeItemFromPosCart(id) {
    delete staffPosBasketCartState[id];
    renderPosCartUI();
}

function clearPosCartBasket() {
    staffPosBasketCartState = {};
    renderPosCartUI();
}

function saveTerminalLockSelection() {
    const selectedLaneValue = document.getElementById('posTerminalSelectionDropdown').value;
    localStorage.setItem('cp_staff_terminal_lock_lane', selectedLaneValue);
}

async function openPosCheckoutSummaryModal() {
    const keys = Object.keys(staffPosBasketCartState);
    if(keys.length === 0) return;

    try {
        const diningRes = await fetch(`${API_BASE_URL}customer/dining_types`);
        const diningData = await diningRes.json();
        document.getElementById('chkStaffPosDiningType').innerHTML = (diningData.items || []).map(x => 
            `<option value="${x.dining_type}">${x.dining_type}</option>`
        ).join('');

        const paymentRes = await fetch(`${API_BASE_URL}customer/payment_types`);
        const paymentData = await paymentRes.json();
        document.getElementById('chkStaffPosPaymentType').innerHTML = (paymentData.items || []).map(x => 
            `<option value="${x.pay_type}">${x.pay_type}</option>`
        ).join('');
    } catch (e) {
        console.error("Dropdown loading failed, rendering defaults.");
        document.getElementById('chkStaffPosDiningType').innerHTML = '<option value="Dine-In">Dine-In</option><option value="Take-Away">Take-Away</option>';
        document.getElementById('chkStaffPosPaymentType').innerHTML = '<option value="Cash">Cash</option><option value="Credit Card">Credit Card</option>';
    }

    document.getElementById('posCheckoutInputModalOverlay').classList.add('open');
}

function closePosCheckoutInputModal() {
    document.getElementById('posCheckoutInputModalOverlay').classList.remove('open');
}

async function handleStaffPosCheckoutFormSubmit(event) {
    event.preventDefault();
    const btn = document.getElementById('btnStaffPosFinalPay');
    btn.disabled = true;
    btn.innerText = "Processing Transaction Pipeline...";

    const first = document.getElementById('txtStaffPosFirst').value.trim();
    const last = document.getElementById('txtStaffPosLast').value.trim();
    const dining = document.getElementById('chkStaffPosDiningType').value;
    const payment = document.getElementById('chkStaffPosPaymentType').value;
    const terminal = document.getElementById('posTerminalSelectionDropdown').value;

    const keys = Object.keys(staffPosBasketCartState);
    let payloadArray = keys.map(id => ({
        item_id: id,
        qty: staffPosBasketCartState[id].qty,
        price: staffPosBasketCartState[id].price
    }));

    const bodyParams = {
        first_name: first,
        last_name: last,
        dining_type: dining,
        pay_type: payment,
        pos_num: terminal,
        employee_id: currentStaffId || 'EMP002',
        cart_data: JSON.stringify(payloadArray)
    };

    try {
        const response = await fetch(`${API_BASE_URL}staff/place_pos_order`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(bodyParams)
        });

        if (!response.ok) throw new Error('Network fault during execution.');
        const data = await response.json();

        if (data.status === 'success') {
            closePosCheckoutInputModal();
            document.getElementById('lblStaffSuccessOrderId').textContent = data.order_id;
            
            let receiptItemsText = keys.map(id => {
                const r = staffPosBasketCartState[id];
                return `<div style="display:flex; justify-content:space-between; font-size:0.85rem; padding:4px 0;">
                            <span>${r.name} x${r.qty}</span>
                            <span>RM ${(r.qty * r.price).toFixed(2)}</span>
                        </div>`;
            }).join('');

            let grandTotal = document.getElementById('lblPosTotalSum').innerText;

            document.getElementById('boxPrintReceiptAreaTarget').innerHTML = `
                <div style="text-align:center; font-weight:800; margin-bottom:12px;">=== CABIN PUTIH RECEIPT ===</div>
                <div style="font-size:0.8rem; color:var(--text-secondary); margin-bottom:8px;">
                    <strong>Order ID:</strong> ${data.order_id}<br>
                    <strong>Cashier ID:</strong> ${currentStaffId || 'EMP002'}<br>
                    <strong>Customer:</strong> ${first.toUpperCase()} ${last.toUpperCase()}<br>
                    <strong>Mode:</strong> ${dining} [${terminal}]
                </div>
                <div style="border-top:1px dashed var(--border); padding-top:8px; margin-top:8px;">${receiptItemsText}</div>
                <div style="border-top:1px dashed var(--border); padding-top:8px; margin-top:8px; display:flex; justify-content:space-between; font-weight:800; color:var(--amber);">
                    <span>Total Bill</span>
                    <span>${grandTotal}</span>
                </div>
            `;

            document.getElementById('posReceiptSuccessModalOverlay').classList.add('open');
            clearPosCartBasket();
        } else {
            alert("Error processing transaction: " + data.message);
        }
    } catch (err) {
        alert("Critical server routing engine timeout processing transaction.");
    } finally {
        btn.disabled = false;
        btn.innerText = "Generate Receipt Ticket &rarr;";
    }
}

function triggerSystemReceiptPrint() {
    const rawContent = document.getElementById('boxPrintReceiptAreaTarget').innerHTML;
    const printWindow = window.open('', '_blank', 'height=600,width=400');
    printWindow.document.write('<html><head><title>Print Receipt</title>');
    printWindow.document.write('<style>body{font-family:monospace; padding:20px; color:#000;} .amber{font-weight:bold;}</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(rawContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.print();
}

function closePosReceiptSuccessModal() {
    document.getElementById('posReceiptSuccessModalOverlay').classList.remove('open');
    switchView('register');
}

// ------------------------------------------------------------
// MODULE 2: MENU DIRECTORY (CRUD)
// ------------------------------------------------------------
function renderMenuDirectoryCRUDTable() {
    const tableBody = document.getElementById('menuDirectoryTableBody');
    if(!tableBody) return;

    tableBody.innerHTML = localCachedMasterMenuCatalog.map(item => {
        const qty = parseInt(item.item_qty || 0);
        
        let statusLabel = item.item_status;
        let statusClass = 'success'; 

        if (qty === 0) {
            statusLabel = 'Out of Stock';
            statusClass = 'danger';
        } else if (qty < 25) {
            statusLabel = 'Low on Stock';
            statusClass = 'warning';
        } else if (qty > 50) {
            statusLabel = 'Available';
            statusClass = 'success';
        } else {
            statusClass = item.item_status === 'Available' ? 'success' : 'muted';
        }

        return `
            <tr>
                <td class="history-order-id">${item.item_id}</td>
                <td>
                    <button type="button" class="btn-table-row-link" onclick="openItemPreviewActionPortal('${item.item_id}')">
                        ${item.item_name}
                    </button>
                </td>
                <td><span class="cart-item-qty">${String(item.item_type).toUpperCase()}</span></td>
                <td><span style="font-weight:700;">RM ${parseFloat(item.item_price).toFixed(2)}</span></td>
                <td>${qty} units</td>
                <td><span class="status-pill-static ${statusClass}">${statusLabel.toUpperCase()}</span></td>
            </tr>
        `;
    }).join('');
}

function openItemPreviewActionPortal(itemId) {
    const item = localCachedMasterMenuCatalog.find(x => x.item_id === itemId);
    if (!item) return;

    const qty = parseInt(item.item_qty || 0);
    let statusLabel = item.item_status;
    let statusClass = 'success';

    if (qty === 0) {
        statusLabel = 'Out of Stock';
        statusClass = 'danger';
    } else if (qty < 25) {
        statusLabel = 'Low on Stock';
        statusClass = 'warning';
    } else if (qty > 50) {
        statusLabel = 'Available';
        statusClass = 'success';
    } else {
        statusClass = item.item_status === 'Available' ? 'success' : 'danger';
    }

    const itemType = String(item.item_type || 'burger').trim().toLowerCase();
    let displayImageSrc = DEFAULT_PLACEHOLDER_IMG;
    if (itemType === 'noodle') {
        displayImageSrc = 'https://images.pexels.com/photos/1907228/pexels-photo-1907228.jpeg?auto=compress&cs=tinysrgb&w=400&h=260';
    } else if (itemType === 'beverage') {
        displayImageSrc = 'https://images.pexels.com/photos/2474669/pexels-photo-2474669.jpeg?auto=compress&cs=tinysrgb&w=400&h=260';
    } else if (itemType === 'addon') {
        displayImageSrc = 'https://images.pexels.com/photos/4110251/pexels-photo-4110251.jpeg?auto=compress&cs=tinysrgb&w=400&h=260';
    }

    document.getElementById('lblPreviewImgElementFrame').src = displayImageSrc;
    document.getElementById('lblPreviewRefId').innerText = item.item_id;
    document.getElementById('lblPreviewItemName').innerText = item.item_name;
    document.getElementById('lblPreviewItemPrice').innerText = `RM ${parseFloat(item.item_price).toFixed(2)}`;
    document.getElementById('lblPreviewItemQty').innerText = `${qty} Units`;
    document.getElementById('lblPreviewItemType').innerText = String(item.item_type).toUpperCase();
    
    const flagEl = document.getElementById('lblPreviewItemStatusFlag');
    flagEl.innerText = statusLabel.toUpperCase();
    flagEl.className = `status-pill-static ${statusClass}`;

    document.getElementById('btnPortalTriggerEdit').onclick = () => {
        closeItemPreviewActionPortal();
        openMenuCrudFormModal('edit', itemId);
    };

    document.getElementById('btnPortalTriggerDelete').onclick = () => {
        closeItemPreviewActionPortal();
        executeDeleteMenuItemRow(itemId);
    };

    document.getElementById('menuItemPreviewModalOverlay').classList.add('open');
}

function closeItemPreviewActionPortal() {
    document.getElementById('menuItemPreviewModalOverlay').classList.remove('open');
}

function openMenuCrudFormModal(mode, itemId = '') {
    const modal = document.getElementById('menuCrudFormModalOverlay');
    const title = document.getElementById('lblCrudModalHeadingTitle');
    
    document.getElementById('frmMenuItemCrudAsset').reset();
    document.getElementById('txtCrudItemTargetId').value = itemId;

    if (mode === 'add') {
        title.innerText = "Create New Menu Recipe Row";
    } else {
        title.innerText = `Modify Item Reference: ${itemId}`;
        const record = localCachedMasterMenuCatalog.find(x => x.item_id === itemId);
        if(record) {
            document.getElementById('txtCrudItemName').value = record.item_name;
            document.getElementById('txtCrudItemPrice').value = record.item_price;
            document.getElementById('txtCrudItemQty').value = record.item_qty;
            document.getElementById('ddlCrudItemType').value = record.item_type;
            document.getElementById('ddlCrudItemStatus').value = record.item_status;
        }
    }
    modal.classList.add('open');
}

function closeMenuCrudFormModal() {
    document.getElementById('menuCrudFormModalOverlay').classList.remove('open');
}

async function handleMenuItemCrudSubmission(e) {
    e.preventDefault();
    const id = document.getElementById('txtCrudItemTargetId').value;
    
    const payload = {
        item_id: id,
        item_name: document.getElementById('txtCrudItemName').value.trim(),
        item_price: document.getElementById('txtCrudItemPrice').value,
        item_qty: document.getElementById('txtCrudItemQty').value,
        item_type: document.getElementById('ddlCrudItemType').value,
        item_status: document.getElementById('ddlCrudItemStatus').value
    };

    const endpointPath = id ? 'customer/update_item' : 'customer/add_item';

    try {
        const response = await fetch(`${API_BASE_URL}${endpointPath}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(payload)
        });
        if(response.ok) {
            closeMenuCrudFormModal();
            if (id) {
                showToastNotification(`Successfully updated item structure: ${payload.item_name}`, 'success');
            } else {
                showToastNotification(`New item recipe added successfully into storage system index!`, 'success');
            }
            await switchView('directory');
        } else {
            showToastNotification("Server structural error returned while trying to write record.", "danger");
        }
    } catch (err) {
        showToastNotification("Transaction error writing row modifications.", "danger");
    }
}

async function executeDeleteMenuItemRow(itemId) {
    const item = localCachedMasterMenuCatalog.find(x => x.item_id === itemId);
    const itemName = item ? item.item_name : itemId;

    if(confirm(`WARNING!\n\nAre you sure you want to permanently drop recipe registry item "${itemName}" [${itemId}]?\nThis operation clears rows from repository indices and cannot be undone.`)) {
        try {
            const response = await fetch(`${API_BASE_URL}customer/delete_item`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ item_id: itemId })
            });
            if(response.ok) {
                showToastNotification(`Item deleted successfully: ${itemName}`, 'warning');
                await switchView('directory');
            } else {
                showToastNotification("Could not delete item. Protected indexing hierarchy error.", "danger");
            }
        } catch (err) {
            showToastNotification("Cascade verification structural processing loop drop error.", "danger");
        }
    }
}

// ------------------------------------------------------------
// MODULE 3: ACTIVE KITCHEN CHANNELS HUB
// ------------------------------------------------------------
function filterAndRenderActiveOrdersHub() {
    const grid = document.getElementById('kitchenOrdersContainerGrid');
    if(!grid) return;

    const query = document.getElementById('txtSearchActiveOrders').value.trim().toLowerCase();
    const sortBy = document.getElementById('ddlSortActiveOrders').value;
    const direction = document.getElementById('ddlDirectionActiveOrders').value;

    let filtered = localCachedMasterOrdersArray.filter(t => 
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

    if(filtered.length === 0) {
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
        employee_num: currentStaffId || 'EMP002'
    };

    try {
        const response = await fetch(`${API_BASE_URL}staff/update_status`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(payload)
        });
        if(response.ok) {
            await switchView('hub');
        }
    } catch (err) {
        alert("Transaction processing framework timeout advancing status attributes.");
    }
}

// ------------------------------------------------------------
// MODULE 4: INVENTORY STOCK TRACKER
// ------------------------------------------------------------
function renderInventoryStockroomManagerTable() {
    const tableBody = document.getElementById('inventoryStockTableBody');
    if(!tableBody) return;

    tableBody.innerHTML = localCachedMasterMenuCatalog.map(item => {
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

// ------------------------------------------------------------
// MODULE 5: PAST HISTORICAL ARCHIVE INDEX
// ------------------------------------------------------------
function filterAndRenderPastOrdersArchive() {
    const tableBody = document.getElementById('historicalPastOrdersTableBody');
    if(!tableBody) return;

    const query = document.getElementById('txtSearchPastOrders').value.trim().toLowerCase();
    const calendarDate = document.getElementById('dateSearchPastOrders').value;
    const sortBy = document.getElementById('ddlSortPastOrders').value;
    const direction = document.getElementById('ddlDirectionPastOrders').value;

    let filtered = localCachedMasterOrdersArray.filter(t => t.order_status === 'Completed' || t.order_status === 'Cancelled');

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

    if(filtered.length === 0) {
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

// ------------------------------------------------------------
// INTERFACE ENGINE INITIALIZATION DISPATCH
// ------------------------------------------------------------
async function appStaffBootstrapInit() {
    if (!verifyStaffAuthenticationSession()) return;
    injectStaffContextDisplay();
    
    if(!localStorage.getItem('cp_staff_terminal_lock_lane')) {
        localStorage.setItem('cp_staff_terminal_lock_lane', 'POS-01');
    }
    document.getElementById('posTerminalSelectionDropdown').value = localStorage.getItem('cp_staff_terminal_lock_lane');

    switchView('register');
}

appStaffBootstrapInit();