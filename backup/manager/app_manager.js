// ============================================================
// CABIN PUTIH EXECUTIVE NODE PIPELINE MANAGER
// Processes backend relational maps dynamically
// ============================================================
const API_BASE_URL = 'https://oracleapex.com/ords/cabin_putih/api/';

const currentManagerId = sessionStorage.getItem('staff_id') || 'EMP001';
const cachedManagerName = sessionStorage.getItem('staff_name');

// In-memory data repository map cache to simplify client-side filtering operations
let cachedRosteredStaffArray = [];
let pendingDeletionTargetEmployeeId = null;

function verifyManagerSessionContext() {
    if (!sessionStorage.getItem('staff_id')) {
        alert("Session credentials unauthenticated. Returning to portal gateway...");
        window.location.href = 'login.html';
        return false;
    }
    return true;
}

function switchView(targetViewId) {
    document.querySelectorAll('.view-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.nav-item').forEach(t => t.classList.remove('active'));
    
    const targetElement = document.getElementById('view-' + targetViewId);
    if(targetElement) targetElement.classList.add('active');
    
    const activeNavButton = document.getElementById('btn-nav-' + targetViewId);
    if (activeNavButton) activeNavButton.classList.add('active');

    if (targetViewId === 'analytics') {
        fetchExecutiveSummaryStatistics();
    } else if (targetViewId === 'staff') {
        fetchRosteredStaffProfiles();
    } else if (targetViewId === 'replenish') {
        fetchReplenishAlertRequests();
    } else if (targetViewId === 'suppliers') {
        fetchSupplierLogisticsDirectory();
    }
}

// ------------------------------------------------------------
// INTERFACE RENDERING ENGINE METHODS (ORACLE SYNC)
// ------------------------------------------------------------
async function fetchExecutiveSummaryStatistics() {
    try {
        const response = await fetch(`${API_BASE_URL}manager/dashboard_summary`);
        if (!response.ok) throw new Error('Data endpoint drop.');
        const data = await response.json();
        const metrics = data.items[0] || { gross_revenue: 0, total_tickets: 0, active_queues: 0, total_staff: 0 };

        document.getElementById('statGrossRevenue').innerText = `RM ${parseFloat(metrics.gross_revenue).toFixed(2)}`;
        document.getElementById('statTotalOrders').innerText = metrics.total_tickets;
        document.getElementById('statActiveOrders').innerText = metrics.active_queues;
        document.getElementById('statTotalStaff').innerText = metrics.total_staff;

        await generateWeeklyRevenueBarGraph();
        await generateDiningDistributionPieGraph();
    } catch (e) {
        console.error("Summary metrics failed initialization maps.", e);
    }
}

async function generateWeeklyRevenueBarGraph() {
    const svg = document.getElementById('svgWeeklyRevenueChart');
    if (!svg) return;
    svg.innerHTML = ''; 

    try {
        const res = await fetch(`${API_BASE_URL}manager/revenue_by_day`);
        const data = await res.json();
        const rows = data.items || [];

        const weekDaysTemplate = [
            { name: 'Mon', revenue: 0 }, { name: 'Tue', revenue: 0 },
            { name: 'Wed', revenue: 0 }, { name: 'Thu', revenue: 0 },
            { name: 'Fri', revenue: 0 }, { name: 'Sat', revenue: 0 },
            { name: 'Sun', revenue: 0 }
        ];

        rows.forEach(item => {
            const cleanDayName = String(item.day_name).trim().substring(0,3).toLowerCase();
            const matchIndex = weekDaysTemplate.findIndex(d => d.name.toLowerCase() === cleanDayName);
            if(matchIndex !== -1) {
                weekDaysTemplate[matchIndex].revenue = parseFloat(item.daily_revenue);
            }
        });

        const maxRevenueValue = Math.max(...weekDaysTemplate.map(d => d.revenue), 5);
        const chartHeightBoundary = 180;
        const totalBarsCount = weekDaysTemplate.length;
        const horizontalWidthStep = 700 / totalBarsCount;

        svg.innerHTML += `<line x1="0" y1="${chartHeightBoundary}" x2="700" y2="${chartHeightBoundary}" stroke="var(--border)" stroke-width="2"/>`;

        weekDaysTemplate.forEach((day, index) => {
            const barWidthSize = 42;
            const barProportionalHeight = (day.revenue / maxRevenueValue) * chartHeightBoundary;
            
            const positionX = (index * horizontalWidthStep) + (horizontalWidthStep - barWidthSize) / 2;
            const positionY = chartHeightBoundary - barProportionalHeight;

            svg.innerHTML += `
                <g>
                    <rect x="${positionX}" y="${positionY}" width="${barWidthSize}" height="${barProportionalHeight}" 
                          fill="var(--amber)" opacity="${day.revenue > 0 ? '1' : '0.25'}" rx="6" 
                          style="animation: barGrow 1s cubic-bezier(0.16, 1, 0.3, 1) forwards; 
                                 animation-delay: ${index * 0.05}s; 
                                 transform-origin: bottom; 
                                 transform: scaleY(0);">
                    </rect>
                    <text x="${positionX + (barWidthSize / 2)}" y="${chartHeightBoundary + 22}" class="chart-axis-text" text-anchor="middle">${day.name}</text>
                    ${day.revenue > 0 ? `<text x="${positionX + (barWidthSize / 2)}" y="${positionY - 10}" class="chart-value-label">RM ${day.revenue.toFixed(2)}</text>` : ''}
                </g>
            `;
        });
    } catch(err) {
        console.error("Weekly chart execution exception:", err);
    }
}

