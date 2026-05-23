<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laravel Easy Dev v3 - Developer Hub</title>
    
    <!-- Premium Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-canvas: #0B0F19;
            --bg-container: #111827;
            --bg-surface: #1E293B;
            --primary: #6366F1;
            --primary-container: #EC4899;
            --success: #10B981;
            --text-main: #F8FAFC;
            --text-muted: #94A3B8;
            --ghost-border: rgba(71, 85, 105, 0.15);
            --glow-indigo: rgba(99, 102, 241, 0.35);
            --glow-pink: rgba(236, 72, 153, 0.35);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            user-select: none;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-canvas);
            color: var(--text-main);
            overflow-hidden: true;
            height: 100vh;
            display: flex;
        }

        h1, h2, h3, h4, .font-display {
            font-family: 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.02em;
        }

        /* Scrollbars */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: transparent;
        }
        ::-webkit-scrollbar-thumb {
            background: var(--bg-surface);
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }

        /* Sidebar styling */
        .sidebar {
            width: 260px;
            height: 100vh;
            background-color: rgba(17, 24, 39, 0.8);
            backdrop-filter: blur(20px);
            border-right: 0.5px solid var(--ghost-border);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 24px 16px;
            z-index: 10;
            flex-shrink: 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 32px;
        }

        .brand-logo {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-container) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px var(--glow-indigo);
        }

        .brand-logo svg {
            width: 20px;
            height: 20px;
            fill: #fff;
        }

        .brand-title {
            font-size: 1.15rem;
            font-weight: 800;
            background: linear-gradient(135deg, #fff 50%, var(--text-muted) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .menu-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex-grow: 1;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 10px;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            position: relative;
        }

        .menu-item:hover {
            color: var(--text-main);
            background-color: rgba(255, 255, 255, 0.03);
        }

        .menu-item.active {
            color: var(--text-main);
            background-color: rgba(99, 102, 241, 0.1);
        }

        .menu-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 15%;
            height: 70%;
            width: 4px;
            border-radius: 4px;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-container) 100%);
            box-shadow: 0 0 10px var(--primary-container);
        }

        .menu-item svg {
            width: 20px;
            height: 20px;
            opacity: 0.7;
        }

        .menu-item.active svg {
            opacity: 1;
            color: var(--primary);
        }

        .sidebar-footer {
            padding-top: 24px;
            border-top: 0.5px solid var(--ghost-border);
            text-align: center;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        /* Main Stage Layout */
        .stage {
            flex-grow: 1;
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* Top Bar */
        .topbar {
            height: 70px;
            padding: 0 32px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 0.5px solid var(--ghost-border);
            background-color: rgba(11, 15, 25, 0.5);
            backdrop-filter: blur(10px);
            z-index: 5;
        }

        .page-title {
            font-size: 1.4rem;
            font-weight: 700;
        }

        .status-badge {
            display: flex;
            align-items: center;
            gap: 8px;
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            background-color: var(--success);
            border-radius: 50%;
            box-shadow: 0 0 8px var(--success);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 0.5; }
            50% { transform: scale(1.2); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.5; }
        }

        /* View Panels */
        .view-panel {
            flex-grow: 1;
            padding: 32px;
            overflow-y: auto;
            display: none;
        }

        .view-panel.active {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* Metrics Bar */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }

        .metric-card {
            background-color: var(--bg-container);
            border-radius: 16px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .metric-icon-box {
            width: 52px;
            height: 52px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .metric-icon-box.indigo {
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary);
        }

        .metric-icon-box.pink {
            background-color: rgba(236, 72, 153, 0.1);
            color: var(--primary-container);
        }

        .metric-icon-box.green {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }

        .metric-icon-box svg {
            width: 26px;
            height: 26px;
        }

        .metric-details h4 {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .metric-value {
            font-size: 1.8rem;
            font-weight: 800;
            margin-top: 4px;
        }

        /* Node Graph Section */
        .canvas-container {
            background-color: var(--bg-container);
            border-radius: 20px;
            height: 520px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.25);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .grid-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(rgba(99, 102, 241, 0.05) 1px, transparent 1px),
                radial-gradient(rgba(236, 72, 153, 0.05) 1px, transparent 1px);
            background-size: 20px 20px;
            background-position: 0 0, 10px 10px;
            z-index: 1;
        }

        .graph-svg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
            pointer-events: none;
        }

        .model-node {
            position: absolute;
            background-color: var(--bg-surface);
            border-radius: 12px;
            padding: 16px;
            width: 180px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.3);
            z-index: 3;
            cursor: grab;
            transition: box-shadow 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }

        .model-node:hover {
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.3);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .model-node:active {
            cursor: grabbing;
        }

        .node-header {
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-main);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .node-table {
            font-size: 0.75rem;
            color: var(--text-muted);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .node-fields {
            margin-top: 8px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .node-field {
            font-size: 0.75rem;
            display: flex;
            justify-content: space-between;
            color: var(--text-muted);
        }

        .node-field .field-name {
            color: #c7d2fe;
        }

        /* Dream Playground Styling */
        .dream-split {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 24px;
            height: 520px;
        }

        .panel-card {
            background-color: var(--bg-container);
            border-radius: 20px;
            padding: 24px;
            display: flex;
            flex-direction: column;
            box-shadow: 0 8px 32px rgba(0,0,0,0.2);
            height: 100%;
        }

        .panel-card h3 {
            font-size: 1.15rem;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .prompter-textarea {
            flex-grow: 1;
            background-color: var(--bg-surface);
            border: 1px solid var(--ghost-border);
            border-radius: 12px;
            padding: 16px;
            color: var(--text-main);
            font-family: inherit;
            font-size: 0.95rem;
            resize: none;
            outline: none;
            transition: all 0.3s;
        }

        .prompter-textarea:focus {
            border-color: var(--primary);
            box-shadow: 0 0 10px var(--glow-indigo);
        }

        .gradient-btn {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-container) 100%);
            border: none;
            border-radius: 12px;
            padding: 14px 28px;
            color: #fff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(236, 72, 153, 0.2);
            margin-top: 16px;
        }

        .gradient-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(236, 72, 153, 0.4);
        }

        .gradient-btn:active {
            transform: translateY(0);
        }

        .gradient-btn:disabled {
            background: var(--bg-surface);
            color: var(--text-muted);
            box-shadow: none;
            cursor: not-allowed;
            transform: none;
        }

        .drafting-stage {
            display: flex;
            flex-direction: column;
            gap: 16px;
            overflow-y: auto;
            flex-grow: 1;
            padding: 10px;
            background-color: var(--bg-surface);
            border-radius: 12px;
            border: 1px solid var(--ghost-border);
        }

        .blueprint-card {
            background-color: var(--bg-container);
            border-radius: 10px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            border-left: 4px solid var(--success);
        }

        .blueprint-title {
            font-size: 0.95rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .blueprint-badge {
            background-color: rgba(16, 185, 129, 0.15);
            color: var(--success);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.7rem;
        }

        .blueprint-fields {
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .blueprint-field {
            background-color: var(--bg-surface);
            padding: 4px 8px;
            border-radius: 6px;
            border: 0.5px solid var(--ghost-border);
        }

        /* Scaffolder & Code Previewer */
        .scaffold-layout {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 24px;
            height: 520px;
        }

        .options-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 12px;
            margin-top: 12px;
        }

        .option-checkbox-card {
            background-color: var(--bg-surface);
            border-radius: 12px;
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            border: 1px solid var(--ghost-border);
            transition: all 0.3s;
        }

        .option-checkbox-card:hover {
            border-color: var(--primary);
            background-color: rgba(99, 102, 241, 0.05);
        }

        .option-checkbox-card input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
            cursor: pointer;
        }

        .option-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .option-title {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .option-desc {
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .code-preview-card {
            background-color: #05070c;
            border-radius: 20px;
            padding: 20px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.85rem;
            color: #a5b4fc;
            overflow-y: auto;
            position: relative;
            border: 1px solid var(--ghost-border);
            height: 100%;
        }

        .code-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--ghost-border);
            margin-bottom: 16px;
            color: var(--text-muted);
            font-size: 0.75rem;
        }

        .mac-dots {
            display: flex;
            gap: 6px;
        }

        .mac-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }

        .mac-red { background-color: #ef4444; }
        .mac-yellow { background-color: #f59e0b; }
        .mac-green { background-color: #10b981; }

        .keyword { color: #f472b6; }
        .class-name { color: #60a5fa; }
        .string-lit { color: #34d399; }
        .comment-lit { color: #6b7280; }

        /* Loader Overlay */
        .loader-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(11, 15, 25, 0.85);
            backdrop-filter: blur(8px);
            z-index: 100;
            display: none;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 20px;
            border-radius: 20px;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid var(--bg-surface);
            border-top: 4px solid var(--primary-container);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loader-text {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 1.1rem;
            font-weight: 700;
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <div>
            <div class="brand">
                <div class="brand-logo">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2L2 22h20L12 2zm0 4l6.5 13h-13L12 6z"/>
                    </svg>
                </div>
                <div class="brand-title">Easy Dev v3</div>
            </div>

            <ul class="menu-list">
                <li class="menu-item active" onclick="switchTab('dashboard', this)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="9" rx="1"/>
                        <rect x="14" y="3" width="7" height="5" rx="1"/>
                        <rect x="14" y="12" width="7" height="9" rx="1"/>
                        <rect x="3" y="16" width="7" height="5" rx="1"/>
                    </svg>
                    <span>Dashboard Graph</span>
                </li>
                <li class="menu-item" onclick="switchTab('dream', this)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                        <line x1="12" y1="22.08" x2="12" y2="12"/>
                    </svg>
                    <span>Dream Console</span>
                </li>
                <li class="menu-item" onclick="switchTab('scaffolder', this)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"/>
                        <polyline points="2 17 12 22 22 17"/>
                        <polyline points="2 12 12 17 22 12"/>
                    </svg>
                    <span>Scaffold Panel</span>
                </li>
            </ul>
        </div>

        <div class="sidebar-footer">
            <p>Environment: <strong>Local</strong></p>
            <p style="margin-top: 4px; opacity: 0.5;">AnasNashat \ EasyDev v3</p>
        </div>
    </div>

    <!-- MAIN STAGE -->
    <div class="stage">
        <!-- TOP BAR -->
        <div class="topbar">
            <div class="page-title" id="page-title-text">Dashboard Graph</div>
            <div class="status-badge">
                <div class="status-dot"></div>
                <span>Dev Server Online</span>
            </div>
        </div>

        <!-- PANEL 1: DASHBOARD / RELATION GRAPH -->
        <div class="view-panel active" id="panel-dashboard">
            <!-- Metrics -->
            <div class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-icon-box indigo">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <div class="metric-details">
                        <h4>Total Models</h4>
                        <div class="metric-value" id="stat-models">0</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon-box pink">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/>
                            <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>
                        </svg>
                    </div>
                    <div class="metric-details">
                        <h4>Synced Relations</h4>
                        <div class="metric-value" id="stat-relations">0</div>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-icon-box green">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="12 2 22 8.5 22 15.5 12 22 2 15.5 2 8.5 12 2"/>
                            <line x1="12" y1="22" x2="12" y2="12.5"/>
                            <line x1="22" y1="8.5" x2="12" y2="12.5"/>
                            <line x1="2" y1="8.5" x2="12" y2="12.5"/>
                        </svg>
                    </div>
                    <div class="metric-details">
                        <h4>System Quality</h4>
                        <div class="metric-value">A+</div>
                    </div>
                </div>
            </div>

            <!-- Canvas Container -->
            <div class="canvas-container" id="canvas-container">
                <div class="grid-overlay"></div>
                <svg class="graph-svg" id="graph-svg"></svg>
                <!-- Nodes populated by JS -->
            </div>
        </div>

        <!-- PANEL 2: DREAM CONSOLE -->
        <div class="view-panel" id="panel-dream">
            <div class="dream-split">
                <!-- Prompter Card -->
                <div class="panel-card">
                    <h3>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--primary-container)">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                        Dream Console Specifier
                    </h3>
                    <textarea class="prompter-textarea" id="dream-prompt" placeholder="E.g., Create customer subscriptions connected to users and products with status:string, price:integer, active:boolean"></textarea>
                    
                    <button class="gradient-btn" id="dream-btn" onclick="runDreamCompile()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="5 3 19 12 5 21 5 3"/>
                        </svg>
                        Compile Prompt Blueprint
                    </button>
                </div>

                <!-- Drafting Card -->
                <div class="panel-card">
                    <h3>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--success)">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                        Drafting Blueprint Stage
                    </h3>

                    <div class="drafting-stage" id="drafting-stage">
                        <div style="text-align: center; color: var(--text-muted); margin-top: 100px;">
                            <p>No active blueprint drafted yet.</p>
                            <p style="font-size: 0.8rem; margin-top: 8px;">Enter a natural language specification on the left and compile.</p>
                        </div>
                    </div>

                    <button class="gradient-btn" id="execute-blueprint-btn" disabled onclick="executeDreamBlueprint()">
                        ⚡ Execute Scaffolding Blueprint
                    </button>
                </div>
            </div>
        </div>

        <!-- PANEL 3: SCAFFOLDER & PREVIEWER -->
        <div class="view-panel" id="panel-scaffolder">
            <div class="scaffold-layout">
                <!-- Panel Options -->
                <div class="panel-card">
                    <h3>🏗️ Scaffold New Entity</h3>
                    <div style="display: flex; flex-direction: column; gap: 16px;">
                        <div>
                            <label style="font-size: 0.85rem; color: var(--text-muted); display: block; margin-bottom: 6px;">Entity Model Name</label>
                            <input type="text" id="scaffold-model" placeholder="E.g., Transaction" style="width: 100%; padding: 12px; border-radius: 10px; background-color: var(--bg-surface); border: 1px solid var(--ghost-border); color: var(--text-main); font-size: 0.95rem; outline: none;">
                        </div>

                        <div class="options-grid">
                            <div class="option-checkbox-card" onclick="toggleCheckbox('check-repo')">
                                <div class="option-info">
                                    <div class="option-title">Repository Pattern</div>
                                    <div class="option-desc">Interface & Eloquent repository layer</div>
                                </div>
                                <input type="checkbox" id="check-repo" checked>
                            </div>

                            <div class="option-checkbox-card" onclick="toggleCheckbox('check-service')">
                                <div class="option-info">
                                    <div class="option-title">Service Layer</div>
                                    <div class="option-desc">Injectable business services</div>
                                </div>
                                <input type="checkbox" id="check-service" checked>
                            </div>

                            <div class="option-checkbox-card" onclick="toggleCheckbox('check-api')">
                                <div class="option-info">
                                    <div class="option-title">API Resource & Controller</div>
                                    <div class="option-desc">Generate robust JSON endpoints</div>
                                </div>
                                <input type="checkbox" id="check-api" checked>
                            </div>
                        </div>

                        <button class="gradient-btn" style="margin-top: 8px;" onclick="runStandardScaffold()">
                            🚀 Generate Entity CRUD
                        </button>
                    </div>
                </div>

                <!-- Preview Panel -->
                <div class="code-preview-card">
                    <div class="code-header">
                        <div class="mac-dots">
                            <div class="mac-dot mac-red"></div>
                            <div class="mac-dot mac-yellow"></div>
                            <div class="mac-dot mac-green"></div>
                        </div>
                        <div id="preview-filename">Preview.php</div>
                    </div>
                    <pre><code id="code-content"><span class="comment-lit">// Standard scaffold blueprint preview is generated here.</span>
<span class="keyword">namespace</span> <span class="class-name">App\Models</span>;

<span class="keyword">class</span> <span class="class-name">PreviewModel</span> <span class="keyword">extends</span> <span class="class-name">Model</span>
{
    <span class="keyword">protected</span> <span class="comment-lit">$fillable</span> = [
        <span class="string-lit">'status'</span>,
        <span class="string-lit">'price'</span>,
    ];
}</code></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- LOADER OVERLAY -->
    <div class="loader-overlay" id="loader-overlay">
        <div class="spinner"></div>
        <div class="loader-text" id="loader-text">Compiling Blueprint...</div>
    </div>

    <!-- DYNAMIC JAVASCRIPT SYSTEM -->
    <script>
        let currentTab = 'dashboard';
        let modelsDataGlobal = [];
        let draftedBlueprint = null;

        document.addEventListener('DOMContentLoaded', () => {
            fetchModels();
        });

        function switchTab(tab, element) {
            currentTab = tab;
            
            // Manage Active Links
            document.querySelectorAll('.menu-item').forEach(item => item.classList.remove('active'));
            element.classList.add('active');

            // Manage Active View Panel
            document.querySelectorAll('.view-panel').forEach(panel => panel.classList.remove('active'));
            document.getElementById(`panel-${tab}`).classList.add('active');

            // Update Top bar Title
            const titles = {
                dashboard: 'Dashboard Graph',
                dream: 'Dream Console',
                scaffolder: 'Scaffold Panel'
            };
            document.getElementById('page-title-text').innerText = titles[tab];
        }

        function toggleCheckbox(id) {
            const el = document.getElementById(id);
            el.checked = !el.checked;
        }

        function showLoader(text) {
            document.getElementById('loader-text').innerText = text;
            document.getElementById('loader-overlay').style.display = 'flex';
        }

        function hideLoader() {
            document.getElementById('loader-overlay').style.display = 'none';
        }

        // Fetch Models & Draw Graph
        async function fetchModels() {
            try {
                const response = await fetch('/easy-dev/api/models');
                const data = await response.json();

                if (data.status === 'success') {
                    modelsDataGlobal = data.models;
                    document.getElementById('stat-models').innerText = data.models.length;
                    
                    // Count unique relationships
                    let relCount = 0;
                    data.models.forEach(m => relCount += (m.relations ? m.relations.length : 0));
                    document.getElementById('stat-relations').innerText = relCount;

                    renderNodeGraph(data.models);
                }
            } catch (err) {
                console.error("Error fetching models:", err);
            }
        }

        // Beautiful SVG-based Interactive Model Graph Renderer
        function renderNodeGraph(models) {
            const container = document.getElementById('canvas-container');
            const svg = document.getElementById('graph-svg');
            
            // Clean up existing nodes and paths
            document.querySelectorAll('.model-node').forEach(el => el.remove());
            svg.innerHTML = '';

            if (models.length === 0) {
                container.innerHTML += `
                    <div style="text-align: center; color: var(--text-muted); z-index: 5;" class="model-node">
                        No models found. Create one to begin.
                    </div>
                `;
                return;
            }

            // Positions configuration
            const width = container.clientWidth;
            const height = container.clientHeight;
            const centerX = width / 2;
            const centerY = height / 2;
            const radius = Math.min(width, height) * 0.35;

            // Map models to specific coordinates in circle
            const positions = {};
            models.forEach((model, i) => {
                const angle = (i / models.length) * 2 * Math.PI;
                positions[model.name] = {
                    x: centerX + radius * Math.cos(angle) - 90, // center offset
                    y: centerY + radius * Math.sin(angle) - 60
                };
            });

            // Create nodes
            models.forEach(model => {
                const pos = positions[model.name];
                const node = document.createElement('div');
                node.className = 'model-node';
                node.style.left = `${pos.x}px`;
                node.style.top = `${pos.y}px`;
                node.id = `node-${model.name}`;

                // Populate Fields lists
                let fieldsHtml = '';
                const displayCols = model.columns ? model.columns.slice(0, 3) : [];
                displayCols.forEach(col => {
                    fieldsHtml += `
                        <div class="node-field">
                            <span class="field-name">${col.name}</span>
                            <span>${col.type}</span>
                        </div>
                    `;
                });
                
                if (model.columns && model.columns.length > 3) {
                    fieldsHtml += `<div class="node-field" style="color:var(--text-muted); font-style:italic;">+ ${model.columns.length - 3} more</div>`;
                }

                node.innerHTML = `
                    <div class="node-header">
                        <span>${model.name}</span>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                            <line x1="9" y1="3" x2="9" y2="21"/>
                        </svg>
                    </div>
                    <div class="node-table">table: ${model.table}</div>
                    <div class="node-fields">${fieldsHtml}</div>
                `;

                // Add simple drag & drop handling
                makeDraggable(node, (newX, newY) => {
                    positions[model.name] = { x: newX, y: newY };
                    drawConnections(models, positions);
                });

                container.appendChild(node);
            });

            // Define marker arrows for SVG paths
            const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
            
            const markerIndigo = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
            markerIndigo.setAttribute('id', 'arrow-indigo');
            markerIndigo.setAttribute('viewBox', '0 0 10 10');
            markerIndigo.setAttribute('refX', '8');
            markerIndigo.setAttribute('refY', '5');
            markerIndigo.setAttribute('markerWidth', '6');
            markerIndigo.setAttribute('markerHeight', '6');
            markerIndigo.setAttribute('orient', 'auto-start-reverse');
            markerIndigo.innerHTML = `<path d="M 0 0 L 10 5 L 0 10 z" fill="#6366F1"/>`;
            
            const markerPink = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
            markerPink.setAttribute('id', 'arrow-pink');
            markerPink.setAttribute('viewBox', '0 0 10 10');
            markerPink.setAttribute('refX', '8');
            markerPink.setAttribute('refY', '5');
            markerPink.setAttribute('markerWidth', '6');
            markerPink.setAttribute('markerHeight', '6');
            markerPink.setAttribute('orient', 'auto-start-reverse');
            markerPink.innerHTML = `<path d="M 0 0 L 10 5 L 0 10 z" fill="#EC4899"/>`;

            defs.appendChild(markerIndigo);
            defs.appendChild(markerPink);
            svg.appendChild(defs);

            drawConnections(models, positions);
        }

        // Draw relationship path connections using SVG bezier paths
        function drawConnections(models, positions) {
            const svg = document.getElementById('graph-svg');
            
            // Clear path tags
            svg.querySelectorAll('path').forEach(p => p.remove());

            models.forEach(model => {
                const sourcePos = positions[model.name];
                if (!sourcePos || !model.relations) return;

                model.relations.forEach(rel => {
                    const targetPos = positions[rel.related];
                    if (!targetPos) return;

                    // Compute center positions of nodes
                    const startX = sourcePos.x + 90;
                    const startY = sourcePos.y + 60;
                    const endX = targetPos.x + 90;
                    const endY = targetPos.y + 60;

                    // Draw sleek curves
                    const midX = (startX + endX) / 2;
                    const midY = (startY + endY) / 2;
                    
                    const isBelongsTo = rel.type === 'belongsTo';
                    const color = isBelongsTo ? '#6366F1' : '#EC4899';
                    const marker = isBelongsTo ? 'url(#arrow-indigo)' : 'url(#arrow-pink)';

                    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    // Cubic bezier path layout
                    path.setAttribute('d', `M ${startX} ${startY} Q ${midX + 25} ${midY - 25} ${endX} ${endY}`);
                    path.setAttribute('stroke', color);
                    path.setAttribute('stroke-width', '2');
                    path.setAttribute('fill', 'none');
                    path.setAttribute('marker-end', marker);
                    path.setAttribute('opacity', '0.75');
                    path.style.filter = `drop-shadow(0px 0px 4px ${color})`;

                    svg.appendChild(path);
                });
            });
        }

        // Simple Draggable Node utility
        function makeDraggable(element, onDrag) {
            let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
            element.onmousedown = dragMouseDown;

            function dragMouseDown(e) {
                e = e || window.event;
                e.preventDefault();
                pos3 = e.clientX;
                pos4 = e.clientY;
                document.onmouseup = closeDragElement;
                document.onmousemove = elementDrag;
            }

            function elementDrag(e) {
                e = e || window.event;
                e.preventDefault();
                pos1 = pos3 - e.clientX;
                pos2 = pos4 - e.clientY;
                pos3 = e.clientX;
                pos4 = e.clientY;
                
                const newY = element.offsetTop - pos2;
                const newX = element.offsetLeft - pos1;
                
                element.style.top = `${newY}px`;
                element.style.left = `${newX}px`;
                
                onDrag(newX, newY);
            }

            function closeDragElement() {
                document.onmouseup = null;
                document.onmousemove = null;
            }
        }

        // Run Dream Prompter
        async function runDreamCompile() {
            const promptText = document.getElementById('dream-prompt').value;
            if (!promptText.trim()) return;

            showLoader('Compiling AI Blueprint...');

            try {
                const response = await fetch('/easy-dev/api/scaffold', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        action: 'dream',
                        prompt: promptText
                    })
                });

                const data = await response.json();
                hideLoader();

                if (data.status === 'success' && data.plans) {
                    draftedBlueprint = data.plans;
                    renderBlueprint(data.plans);
                    document.getElementById('execute-blueprint-btn').disabled = false;
                } else if (data.status === 'success' && data.details) {
                    // direct execute
                    fetchModels();
                    switchTab('dashboard', document.querySelector('.menu-item'));
                } else {
                    alert('Parser error: ' + (data.message || 'Unknown error'));
                }
            } catch (err) {
                hideLoader();
                console.error(err);
                alert('Scaffolding failed, check console details.');
            }
        }

        function renderBlueprint(blueprint) {
            const container = document.getElementById('drafting-stage');
            container.innerHTML = '';

            let fieldsHtml = '';
            for (const [field, type] of Object.entries(blueprint.fields)) {
                fieldsHtml += `<span class="blueprint-field">${field} (${type})</span>`;
            }
            if (!fieldsHtml) fieldsHtml = '<span style="color:var(--text-muted)">Default timestamps/ID only</span>';

            let relationsHtml = '';
            blueprint.relations.forEach(rel => {
                relationsHtml += `<span class="blueprint-field" style="border-color:var(--primary)">BelongsTo: ${rel}</span>`;
            });
            if (!relationsHtml) relationsHtml = '<span style="color:var(--text-muted)">none</span>';

            container.innerHTML = `
                <div class="blueprint-card">
                    <div class="blueprint-title">
                        <span>Entity Blueprint: <strong>${blueprint.model}</strong></span>
                        <span class="blueprint-badge">Ready</span>
                    </div>
                    <p style="font-size: 0.8rem; color: var(--text-muted);">Table: ${blueprint.model.toLowerCase()}s</p>
                    
                    <div style="margin-top: 10px;">
                        <h5 style="font-size: 0.8rem; margin-bottom: 4px;">Columns</h5>
                        <div class="blueprint-fields">${fieldsHtml}</div>
                    </div>

                    <div style="margin-top: 10px;">
                        <h5 style="font-size: 0.8rem; margin-bottom: 4px;">Relations</h5>
                        <div class="blueprint-fields">${relationsHtml}</div>
                    </div>
                </div>
            `;
        }

        async function executeDreamBlueprint() {
            if (!draftedBlueprint) return;
            showLoader('Executing Scaffolding Blueprint...');

            try {
                // Call again without dry run, directly using prompt text
                const promptText = document.getElementById('dream-prompt').value;
                const response = await fetch('/easy-dev/api/scaffold', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        action: 'dream',
                        prompt: promptText
                    })
                });

                const data = await response.json();
                hideLoader();

                if (data.status === 'success') {
                    // Update preview and dashboard
                    fetchModels();
                    
                    // Display details in preview
                    const filesList = data.details.files.map(f => `  ✓ ${f}`).join('\n');
                    document.getElementById('preview-filename').innerText = `${draftedBlueprint.model}.php`;
                    document.getElementById('code-content').innerHTML = `
<span class="comment-lit">// Successfully Scaffolder Blueprint for ${draftedBlueprint.model}</span>
<span class="keyword">class</span> <span class="class-name">${draftedBlueprint.model}</span> <span class="keyword">extends</span> <span class="class-name">Model</span>
{
    <span class="keyword">protected</span> <span class="comment-lit">$fillable</span> = [
        ${Object.keys(draftedBlueprint.fields).map(f => `'${f}'`).join(', ')}
    ];
}

<span class="keyword">Generated Files:</span>
${filesList}
                    `;
                    
                    // Reset blueprint buttons
                    draftedBlueprint = null;
                    document.getElementById('execute-blueprint-btn').disabled = true;
                    switchTab('scaffolder', document.querySelectorAll('.menu-item')[2]);
                }
            } catch (err) {
                hideLoader();
                console.error(err);
            }
        }

        // Run Standard Scaffold
        async function runStandardScaffold() {
            const modelName = document.getElementById('scaffold-model').value.trim();
            if (!modelName) return;

            showLoader('Scaffolding Entity...');

            const options = {
                repository: document.getElementById('check-repo').checked,
                service: document.getElementById('check-service').checked,
                api: document.getElementById('check-api').checked,
            };

            try {
                const response = await fetch('/easy-dev/api/scaffold', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        action: 'crud',
                        model: modelName,
                        options: options
                    })
                });

                const data = await response.json();
                hideLoader();

                if (data.status === 'success') {
                    fetchModels();
                    document.getElementById('preview-filename').innerText = `${modelName}.php`;
                    document.getElementById('code-content').innerHTML = `
<span class="comment-lit">// Standard scaffold blueprint completed successfully.</span>
<span class="keyword">namespace</span> <span class="class-name">App\Models</span>;

<span class="keyword">class</span> <span class="class-name">${modelName}</span> <span class="keyword">extends</span> <span class="class-name">Model</span>
{
    <span class="comment-lit">// Auto-generated model class and related CRUD stack.</span>
}
                    `;
                    alert(data.message);
                } else {
                    alert('Scaffold failed: ' + data.message);
                }
            } catch (err) {
                hideLoader();
                console.error(err);
            }
        }
    </script>
</body>
</html>
