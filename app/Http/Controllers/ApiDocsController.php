<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class ApiDocsController extends Controller
{
    /**
     * Display API documentation page
     */
    public function index()
    {
        $docs = $this->loadAllDocs();
        
        // Parse markdown for each doc
        foreach ($docs as &$doc) {
            $doc['html'] = $this->parseMarkdown($doc['content']);
        }
        
        return view('api-docs.index', [
            'docs' => $docs,
            'sections' => $this->getSections($docs)
        ]);
    }

    /**
     * Load all markdown files from root and MD directory
     * Only loads English content from API_DOCUMENTATION.md
     */
    private function loadAllDocs(): array
    {
        $docs = [];
        
        // Load only the consolidated API documentation file (English only)
        $mainDoc = base_path('API_DOCUMENTATION.md');
        if (File::exists($mainDoc)) {
            $docs[] = [
                'title' => $this->extractTitle($mainDoc),
                'content' => File::get($mainDoc),
                'filename' => 'API_DOCUMENTATION.md',
                'type' => 'root',
                'priority' => true
            ];
        }
        
        return $docs;
    }

    /**
     * Extract title from markdown file
     */
    private function extractTitle(string $file): string
    {
        $content = File::get($file);
        // Try to get first H1
        if (preg_match('/^#\s+(.+)$/m', $content, $matches)) {
            return trim($matches[1]);
        }
        // Fallback to filename
        return str_replace(['.md', '_', '-'], ['', ' ', ' '], basename($file));
    }

    /**
     * Get sections from docs for navigation
     */
    private function getSections(array $docs): array
    {
        $sections = [];
        
        foreach ($docs as $doc) {
            // Extract H2 sections
            if (preg_match_all('/^##\s+(.+)$/m', $doc['content'], $matches)) {
                foreach ($matches[1] as $section) {
                    $sections[] = [
                        'title' => trim($section),
                        'doc' => $doc['filename'],
                        'anchor' => $this->slugify(trim($section))
                    ];
                }
            }
        }
        
        return $sections;
    }

    /**
     * Convert string to URL-friendly slug
     */
    private function slugify(string $text): string
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    /**
     * Parse markdown to HTML
     */
    private function parseMarkdown(string $markdown): string
    {
        $html = $markdown;
        
        // API Endpoints - Format: ### 1. GET /admin/api/products
        $html = preg_replace_callback('/^### (\d+)\.\s*(GET|POST|PUT|DELETE|PATCH)\s+(.+)$/m', function($matches) {
            $method = strtolower($matches[2]);
            $url = trim($matches[3]);
            return '<div class="docs-endpoint"><span class="docs-endpoint-method ' . $method . '">' . strtoupper($matches[2]) . '</span><span class="docs-endpoint-url">' . htmlspecialchars($url) . '</span></div>';
        }, $html);
        
        // Headers with IDs
        $html = preg_replace_callback('/^### (.+)$/m', function($matches) {
            $id = $this->slugify($matches[1]);
            return '<h3 id="' . $id . '">' . $matches[1] . '</h3>';
        }, $html);
        
        $html = preg_replace_callback('/^## (.+)$/m', function($matches) {
            $id = $this->slugify($matches[1]);
            return '<h2 id="' . $id . '">' . $matches[1] . '</h2>';
        }, $html);
        
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
        
        // Code blocks with language
        $html = preg_replace_callback('/```(\w+)?\n(.*?)```/s', function($matches) {
            $lang = $matches[1] ?? 'text';
            $code = htmlspecialchars(trim($matches[2]), ENT_QUOTES, 'UTF-8');
            return '<pre><code class="language-' . $lang . '">' . $code . '</code></pre>';
        }, $html);
        
        // Inline code
        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);
        
        // Bold and italic
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);
        
        // Links
        $html = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2" target="_blank">$1</a>', $html);
        
        // Horizontal rules
        $html = preg_replace('/^---$/m', '<hr>', $html);
        
        // Status badges
        $html = preg_replace('/\*\*Trạng thái:\*\*\s*(Hoàn thành|Hoan thanh|Completed|Đang cập nhật)/i', '<span class="docs-badge success">$1</span>', $html);
        $html = preg_replace('/\*\*Trạng thái:\*\*\s*(待实现|Đang phát triển)/i', '<span class="docs-badge warning">$1</span>', $html);
        
        // Lists - unordered
        $lines = explode("\n", $html);
        $inList = false;
        $result = [];
        
        foreach ($lines as $line) {
            if (preg_match('/^[\-\*]\s+(.+)$/', $line, $matches)) {
                if (!$inList) {
                    $result[] = '<ul>';
                    $inList = true;
                }
                $result[] = '<li>' . trim($matches[1]) . '</li>';
            } elseif (preg_match('/^\d+\.\s+(.+)$/', $line, $matches)) {
                if (!$inList) {
                    $result[] = '<ol>';
                    $inList = true;
                }
                $result[] = '<li>' . trim($matches[1]) . '</li>';
            } else {
                if ($inList) {
                    $result[] = $inList ? '</ul>' : '</ol>';
                    $inList = false;
                }
                $result[] = $line;
            }
        }
        
        if ($inList) {
            $result[] = '</ul>';
        }
        
        $html = implode("\n", $result);
        
        // Tables
        $html = preg_replace_callback('/\|(.+)\|\n\|[-\|:\s]+\|\n((?:\|.+\|\n?)+)/', function($matches) {
            $headers = array_map('trim', explode('|', trim($matches[1], '|')));
            $rows = array_filter(explode("\n", trim($matches[2])));
            
            $table = '<table><thead><tr>';
            foreach ($headers as $header) {
                if ($header) {
                    $table .= '<th>' . htmlspecialchars($header) . '</th>';
                }
            }
            $table .= '</tr></thead><tbody>';
            
            foreach ($rows as $row) {
                if (trim($row)) {
                    $cells = array_map('trim', explode('|', trim($row, '|')));
                    $table .= '<tr>';
                    foreach ($cells as $cell) {
                        if ($cell !== '') {
                            $table .= '<td>' . htmlspecialchars($cell) . '</td>';
                        }
                    }
                    $table .= '</tr>';
                }
            }
            $table .= '</tbody></table>';
            
            return $table;
        }, $html);
        
        // Blockquotes
        $html = preg_replace_callback('/^>\s*(.+)$/m', function($matches) {
            return '<blockquote>' . trim($matches[1]) . '</blockquote>';
        }, $html);
        
        // Paragraphs - split by double newlines
        $paragraphs = preg_split('/\n\n+/', $html);
        $html = '';
        foreach ($paragraphs as $para) {
            $para = trim($para);
            if ($para && !preg_match('/^<(h[1-6]|ul|ol|table|pre|blockquote|hr|div)/', $para)) {
                $html .= '<p>' . $para . '</p>';
            } else {
                $html .= $para;
            }
        }
        
        // Clean up empty paragraphs
        $html = preg_replace('/<p>\s*<\/p>/', '', $html);
        $html = preg_replace('/<p>(<[^>]+>)/', '$1', $html);
        $html = preg_replace('/(<\/[^>]+>)<\/p>/', '$1', $html);
        
        return $html;
    }
}

