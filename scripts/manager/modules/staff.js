// ============================================================
// MANAGER STAFF MODULE - Personnel directory CRUD
// ============================================================

let cachedRosteredStaffArray = [];
let pendingDeletionTargetEmployeeId = null;

async function fetchRosteredStaffProfiles() {
    const tbody = document.getElementById('staffDirectoryTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="5">Synchronizing employee table columns...</td></tr>';

    try {
        const response = await fetch(`${API_BASE_URL}manager/staff_list`);
        const data = await response.json();
        cachedRosteredStaffArray = data.items || [];

        tbody.innerHTML = cachedRosteredStaffArray.map(emp => `
            <tr>
                <td class="history-order-id">${emp.employee_num}</td>
                <td><strong>${emp.employee_name}</strong></td>
                <td><span class="cart-item-qty">${emp.employee_type.toUpperCase()}</span></td>
                <td><span style="color:var(--text-muted); font-family:monospace;">${emp.manager_num || 'SYSTEM'}</span></td>
                <td style="text-align: right;">
                    <button type="button" class="btn-reorder" style="border-color: var(--danger); color: var(--danger);" 
                            onclick="openDeleteStaffModal('${emp.employee_num}')">
                        Delete Profile
                    </button>
                </td>
            </tr>
        `).join('');
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="5" style="color:var(--danger);">Error reading data rows.</td></tr>';
    }
}

function openAddStaffModal() {
    document.getElementById('addStaffModalOverlay').classList.add('open');
}

function closeAddStaffModal() {
    document.getElementById('addStaffModalOverlay').classList.remove('open');
}

async function handleCreateStaffSubmit(event) {
    event.preventDefault();
    const payload = {
        employee_name: document.getElementById('txtNewStaffName').value.trim(),
        employee_type: document.getElementById('ddlNewStaffRole').value,
        manager_num: document.getElementById('ddlNewStaffManager').value,
        employee_password: 'password123'
    };

    try {
        const response = await fetch(`${API_BASE_URL}manager/add_staff`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(payload)
        });

        const data = await response.json();
        if (data.status === 'success') {
            alert("Personnel added successfully using seq_employee trigger context!");
            closeAddStaffModal();
            document.getElementById('txtNewStaffName').value = '';
            fetchRosteredStaffProfiles();
        }
    } catch (e) {
        alert("Error mapping insert action parameters to database layout.");
    }
}

function openDeleteStaffModal(employeeNum) {
    const staffRecord = cachedRosteredStaffArray.find(x => x.employee_num === employeeNum);
    if (!staffRecord) return;

    pendingDeletionTargetEmployeeId = employeeNum;

    document.getElementById('lblDelStaffNum').textContent = staffRecord.employee_num;
    document.getElementById('lblDelStaffName').textContent = staffRecord.employee_name;
    document.getElementById('lblDelStaffRole').textContent = staffRecord.employee_type.toUpperCase();
    document.getElementById('txtManagerVerificationPassword').value = '';

    document.getElementById('deleteStaffVerificationModalOverlay').classList.add('open');
}

function closeDeleteStaffModal() {
    document.getElementById('deleteStaffVerificationModalOverlay').classList.remove('open');
    pendingDeletionTargetEmployeeId = null;
}

async function handleExecuteDeleteStaffSubmit(event) {
    event.preventDefault();
    const verificationPass = document.getElementById('txtManagerVerificationPassword').value;

    const payload = {
        manager_id: currentManagerId,
        manager_password: verificationPass,
        target_employee_id: pendingDeletionTargetEmployeeId
    };

    try {
        const response = await fetch(`${API_BASE_URL}manager/delete_staff`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(payload)
        });

        const data = await response.json();

        if (data.status === 'success') {
            closeDeleteStaffModal();
            triggerFloatingSuccessToastNotification('Employee removed successfully');
            fetchRosteredStaffProfiles();
        } else {
            alert("Security Verification Mismatch: " + data.message);
        }
    } catch (err) {
        alert("Transaction processing timeout clearing data row components.");
    }
}

window.fetchRosteredStaffProfiles = fetchRosteredStaffProfiles;
window.openAddStaffModal = openAddStaffModal;
window.closeAddStaffModal = closeAddStaffModal;
window.handleCreateStaffSubmit = handleCreateStaffSubmit;
window.openDeleteStaffModal = openDeleteStaffModal;
window.closeDeleteStaffModal = closeDeleteStaffModal;
window.handleExecuteDeleteStaffSubmit = handleExecuteDeleteStaffSubmit;
