// ============================================================
// DIRECTORY MODULE - Menu Directory CRUD functionality
// ============================================================
function renderMenuDirectoryCRUDTable() {
    const tableBody = document.getElementById('menuDirectoryTableBody');
    if (!tableBody) return;

    tableBody.innerHTML = window.localCachedMasterMenuCatalog.map(item => {
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
    const item = window.localCachedMasterMenuCatalog.find(x => x.item_id === itemId);
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
    let displayImageSrc = window.DEFAULT_PLACEHOLDER_IMG;
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
        const record = window.localCachedMasterMenuCatalog.find(x => x.item_id === itemId);
        if (record) {
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
        const response = await fetch(`${window.API_BASE_URL}${endpointPath}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(payload)
        });
        if (response.ok) {
            closeMenuCrudFormModal();
            if (id) {
                window.showToastNotification(`Successfully updated item structure: ${payload.item_name}`, 'success');
            } else {
                window.showToastNotification(`New item recipe added successfully into storage system index!`, 'success');
            }
            await window.switchView('directory');
        } else {
            window.showToastNotification("Server structural error returned while trying to write record.", "danger");
        }
    } catch (err) {
        window.showToastNotification("Transaction error writing row modifications.", "danger");
    }
}

async function executeDeleteMenuItemRow(itemId) {
    const item = window.localCachedMasterMenuCatalog.find(x => x.item_id === itemId);
    const itemName = item ? item.item_name : itemId;

    if (confirm(`WARNING!\n\nAre you sure you want to permanently drop recipe registry item "${itemName}" [${itemId}]?\nThis operation clears rows from repository indices and cannot be undone.`)) {
        try {
            const response = await fetch(`${window.API_BASE_URL}customer/delete_item`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ item_id: itemId })
            });
            if (response.ok) {
                window.showToastNotification(`Item deleted successfully: ${itemName}`, 'warning');
                await window.switchView('directory');
            } else {
                window.showToastNotification("Could not delete item. Protected indexing hierarchy error.", "danger");
            }
        } catch (err) {
            window.showToastNotification("Cascade verification structural processing loop drop error.", "danger");
        }
    }
}

// Export for module usage
window.renderMenuDirectoryCRUDTable = renderMenuDirectoryCRUDTable;
window.openItemPreviewActionPortal = openItemPreviewActionPortal;
window.closeItemPreviewActionPortal = closeItemPreviewActionPortal;
window.openMenuCrudFormModal = openMenuCrudFormModal;
window.closeMenuCrudFormModal = closeMenuCrudFormModal;
window.handleMenuItemCrudSubmission = handleMenuItemCrudSubmission;
window.executeDeleteMenuItemRow = executeDeleteMenuItemRow;
