// ============================================================
// MANAGER ANALYTICS MODULE - KPI stats, bar chart, pie chart
// ============================================================

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
            const cleanDayName = String(item.day_name).trim().substring(0, 3).toLowerCase();
            const matchIndex = weekDaysTemplate.findIndex(d => d.name.toLowerCase() === cleanDayName);
            if (matchIndex !== -1) {
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
    } catch (err) {
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

        if (structuralTotalSum === 0) {
            legendContainer.innerHTML = '<div style="color:var(--text-muted)">No revenue logged inside data tables.</div>';
            return;
        }

        const colorPalettesPalette = ['var(--amber)', 'var(--success)', 'var(--info)', '#9333ea'];
        let cumulativeOffset = 0;

        records.forEach((row, index) => {
            const cleanTypeName = String(row.dining_type).trim();
            const revenueVal = parseFloat(row.total_count);

            const pct = (revenueVal / structuralTotalSum) * 100;
            const colorChoice = colorPalettesPalette[index % colorPalettesPalette.length];

            // Donut slice: dasharray = "slice gap", dashoffset = 25 - accumulated
            // r=15.915 => circumference=100, so percentages map directly to dash units
            svgCircle.insertAdjacentHTML('beforeend', `
                <circle cx="50" cy="50" r="15.915" fill="none"
                        stroke="${colorChoice}"
                        stroke-width="31.83"
                        stroke-dasharray="${pct} ${100 - pct}"
                        stroke-dashoffset="${25 - cumulativeOffset}">
                </circle>
            `);

            legendContainer.insertAdjacentHTML('beforeend', `
                <div class="legend-row-item" style="animation: animFadeIn 0.4s ease forwards; animation-delay: ${index * 0.15}s; opacity:0;">
                    <div style="display:flex; align-items:center; gap:10px;">
                        <span style="color:${colorChoice}; font-size:1.2rem;">■</span>
                        <strong>${cleanTypeName}</strong>
                    </div>
                    <span style="font-weight:700; color:var(--text-secondary);">RM ${revenueVal.toFixed(2)} (${pct.toFixed(1)}%)</span>
                </div>
            `);

            cumulativeOffset += pct;
        });
    } catch (err) {
        console.error("Pie solid configuration exception:", err);
    }
}

window.fetchExecutiveSummaryStatistics = fetchExecutiveSummaryStatistics;
window.generateWeeklyRevenueBarGraph = generateWeeklyRevenueBarGraph;
window.generateDiningDistributionPieGraph = generateDiningDistributionPieGraph;
