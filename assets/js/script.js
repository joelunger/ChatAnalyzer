const uploadZone = document.getElementById('uploadRequest');
const fileInput = document.getElementById('fileInput');
const loading = document.getElementById('loading');
const dashboard = document.getElementById('dashboard-view');

// Theme Colors
const COLORS = [
    '#25d366', // WhatsApp Green
    '#3b82f6', // Mobile Blue
    '#f59e0b', // Amber
    '#ec4899', // Pink
    '#8b5cf6', // Violet
    '#10b981'  // Emerald
];

const CHART_CONFIG = {
    color: '#a1a1aa',
    gridColor: 'rgba(255, 255, 255, 0.05)',
    fontFamily: "'Inter', sans-serif"
};

// Drag & Drop Handlers
uploadZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    uploadZone.classList.add('dragover');
});

uploadZone.addEventListener('dragleave', () => {
    uploadZone.classList.remove('dragover');
});

uploadZone.addEventListener('drop', (e) => {
    e.preventDefault();
    uploadZone.classList.remove('dragover');
    if (e.dataTransfer.files.length) {
        handleFile(e.dataTransfer.files[0]);
    }
});

uploadZone.addEventListener('click', () => {
    fileInput.click();
});

fileInput.addEventListener('change', (e) => {
    if (e.target.files.length) {
        handleFile(e.target.files[0]);
    }
});

function handleFile(file) {
    if (!file) return;

    // Show loading
    uploadZone.classList.add('hidden');
    // Hide header content to minimal
    document.querySelector('header p').classList.add('hidden');
    loading.classList.remove('hidden');

    const formData = new FormData();
    formData.append('chat_file', file);

    fetch('api/process.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                renderDashboard(result.data);
            } else {
                alert('Fehler: ' + (result.error || 'Unbekannter Fehler'));
                location.reload();
            }
        })
        .catch(err => {
            console.error(err);
            alert('Netzwerkfehler beim Hochladen.');
            location.reload();
        });
}

function renderDashboard(data) {
    loading.classList.add('hidden');
    dashboard.classList.remove('hidden');

    // Animate stats
    animateValue('totalMessages', data.total);
    animateValue('activeUsers', Object.keys(data.users).length);
    animateValue('daysCount', data.active_days);
    animateValue('longestStreak', data.longest_streak || 0);

    // Details
    animateValue('avgLength', data.avg_length);
    animateValue('letterCount', data.letters, 2000);
    animateValue('mediaCount', data.media);
    animateValue('linkCount', data.links);
    // Deleted count not present in DOM anymore in previous edit? 
    // Wait, I think I removed it or moved it? 
    // In edit 86 "Deep Dive Stats" removed "Gelöscht" card? 
    // Ah no, I see the cards in my previous replace step: avgLength, letterCount, mediaCount, linkCount.
    // deletedCount was REMOVED in specific placement. I should check if user wants it back or if I should skip it.
    // The previous prompt said "clean up", maybe deleting it was fine? 
    // I will assume lighter cleanup. But let's check standard logic.

    // Fun Facts logic
    setWinner('topStarter', data.conversation_starters);
    setWinner('topAsker', data.questions);
    setWinner('topLaugher', data.laughs);

    // Optimal Time
    if (data.optimal_time) {
        const activeSize = data.optimal_time.active_group_size || data.total_users_count;
        document.getElementById('optimalTime').innerHTML = `
            <div style="color: var(--accent-primary); font-weight: 800; font-size: 1.2rem;">${data.optimal_time.day_name}s</div>
            <div style="font-size: 2.2rem; font-weight: 800; margin: 0.1rem 0;">${data.optimal_time.hour}:00 Uhr</div>
            <div style="margin-bottom: 0.5rem; font-size: 0.9rem; color: var(--text-main); font-weight: 600;">
                ca. ${data.optimal_time.score} von ${activeSize} aktiven Personen
            </div>
            <div style="display: flex; gap: 1rem; font-size: 0.85rem;">
                <div title="Wahrscheinlichkeit, dass aktive Mitglieder die Nachricht lesen">
                    <span style="color: var(--accent-primary); font-weight: 700;">${data.optimal_time.reach_pct}%</span> <span style="color: var(--text-muted)">Reach</span>
                </div>
                <div title="Wahrscheinlichkeit einer Antwort von aktiven Mitgliedern">
                    <span style="color: var(--accent-secondary); font-weight: 700;">${data.optimal_time.interaction_pct}%</span> <span style="color: var(--text-muted)">Aktiv</span>
                </div>
            </div>
            <div style="font-size: 0.7rem; color: var(--text-muted); margin-top: 0.5rem;">* Aktive Personen: Im Chat aktiv in den letzten 90 Tagen.</div>
        `;

        const list = document.getElementById('optimalTimesList');
        list.innerHTML = '';
        if (data.top_optimal_times) {
            data.top_optimal_times.forEach((t, i) => {
                const li = document.createElement('li');
                li.style.display = 'flex';
                li.style.justifyContent = 'space-between';
                li.style.alignItems = 'center';
                li.style.padding = '0.6rem 0';
                li.style.fontSize = '0.9rem';
                li.style.borderBottom = i < 4 ? '1px solid rgba(255,255,255,0.03)' : 'none';
                li.innerHTML = `
                    <div style="color: var(--text-main); font-weight: 500;">
                        ${t.day_name.substring(0, 2)}, ${t.hour}:00
                        <div style="font-size: 0.7rem; color: var(--text-muted); font-weight: 400;">ø ${t.score} User</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 0.85rem; font-weight: 700; color: var(--accent-primary);">${t.reach_pct}% <span style="font-size: 0.7rem; color: var(--text-muted); font-weight: 400;">Reach</span></div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">${t.interaction_pct}% Aktiv</div>
                    </div>
                `;
                list.appendChild(li);
            });
        }
    }

    // Render Chat Visualizer
    renderChatView(data.messages);

    // Active Date
    if (data.most_active_date && data.most_active_date.date) {
        document.getElementById('activeDateDate').innerText = formatDate(data.most_active_date.date);
        document.getElementById('activeDateCount').innerText = data.most_active_date.count + ' Nachrichten';
    }

    // Charts
    renderTimelineChart(data.timeline);
    renderDistributionChart(data.users);
    renderHoursChart(data.hours);
    renderDaysChart(data.weekdays);
    renderEmojiChart(data.top_emojis);
    renderWordCloud(data.top_words);

    // Tabs & UI
    document.getElementById('tabs').classList.remove('hidden');
    initTabs();

    // Export Logic
    document.getElementById('exportBtn').onclick = exportReport;
}

