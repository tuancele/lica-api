/**
 * Debug Logger - Write logs to Laravel log file
 * 
 * Usage:
 *   DebugLogger.log('message', { context: 'data' });
 *   DebugLogger.warn('warning message', { data: 'value' });
 *   DebugLogger.error('error message', { error: 'details' });
 */

(function() {
    'use strict';

    const DebugLogger = {
        /**
         * Write log to Laravel
         * 
         * @param {string} level - Log level: debug, info, warning, error
         * @param {string} message - Log message
         * @param {Object} context - Additional context data
         */
        writeLog: function(level, message, context) {
            // Also log to console for immediate debugging
            const consoleMethod = level === 'error' ? 'error' : 
                                 level === 'warning' ? 'warn' : 
                                 'log';
            console[consoleMethod]('[Laravel Log]', message, context || {});
            
            // Rate limiting: Only log errors and warnings to Laravel, skip info logs to avoid 429
            // Or use debouncing for info logs
            const shouldLogToLaravel = level === 'error' || level === 'warning' || 
                                      message.includes('TOTAL MISMATCH') || 
                                      message.includes('SHIPPING FEE DEBUG');
            
            if (!shouldLogToLaravel) {
                return; // Skip logging to Laravel for non-critical info logs
            }
            
            // Send to Laravel log via AJAX
            if (typeof $ !== 'undefined' && $.ajax) {
                $.ajax({
                    url: '/api/debug/log',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    data: JSON.stringify({
                        level: level,
                        message: message,
                        context: context || {}
                    }),
                    contentType: 'application/json',
                    // Don't wait for response, fire and forget
                    timeout: 1000
                }).fail(function(xhr, status, error) {
                    // Silently fail - don't break the app if logging fails
                    if (xhr.status !== 429) { // Don't warn about rate limiting
                        console.warn('[DebugLogger] Failed to write log to Laravel:', error);
                    }
                });
            } else {
                // Fallback: Use fetch API
                if (typeof fetch !== 'undefined') {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    fetch('/api/debug/log', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken || '',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            level: level,
                            message: message,
                            context: context || {}
                        })
                    }).catch(function(error) {
                        // Silently fail
                        console.warn('[DebugLogger] Failed to write log to Laravel:', error);
                    });
                }
            }
        },

        /**
         * Log info message
         */
        log: function(message, context) {
            this.writeLog('info', message, context);
        },

        /**
         * Log warning message
         */
        warn: function(message, context) {
            this.writeLog('warning', message, context);
        },

        /**
         * Log error message
         */
        error: function(message, context) {
            this.writeLog('error', message, context);
        },

        /**
         * Log debug message
         */
        debug: function(message, context) {
            this.writeLog('debug', message, context);
        }
    };

    // Export to window for global access
    window.DebugLogger = DebugLogger;

    // Log initialization
    if (typeof console !== 'undefined' && console.log) {
        console.log('[DebugLogger] Module initialized');
    }

})();

