import DOMPurify from 'dompurify';

/**
 * Composable for sanitizing HTML content to prevent XSS attacks
 */
export function useSanitize() {
    /**
     * Sanitize HTML with safe tags only
     * @param {string} html - Raw HTML string
     * @returns {string} - Sanitized HTML
     */
    const sanitize = (html) => {
        if (!html) return '';
        
        return DOMPurify.sanitize(html, {
            ALLOWED_TAGS: [
                'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's',
                'ul', 'ol', 'li',
                'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                'a', 'span', 'div',
                'blockquote', 'pre', 'code',
                'table', 'thead', 'tbody', 'tr', 'th', 'td',
                'img', 'figure', 'figcaption',
                'hr'
            ],
            ALLOWED_ATTR: [
                'href', 'target', 'rel', 'title', 'class', 'id',
                'src', 'alt', 'width', 'height',
                'colspan', 'rowspan'
            ],
            ALLOW_DATA_ATTR: false,
            ADD_ATTR: ['target'],
            ADD_TAGS: [],
            // Force all links to open in new tab
            FORCE_BODY: true,
        });
    };

    /**
     * Sanitize with minimal tags (for comments, short text)
     * @param {string} html - Raw HTML string
     * @returns {string} - Sanitized HTML
     */
    const sanitizeMinimal = (html) => {
        if (!html) return '';
        
        return DOMPurify.sanitize(html, {
            ALLOWED_TAGS: ['p', 'br', 'strong', 'em', 'a'],
            ALLOWED_ATTR: ['href', 'target'],
        });
    };

    /**
     * Strip all HTML tags, return plain text
     * @param {string} html - Raw HTML string
     * @returns {string} - Plain text
     */
    const stripHtml = (html) => {
        if (!html) return '';
        return DOMPurify.sanitize(html, { ALLOWED_TAGS: [] });
    };

    return {
        sanitize,
        sanitizeMinimal,
        stripHtml
    };
}

export default useSanitize;