function initTabs() {
    const btns = document.querySelectorAll('.tab-btn');
    const contents = document.querySelectorAll('.tab-content');

    btns.forEach(btn => {
        btn.onclick = () => {
            const target = btn.getAttribute('data-tab');
            
            btns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            contents.forEach(c => {
                c.classList.add('hidden');
                if (c.id === target) c.classList.remove('hidden');
            });
        };
    });
}

function renderChatView(messages) {
    const container = document.getElementById('chatContainer');
    container.innerHTML = '';
    
    if (!messages || messages.length === 0) return;

    // Determine the "main" user (most messages) to put them on the right
    const userCounts = {};
    messages.forEach(m => {
        if (m.sender) userCounts[m.sender] = (userCounts[m.sender] || 0) + 1;
    });
    const mainUser = Object.entries(userCounts).reduce((a, b) => a[1] > b[1] ? a : b)[0];

    // Assign colors to users
    const userColors = {};
    Object.keys(userCounts).forEach((u, i) => {
        userColors[u] = COLORS[i % COLORS.length];
    });

    // Populate messages
    messages.forEach(m => {
        if (!m.sender) return; // Skip system messages for now if needed

        const div = document.createElement('div');
        const isMe = m.sender === mainUser;
        div.className = `chat-bubble ${isMe ? 'right' : 'left'}`;
        
        div.innerHTML = `
            ${!isMe ? `<div class="chat-sender" style="color: ${userColors[m.sender]}">${m.sender}</div>` : ''}
            <div class="chat-content">${m.message.replace(/\n/g, '<br>')}</div>
            <div class="chat-time">${m.time}</div>
        `;
        
        container.appendChild(div);
    });
    
    // Scroll to bottom (optional)
    setTimeout(() => container.scrollTop = container.scrollHeight, 100);
}

