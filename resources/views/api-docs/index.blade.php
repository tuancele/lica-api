<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>API Documentation - LICA</title>
    
    <!-- Highlight.js for syntax highlighting -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/json.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/bash.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/php.min.js"></script>
    
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-dark: #1e40af;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --dark-bg: #1e293b;
            --dark-sidebar: #0f172a;
            --light-bg: #f8fafc;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-light: #94a3b8;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen', 'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue', sans-serif;
            line-height: 1.7;
            color: var(--text-primary);
            background: var(--light-bg);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        .docs-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .docs-sidebar {
            width: 320px;
            background: linear-gradient(180deg, var(--dark-sidebar) 0%, #1e293b 100%);
            color: #fff;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            padding: 0;
            z-index: 1000;
            box-shadow: var(--shadow-xl);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .docs-sidebar-header {
            padding: 24px 20px;
            background: rgba(255, 255, 255, 0.05);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 10;
            backdrop-filter: blur(10px);
        }
        
        .docs-sidebar-header h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 16px;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .docs-sidebar-header h1 i {
            color: var(--primary-color);
        }
        
        .docs-search {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            font-size: 14px;
            transition: all 0.3s;
            backdrop-filter: blur(10px);
        }
        
        .docs-search:focus {
            outline: none;
            border-color: var(--primary-color);
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .docs-search::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .docs-nav {
            padding: 16px 0;
        }
        
        .docs-nav-section {
            margin-bottom: 24px;
        }
        
        .docs-nav-section-title {
            font-size: 11px;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.5);
            padding: 0 20px 12px;
            font-weight: 600;
            letter-spacing: 1.5px;
        }
        
        .docs-nav-item {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.2s;
            font-size: 14px;
            border-left: 3px solid transparent;
            position: relative;
        }
        
        .docs-nav-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            border-left-color: var(--primary-color);
        }
        
        .docs-nav-item.active {
            background: rgba(37, 99, 235, 0.2);
            color: #fff;
            border-left-color: var(--primary-color);
            font-weight: 600;
        }
        
        .docs-nav-item.active::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: var(--primary-color);
        }
        
        .docs-nav-subitem {
            padding-left: 40px;
            font-size: 13px;
            color: rgba(255, 255, 255, 0.7);
        }
        
        /* Main Content */
        .docs-content {
            flex: 1;
            margin-left: 320px;
            padding: 0;
            background: var(--light-bg);
        }
        
        .docs-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 60px 40px;
            margin-bottom: 40px;
            box-shadow: var(--shadow-lg);
        }
        
        .docs-header h1 {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        
        .docs-header p {
            font-size: 18px;
            opacity: 0.9;
            max-width: 800px;
        }
        
        .docs-section {
            background: #fff;
            border-radius: 12px;
            padding: 40px;
            margin: 0 40px 40px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }
        
        .docs-section:first-of-type {
            margin-top: 40px;
        }
        
        .docs-section h1 {
            font-size: 36px;
            margin-bottom: 24px;
            color: var(--text-primary);
            font-weight: 700;
            padding-bottom: 16px;
            border-bottom: 3px solid var(--primary-color);
        }
        
        .docs-section h2 {
            font-size: 28px;
            margin-top: 48px;
            margin-bottom: 24px;
            color: var(--text-primary);
            font-weight: 600;
            padding-top: 24px;
            border-top: 2px solid var(--border-color);
            scroll-margin-top: 100px;
        }
        
        .docs-section h3 {
            font-size: 22px;
            margin-top: 36px;
            margin-bottom: 16px;
            color: var(--text-primary);
            font-weight: 600;
            scroll-margin-top: 100px;
        }
        
        .docs-section h4 {
            font-size: 18px;
            margin-top: 24px;
            margin-bottom: 12px;
            color: var(--text-secondary);
            font-weight: 600;
        }
        
        .docs-section p {
            margin-bottom: 16px;
            color: var(--text-secondary);
            font-size: 16px;
        }
        
        .docs-section ul, .docs-section ol {
            margin-left: 24px;
            margin-bottom: 20px;
        }
        
        .docs-section li {
            margin-bottom: 10px;
            color: var(--text-secondary);
            line-height: 1.8;
        }
        
        .docs-section code {
            background: #f1f5f9;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', 'Courier New', monospace;
            font-size: 14px;
            color: #e11d48;
            border: 1px solid var(--border-color);
        }
        
        .docs-section pre {
            background: #1e293b;
            color: #e2e8f0;
            padding: 24px;
            border-radius: 8px;
            overflow-x: auto;
            margin: 24px 0;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .docs-section pre code {
            background: transparent;
            padding: 0;
            color: inherit;
            border: none;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .docs-section table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            display: table;
        }
        
        .docs-section table th,
        .docs-section table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .docs-section table th {
            background: #f8fafc;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }
        
        .docs-section table td {
            color: var(--text-secondary);
            font-size: 14px;
            word-break: break-word;
        }
        
        .docs-section table tr:last-child td {
            border-bottom: none;
        }
        
        .docs-section table tr:hover {
            background: #f8fafc;
        }
        
        /* Mobile Table Responsive - 2026 Standards */
        @media (max-width: 768px) {
            .docs-section table,
            .docs-section thead,
            .docs-section tbody,
            .docs-section th,
            .docs-section td,
            .docs-section tr {
                display: block;
            }
            
            .docs-section thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            
            .docs-section tr {
                background: #fff;
                border: 1px solid var(--border-color);
                border-radius: 8px;
                margin-bottom: 16px;
                padding: 0;
                box-shadow: var(--shadow-sm);
                transition: all 0.3s ease;
            }
            
            .docs-section tr:hover {
                box-shadow: var(--shadow-md);
                transform: translateY(-2px);
            }
            
            .docs-section td {
                border: none;
                border-bottom: 1px solid var(--border-color);
                position: relative;
                padding: 12px 16px 12px 45%;
                text-align: left;
                min-height: 44px;
                display: flex;
                align-items: center;
            }
            
            .docs-section td:last-child {
                border-bottom: none;
            }
            
            .docs-section td:before {
                content: attr(data-label);
                position: absolute;
                left: 16px;
                width: 40%;
                font-weight: 600;
                font-size: 12px;
                text-transform: uppercase;
                color: var(--text-primary);
                letter-spacing: 0.5px;
                display: flex;
                align-items: center;
                height: 100%;
            }
            
            .docs-section td:empty:before {
                display: none;
            }
            
            /* Alternative: Horizontal Scroll for Complex Tables (2026 Standard) */
            .docs-table-wrapper {
                position: relative;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                margin: 24px -16px;
                padding: 0 16px;
                scrollbar-width: thin;
                scrollbar-color: var(--primary-color) #f1f5f9;
            }
            
            .docs-table-wrapper table {
                min-width: 600px;
                display: table;
                margin: 0;
            }
            
            .docs-table-wrapper table thead {
                display: table-header-group;
            }
            
            .docs-table-wrapper table tbody {
                display: table-row-group;
            }
            
            .docs-table-wrapper table tr {
                display: table-row;
                background: transparent;
                border: none;
                box-shadow: none;
                margin: 0;
            }
            
            .docs-table-wrapper table td {
                display: table-cell;
                padding: 14px 16px;
                border-bottom: 1px solid var(--border-color);
                min-height: auto;
            }
            
            .docs-table-wrapper table td:before {
                display: none;
            }
            
            /* Scroll indicator hint */
            .docs-table-wrapper::before {
                content: 'Swipe →';
                position: sticky;
                left: 100%;
                top: 8px;
                font-size: 11px;
                color: var(--primary-color);
                opacity: 0.6;
                pointer-events: none;
                white-space: nowrap;
                margin-left: 8px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            /* Touch-friendly scrollbar (2026 Standard) */
            .docs-table-wrapper::-webkit-scrollbar {
                height: 6px;
            }
            
            .docs-table-wrapper::-webkit-scrollbar-track {
                background: #f1f5f9;
                border-radius: 3px;
            }
            
            .docs-table-wrapper::-webkit-scrollbar-thumb {
                background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
                border-radius: 3px;
            }
            
            .docs-table-wrapper::-webkit-scrollbar-thumb:hover {
                background: var(--primary-dark);
            }
            
            /* Firefox scrollbar */
            .docs-table-wrapper {
                scrollbar-width: thin;
                scrollbar-color: var(--primary-color) #f1f5f9;
            }
            
            /* Card layout fallback for very small screens */
            @media (max-width: 480px) {
                .docs-table-wrapper {
                    margin: 24px -16px;
                    padding: 0;
                }
                
                .docs-table-wrapper table {
                    min-width: 100%;
                    display: block;
                }
                
                .docs-table-wrapper table thead {
                    display: none;
                }
                
                .docs-table-wrapper table tbody {
                    display: block;
                }
                
                .docs-table-wrapper table tr {
                    display: block;
                    background: #fff;
                    border: 1px solid var(--border-color);
                    border-radius: 8px;
                    margin-bottom: 16px;
                    padding: 0;
                    box-shadow: var(--shadow-sm);
                }
                
                .docs-table-wrapper table td {
                    display: block;
                    padding: 12px 16px 12px 45%;
                    border-bottom: 1px solid var(--border-color);
                    position: relative;
                    min-height: 44px;
                    text-align: left;
                }
                
                .docs-table-wrapper table td:last-child {
                    border-bottom: none;
                }
                
                .docs-table-wrapper table td:before {
                    content: attr(data-label);
                    position: absolute;
                    left: 16px;
                    width: 40%;
                    font-weight: 600;
                    font-size: 12px;
                    text-transform: uppercase;
                    color: var(--text-primary);
                    letter-spacing: 0.5px;
                    display: flex;
                    align-items: center;
                    height: 100%;
                }
                
                .docs-table-wrapper::before {
                    display: none;
                }
            }
        }
        
        /* Tablet Optimization */
        @media (min-width: 769px) and (max-width: 1024px) {
            .docs-section table {
                font-size: 13px;
            }
            
            .docs-section table th,
            .docs-section table td {
                padding: 12px 14px;
            }
        }
        
        .docs-section blockquote {
            border-left: 4px solid var(--primary-color);
            padding: 16px 24px;
            margin: 24px 0;
            background: #f8fafc;
            border-radius: 0 8px 8px 0;
            color: var(--text-secondary);
            font-style: italic;
        }
        
        .docs-badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .docs-badge.success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .docs-badge.warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .docs-badge.info {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .docs-endpoint {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 20px 24px;
            border-radius: 8px;
            margin: 24px 0;
            border-left: 4px solid var(--primary-color);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        .docs-endpoint-method {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 6px 14px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 70px;
            text-align: center;
        }
        
        .docs-endpoint-method.get {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: #fff;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);
        }
        
        .docs-endpoint-method.post {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: #fff;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.3);
        }
        
        .docs-endpoint-method.put {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #fff;
            box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
        }
        
        .docs-endpoint-method.delete {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: #fff;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.3);
        }
        
        .docs-endpoint-method.patch {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: #fff;
            box-shadow: 0 2px 4px rgba(139, 92, 246, 0.3);
        }
        
        .docs-endpoint-url {
            font-family: 'SF Mono', 'Monaco', 'Inconsolata', 'Roboto Mono', 'Courier New', monospace;
            font-size: 15px;
            color: var(--text-primary);
            font-weight: 500;
            flex: 1;
        }
        
        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .docs-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .docs-sidebar.open {
                transform: translateX(0);
            }
            
            .docs-content {
                margin-left: 0;
            }
            
            .docs-section {
                margin: 0 20px 30px;
                padding: 30px 20px;
            }
            
            .docs-header {
                padding: 40px 24px;
            }
            
            .docs-header h1 {
                font-size: 32px;
            }
        }
        
        @media (max-width: 640px) {
            .docs-section {
                margin: 0 16px 24px;
                padding: 24px 16px;
                border-radius: 8px;
            }
            
            .docs-section h1 {
                font-size: 28px;
            }
            
            .docs-section h2 {
                font-size: 24px;
                margin-top: 36px;
            }
            
            .docs-section h3 {
                font-size: 20px;
            }
            
            .docs-header {
                padding: 32px 20px;
            }
            
            .docs-header h1 {
                font-size: 28px;
            }
            
            .docs-header p {
                font-size: 16px;
            }
            
            .docs-endpoint {
                flex-direction: column;
                align-items: flex-start;
                padding: 16px;
            }
            
            .docs-endpoint-url {
                width: 100%;
                word-break: break-all;
                margin-top: 8px;
            }
        }
        
        .docs-mobile-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: var(--primary-color);
            color: #fff;
            border: none;
            padding: 12px 18px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s;
        }
        
        .docs-mobile-toggle:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }
        
        @media (max-width: 1024px) {
            .docs-mobile-toggle {
                display: block;
            }
        }
        
        /* Scrollbar */
        .docs-sidebar::-webkit-scrollbar {
            width: 8px;
        }
        
        .docs-sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .docs-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
        }
        
        .docs-sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .docs-empty {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-secondary);
        }
        
        .docs-empty-icon {
            font-size: 80px;
            margin-bottom: 24px;
            opacity: 0.5;
        }
        
        .docs-empty h2 {
            font-size: 24px;
            margin-bottom: 12px;
            color: var(--text-primary);
        }
        
        /* Smooth transitions */
        * {
            transition: background-color 0.2s, color 0.2s, border-color 0.2s;
        }
        
        /* Print styles */
        @media print {
            .docs-sidebar,
            .docs-mobile-toggle {
                display: none;
            }
            
            .docs-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="docs-container">
        <!-- Sidebar -->
        <aside class="docs-sidebar" id="sidebar">
            <div class="docs-sidebar-header">
                <h1>
                    <i class="fas fa-book"></i>
                    API Documentation
                </h1>
                <input type="text" class="docs-search" id="searchInput" placeholder="Search documentation...">
            </div>
            
            <nav class="docs-nav" id="navMenu">
                <div class="docs-nav-section">
                    <div class="docs-nav-section-title">Main Documentation</div>
                    @foreach($docs as $index => $doc)
                        <a href="#doc-{{ $index }}" class="docs-nav-item" data-doc-index="{{ $index }}">
                            <i class="fas fa-file-alt" style="margin-right: 10px; opacity: 0.6;"></i>
                            {{ $doc['title'] }}
                        </a>
                    @endforeach
                </div>
                
                @if(count($sections) > 0)
                <div class="docs-nav-section">
                    <div class="docs-nav-section-title">Table of Contents</div>
                    @foreach($sections as $section)
                        <a href="#{{ $section['anchor'] }}" class="docs-nav-item docs-nav-subitem">
                            {{ $section['title'] }}
                        </a>
                    @endforeach
                </div>
                @endif
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="docs-content">
            <button class="docs-mobile-toggle" id="mobileToggle">
                <i class="fas fa-bars"></i> Menu
            </button>
            
            <div class="docs-header">
                <h1>API Documentation</h1>
                <p>Complete reference guide for LICA API endpoints, authentication, and integration examples.</p>
            </div>
            
            @if(count($docs) > 0)
                @foreach($docs as $index => $doc)
                    <section class="docs-section" id="doc-{{ $index }}" data-title="{{ strtolower($doc['title']) }}">
                        <div class="docs-content-markdown">
                            {!! $doc['html'] ?? '' !!}
                        </div>
                    </section>
                @endforeach
            @else
                <div class="docs-empty">
                    <div class="docs-empty-icon">📄</div>
                    <h2>No Documentation Found</h2>
                    <p>Please check the .md files in the project directory.</p>
                </div>
            @endif
        </main>
    </div>
    
    <script>
        // Initialize highlight.js
        document.addEventListener('DOMContentLoaded', function() {
            hljs.highlightAll();
            
            // Mobile menu toggle
            const mobileToggle = document.getElementById('mobileToggle');
            const sidebar = document.getElementById('sidebar');
            
            if (mobileToggle) {
                mobileToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('open');
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 1024) {
                    if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target)) {
                        sidebar.classList.remove('open');
                    }
                }
            });
            
            // Search functionality
            const searchInput = document.getElementById('searchInput');
            const navItems = document.querySelectorAll('.docs-nav-item');
            const sections = document.querySelectorAll('.docs-section');
            
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const query = e.target.value.toLowerCase();
                    
                    // Filter nav items
                    navItems.forEach(item => {
                        const text = item.textContent.toLowerCase();
                        if (text.includes(query) || query === '') {
                            item.style.display = 'flex';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                    
                    // Filter sections
                    sections.forEach(section => {
                        const title = section.getAttribute('data-title') || '';
                        const content = section.textContent.toLowerCase();
                        if (content.includes(query) || title.includes(query) || query === '') {
                            section.style.display = 'block';
                        } else {
                            section.style.display = 'none';
                        }
                    });
                });
            }
            
            // Smooth scroll for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    const href = this.getAttribute('href');
                    if (href !== '#') {
                        e.preventDefault();
                        const target = document.querySelector(href);
                        if (target) {
                            const offset = 100;
                            const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - offset;
                            
                            window.scrollTo({
                                top: targetPosition,
                                behavior: 'smooth'
                            });
                            
                            // Close mobile menu
                            if (window.innerWidth <= 1024) {
                                sidebar.classList.remove('open');
                            }
                        }
                    }
                });
            });
            
            // Active nav item on scroll
            const observerOptions = {
                root: null,
                rootMargin: '-20% 0px -70% 0px',
                threshold: 0
            };
            
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const id = entry.target.id;
                        navItems.forEach(item => {
                            item.classList.remove('active');
                            if (item.getAttribute('href') === '#' + id) {
                                item.classList.add('active');
                            }
                        });
                    }
                });
            }, observerOptions);
            
            sections.forEach(section => {
                observer.observe(section);
            });
            
            // Highlight code blocks on page load
            document.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightElement(block);
            });
        });
    </script>
</body>
</html>
