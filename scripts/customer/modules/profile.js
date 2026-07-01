// ============================================================
// PROFILE MODULE - User profile management functionality
// ============================================================

async function fetchActiveProfileFromDB() {
    try {
        if (!window.currentCustomerID) return;

        const response = await fetch(`${window.API_BASE_URL}customer/profile?cust_id=${window.currentCustomerID}`, { method: 'GET' });
        if (!response.ok) throw new Error('Profile synchronization fault.');

        const data = await response.json();

        if (data.items && data.items.length > 0) {
            const profileRow = data.items[0];
            document.getElementById('profFirst').value = profileRow.first_name || '';
            document.getElementById('profLast').value = profileRow.last_name || '';
            document.getElementById('profPhone').value = profileRow.phone_num || '';
            document.getElementById('lblCustID').innerText = profileRow.cust_id || window.currentCustomerID;

            window.updateSessionDisplayElements(profileRow.first_name);
        }
    } catch (err) {
        console.error("Profile sync exception:", err);
    }
}

async function handleProfileUpdate(event) {
    event.preventDefault();
    const btn = document.getElementById('saveProfileBtn');
    btn.disabled = true;
    btn.innerText = "Saving to Database...";

    const payload = {
        cust_id: window.currentCustomerID,
        first_name: document.getElementById('profFirst').value.trim(),
        last_name: document.getElementById('profLast').value.trim(),
        phone_num: document.getElementById('profPhone').value.trim()
    };

    try {
        const response = await fetch(`${window.API_BASE_URL}customer/update_profile`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(payload)
        });

        if (!response.ok) throw new Error('Data processing failure updating profile.');
        const responseData = await response.json();

        alert(responseData.message);
        if (responseData.status === 'success') {
            sessionStorage.setItem('customer_name', payload.first_name);
            window.updateSessionDisplayElements(payload.first_name);
        }
    } catch (err) {
        alert("Network transaction failure communicating changes.");
    } finally {
        btn.disabled = false;
        btn.innerText = "Save System Profile";
    }
}

// Export for module usage
window.fetchActiveProfileFromDB = fetchActiveProfileFromDB;
window.handleProfileUpdate = handleProfileUpdate;