function exportReport() {
    const btn = document.getElementById('exportBtn');
    const originalContent = btn.innerHTML;
    btn.innerHTML = '<i data-feather="loader" class="spin" style="width: 18px; height: 18px;"></i> Bereite Export vor...';
    feather.replace();

    const element = document.getElementById('dashboard');
    
    const options = {
        backgroundColor: '#0f0f13',
        scale: 2,
        useCORS: true,
        logging: false,
        onclone: (clonedDoc) => {
            // Force visibility on cloned element to avoid animation/opacity issues
            const clonedDashboard = clonedDoc.getElementById('dashboard');
            clonedDashboard.style.display = 'block';
            clonedDashboard.style.opacity = '1';
            
            // Fix all animated grids/cards
            const statsGrids = clonedDoc.querySelectorAll('.stats-grid');
            statsGrids.forEach(grid => {
                grid.style.opacity = '1';
                grid.style.animation = 'none';
                grid.style.transform = 'none';
            });

            const cards = clonedDoc.querySelectorAll('.card');
            cards.forEach(card => {
                card.style.opacity = '1';
                card.style.background = '#1a1a24'; // Solid fallback for glassmorphism
                card.style.backdropFilter = 'none';
            });
        }
    };

    html2canvas(element, options).then(canvas => {
        const imgData = canvas.toDataURL('image/png', 1.0);
        const { jsPDF } = window.jspdf;
        
        // Calculate dimensions
        const pdf = new jsPDF('p', 'mm', 'a4');
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = pdf.internal.pageSize.getHeight();
        const imgWidth = canvas.width;
        const imgHeight = canvas.height;
        const ratio = Math.min(pdfWidth / imgWidth, pdfHeight / imgHeight);
        
        const finalWidth = imgWidth * ratio;
        const finalHeight = imgHeight * ratio;

        // If height is more than one page, we might need to adjust or just scale to fit
        // For simplicity and best look, let's scale to fit one page width and handle multiple pages
        const canvasWidth = canvas.width;
        const canvasHeight = canvas.height;
        const imgWidthPdf = 210; // A4 Width in mm
        const imgHeightPdf = (canvasHeight * imgWidthPdf) / canvasWidth;
        
        let heightLeft = imgHeightPdf;
        let position = 0;

        pdf.addImage(imgData, 'PNG', 0, position, imgWidthPdf, imgHeightPdf);
        heightLeft -= pdfHeight;

        while (heightLeft >= 0) {
            position = heightLeft - imgHeightPdf;
            pdf.addPage();
            pdf.addImage(imgData, 'PNG', 0, position, imgWidthPdf, imgHeightPdf);
            heightLeft -= pdfHeight;
        }

        pdf.save('Chat_Insights_Bericht.pdf');
        
        btn.innerHTML = originalContent;
        feather.replace();
    }).catch(err => {
        console.error('Export Error:', err);
        alert('Export fehlgeschlagen: ' + err.message);
        btn.innerHTML = originalContent;
        feather.replace();
    });
}

function renderWordCloud(wordsData) {
    const container = document.getElementById('wordCloud');
    container.innerHTML = '';

    // Find max value for normalization
    const max = Math.max(...Object.values(wordsData));
    const sizes = ['1rem', '1.2rem', '1.5rem', '2rem', '2.5rem', '3rem'];

    // Shuffle keys for random look (optional)
    const entries = Object.entries(wordsData); // can shuffle if needed

    entries.forEach(([word, count]) => {
        const sizeIndex = Math.floor((count / max) * (sizes.length - 1));
        const span = document.createElement('span');
        span.innerText = word;
        span.style.fontSize = sizes[sizeIndex];
        span.style.color = COLORS[Math.floor(Math.random() * COLORS.length)];
        span.style.opacity = 0.8;
        span.style.fontWeight = count > max * 0.5 ? '800' : '400';
        span.title = count + ' mal';
        span.style.cursor = 'default';
        span.classList.add('word-cloud-item');

        // Add hover effect via cleaner class or inline
        span.onmouseover = () => { span.style.transform = 'scale(1.2)'; span.style.opacity = 1; };
        span.onmouseout = () => { span.style.transform = 'scale(1)'; span.style.opacity = 0.8; };
        span.style.transition = 'all 0.2s';

        container.appendChild(span);
    });
}

function animateValue(id, end, duration = 1500) {
    const obj = document.getElementById(id);
    let startTimestamp = null;
    const start = 0;
    const step = (timestamp) => {
        if (!startTimestamp) startTimestamp = timestamp;
        const progress = Math.min((timestamp - startTimestamp) / duration, 1);
        // EaseOutExpo
        const ease = progress === 1 ? 1 : 1 - Math.pow(2, -10 * progress);

        let value = Math.floor(ease * (end - start) + start);
        if (id === 'avgLength') {
            // Keep decimal for avg length but here just floor for simplicity or use value.toFixed(1)
            // Let's re-read the DOM element to preserve span
            obj.childNodes[0].nodeValue = (ease * (end - start) + start).toFixed(1) + " ";
        } else {
            obj.innerText = value;
        }

        if (progress < 1) {
            window.requestAnimationFrame(step);
        } else {
            if (id !== 'avgLength') obj.innerText = end;
        }
    };
    window.requestAnimationFrame(step);
}

