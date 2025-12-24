const MAX_ITEMS = 15;
const UP_ARROW = "ArrowUp";
const DOWN_ARROW = "ArrowDown";
const ENTER_KEY = "Enter";
const ESCAPE_KEY = "Escape";

const searchTrigger = document.getElementById("search-trigger");
const searchModal = document.getElementById("search-modal");
const searchModalBackdrop = document.getElementById("search-modal-backdrop");
const searchInput = document.getElementById("search");
const searchResults = document.getElementById("search-results");
const searchResultsItems = document.getElementById("search-results__items");
const searchFilters = document.getElementById("search-filters");

let searchItemSelected = null;
let resultsItemsIndex = -1;
let activeFilter = 'all'; // Track active filter: 'all', 'docs', 'api'

////////////////////////////////////
// Viewport Height Handler for Mobile
////////////////////////////////////

// Set CSS custom property for viewport height (for browsers without dvh support)
function setViewportHeight() {
    // Get the actual viewport height (excludes keyboard on mobile)
    const vh = window.visualViewport ? window.visualViewport.height * 0.01 : window.innerHeight * 0.01;
    document.documentElement.style.setProperty('--vh', `${vh}px`);
}

// Initialize and listen for viewport changes
if (window.visualViewport) {
    setViewportHeight();
    window.visualViewport.addEventListener('resize', setViewportHeight);
} else {
    // Fallback for browsers without visualViewport API
    setViewportHeight();
    window.addEventListener('resize', setViewportHeight);
}

////////////////////////////////////
// Modal Management
////////////////////////////////////

function openSearchModal() {
    searchModal.setAttribute("aria-hidden", "false");
    
    // Detect iOS devices
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    
    if (isIOS) {
        // iOS-specific scroll lock (position: fixed approach)
        const scrollY = window.scrollY;
        document.body.style.position = "fixed";
        document.body.style.top = `-${scrollY}px`;
        document.body.style.width = "100%";
        document.body.style.overflow = "hidden";
        document.body.setAttribute("data-scroll-y", scrollY.toString());
    } else {
        // Simple overflow: hidden for other browsers
        document.body.style.overflow = "hidden";
    }

    // Focus the search input after a brief delay to ensure modal is visible
    // Longer delay for iOS to account for position: fixed layout changes
    const isMobile = window.innerWidth <= 768;
    const delay = isIOS ? 300 : (isMobile ? 200 : 100);
    
    setTimeout(() => {
        searchInput.focus();
        // For mobile, ensure keyboard shows up
        if (isMobile) {
            searchInput.click();
        }
        
        // iOS sometimes needs an additional nudge
        if (isIOS) {
            setTimeout(() => {
                searchInput.focus();
                searchInput.click();
            }, 50);
        }
    }, delay);
}

function closeSearchModal() {
    searchModal.setAttribute("aria-hidden", "true");
    
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    
    if (isIOS) {
        // iOS-specific: restore scroll position
        const scrollY = document.body.getAttribute("data-scroll-y");
        document.body.style.position = "";
        document.body.style.top = "";
        document.body.style.width = "";
        document.body.style.overflow = "";
        document.body.removeAttribute("data-scroll-y");
        
        if (scrollY) {
            window.scrollTo(0, parseInt(scrollY));
        }
    } else {
        // Simple overflow restore for other browsers
        document.body.style.overflow = "";
    }
    
    searchInput.value = "";
    searchResultsItems.innerHTML = "";
    searchItemSelected = null;
    resultsItemsIndex = -1;
}

// Open modal when trigger button is clicked
if (searchTrigger) {
    searchTrigger.addEventListener("click", openSearchModal);
}

// Close modal when backdrop is clicked
if (searchModalBackdrop) {
    searchModalBackdrop.addEventListener("click", closeSearchModal);
}

// Close modal when ESC shortcut indicator is clicked
const searchModalShortcut = document.querySelector(".search-modal__shortcut");
if (searchModalShortcut) {
    searchModalShortcut.addEventListener("click", closeSearchModal);
}

// Focus input when clicking anywhere in the input wrapper (helpful on mobile)
const searchInputWrapper = document.querySelector(".search-modal__input-wrapper");
if (searchInputWrapper) {
    searchInputWrapper.addEventListener("click", (e) => {
        // Don't focus if clicking the ESC button
        if (!e.target.classList.contains("search-modal__shortcut")) {
            searchInput.focus();
        }
    });
}

