# WhatsApp Chat Analyzer

Fast, private, and local WhatsApp chat statistics and predictive analytics.

## Features
- **Predictive Sending:** Weekday/hour reach and interaction probability.
- **Precision Metrics:** Filtering by 90-day activity for realistic stats.
- **Reporting:** Direct PDF export of the dashboard.
- **Content Stats:** Word frequency (German stopwords), emoji usage, and activity heatmaps.

## Quick Start
```bash
git clone https://git.joelunger.de/homelab/Whatsapp-Chat-Analyzer.git
php -S localhost:3000
```
Open `http://localhost:3000`.

## Structure
- `/api`: Processing entry point.
- `/core`: PHP parsing engine.
- `/assets`: Frontend JS/CSS and Chart.js integration.
- `index.php`: Main UI.

## Technical
- **Backend:** PHP 8+ (OO Parser).
- **Frontend:** Vanilla JS, CSS3, html2canvas, jsPDF.
- **Privacy:** Stateless processing. No data stored.