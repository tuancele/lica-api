<!DOCTYPE html>
<html lang="vi">
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
    
    <!-- Marked.js for markdown parsing -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
        }
        
        .docs-container {
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .docs-sidebar {
            width: 300px;
            background: #2c3e50;
            color: #ecf0f1;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            padding: 20px 0;
            z-index: 1000;
        }
        
        .docs-sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #34495e;
            margin-bottom: 20px;
        }
        
        .docs-sidebar-header h1 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #fff;
        }
        
        .docs-search {
            width: 100%;
            padding: 10px;
            border: 1px solid #34495e;
            border-radius: 4px;
            background: #34495e;
            color: #ecf0f1;
            font-size: 14px;
        }
        
        .docs-search::placeholder {
            color: #95a5a6;
        }
        
        .docs-nav {
            padding: 0 10px;
        }
        
        .docs-nav-item {
            display: block;
            padding: 12px 15px;
            color: #bdc3c7;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 5px;
            transition: all 0.3s;
            font-size: 14px;
        }
        
        .docs-nav-item:hover {
            background: #34495e;
            color: #fff;
        }
        
        .docs-nav-item.active {
            background: #3498db;
            color: #fff;
        }
        
        .docs-nav-section {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #34495e;
        }
        
        .docs-nav-section-title {
            font-size: 12px;
            text-transform: uppercase;
            color: #7f8c8d;
            padding: 0 15px 10px;
            font-weight: 600;
            letter-spacing: 1px;
        }
        
        /* Main Content */
        .docs-content {
            flex: 1;
            margin-left: 300px;
            padding: 40px;
            max-width: 1200px;
        }
        
        .docs-section {
            background: #fff;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .docs-section h1 {
            font-size: 32px;
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        
        .docs-section h2 {
            font-size: 24px;
            margin-top: 40px;
            margin-bottom: 20px;
            color: #34495e;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
        }
        
        .docs-section h3 {
            font-size: 20px;
            margin-top: 30px;
            margin-bottom: 15px;
            color: #34495e;
        }
        
        .docs-section h4 {
            font-size: 16px;
            margin-top: 20px;
            margin-bottom: 10px;
            color: #555;
        }
        
        .docs-section p {
            margin-bottom: 15px;
            color: #555;
        }
        
        .docs-section ul, .docs-section ol {
            margin-left: 30px;
            margin-bottom: 15px;
        }
        
        .docs-section li {
            margin-bottom: 8px;
            color: #555;
        }
        
        .docs-section code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            color: #e74c3c;
        }
        
        .docs-section pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 20px;
            border-radius: 6px;
            overflow-x: auto;
            margin: 20px 0;
        }
        
        .docs-section pre code {
            background: transparent;
            padding: 0;
            color: inherit;
        }
        
        .docs-section table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .docs-section table th,
        .docs-section table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .docs-section table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .docs-section blockquote {
            border-left: 4px solid #3498db;
            padding-left: 20px;
            margin: 20px 0;
            color: #7f8c8d;
            font-style: italic;
        }
        
        .docs-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        
        .docs-badge.success {
            background: #27ae60;
            color: #fff;
        }
        
        .docs-badge.warning {
            background: #f39c12;
            color: #fff;
        }
        
        .docs-badge.info {
            background: #3498db;
            color: #fff;
        }
        
        .docs-endpoint {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #3498db;
        }
        
        .docs-endpoint-method {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
            margin-right: 10px;
        }
        
        .docs-endpoint-method.get {
            background: #27ae60;
            color: #fff;
        }
        
        .docs-endpoint-method.post {
            background: #3498db;
            color: #fff;
        }
        
        .docs-endpoint-method.put {
            background: #f39c12;
            color: #fff;
        }
        
        .docs-endpoint-method.delete {
            background: #e74c3c;
            color: #fff;
        }
        
        .docs-endpoint-method.patch {
            background: #9b59b6;
            color: #fff;
        }
        
        .docs-endpoint-url {
            font-family: 'Courier New', monospace;
            font-size: 16px;
            color: #2c3e50;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .docs-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            
            .docs-sidebar.open {
                transform: translateX(0);
            }
            
            .docs-content {
                margin-left: 0;
                padding: 20px;
            }
            
            .docs-mobile-toggle {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
                background: #3498db;
                color: #fff;
                border: none;
                padding: 10px 15px;
                border-radius: 4px;
                cursor: pointer;
            }
        }
        
        .docs-mobile-toggle {
            display: none;
        }
        
        /* Scrollbar */
        .docs-sidebar::-webkit-scrollbar {
            width: 8px;
        }
        
        .docs-sidebar::-webkit-scrollbar-track {
            background: #2c3e50;
        }
        
        .docs-sidebar::-webkit-scrollbar-thumb {
            background: #34495e;
            border-radius: 4px;
        }
        
        .docs-sidebar::-webkit-scrollbar-thumb:hover {
            background: #4a5f7a;
        }
        
        .docs-empty {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        
        .docs-empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="docs-container">
        <!-- Sidebar -->
        <aside class="docs-sidebar" id="sidebar">
            <div class="docs-sidebar-header">
                <h1>📚 API Docs</h1>
                <input type="text" class="docs-search" id="searchInput" placeholder="Tìm kiếm...">
            </div>
            
            <nav class="docs-nav" id="navMenu">
                <div class="docs-nav-section">
                    <div class="docs-nav-section-title">Tài liệu chính</div>
                    @foreach($docs as $index => $doc)
                        <a href="#doc-{{ $index }}" class="docs-nav-item" data-doc-index="{{ $index }}">
                            {{ $doc['title'] }}
                        </a>
                    @endforeach
                </div>
                
                @if(count($sections) > 0)
                <div class="docs-nav-section">
                    <div class="docs-nav-section-title">Mục lục</div>
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
            <button class="docs-mobile-toggle" id="mobileToggle">☰ Menu</button>
            
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
                    <h2>Không tìm thấy tài liệu</h2>
                    <p>Vui lòng kiểm tra lại các file .md trong thư mục dự án.</p>
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
                            item.style.display = 'block';
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
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                            
                            // Close mobile menu
                            if (window.innerWidth <= 768) {
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
        });
    </script>
</body>
</html>