////////////////////////////////////
// Search Filters
////////////////////////////////////

// Handle filter button clicks
if (searchFilters) {
    searchFilters.addEventListener('click', function(e) {
        if (e.target.classList.contains('search-filter')) {
            const filterValue = e.target.getAttribute('data-filter');
            
            // Update active filter
            activeFilter = filterValue;
            
            // Update button states
            searchFilters.querySelectorAll('.search-filter').forEach(btn => {
                btn.classList.remove('search-filter--active');
            });
            e.target.classList.add('search-filter--active');
            
            // Re-run search with new filter
            if (searchInput.value.trim() !== '') {
                // Trigger search by dispatching keyup event
                const event = new KeyboardEvent('keyup', { key: 'a' });
                searchInput.dispatchEvent(event);
            }
        }
    });
}

////////////////////////////////////
// Keyboard shortcuts
////////////////////////////////////

document.addEventListener("keydown", function (keyboardEvent) {
    // Open modal with /, s, or S (only if modal is not already open)
    if (["s", "S", "/"].includes(keyboardEvent.key) && searchModal.getAttribute("aria-hidden") === "true") {
        // Don't trigger if user is typing in an input/textarea
        if (document.activeElement.tagName === "INPUT" ||
            document.activeElement.tagName === "TEXTAREA" ||
            document.activeElement.isContentEditable) {
            return;
        }
        keyboardEvent.preventDefault();
        openSearchModal();
        return;
    }

    // Only handle these keys when modal is open
    if (searchModal.getAttribute("aria-hidden") === "false") {
        const items = searchResultsItems.getElementsByTagName("li");
        const len = items.length - 1;

        switch (keyboardEvent.key) {
            case DOWN_ARROW:
                if (items.length > 0) {
                    keyboardEvent.preventDefault();
                    downArrow(len);
                }
                break;

            case UP_ARROW:
                if (items.length > 0) {
                    keyboardEvent.preventDefault();
                    upArrow(len);
                }
                break;

            case ENTER_KEY: {
                const parent = searchItemSelected || searchResultsItems;
                const target = parent.querySelector("a");

                if (target) {
                    target.click();
                    closeSearchModal();
                }
                break;
            }

            case ESCAPE_KEY:
                keyboardEvent.preventDefault();
                closeSearchModal();
                break;
        }
    }
});

function downArrow(len) {
    resultsItemsIndex++;

    if (!searchItemSelected) {
        resultsItemsIndex = 0;
        searchItemSelected = searchResultsItems.getElementsByTagName("li")[0];
    } else {
        removeClass(searchItemSelected, "selected");
        const next = searchResultsItems.getElementsByTagName("li")[resultsItemsIndex];

        if (typeof next !== undefined && resultsItemsIndex <= len) {
            searchItemSelected = next;
        } else {
            resultsItemsIndex = 0;
            searchItemSelected = searchResultsItems.getElementsByTagName("li")[0];
        }
    }

    searchItemSelected.scrollIntoView({ block: "nearest" });
    addClass(searchItemSelected, "selected");
}

function upArrow(len) {
    if (!searchItemSelected) {
        resultsItemsIndex = -1;
        searchItemSelected = searchResultsItems.getElementsByTagName("li")[len];
    } else {
        removeClass(searchItemSelected, "selected");
        resultsItemsIndex--;
        const next = searchResultsItems.getElementsByTagName("li")[resultsItemsIndex];

        if (typeof next !== undefined && resultsItemsIndex >= 0) {
            searchItemSelected = next;
        } else {
            resultsItemsIndex = len;
            searchItemSelected = searchResultsItems.getElementsByTagName("li")[len];
        }
    }
    searchItemSelected.scrollIntoView({ block: "nearest" });
    addClass(searchItemSelected, "selected");
}

function removeClass(el, className) {
    if (el.classList) {
        el.classList.remove(className);
    } else {
        el.className = el.className.replace(new RegExp("(^|\\b)" + className.split(" ").join("|") + "(\\b|$)", "gi"), " ");
    }
}

function addClass(el, className) {
    if (el.classList) {
        el.classList.add(className);
    } else {
        el.className += " " + className;
    }
}

///////////////////////////////
// Initialize search
///////////////////////////////

if (document.readyState === "complete" || (document.readyState !== "loading" && !document.documentElement.doScroll)) {
    initSearch();
} else {
    document.addEventListener("DOMContentLoaded", initSearch);
}

