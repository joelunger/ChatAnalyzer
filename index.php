<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Insights - Premium WhatsApp Analysis</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
    <!-- Feather Icons -->
    <script src="https://unpkg.com/feather-icons"></script>
    <!-- Export Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>

<body>
    <div class="container">
        <header>
            <h1>Chat Insights</h1>
            <p class="subtitle">Entdecke die verborgenen Geschichten in deinen Chats. <br>Lade deinen WhatsApp-Export
                (.txt) hoch und erhalte sofortige Analysen.</p>
        </header>

        <div id="uploadRequest" class="upload-zone">
            <input type="file" id="fileInput" accept=".txt, .zip">
            <div class="upload-icon">
                <i data-feather="upload-cloud"></i>
            </div>
            <div class="upload-text">
                <h3>Chat Datei hier ablegen</h3>
                <p>oder klicken um auszuwählen</p>
            </div>
        </div>

        <div id="loading" class="hidden" style="text-align: center; margin: 4rem 0;">
            <div style="font-size: 2rem; color: var(--accent-primary); margin-bottom: 1rem;">
                <i data-feather="loader" class="spin"></i>
            </div>
            <p style="color: var(--text-muted);">Analysiere Chat Daten...</p>
        </div>

        <div id="tabs" class="hidden" style="display: flex; gap: 1rem; margin-bottom: 2rem; border-bottom: 1px solid var(--glass-border); padding-bottom: 1rem;">
            <button class="tab-btn active" data-tab="dashboard-view" style="background: transparent; color: var(--text-main); border: none; font-weight: 700; cursor: pointer; padding: 0.5rem 1rem; border-radius: 8px; transition: var(--transition);">Dashboard</button>
            <button class="tab-btn" data-tab="chat-view" style="background: transparent; color: var(--text-muted); border: none; font-weight: 600; cursor: pointer; padding: 0.5rem 1rem; border-radius: 8px; transition: var(--transition);">Chat Viewer</button>
        </div>

        <div id="dashboard-view" class="tab-content hidden">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <div class="section-title" style="margin: 0; flex-grow: 1;">
                    <i data-feather="grid"></i> Übersicht
                </div>
                <button id="exportBtn" class="btn-primary" style="display: flex; align-items: center; gap: 0.5rem; padding: 0.8rem 1.5rem; border-radius: 12px; border: none; background: var(--accent-gradient); color: white; cursor: pointer; font-weight: 600; box-shadow: 0 10px 20px rgba(37, 211, 102, 0.2); transition: var(--transition);">
                    <i data-feather="download" style="width: 18px; height: 18px;"></i>
                    Bericht Exportieren
                </button>
            </div>
            <div class="stats-grid">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Nachrichten</span>
                        <i data-feather="message-square" style="color: var(--accent-primary)"></i>
                    </div>
                    <div class="stat-value" id="totalMessages">0</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Aktive Nutzer</span>
                        <i data-feather="users" style="color: var(--accent-secondary)"></i>
                    </div>
                    <div class="stat-value" id="activeUsers">0</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Zeitraum (Tage)</span>
                        <i data-feather="calendar" style="color: #f59e0b"></i>
                    </div>
                    <div class="stat-value" id="daysCount">0</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Aktive Tage (Streak)</span>
                        <i data-feather="zap" style="color: #fbbf24"></i>
                    </div>
                    <div class="stat-value" id="longestStreak">0</div>
                </div>
            </div>

            <!-- Deep Dive Stats -->
            <div class="section-title">
                <i data-feather="layers"></i> Details
            </div>
            <div class="stats-grid">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Durchschn. Länge</span>
                        <i data-feather="align-left" style="color: #ec4899"></i>
                    </div>
                    <div class="stat-value" id="avgLength">0 <span style="font-size: 1rem;">Wörter</span></div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Buchstaben</span>
                        <i data-feather="type" style="color: #6366f1"></i>
                    </div>
                    <div class="stat-value" id="letterCount">0</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Medien</span>
                        <i data-feather="image" style="color: #8b5cf6"></i>
                    </div>
                    <div class="stat-value" id="mediaCount">0</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Links</span>
                        <i data-feather="link" style="color: #3b82f6"></i>
                    </div>
                    <div class="stat-value" id="linkCount">0</div>
                </div>
            </div>

            <!-- Fun Facts -->
            <div class="section-title">
                <i data-feather="smile"></i> Fun Facts
            </div>
            <div class="stats-grid">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Wer fängt an?</span>
                        <i data-feather="play-circle" style="color: #10b981"></i>
                    </div>
                    <div class="stat-value" id="topStarter" style="font-size: 1.5rem;">-</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Der Frager?</span>
                        <i data-feather="help-circle" style="color: #fca5a5"></i>
                    </div>
                    <div class="stat-value" id="topAsker" style="font-size: 1.5rem;">-</div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Der Lacher?</span>
                        <i data-feather="meh" style="color: #fcd34d"></i>
                    </div>
                    <div class="stat-value" id="topLaugher" style="font-size: 1.5rem;">-</div>
                </div>
                <div class="card" style="grid-row: span 2;">
                    <div class="card-header">
                        <span class="card-title">Beste Sendezeit</span>
                        <i data-feather="clock" style="color: #6366f1"></i>
                    </div>
                    <div id="optimalTimeMain">
                        <div class="stat-value" id="optimalTime" style="font-size: 1.5rem;">-</div>
                    </div>
                    <div id="topOptimalTimes" style="margin-top: 1.5rem; border-top: 1px solid var(--glass-border); padding-top: 1rem;">
                        <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 0.8rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Top 5 Empfehlungen</p>
                        <ul id="optimalTimesList" style="list-style: none; padding: 0; margin: 0;">
                            <!-- List items injected here -->
                        </ul>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Aktivster Tag</span>
                        <i data-feather="activity" style="color: #ef4444"></i>
                    </div>
                    <div class="stat-value" style="font-size: 1.2rem;">
                        <div id="activeDateDate">-</div>
                        <div id="activeDateCount" style="font-size: 0.9rem; color: var(--text-muted);">- Nachrichten
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="section-title">
                <i data-feather="bar-chart-2"></i> Analysen
            </div>


            <!-- Charts Row 1 -->
            <div class="stats-grid" style="margin-top: 1.5rem;">
                <div class="card" style="grid-column: span 2;">
                    <div class="card-header">
                        <span class="card-title">Aktivität im Zeitverlauf</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="timelineChart"></canvas>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Nachrichten Verteilung</span>
                    </div>
                    <div class="chart-container"
                        style="position: relative; height: 200px; display: flex; justify-content: center;">
                        <canvas id="distributionChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Charts Row 2 -->
            <div class="stats-grid" style="margin-top: 1.5rem;">
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Aktivste Uhrzeit</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="hoursChart"></canvas>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Aktivster Wochentag</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="daysChart"></canvas>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header">
                        <span class="card-title">Top Emojis</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="emojiChart"></canvas>
                    </div>
                </div>

                <div class="card" style="grid-column: span 3;">
                    <div class="card-header">
                        <span class="card-title">Wort Wolke (Top 30)</span>
                        <i data-feather="cloud" style="color: var(--accent-secondary)"></i>
                    </div>
                    <div id="wordCloud"
                        style="display: flex; flex-wrap: wrap; gap: 0.8rem; justify-content: center; padding: 1rem;">
                        <!-- Words injected here -->
                    </div>
                </div>

            </div>
        </div>

        <div id="chat-view" class="tab-content hidden">
            <div class="section-title">
                <i data-feather="message-circle"></i> Visueller Chat
            </div>
            <div id="chatContainer" style="background: rgba(0,0,0,0.2); border-radius: 20px; padding: 2rem; height: 700px; overflow-y: auto; display: flex; flex-direction: column; gap: 1rem; border: 1px solid var(--glass-border);">
                <!-- Messages injected here -->
            </div>
        </div>
    </div>

    <!-- Additional Styles for animations -->
    <style>
        .spin {
            animation: spin 2s linear infinite;
        }

        @keyframes spin {
            100% {
                transform: rotate(360deg);
            }
        }

        /* Chart defaults */
        canvas {
            width: 100% !important;
            height: 100% !important;
        }
    </style>

    <script src="assets/js/script.js"></script>
    <script>
 feather.replace();
    </script>
</body>

</html>