// ============================================================
// HOME MODULE - Chef's recommendations and welcome banner
// ============================================================

function generateHomeRecommendationCardHTML(item) {
    let displayImageSrc = 'https://images.pexels.com/photos/1251198/pexels-photo-1251198.jpeg?auto=compress&cs=tinysrgb&w=400&h=260';
    const itemType = String(item.type || 'burger').trim().toLowerCase();

    if (itemType === 'noodle') {
        displayImageSrc = 'https://images.pexels.com/photos/1907228/pexels-photo-1907228.jpeg?auto=compress&cs=tinysrgb&w=400&h=260';
    } else if (itemType === 'beverage') {
        displayImageSrc = 'https://images.pexels.com/photos/2474669/pexels-photo-2474669.jpeg?auto=compress&cs=tinysrgb&w=400&h=260';
    } else if (itemType === 'addon') {
        displayImageSrc = 'https://images.pexels.com/photos/4110251/pexels-photo-4110251.jpeg?auto=compress&cs=tinysrgb&w=400&h=260';
    }
    return `
        <div class="food-card">
            <div class="food-img-wrapper">
                <img src="${displayImageSrc}" alt="${item.name}">
                ${!item.available ? '<div class="sold-out-overlay"><span class="sold-out-pill">Sold Out</span></div>' : ''}
            </div>
            <div class="food-info">
                <div class="food-title">${item.name}</div>
                <div class="food-meta">
                    <div class="food-price">RM ${item.price.toFixed(2)}</div>
                </div>
            </div>
        </div>`;
}

function renderHomeRecommendations() {
    const recommendationGrid = document.getElementById('homeRecommendationsTargetGrid');
    if (recommendationGrid) {
        if (!window.globalCatalogItems || window.globalCatalogItems.length === 0) {
            recommendationGrid.innerHTML = '<div class="loading" style="color: var(--text-secondary);">No recommended menu rows active in kitchen inventory profiles.</div>';
            return;
        }
        recommendationGrid.innerHTML = window.globalCatalogItems.slice(0, 3).map(item => generateHomeRecommendationCardHTML(item)).join('');
    }
}



// Export for module usage
window.generateHomeRecommendationCardHTML = generateHomeRecommendationCardHTML;
window.renderHomeRecommendations = renderHomeRecommendations;