function initSearch() {
    elasticlunr.trimmer = function (token) {
        if (token === null || token === undefined) {
            throw new Error("token should not be undefined");
        }

        return token;
    };

    // Create API index
    const apiIndex = elasticlunr(function () {
        this.addField("name");
        this.addField("desc");
        this.addField("title");
        this.addField("content");
        this.setRef("id");
        elasticlunr.stopWordFilter.stopWords = {};
        elasticlunr.Pipeline.registerFunction(elasticlunr.trimmer, "trimmer");
        elasticlunr.tokenizer.seperator = /[\s~~]+/;
    });

    // Custom tokenizer to handle symbols with '/', ':', and camelCase
    const originalTokenizer = elasticlunr.tokenizer;
    elasticlunr.tokenizer = function (obj, metadata) {
        if (obj == null) {
            return [];
        }

        if (Array.isArray(obj)) {
            return obj.reduce(function (tokens, token) {
                return tokens.concat(elasticlunr.tokenizer(token, metadata));
            }, []);
        }

        const originalStr = obj.toString();
        const str = originalStr.toLowerCase();
        let tokens = originalTokenizer(str, metadata);

        // Handle camelCase: split "SrcDirs" into ["src", "dirs", "srcdirs"]
        const camelCaseMatch = originalStr.match(/[a-z]+|[A-Z][a-z]*/g);
        if (camelCaseMatch && camelCaseMatch.length > 1) {
            const camelCaseLower = camelCaseMatch.map(s => s.toLowerCase()).join('');
            if (camelCaseLower && !tokens.includes(camelCaseLower)) {
                tokens.push(camelCaseLower);
            }
            // Also add individual parts
            camelCaseMatch.forEach(part => {
                const partLower = part.toLowerCase();
                if (partLower && !tokens.includes(partLower)) {
                    tokens.push(partLower);
                }
            });
        }

        // Handle strings with '/' (namespaces)
        if (str.includes('/')) {
            const parts = str.split('/');
            parts.forEach(part => {
                if (part && !tokens.includes(part)) {
                    tokens.push(part);
                }
            });
        }

        // Handle strings with ':' (keywords like :pairs, :keys)
        // Preserve the colon version and the version without colon
        if (str.includes(':')) {
            const colonParts = str.split(':');
            colonParts.forEach((part, index) => {
                if (part && !tokens.includes(part)) {
                    tokens.push(part);
                }
                // Add version with colon prefix for keywords
                if (index > 0 && colonParts[0] === '') {
                    const keyword = ':' + part;
                    if (!tokens.includes(keyword)) {
                        tokens.push(keyword);
                    }
                }
            });
            // Also add the full string if it starts with :
            if (str.startsWith(':')) {
                if (!tokens.includes(str)) {
                    tokens.push(str);
                }
            }
        }

        return tokens;
    };

    // Load API symbols into elasticlunr index
    if (window.searchIndexApi) {
        window.searchIndexApi.forEach(item => apiIndex.addDoc(item));
    }

    // Load Zola documentation index
    let docsIndex = null;
    if (window.searchIndex) {
        docsIndex = elasticlunr.Index.load(window.searchIndex);
    }

    // Create combined search object
    const searchIndices = { api: apiIndex, docs: docsIndex };

    // Search on input
    searchInput.addEventListener("keyup", function (keyboardEvent) {
        if (keyboardEvent.key === DOWN_ARROW || keyboardEvent.key === UP_ARROW || keyboardEvent.key === ENTER_KEY || keyboardEvent.key === ESCAPE_KEY) {
            return;
        }

        searchItemSelected = null;
        resultsItemsIndex = -1;
        debounce(showResults(searchIndices), 150)();
    });

    // Hide results list when user clears the search field
    searchInput.addEventListener("search", () => {
        if (searchInput.value === "") {
            searchResultsItems.innerHTML = "";
        }
    });

    // Show results when input is focused and has value
    searchInput.addEventListener("focus", function () {
        if (searchInput.value.trim() !== "") {
            showResults(searchIndices)();
        }
    });
}

function debounce(func, wait) {
    let timeout;

    return function () {
        const context = this;
        const args = arguments;
        clearTimeout(timeout);

        timeout = setTimeout(function () {
            timeout = null;
            func.apply(context, args);
        }, wait);
    };
}