async function generateDiningDistributionPieGraph() {
    const svgCircle = document.getElementById('svgDiningTypePieChart');
    const legendContainer = document.getElementById('pieChartLegendLabelsContainer');
    if (!svgCircle || !legendContainer) return;

    svgCircle.innerHTML = '';
    legendContainer.innerHTML = '';

    try {
        const res = await fetch(`${API_BASE_URL}manager/orders_by_dining_type`);
        const data = await res.json();
        const records = data.items || [];

        const structuralTotalSum = records.reduce((sum, current) => sum + parseFloat(current.total_count), 0);

        if(structuralTotalSum === 0) {
            legendContainer.innerHTML = '<div style="color:var(--text-muted)">No revenue logged inside data tables.</div>';
            return;
        }

        const colorPalettesPalette = ['var(--amber)', 'var(--success)', 'var(--info)', '#9333ea'];
        let mathematicalCumulativeOffset = 0;

        records.forEach((row, index) => {
            const cleanTypeName = String(row.dining_type).trim();
            const revenueVal = parseFloat(row.total_count);
            
            const percentageSliceValue = (revenueVal / structuralTotalSum) * 100;
            const colorChoice = colorPalettesPalette[index % colorPalettesPalette.length];

            const strokeDashArrayRule = `${percentageSliceValue} 100`;
            const strokeDashOffsetRule = 100 - mathematicalCumulativeOffset + 25; 

            svgCircle.innerHTML += `
                <circle cx="50" cy="50" r="15.915" fill="none" 
                        stroke="${colorChoice}" 
                        stroke-width="31.83" 
                        stroke-dasharray="${strokeDashArrayRule}" 
                        stroke-dashoffset="${strokeDashOffsetRule}" 
                        style="transition: all 0.5s ease; 
                               animation: pieGrow 1.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
                               animation-delay: ${index * 0.1}s;
                               transform-origin: center;
                               opacity: 0;">
                </circle>
            `;

            legendContainer.innerHTML += `
                <div class="legend-row-item" style="animation: animFadeIn 0.4s ease forwards; animation-delay: ${index * 0.15}s; opacity:0;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span style="color:${colorChoice}; font-size:1.2rem;">■</span>
                        <strong>${cleanTypeName}</strong>
                    </div>
                    <span style="font-weight:700; color:var(--text-secondary);">RM ${revenueVal.toFixed(2)} (${percentageSliceValue.toFixed(1)}%)</span>
                </div>
            `;

            mathematicalCumulativeOffset += percentageSliceValue;
        });

    } catch(err) {
        console.error("Pie solid configuration exception:", err);
    }
}

async function fetchRosteredStaffProfiles() {
    const tbody = document.getElementById('staffDirectoryTableBody');
    if(!tbody) return;
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

// ------------------------------------------------------------
// MODULE MODAL DELETION CONTROLLERS
// ------------------------------------------------------------
function openDeleteStaffModal(employeeNum) {
    const staffRecord = cachedRosteredStaffArray.find(x => x.employee_num === employeeNum);
    if (!staffRecord) return;

    pendingDeletionTargetEmployeeId = employeeNum;
    
    // Bind current text contexts onto preview elements
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

// VERIFY MANAGER CREDENTIALS AND REMOVE DATA THROUGH PL/SQL TRANSACTION
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

function triggerFloatingSuccessToastNotification(messageText) {
    const container = document.getElementById('toastNotificationMasterContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = 'alert-toast-container';
    toast.innerHTML = `
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
        <span>${messageText}</span>
    `;

    container.appendChild(toast);

    // Fade and drop toast element cleanly out of layout frame after 4 seconds
    setTimeout(() => {
        toast.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-12px)';
        setTimeout(() => toast.remove(), 400);
    }, 4000);
}

async function fetchReplenishAlertRequests() {
    const tbody = document.getElementById('replenishRequestsTableBody');
    if(!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6">Checking menu inventory levels...</td></tr>';

    try {
        const response = await fetch(`${API_BASE_URL}manager/replenish_alerts`);
        const data = await response.json();
        const items = data.items || [];

        if(items.length === 0) {
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
        if(response.ok) {
            alert("Inventory transaction processed! Row increment updated.");
            fetchReplenishAlertRequests();
        }
    } catch(err) {
        alert("Failed to write adjustment to database item schema.");
    }
}

async function fetchSupplierLogisticsDirectory() {
    const tbody = document.getElementById('supplierProfilesTableBody');
    if(!tbody) return;
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

function openAddStaffModal() { document.getElementById('addStaffModalOverlay').classList.add('open'); }
function closeAddStaffModal() { document.getElementById('addStaffModalOverlay').classList.remove('open'); }

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

function appManagerBootstrapInit() {
    if(!verifyManagerSessionContext()) return;
    if(cachedManagerName) {
        document.getElementById('lblManagerDisplayName').textContent = cachedManagerName.toUpperCase();
    }
    switchView('analytics');
}

appManagerBootstrapInit();