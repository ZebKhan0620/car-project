class ErrorHandler {
    static init() {
        window.onerror = function(msg, url, lineNo, columnNo, error) {
            // Only log actual errors, not debug messages
            if (error) {
                console.error('Error:', {
                    message: msg,
                    url: url,
                    line: lineNo,
                    column: columnNo,
                    error: error
                });
                
                const errorDiv = document.getElementById('error-message');
                if (errorDiv) {
                    errorDiv.textContent = msg;
                    errorDiv.classList.remove('hidden');
                }
            }
            return false;
        };
    }

    static showError(message) {
        const errorDiv = document.getElementById('error-message');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.classList.remove('hidden');
        }
    }
}

// Initialize error handling
document.addEventListener('DOMContentLoaded', ErrorHandler.init);