function showResults(searchIndices) {
    return function () {
        const term = searchInput.value.trim();
        
        searchResultsItems.innerHTML = "";

        if (term === "") {
            return;
        }

        // Search options for API index
        const apiOptions = {
            bool: "OR",
            fields: {
                name: {boost: 3},
                title: {boost: 2},
                desc: {boost: 1},
                content: {boost: 1}
            },
            expand: true
        };

        // Search options for docs index
        const docsOptions = {
            bool: "OR",
            fields: {
                title: {boost: 2},
                body: {boost: 1}
            },
            expand: true
        };

        // Helper function to highlight matched term
        function highlightTerm(text, searchTerm) {
            if (!text) return text;
            // Escape special regex characters in search term
            const escapedTerm = searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const regex = new RegExp(`(${escapedTerm})`, 'gi');
            return text.replace(regex, '<strong>$1</strong>');
        }

        // Helper function to extract snippet around the search term
        function getSnippetAroundTerm(text, searchTerm, snippetLength = 150) {
            if (!text) return '';
            
            const lowerText = text.toLowerCase();
            const lowerTerm = searchTerm.toLowerCase();
            const termIndex = lowerText.indexOf(lowerTerm);
            
            if (termIndex === -1) {
                // Term not found, return beginning of text
                return text.substring(0, snippetLength) + (text.length > snippetLength ? '...' : '');
            }
            
            // Calculate start and end positions for the snippet
            const halfSnippet = Math.floor(snippetLength / 2);
            let start = Math.max(0, termIndex - halfSnippet);
            let end = Math.min(text.length, termIndex + lowerTerm.length + halfSnippet);
            
            // Adjust to avoid cutting words
            if (start > 0) {
                const spaceIndex = text.indexOf(' ', start);
                if (spaceIndex !== -1 && spaceIndex < termIndex) {
                    start = spaceIndex + 1;
                }
            }
            if (end < text.length) {
                const spaceIndex = text.lastIndexOf(' ', end);
                if (spaceIndex !== -1 && spaceIndex > termIndex + lowerTerm.length) {
                    end = spaceIndex;
                }
            }
            
            let snippet = text.substring(start, end);
            if (start > 0) snippet = '...' + snippet;
            if (end < text.length) snippet = snippet + '...';
            
            // Highlight the matched term
            return highlightTerm(snippet, searchTerm);
        }

        // Search API index
        let apiResults = [];
        if (searchIndices.api) {
            apiResults = searchIndices.api.search(term, apiOptions).map(result => {
                // Apply snippet extraction and highlighting to API results
                const doc = result.doc;
                
                // Check if this is actually an API function or a documentation item
                const isApiFunction = doc.type === 'api';
                const isDocumentation = doc.type === 'documentation';
                
                if (isApiFunction) {
                    // API function result
                    const cleanDoc = {};
                    if (doc.id !== undefined) cleanDoc.id = doc.id;
                    if (doc.name !== undefined) cleanDoc.name = highlightTerm(doc.name, term);
                    if (doc.signatures !== undefined) cleanDoc.signatures = doc.signatures;
                    if (doc.desc !== undefined) cleanDoc.desc = getSnippetAroundTerm(doc.desc, term);
                    if (doc.anchor !== undefined) cleanDoc.anchor = doc.anchor;
                    
                    return {
                        ref: result.ref,
                        score: result.score,
                        doc: cleanDoc,
                        source: 'api'
                    };
                } else if (isDocumentation) {
                    // Documentation item from API index
                    return {
                        ref: result.ref,
                        score: result.score,
                        doc: {
                            id: doc.id,
                            title: highlightTerm(doc.title || 'Untitled', term),
                            content: getSnippetAroundTerm(doc.content || '', term),
                            url: doc.url,
                            type: 'documentation'
                        },
                        source: 'docs'
                    };
                }
                return null;
            }).filter(r => r !== null);
        }

        // Search documentation index
        let docsResults = [];
        if (searchIndices.docs) {
            docsResults = searchIndices.docs.search(term, docsOptions).map(result => {
                // The doc is already included in the result from elasticlunr
                const doc = result.doc;
                // Convert URL to relative path for proper linking
                let url = result.ref;
                try {
                    const urlObj = new URL(result.ref);
                    url = urlObj.pathname;
                } catch (e) {
                    // Already a relative path
                }
                return {
                    ref: result.ref,
                    score: result.score,
                    doc: {
                        id: result.ref,
                        title: highlightTerm(doc.title || 'Untitled', term),
                        content: getSnippetAroundTerm(doc.body, term),
                        url: url,
                        type: 'documentation'
                    },
                    source: 'docs'
                };
            });
        }

        // Combine and sort results by score
        let allResults = [...apiResults, ...docsResults]
            .sort((a, b) => b.score - a.score);

        // Apply active filter
        if (activeFilter === 'docs') {
            allResults = allResults.filter(result => result.source === 'docs');
        } else if (activeFilter === 'api') {
            allResults = allResults.filter(result => result.source === 'api');
        }
        // 'all' filter shows everything (no filtering needed)

        // Deduplicate results by normalized URL (remove trailing slashes)
        const seenUrls = new Set();
        const uniqueResults = allResults.filter(result => {
            const url = result.doc.url || result.doc.anchor || '';
            // Normalize URL: remove trailing slash for comparison
            const normalizedUrl = url.replace(/\/$/, '');
            if (seenUrls.has(normalizedUrl)) {
                return false;
            }
            seenUrls.add(normalizedUrl);
            return true;
        });

        // Separate release pages from other results
        const releaseResults = [];
        const regularResults = [];
        
        uniqueResults.forEach(result => {
            const url = result.doc.url || '';
            if (url.includes('/releases/')) {
                releaseResults.push(result);
            } else {
                regularResults.push(result);
            }
        });
        
        // Put regular results first, then release pages
        const sortedResults = [...regularResults, ...releaseResults];

        if (sortedResults.length === 0) {
            let emptyResult = {
                name: "No results found",
                signatures: "",
                desc: "Cannot find any matching content. Try something else",
                anchor: "#",
                type: "empty"
            };

            createMenuItem(emptyResult, null, activeFilter);
            return;
        }

        const numberOfResults = Math.min(sortedResults.length, MAX_ITEMS);
        for (let i = 0; i < numberOfResults; i++) {
            createMenuItem(sortedResults[i].doc, i, activeFilter);
        }
    }
}