// Chart Renderers

function getChartCommonOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: { color: CHART_CONFIG.color, font: { family: CHART_CONFIG.fontFamily } }
            }
        },
        scales: {
            y: {
                grid: { color: CHART_CONFIG.gridColor },
                ticks: { color: CHART_CONFIG.color, font: { family: CHART_CONFIG.fontFamily } }
            },
            x: {
                grid: { color: CHART_CONFIG.gridColor },
                ticks: { color: CHART_CONFIG.color, font: { family: CHART_CONFIG.fontFamily } }
            }
        }
    };
}

function renderTimelineChart(timelineData) {
    const ctx = document.getElementById('timelineChart').getContext('2d');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: Object.keys(timelineData),
            datasets: [{
                label: 'Nachrichten',
                data: Object.values(timelineData),
                borderColor: COLORS[0],
                backgroundColor: 'rgba(37, 211, 102, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 0,
                pointHitRadius: 10
            }]
        },
        options: {
            ...getChartCommonOptions(),
            interaction: {
                mode: 'index',
                intersect: false,
            },
        }
    });
}

function renderDistributionChart(usersData) {
    const ctx = document.getElementById('distributionChart').getContext('2d');

    // Sort users by count
    const sortedUsers = Object.entries(usersData).sort((a, b) => b[1] - a[1]);

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: sortedUsers.map(u => u[0]),
            datasets: [{
                data: sortedUsers.map(u => u[1]),
                backgroundColor: COLORS,
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: { color: CHART_CONFIG.color, font: { family: CHART_CONFIG.fontFamily } }
                }
            },
            cutout: '70%'
        }
    });
}

function renderHoursChart(hoursData) {
    const ctx = document.getElementById('hoursChart').getContext('2d');

    // Fill 0-23
    const data = [];
    const labels = [];
    for (let i = 0; i < 24; i++) {
        const h = i.toString().padStart(2, '0');
        labels.push(h + ':00');
        data.push(hoursData[h] || 0);
    }

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Nachrichten nach Uhrzeit',
                data: data,
                backgroundColor: COLORS[1],
                borderRadius: 4
            }]
        },
        options: getChartCommonOptions()
    });
}

function renderDaysChart(weekdaysData) {
    const ctx = document.getElementById('daysChart').getContext('2d');

    const days = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
    const data = days.map((_, i) => weekdaysData[i] || 0);

    new Chart(ctx, {
        type: 'radar',
        data: {
            labels: days,
            datasets: [{
                label: 'Aktivität',
                data: data,
                backgroundColor: 'rgba(236, 72, 153, 0.2)',
                borderColor: COLORS[3],
                pointBackgroundColor: COLORS[3],
                pointBorderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                r: {
                    grid: { color: CHART_CONFIG.gridColor },
                    angleLines: { color: CHART_CONFIG.gridColor },
                    pointLabels: { color: CHART_CONFIG.color, font: { family: CHART_CONFIG.fontFamily } },
                    ticks: { backdropColor: 'transparent', display: false }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
}

function renderEmojiChart(emojiData) {
    const ctx = document.getElementById('emojiChart').getContext('2d');

    new Chart(ctx, {
        type: 'bar', // Horizontal bar? Chart.js 3+ uses indexAxis
        data: {
            labels: Object.keys(emojiData),
            datasets: [{
                label: 'Top Emojis',
                data: Object.values(emojiData),
                backgroundColor: COLORS[2],
                borderRadius: 4
            }]
        },
        options: {
            ...getChartCommonOptions(),
            indexAxis: 'y'
        }
    });
}

function setWinner(elementId, dataMap) {
    if (!dataMap || Object.keys(dataMap).length === 0) {
        document.getElementById(elementId).innerText = '-';
        return;
    }

    // Find key with max value
    const winner = Object.entries(dataMap).reduce((a, b) => a[1] > b[1] ? a : b);

    if (winner) {
        document.getElementById(elementId).innerHTML = `
            <span style="color: var(--accent-primary); font-weight: 800;">${winner[0]}</span> 
            <span style="font-size: 0.8em; color: var(--text-muted);">(${winner[1]})</span>
        `;
    }
}

function formatDate(isoDate) {
    if (!isoDate) return '-';
    // isoDate is YYYY-MM-DD
    const parts = isoDate.split('-');
    if (parts.length < 3) return isoDate;
    return `${parts[2]}.${parts[1]}.${parts[0]}`;
}