function createMenuItem(result, index, filter) {
    const item = document.createElement("li");
    item.innerHTML = formatSearchResultItem(result, filter);

    item.addEventListener("mouseenter", (mouseEvent) => {
        removeSelectedClassFromSearchResult();
        mouseEvent.currentTarget.classList.add("selected");
        searchItemSelected = mouseEvent.currentTarget;
        resultsItemsIndex = index;
    });

    item.addEventListener("click", () => {
        closeSearchModal();
    });

    searchResultsItems.appendChild(item);
}

function formatSearchResultItem(item, filter) {
    // Determine if we should show the badge
    const showDocsBadge = filter !== 'docs';
    const showApiBadge = filter !== 'api';
    
    if (item.type === "documentation") {
        const badge = showDocsBadge 
            ? `<span class="search-results__badge search-results__badge--docs">Docs</span>` 
            : '';
        return `<a class="search-results__link" href="${item.url || ''}">`
            + `<div class="search-results__item">`
            + `<div class="search-results__header">`
            + `<span class="title">${item.title || ''}</span>`
            + badge
            + `</div>`
            + `<span class="desc">${item.content || ''}</span>`
            + `</div></a>`;
    } else if (item.type === "empty") {
        return `<a class="search-results__link" href="${item.anchor || '#'}">`
            + `<div class="search-results__item">`
            + `<div class="search-results__header">`
            + `<div class="fn-info">`
            + `<span class="fn-name">${item.name || ''}</span> `
            + `<small class="fn-signatures">${item.signatures || ''}</small>`
            + `</div>`
            + `</div>`
            + `<span class="desc">${item.desc || ''}</span>`
            + `</div></a>`;
    } else {
        const badge = showApiBadge 
            ? `<span class="search-results__badge search-results__badge--api">API</span>` 
            : '';
        return `<a class="search-results__link" href="/documentation/api/#${item.anchor || ''}">`
            + `<div class="search-results__item">`
            + `<div class="search-results__header">`
            + `<div class="fn-info">`
            + `<span class="fn-name">${item.name || ''}</span> `
            + `<small class="fn-signatures">${item.signatures || ''}</small>`
            + `</div>`
            + badge
            + `</div>`
            + `<span class="desc">${item.desc || ''}</span>`
            + `</div></a>`;
    }
}

function removeSelectedClassFromSearchResult() {
    const searchResultsItemChildren = searchResultsItems.children;
    for (let i = 0; i < searchResultsItemChildren.length; i++) {
        removeClass(searchResultsItemChildren[i], "selected")
    }
}